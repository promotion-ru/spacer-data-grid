// composables/useProfile.js

export const useProfile = () => {
    const { $api } = useNuxtApp()

    const profile = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const fetchProfile = async () => {
        loading.value = true
        error.value = null

        try {
            const response = await $api('/profile', {
                method: 'GET'
            })

            console.log('fetchProfile response:', response)

            if (response.success && response.data) {
                profile.value = response.data
            } else {
                error.value = response.message || 'Ошибка при загрузке профиля'
            }
        } catch (err) {
            error.value = err.data?.message || err.message || 'Ошибка при загрузке профиля'
            console.error('Fetch profile error:', err)
        } finally {
            loading.value = false
        }
    }

    const updateProfile = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await $api('/profile', {
                method: 'PATCH',
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            console.log('updateProfile response:', response)

            if (response.success && response.data) {
                profile.value = response.data
                return response
            } else {
                error.value = response.message || 'Ошибка при обновлении профиля'
                throw new Error(response.message || 'Ошибка при обновлении профиля')
            }
        } catch (err) {
            error.value = err.data?.message || err.message || 'Ошибка при обновлении профиля'

            if (err.data?.errors) {
                const validationError = new Error(err.data.message || 'Ошибка валидации')
                validationError.response = { data: err.data }
                throw validationError
            }

            throw err
        } finally {
            loading.value = false
        }
    }

    const clearError = () => {
        error.value = null
    }

    const refreshProfile = async () => {
        await fetchProfile()
    }

    return {
        // Реактивные данные
        profile: readonly(profile),
        loading: readonly(loading),
        error: readonly(error),

        // Методы
        fetchProfile,
        updateProfile,
        clearError,
        refreshProfile
    }
}