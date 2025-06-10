// plugins/api.client.ts

export default defineNuxtPlugin(() => {
    const { getAuthHeaders, logout } = useAuth()

    const api = $fetch.create({
        baseURL: useRuntimeConfig().public.apiBase,

        onRequest({ request, options }) {
            // Добавляем заголовки авторизации автоматически
            const headers = getAuthHeaders()
            options.headers = {
                ...options.headers,
                ...headers
            }
        },

        onResponseError({ response }) {
            // Автоматический логаут при ошибке 401
            if (response.status === 401) {
                logout()
            }
        }
    })

    return {
        provide: {
            api
        }
    }
})