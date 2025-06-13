import {useDark, useToggle} from '@vueuse/core'

export const useTheme = () => {
    const isDark = useDark({
        selector: 'html',
        attribute: 'data-theme',
        valueDark: 'dark',
        valueLight: 'light',
        initialValue: 'light', // Светлая тема по умолчанию
        storageKey: 'theme-preference',
        storage: typeof window !== 'undefined' ? localStorage : undefined
    })

    const toggleDark = useToggle(isDark)

    const currentTheme = computed(() => isDark.value ? 'dark' : 'light')
    
    const setTheme = (theme: 'light' | 'dark') => {
        isDark.value = theme === 'dark'
    }

    return {
        isDark: readonly(isDark),
        currentTheme: readonly(currentTheme),
        toggleDark,
        setTheme
    }
}