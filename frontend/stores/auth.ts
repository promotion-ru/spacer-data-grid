import { defineStore } from 'pinia'

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
    tokens: TokenInfo[]
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        token: null,
        loggedIn: false,
        loading: false,
        tokens: []
    }),

    getters: {
        isAuthenticated: (state) => state.loggedIn && !!state.user,
        userName: (state) => state.user?.name || '',
        userEmail: (state) => state.user?.email || '',
        hasValidToken: (state) => !!state.token,
        activeTokensCount: (state) => state.tokens.length
    },

    actions: {
        setUser(user: User | null) {
            this.user = user
            this.loggedIn = !!user
        },

        setToken(token: string | null) {
            this.token = token
            if (process.client) {
                if (token) {
                    localStorage.setItem('auth_token', token)
                } else {
                    localStorage.removeItem('auth_token')
                }
            }
        },

        setLoading(loading: boolean) {
            this.loading = loading
        },

        setTokens(tokens: TokenInfo[]) {
            this.tokens = tokens
        },

        clearAuth() {
            this.user = null
            this.token = null
            this.loggedIn = false
            this.tokens = []
            if (process.client) {
                localStorage.removeItem('auth_token')
            }
        },

        initTokenFromStorage() {
            if (process.client) {
                const token = localStorage.getItem('auth_token')
                if (token) {
                    this.token = token
                }
            }
        }
    },

    persist: {
        key: 'auth-store',
        storage: typeof window !== 'undefined' ? localStorage : undefined,
        paths: ['user', 'loggedIn']
    }
})