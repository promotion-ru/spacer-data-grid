import {useDark, useToggle} from '@vueuse/core'

export const useTheme = () => {
    const isDark = useDark({
        selector: 'html',
        attribute: 'data-theme',
        valueDark: 'dark',
        valueLight: 'light',
        initialValue: 'dark', // Темная тема по умолчанию
    })

    const toggleDark = useToggle(isDark)

    return {
        isDark: readonly(isDark),
        toggleDark
    }
}