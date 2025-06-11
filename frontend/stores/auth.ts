// stores/auth.ts
import { defineStore } from 'pinia'
import type { User, TokenInfo } from '~/types/auth'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        token: null as string | null,
        tokens: [] as TokenInfo[],
        loading: false,
        initialized: false // Добавляем флаг инициализации
    }),

    getters: {
        loggedIn: (state) => !!state.user,
        isAuthenticated: (state) => !!state.user && !!state.token,
        userName: (state) => state.user?.name || '',
        userEmail: (state) => state.user?.email || '',
        hasToken: (state) => {
            // Проверяем токен в store или localStorage
            if (process.client) {
                return !!state.token || !!localStorage.getItem('auth_token')
            }
            return !!state.token
        }
    },

    actions: {
        setUser(user: User | null) {
            this.user = user
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

        setTokens(tokens: TokenInfo[]) {
            this.tokens = tokens
        },

        setLoading(loading: boolean) {
            this.loading = loading
        },

        setInitialized(initialized: boolean) {
            this.initialized = initialized
        },

        initTokenFromStorage() {
            if (process.client) {
                const token = localStorage.getItem('auth_token')
                if (token) {
                    this.token = token
                }
            }
        },

        clearAuth() {
            this.user = null
            this.token = null
            this.tokens = []
            if (process.client) {
                localStorage.removeItem('auth_token')
            }
        }
    }
})