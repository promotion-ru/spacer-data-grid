export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig()
    console.log('API Base URL:', config.public.apiBase)
    console.log('App URL:', config.public.appUrl)
})