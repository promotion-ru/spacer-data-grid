export default defineNuxtPlugin(() => {
    const { getAuthHeaders, logout } = useAuth()

    // Перехватчик для автоматического добавления токена
    $fetch.create({
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
})