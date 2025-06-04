export default defineNuxtRouteMiddleware((to, from) => {
    const { loggedIn, isAuthenticated } = useAuth()

    if (!loggedIn.value) {
        return navigateTo('/login')
    }

    if (!isAuthenticated.value) {
        return navigateTo('/login')
    }
})