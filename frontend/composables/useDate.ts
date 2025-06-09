export const useDate = () => {
    // Используем специальный composable от модуля - это лучшая практика
    const dayjs = useDayjs()

    const getRelativeTime = (dateString?: string | null): string => {
        if (!dateString) {
            return '-'
        }

        const date = dayjs(dateString, 'DD.MM.YYYY')
        if (!date.isValid()) {
            return '-'
        }
        return date.fromNow()
    }

    return {
        getRelativeTime,
    }
}