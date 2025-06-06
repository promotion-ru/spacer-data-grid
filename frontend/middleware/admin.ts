export default defineNuxtRouteMiddleware((to, from) => {
    const {isAdmin} = usePermissions()

    if (!isAdmin.value) {
        return navigateTo('/')
    }
})