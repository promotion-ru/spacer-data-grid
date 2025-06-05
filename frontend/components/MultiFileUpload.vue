<template>
  <div class="multi-file-upload">
    <!-- Заголовок если передан -->
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <FileUpload
      :accept="acceptedTypes"
      :auto="false"
      :class="uploadClass"
      :disabled="disabled"
      :maxFileSize="maxFileSize"
      :multiple="multiple"
      :name="name"
      :showCancelButton="false"
      :showUploadButton="false"
      :url="uploadUrl"
      :chooseLabel="chooseLabel"
      @clear="handleClear"
      @remove="handleRemove"
      @select="handleSelect"
    >
      <!-- Слот для пустого состояния -->
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center space-y-2 p-6">
            <i class="pi pi-cloud-upload text-3xl text-gray-400"></i>
            <span class="text-gray-600">{{ emptyText }}</span>
            <p v-if="showHint" class="text-xs text-gray-500">
              {{ hintText }}
            </p>
          </div>
        </slot>
      </template>
      
      <!-- Слот для контента с файлами -->
      <template #content="{ files, removeFileCallback }">
        <slot
          :files="fileObjects"
          :formatFileSize="formatFileSize"
          :getFileIcon="getFileIcon"
          :removeFile="removeFileByIndex"
          name="content"
        >
          <!-- Выбранные файлы -->
          <div v-if="fileObjects.length > 0">
            <h5 class="text-sm font-medium text-gray-700 mb-3">{{ selectedTitle }}</h5>
            <div class="max-h-48 overflow-y-auto space-y-2">
              <div
                v-for="(fileObj, index) of fileObjects"
                :key="getFileKey(fileObj)"
                class="flex items-center justify-between p-3 bg-gray-50 rounded border"
              >
                <div class="flex items-center space-x-3">
                  <i
                    :class="getFileIcon(fileObj.type)"
                    class="text-2xl text-gray-500"
                  ></i>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ fileObj.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(fileObj.size) }}</p>
                    <!-- Статус файла -->
                    <div v-if="fileObj.data" class="flex items-center mt-1">
                      <i class="pi pi-check text-green-500 mr-1"></i>
                      <span class="text-xs text-green-600">Готов</span>
                    </div>
                    <div v-else class="flex items-center mt-1">
                      <i class="pi pi-clock text-orange-500 mr-1"></i>
                      <span class="text-xs text-orange-600">Ожидание</span>
                    </div>
                  </div>
                </div>
                <Button
                  class="p-button-rounded p-button-sm p-button-text p-button-danger"
                  icon="pi pi-times"
                  type="button"
                  @click="removeFileByIndex(index)"
                />
              </div>
            </div>
          </div>
        </slot>
      </template>
      
      <!-- Слот для заголовка выбора файлов -->
      <template v-if="$slots.header" #header>
        <slot name="header"></slot>
      </template>
    </FileUpload>
    
    <!-- Ошибки валидации -->
    <small v-if="errorMessage" class="p-error block mt-2">{{ errorMessage }}</small>
    
    <!-- Дополнительная информация -->
    <div v-if="showFileInfo && fileObjects.length > 0" class="mt-2 text-xs text-gray-500">
      <span>Выбрано файлов: {{ fileObjects.length }}</span>
      <span v-if="maxFiles"> | Максимум: {{ maxFiles }}</span>
      <span v-if="getTotalSize() > 0"> | Общий размер: {{ formatFileSize(getTotalSize()) }}</span>
      <span v-if="getReadyCount() < fileObjects.length"> | Готово: {{ getReadyCount() }}</span>
    </div>
  </div>
</template>

<script setup>
import {computed, nextTick, ref, watch} from 'vue'

// Props для настройки компонента
const props = defineProps({
  // Основные настройки
  modelValue: {
    type: Array,
    default: () => []
  },
  name: {
    type: String,
    default: 'files[]'
  },
  
  // UI настройки
  label: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  chooseLabel: {
    type: String,
    default: 'Выбрать файлы'
  },
  
  // Настройки файлов
  multiple: {
    type: Boolean,
    default: true
  },
  maxFiles: {
    type: Number,
    default: 10
  },
  maxFileSize: {
    type: Number,
    default: 10485760 // 10MB
  },
  acceptedTypes: {
    type: String,
    default: 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar'
  },
  
  // Настройки обработки
  autoConvert: {
    type: Boolean,
    default: true // Автоматически конвертировать в base64
  },
  
  // UI настройки
  showFileInfo: {
    type: Boolean,
    default: true
  },
  showHint: {
    type: Boolean,
    default: true
  },
  
  // Тексты
  emptyText: {
    type: String,
    default: 'Перетащите файлы сюда для выбора'
  },
  hintText: {
    type: String,
    default: 'Поддерживаемые форматы: изображения, документы, архивы'
  },
  selectedTitle: {
    type: String,
    default: 'Выбранные файлы'
  },
  
  // Стили
  uploadClass: {
    type: String,
    default: 'w-full'
  },
  
  // Валидация
  errorMessage: {
    type: String,
    default: ''
  },
  
  // Дополнительные настройки
  uploadUrl: {
    type: String,
    default: '/api/upload' // Не используется, но оставляем для совместимости
  }
})

// События
const emit = defineEmits([
  'update:modelValue',
  'files-selected',
  'files-removed',
  'files-converted',
  'validation-error'
])

// Локальное состояние
const fileObjects = ref([...props.modelValue])

// Computed свойства
const totalFiles = computed(() => fileObjects.value.length)

// Методы для работы с файлами
const getFileKey = (fileObj) => {
  return `${fileObj.name}-${fileObj.type}-${fileObj.size}-${fileObj.id || Date.now()}`
}

const getFileIcon = (mimeType) => {
  if (mimeType.startsWith('image/')) return 'pi pi-image'
  if (mimeType.includes('pdf')) return 'pi pi-file-pdf'
  if (mimeType.includes('word')) return 'pi pi-file-word'
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'pi pi-file-excel'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'pi pi-file-archive'
  if (mimeType.startsWith('video/')) return 'pi pi-video'
  if (mimeType.startsWith('audio/')) return 'pi pi-volume-up'
  return 'pi pi-file'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const getTotalSize = () => {
  return fileObjects.value.reduce((total, file) => total + file.size, 0)
}

const getReadyCount = () => {
  return fileObjects.value.filter(f => f.data).length
}

// Валидация файлов
const validateFiles = (files) => {
  const errors = []
  
  // Проверка количества файлов
  if (totalFiles.value + files.length > props.maxFiles) {
    errors.push(`Можно выбрать максимум ${props.maxFiles} файлов`)
  }
  
  // Проверка размера файлов
  const oversizedFiles = files.filter(file => file.size > props.maxFileSize)
  if (oversizedFiles.length > 0) {
    errors.push(`Файлы превышают максимальный размер: ${oversizedFiles.map(f => f.name).join(', ')}`)
  }
  
  if (errors.length > 0) {
    emit('validation-error', errors)
    return false
  }
  
  return true
}

// Конвертация файла в base64
const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => {
      resolve(reader.result.split(',')[1]) // Убираем префикс data:mime;base64,
    }
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

// Создание объекта файла
const createFileObject = (file, base64 = null) => {
  return {
    id: Date.now() + Math.random(), // Уникальный ID
    name: file.name,
    type: file.type,
    size: file.size,
    lastModified: file.lastModified,
    data: base64
  }
}

// Конвертация файлов в объекты с base64
const convertFilesToObjects = async (files) => {
  const newFileObjects = []
  
  for (const file of files) {
    const fileObj = createFileObject(file, null)
    
    // Сразу добавляем в массив
    fileObjects.value.push(fileObj)
    newFileObjects.push(fileObj)
    
    if (props.autoConvert) {
      try {
        // Конвертируем в base64
        const base64Content = await convertFileToBase64(file)
        fileObj.data = base64Content
      } catch (error) {
        console.error('Ошибка конвертации файла:', file.name, error)
        
        // При ошибке удаляем файл
        const index = fileObjects.value.findIndex(f => f.id === fileObj.id)
        if (index > -1) {
          fileObjects.value.splice(index, 1)
        }
      }
    }
  }
  
  return newFileObjects
}

// Обработчики событий FileUpload
const handleSelect = async (event) => {
  const files = Array.from(event.files)
  
  if (!validateFiles(files)) {
    return
  }
  
  try {
    const newFileObjects = await convertFilesToObjects(files)
    
    // Сразу обновляем v-model после добавления файлов
    updateModelValue()
    
    emit('files-selected', files)
    
    if (props.autoConvert) {
      // После конвертации снова обновляем v-model
      await nextTick()
      updateModelValue()
      emit('files-converted', newFileObjects.filter(f => f.data))
    }
  } catch (error) {
    console.error('Ошибка при обработке файлов:', error)
  }
}

const handleRemove = (event) => {
  const fileToRemove = event.file
  const index = fileObjects.value.findIndex(f =>
    f.name === fileToRemove.name && f.size === fileToRemove.size
  )
  
  if (index > -1) {
    const removedFile = fileObjects.value.splice(index, 1)[0]
    updateModelValue()
    emit('files-removed', removedFile)
  }
}

const handleClear = () => {
  fileObjects.value = []
  updateModelValue()
  emit('files-removed', null)
}

// Удаление файла по индексу
const removeFileByIndex = (index) => {
  if (index >= 0 && index < fileObjects.value.length) {
    const removedFile = fileObjects.value.splice(index, 1)[0]
    updateModelValue()
    emit('files-removed', removedFile)
  }
}

// Обновление v-model - УПРОЩЕНО!
const updateModelValue = () => {
  // Отправляем все файлы в v-model, независимо от наличия base64
  emit('update:modelValue', [...fileObjects.value])
}

// Публичные методы компонента
const clear = () => {
  handleClear()
}

const getFiles = () => {
  return [...fileObjects.value]
}

const getValidFiles = () => {
  return fileObjects.value.filter(f => f.data)
}

const getAllFiles = () => {
  return [...fileObjects.value]
}

const addFile = async (file) => {
  if (!validateFiles([file])) {
    return false
  }
  
  try {
    await convertFilesToObjects([file])
    updateModelValue()
    return true
  } catch (error) {
    console.error('Ошибка добавления файла:', error)
    return false
  }
}

const removeFile = (fileId) => {
  const index = fileObjects.value.findIndex(f => f.id === fileId)
  if (index > -1) {
    removeFileByIndex(index)
  }
}

// Создание превью для изображений
const createImagePreview = (fileObj) => {
  if (fileObj.data && fileObj.type.startsWith('image/')) {
    return `data:${fileObj.type};base64,${fileObj.data}`
  }
  return null
}

// Получение файла как Blob
const getFileAsBlob = (fileObj) => {
  if (!fileObj.data) return null
  
  try {
    const byteCharacters = atob(fileObj.data)
    const byteNumbers = new Array(byteCharacters.length)
    
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i)
    }
    
    const byteArray = new Uint8Array(byteNumbers)
    return new Blob([byteArray], {type: fileObj.type})
  } catch (error) {
    console.error('Ошибка создания Blob:', error)
    return null
  }
}

// Наблюдение за изменениями modelValue извне
watch(() => props.modelValue, (newValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(fileObjects.value)) {
    fileObjects.value = [...newValue]
  }
}, {immediate: true, deep: true})

// Экспорт методов для родительского компонента
defineExpose({
  clear,
  getFiles,
  getValidFiles,
  getAllFiles,
  addFile,
  removeFile,
  createImagePreview,
  getFileAsBlob,
  totalFiles,
  formatFileSize,
  getFileIcon
})
</script>

<style scoped>
.multi-file-upload {
  @apply w-full;
}

.p-fileupload .p-fileupload-content {
  @apply border-t-0;
}
</style>