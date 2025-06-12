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

    /**
     * Форматирует Date объект в строку для отправки на бэкенд
     * @param {Date|null} date - Date объект из формы
     * @returns {string|null} - Строка в формате "DD.MM.YYYY" или null
     */
    const formatDateForBackend = (date) => {
        if (!date) return null

        // Используем dayjs для надежного форматирования
        // dayjs() принимает Date объект и форматирует его корректно
        return dayjs(date).format('DD.MM.YYYY')
    }

    /**
     * Парсит строку даты с бэкенда в Date объект
     * @param {string|null} dateString - Строка в формате "DD.MM.YYYY"
     * @returns {Date|null} - Date объект или null
     */
    const parseDateFromBackend = (dateString) => {
        if (!dateString) return null

        // Парсим строку с указанным форматом
        const parsed = dayjs(dateString, 'DD.MM.YYYY', true) // true = strict mode

        if (!parsed.isValid()) {
            console.warn(`Invalid date format: ${dateString}`)
            return null
        }

        // Возвращаем нативный Date объект
        return parsed.toDate()
    }

    /**
     * Сравнивает две даты (только дата, без времени)
     * @param {Date|null} date1
     * @param {Date|null} date2
     * @returns {boolean}
     */
    const datesEqual = (date1, date2) => {
        if (!date1 && !date2) return true
        if (!date1 || !date2) return false

        // Используем dayjs для точного сравнения только даты (без времени)
        const d1 = dayjs(date1).startOf('day')
        const d2 = dayjs(date2).startOf('day')

        return d1.isSame(d2)
    }

    /**
     * Проверяет валидность даты
     * @param {Date|string|null} date
     * @returns {boolean}
     */
    const isValidDate = (date) => {
        if (!date) return false
        return dayjs(date).isValid()
    }

    /**
     * Форматирует дату для отображения в UI
     * @param {Date|string|null} date
     * @param {string} format - Формат для отображения (по умолчанию DD.MM.YYYY)
     * @returns {string}
     */
    const formatDateForDisplay = (date, format = 'DD.MM.YYYY') => {
        if (!date) return '-'

        const parsed = dayjs(date)
        if (!parsed.isValid()) return '-'

        return parsed.format(format)
    }

    return {
        getRelativeTime,
        formatDateForBackend,
        parseDateFromBackend,
        datesEqual,
        isValidDate,
        formatDateForDisplay,
    }
}