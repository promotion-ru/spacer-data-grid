/**
 * Композабл для работы с файлами и их метаданными
 */
export const useFileUtils = () => {
    /**
     * Получить иконку файла на основе MIME типа
     * @param {string} mimeType - MIME тип файла
     * @returns {string} CSS класс иконки PrimeIcons
     */
    const getFileIcon = (mimeType) => {
        if (!mimeType) return 'pi pi-file'

        // Нормализуем MIME тип в нижний регистр
        const type = mimeType.toLowerCase()

        // Изображения
        if (type.startsWith('image/')) return 'pi pi-image'

        // Документы PDF
        if (type.includes('pdf')) return 'pi pi-file-pdf'

        // Microsoft Word документы
        if (type.includes('word') ||
            type.includes('msword') ||
            type.includes('officedocument.wordprocessingml')) {
            return 'pi pi-file-word'
        }

        // Microsoft Excel таблицы
        if (type.includes('excel') ||
            type.includes('spreadsheet') ||
            type.includes('ms-excel') ||
            type.includes('officedocument.spreadsheetml')) {
            return 'pi pi-file-excel'
        }

        // PowerPoint презентации
        if (type.includes('powerpoint') ||
            type.includes('presentation') ||
            type.includes('ms-powerpoint')) {
            return 'pi pi-file'
        }

        // Архивы
        if (type.includes('zip') ||
            type.includes('rar') ||
            type.includes('7z') ||
            type.includes('tar') ||
            type.includes('gzip')) {
            return 'pi pi-file-archive'
        }

        // Видео файлы
        if (type.startsWith('video/')) return 'pi pi-video'

        // Аудио файлы
        if (type.startsWith('audio/')) return 'pi pi-volume-up'

        // Текстовые файлы
        if (type.startsWith('text/') ||
            type.includes('json') ||
            type.includes('xml') ||
            type.includes('csv')) {
            return 'pi pi-file-edit'
        }

        // По умолчанию
        return 'pi pi-file'
    }

    /**
     * Получить читаемое название типа файла
     * @param {string} mimeType - MIME тип файла
     * @returns {string} Человекочитаемое название типа
     */
    const getFileTypeLabel = (mimeType) => {
        if (!mimeType) return 'Документ'

        const type = mimeType.toLowerCase()

        // Изображения
        if (type.startsWith('image/')) {
            if (type.includes('jpeg') || type.includes('jpg')) return 'JPEG изображение'
            if (type.includes('png')) return 'PNG изображение'
            if (type.includes('gif')) return 'GIF изображение'
            if (type.includes('svg')) return 'SVG изображение'
            if (type.includes('webp')) return 'WebP изображение'
            return 'Изображение'
        }

        // Документы
        if (type.includes('pdf')) return 'PDF документ'
        if (type.includes('word') || type.includes('msword') || type.includes('wordprocessingml')) return 'Word документ'
        if (type.includes('excel') || type.includes('spreadsheet') || type.includes('ms-excel')) return 'Excel таблица'
        if (type.includes('powerpoint') || type.includes('presentation')) return 'PowerPoint презентация'

        // Архивы
        if (type.includes('zip')) return 'ZIP архив'
        if (type.includes('rar')) return 'RAR архив'
        if (type.includes('7z')) return '7-Zip архив'
        if (type.includes('tar')) return 'TAR архив'

        // Мультимедиа
        if (type.startsWith('video/')) {
            if (type.includes('mp4')) return 'MP4 видео'
            if (type.includes('avi')) return 'AVI видео'
            if (type.includes('mov')) return 'MOV видео'
            return 'Видео'
        }

        if (type.startsWith('audio/')) {
            if (type.includes('mp3')) return 'MP3 аудио'
            if (type.includes('wav')) return 'WAV аудио'
            if (type.includes('ogg')) return 'OGG аудио'
            return 'Аудио'
        }

        // Текст
        if (type.startsWith('text/')) {
            if (type.includes('plain')) return 'Текстовый файл'
            if (type.includes('html')) return 'HTML файл'
            if (type.includes('css')) return 'CSS файл'
            if (type.includes('javascript')) return 'JavaScript файл'
            return 'Текст'
        }

        if (type.includes('json')) return 'JSON файл'
        if (type.includes('xml')) return 'XML файл'
        if (type.includes('csv')) return 'CSV файл'

        return 'Документ'
    }

    /**
     * Получить короткое обозначение типа файла
     * @param {string} mimeType - MIME тип файла
     * @returns {string} Короткое обозначение (3-4 символа)
     */
    const getShortFileType = (mimeType) => {
        if (!mimeType) return 'FILE'

        const type = mimeType.toLowerCase()

        // Изображения
        if (type.startsWith('image/')) {
            if (type.includes('jpeg') || type.includes('jpg')) return 'JPG'
            if (type.includes('png')) return 'PNG'
            if (type.includes('gif')) return 'GIF'
            if (type.includes('svg')) return 'SVG'
            if (type.includes('webp')) return 'WEBP'
            return 'IMG'
        }

        // Документы
        if (type.includes('pdf')) return 'PDF'
        if (type.includes('word') || type.includes('msword') || type.includes('wordprocessingml')) return 'DOC'
        if (type.includes('excel') || type.includes('spreadsheet') || type.includes('ms-excel')) return 'XLS'
        if (type.includes('powerpoint') || type.includes('presentation')) return 'PPT'

        // Архивы
        if (type.includes('zip')) return 'ZIP'
        if (type.includes('rar')) return 'RAR'
        if (type.includes('7z')) return '7Z'

        // Мультимедиа
        if (type.startsWith('video/')) return 'VID'
        if (type.startsWith('audio/')) return 'AUD'

        // Текст
        if (type.startsWith('text/') || type.includes('json') || type.includes('xml') || type.includes('csv')) return 'TXT'

        return 'FILE'
    }

    /**
     * Форматировать размер файла в человекочитаемый вид
     * @param {number} bytes - Размер в байтах
     * @param {number} decimals - Количество знаков после запятой (по умолчанию 2)
     * @returns {string} Отформатированный размер
     */
    const formatFileSize = (bytes, decimals = 2) => {
        if (!bytes || bytes === 0) return '0 B'

        const k = 1024
        const dm = decimals < 0 ? 0 : decimals
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']

        const i = Math.floor(Math.log(bytes) / Math.log(k))

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
    }

    const getFileKey = (fileObj) => {
        return `${fileObj.name}-${fileObj.type}-${fileObj.size}-${fileObj.id || Date.now()}`
    }

    /**
     * Проверить, является ли файл изображением
     * @param {string} mimeType - MIME тип файла
     * @returns {boolean} true если файл является изображением
     */
    const isImageFile = (mimeType) => {
        return mimeType && mimeType.toLowerCase().startsWith('image/')
    }

    /**
     * Проверить, является ли файл видео
     * @param {string} mimeType - MIME тип файла
     * @returns {boolean} true если файл является видео
     */
    const isVideoFile = (mimeType) => {
        return mimeType && mimeType.toLowerCase().startsWith('video/')
    }

    /**
     * Проверить, является ли файл аудио
     * @param {string} mimeType - MIME тип файла
     * @returns {boolean} true если файл является аудио
     */
    const isAudioFile = (mimeType) => {
        return mimeType && mimeType.toLowerCase().startsWith('audio/')
    }

    /**
     * Проверить, можно ли предварительно просмотреть файл в браузере
     * @param {string} mimeType - MIME тип файла
     * @returns {boolean} true если файл можно просмотреть
     */
    const isPreviewableFile = (mimeType) => {
        if (!mimeType) return false

        const type = mimeType.toLowerCase()

        return type.startsWith('image/') ||
            type.includes('pdf') ||
            type.startsWith('text/') ||
            type.includes('json') ||
            type.includes('xml')
    }

    /**
     * Получить цвет для типа файла (для UI элементов)
     * @param {string} mimeType - MIME тип файла
     * @returns {string} CSS класс цвета
     */
    const getFileTypeColor = (mimeType) => {
        if (!mimeType) return 'text-gray-500'

        const type = mimeType.toLowerCase()

        if (type.startsWith('image/')) return 'text-green-500'
        if (type.includes('pdf')) return 'text-red-500'
        if (type.includes('word') || type.includes('msword')) return 'text-blue-500'
        if (type.includes('excel') || type.includes('spreadsheet')) return 'text-green-600'
        if (type.includes('powerpoint') || type.includes('presentation')) return 'text-orange-500'
        if (type.includes('zip') || type.includes('rar')) return 'text-purple-500'
        if (type.startsWith('video/')) return 'text-pink-500'
        if (type.startsWith('audio/')) return 'text-indigo-500'
        if (type.startsWith('text/')) return 'text-gray-600'

        return 'text-gray-500'
    }

    /**
     * Валидировать размер файла
     * @param {number} bytes - Размер файла в байтах
     * @param {number} maxSizeBytes - Максимальный размер в байтах
     * @returns {boolean} true если размер допустимый
     */
    const validateFileSize = (bytes, maxSizeBytes) => {
        return bytes <= maxSizeBytes
    }

    /**
     * Валидировать тип файла
     * @param {string} mimeType - MIME тип файла
     * @param {string[]} allowedTypes - Массив разрешенных MIME типов
     * @returns {boolean} true если тип разрешен
     */
    const validateFileType = (mimeType, allowedTypes) => {
        if (!allowedTypes || allowedTypes.length === 0) return true
        return allowedTypes.some(allowed => mimeType.toLowerCase().includes(allowed.toLowerCase()))
    }

    return {
        // Основные функции
        getFileIcon,
        getFileTypeLabel,
        getShortFileType,
        formatFileSize,
        getFileKey,

        // Проверки типов
        isImageFile,
        isVideoFile,
        isAudioFile,
        isPreviewableFile,

        // UI утилиты
        getFileTypeColor,

        // Валидация
        validateFileSize,
        validateFileType
    }
}