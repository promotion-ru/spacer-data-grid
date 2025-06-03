interface User {
    id: number
    name: string
    email: string
    email_verified_at: string | null
}

interface TokenInfo {
    id: number
    name: string
    abilities: string[]
    last_used_at: string | null
    expires_at: string | null
    created_at: string
    is_current: boolean
    is_expired: boolean
}

interface AuthState {
    user: User | null
    token: string | null
    loggedIn: boolean
    loading: boolean
}

export const useAuth = () => {
    const config = useRuntimeConfig()
    const router = useRouter()

    const authState = useState<AuthState>('auth.state', () => ({
        user: null,
        token: null,
        loggedIn: false,
        loading: false
    }))

    // Используем localStorage для токена (можно также cookie)
    const getToken = () => {
        if (process.client) {
            return localStorage.getItem('auth_token') || authState.value.token
        }
        return authState.value.token
    }

    const setToken = (token: string | null) => {
        authState.value.token = token
        if (process.client) {
            if (token) {
                localStorage.setItem('auth_token', token)
            } else {
                localStorage.removeItem('auth_token')
            }
        }
    }

    // Создание заголовков с авторизацией
    const getAuthHeaders = () => {
        const token = getToken()
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
        }
    }

    // Логин с токеном
    const login = async (credentials: {
        email: string
        password: string
        device_name?: string
    }) => {
        authState.value.loading = true

        try {
            const response = await $fetch<{
                user: User
                token: string
                message: string
                expires_at: string
            }>('/api/auth/login', {
                baseURL: config.public.apiBase, // http://localhost
                method: 'POST',
                body: {
                    ...credentials,
                    device_name: credentials.device_name || 'Web Browser'
                },
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })

            // Сохраняем токен и пользователя
            setToken(response.token)
            authState.value.user = response.user
            authState.value.loggedIn = true

            return { success: true, user: response.user }
        } catch (error: any) {
            console.error('Login error:', error)

            // Очищаем состояние при ошибке
            setToken(null)
            authState.value.user = null
            authState.value.loggedIn = false

            return {
                success: false,
                error: error.data?.message || 'Ошибка авторизации'
            }
        } finally {
            authState.value.loading = false
        }
    }

    // Регистрация с токеном
    const register = async (userData: {
        name: string
        email: string
        password: string
        password_confirmation: string
        device_name?: string
    }) => {
        authState.value.loading = true

        try {
            const response = await $fetch<{
                user: User
                token: string
                message: string
                expires_at: string
            }>('/api/auth/register', {
                baseURL: config.public.apiBase,
                method: 'POST',
                body: {
                    ...userData,
                    device_name: userData.device_name || 'Web Browser'
                },
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })

            // Сохраняем токен и пользователя
            setToken(response.token)
            authState.value.user = response.user
            authState.value.loggedIn = true

            return { success: true, user: response.user }
        } catch (error: any) {
            console.error('Register error:', error)
            return {
                success: false,
                error: error.data?.message || 'Ошибка регистрации'
            }
        } finally {
            authState.value.loading = false
        }
    }

    // Логаут
    const logout = async () => {
        const token = getToken()

        if (token) {
            try {
                await $fetch('/api/auth/logout', {
                    baseURL: config.public.apiBase,
                    method: 'POST',
                    headers: getAuthHeaders()
                })
            } catch (error) {
                console.error('Logout error:', error)
            }
        }

        // Очищаем состояние
        setToken(null)
        authState.value.user = null
        authState.value.loggedIn = false

        await router.push('/login')
    }

    // Логаут с других устройств
    const logoutOtherDevices = async () => {
        try {
            const response = await $fetch<{ revoked_tokens_count: number }>('/api/auth/logout-other-devices', {
                baseURL: config.public.apiBase,
                method: 'POST',
                headers: getAuthHeaders()
            })

            return { success: true, count: response.revoked_tokens_count }
        } catch (error: any) {
            console.error('Logout other devices error:', error)
            return { success: false, error: 'Ошибка выхода с других устройств' }
        }
    }

    // Получение данных пользователя
    const fetchUser = async () => {
        const token = getToken()

        if (!token) {
            authState.value.user = null
            authState.value.loggedIn = false
            return null
        }

        try {
            const user = await $fetch<User>('/api/user', {
                baseURL: config.public.apiBase,
                method: 'GET',
                headers: getAuthHeaders()
            })

            authState.value.user = user
            authState.value.loggedIn = true

            return user
        } catch (error) {
            console.error('Fetch user error:', error)

            // При ошибке очищаем токен (возможно, истек)
            setToken(null)
            authState.value.user = null
            authState.value.loggedIn = false

            return null
        }
    }

    // Получение активных токенов
    const getTokens = async () => {
        try {
            const tokens = await $fetch<TokenInfo[]>('/api/tokens', {
                baseURL: config.public.apiBase,
                method: 'GET',
                headers: getAuthHeaders()
            })

            return tokens
        } catch (error) {
            console.error('Get tokens error:', error)
            return []
        }
    }

    // Отзыв конкретного токена
    const revokeToken = async (tokenId: number) => {
        try {
            await $fetch(`/api/tokens/${tokenId}`, {
                baseURL: config.public.apiBase,
                method: 'DELETE',
                headers: getAuthHeaders()
            })

            return { success: true }
        } catch (error: any) {
            console.error('Revoke token error:', error)
            return { success: false, error: 'Ошибка отзыва токена' }
        }
    }

    // Обновление токена
    const refreshToken = async () => {
        try {
            const response = await $fetch<{
                token: string
                expires_at: string
            }>('/api/auth/refresh-token', {
                baseURL: config.public.apiBase,
                method: 'POST',
                headers: getAuthHeaders()
            })

            setToken(response.token)

            return { success: true, token: response.token }
        } catch (error: any) {
            console.error('Refresh token error:', error)
            return { success: false, error: 'Ошибка обновления токена' }
        }
    }

    // Проверка валидности токена
    const checkToken = async () => {
        try {
            const response = await $fetch<{
                valid: boolean
                token_info: TokenInfo
                user: User
            }>('/api/auth/check-token', {
                baseURL: config.public.apiBase,
                method: 'GET',
                headers: getAuthHeaders()
            })

            if (response.valid) {
                authState.value.user = response.user
                authState.value.loggedIn = true
            }

            return response
        } catch (error) {
            console.error('Check token error:', error)
            return { valid: false }
        }
    }

    // Инициализация авторизации
    const initAuth = async () => {
        if (process.client) {
            const token = getToken()

            if (token) {
                authState.value.token = token
                await fetchUser()
            }
        }
    }

    return {
        // State
        user: readonly(computed(() => authState.value.user)),
        token: readonly(computed(() => authState.value.token)),
        loggedIn: readonly(computed(() => authState.value.loggedIn)),
        loading: readonly(computed(() => authState.value.loading)),

        // Methods
        login,
        register,
        logout,
        logoutOtherDevices,
        fetchUser,
        getTokens,
        revokeToken,
        refreshToken,
        checkToken,
        initAuth,
        getAuthHeaders
    }
}