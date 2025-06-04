// composables/useAuth.ts
import { useAuthStore } from '~/stores/auth'
import type { User, TokenInfo } from '~/types/auth'

export const useAuth = () => {
    const config = useRuntimeConfig()
    const router = useRouter()
    const authStore = useAuthStore()

    // Получаем токен из store или localStorage
    const getToken = () => {
        if (process.client) {
            return localStorage.getItem('auth_token') || authStore.token
        }
        return authStore.token
    }

    // Устанавливаем токен в store
    const setToken = (token: string | null) => {
        authStore.setToken(token)
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
        authStore.setLoading(true)

        try {
            const response = await $fetch<{
                user: User
                token: string
                message: string
                expires_at: string
            }>('/api/auth/login', {
                baseURL: config.public.apiBase,
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

            setToken(response.token)
            authStore.setUser(response.user)

            return { success: true, user: response.user }
        } catch (error: any) {
            console.error('Login error:', error)
            authStore.clearAuth()
            return {
                success: false,
                error: error.data?.message || 'Ошибка авторизации'
            }
        } finally {
            authStore.setLoading(false)
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
        authStore.setLoading(true)

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

            setToken(response.token)
            authStore.setUser(response.user)

            return { success: true, user: response.user }
        } catch (error: any) {
            console.error('Register error:', error)
            return {
                success: false,
                error: error.data?.message || 'Ошибка регистрации'
            }
        } finally {
            authStore.setLoading(false)
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

        authStore.clearAuth()
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
            authStore.clearAuth()
            return null
        }

        try {
            const user = await $fetch<User>('/api/user', {
                baseURL: config.public.apiBase,
                method: 'GET',
                headers: getAuthHeaders()
            })

            authStore.setUser(user)
            return user
        } catch (error) {
            console.error('Fetch user error:', error)
            authStore.clearAuth()
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

            authStore.setTokens(tokens)
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

            await getTokens()
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
                authStore.setUser(response.user)
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
            authStore.initTokenFromStorage()
            const token = getToken()

            if (token) {
                await fetchUser()
            }
        }
    }

    return {
        // Store state (реактивные геттеры)
        user: computed(() => authStore.user),
        token: computed(() => authStore.token),
        loggedIn: computed(() => authStore.loggedIn),
        loading: computed(() => authStore.loading),
        tokens: computed(() => authStore.tokens),

        // Store getters
        isAuthenticated: computed(() => authStore.isAuthenticated),
        userName: computed(() => authStore.userName),
        userEmail: computed(() => authStore.userEmail),

        // Auth methods only
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