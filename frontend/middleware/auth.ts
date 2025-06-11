// middleware/auth.ts
export default defineNuxtRouteMiddleware((to, from) => {
    const { loggedIn, isAuthenticated, initialized, hasToken } = useAuth()

    // Если есть токен, но инициализация еще не завершена, ждем
    if (hasToken.value && !initialized.value) {
        return // Не перенаправляем, ждем инициализации
    }

    // Если инициализация завершена и пользователь не авторизован
    if (initialized.value && !loggedIn.value) {
        return navigateTo('/login')
    }

    // Дополнительная проверка аутентификации
    if (initialized.value && !isAuthenticated.value) {
        return navigateTo('/login')
    }
})