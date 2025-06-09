<template>
  <div class="multi-file-upload">
    <!-- Заголовок если передан -->
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <FileUpload
      :key="fileUploadKey"
      ref="fileUploadRef"
      :accept="acceptedTypes"
      :auto="false"
      :chooseLabel="chooseLabel"
      :class="uploadClass"
      :disabled="disabled"
      :maxFileSize="maxFileSize"
      :multiple="multiple"
      :name="name"
      :showCancelButton="false"
      :showUploadButton="false"
      :url="uploadUrl"
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
              {{ dynamicHintText }}
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
    default: '' // Пустая строка означает принятие любых файлов
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
    default: ''
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

const {
  getFileIcon,
  formatFileSize,
  getFileKey
} = useFileUtils()

// Локальное состояние
const fileUploadRef = ref(null)
const fileUploadKey = ref(0)
const fileObjects = ref([...props.modelValue])

// Computed свойства
const totalFiles = computed(() => fileObjects.value.length)

// Динамический текст подсказки
const dynamicHintText = computed(() => {
  if (props.hintText) {
    return props.hintText
  }
  
  if (props.acceptedTypes) {
    return `Поддерживаемые форматы: ${props.acceptedTypes}`
  }
  
  return 'Поддерживаемые форматы: любые файлы'
})

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
    newFileObjects.push(fileObj)
    
    if (props.autoConvert) {
      try {
        // Конвертируем в base64
        const base64Content = await convertFileToBase64(file)
        fileObj.data = base64Content
      } catch (error) {
        console.error('Ошибка конвертации файла:', file.name, error)
        // При ошибке удаляем файл из новых объектов
        const index = newFileObjects.findIndex(f => f.id === fileObj.id)
        if (index > -1) {
          newFileObjects.splice(index, 1)
        }
      }
    }
  }
  
  // Добавляем все успешно обработанные файлы в основной массив
  fileObjects.value.push(...newFileObjects)
  
  return newFileObjects
}

// Синхронизация с внутренним состоянием FileUpload
const syncWithFileUpload = async () => {
  await nextTick()
  
  if (fileUploadRef.value && fileUploadRef.value.files) {
    // Получаем текущие файлы из FileUpload
    const internalFiles = fileUploadRef.value.files
    
    // Оставляем только те файлы, которые есть в fileObjects
    const validFiles = internalFiles.filter(internalFile => {
      return fileObjects.value.some(fileObj =>
        fileObj.name === internalFile.name &&
        fileObj.size === internalFile.size &&
        fileObj.type === internalFile.type
      )
    })
    
    // Обновляем внутреннее состояние FileUpload
    fileUploadRef.value.files.splice(0, fileUploadRef.value.files.length, ...validFiles)
    
    // Принудительное обновление компонента
    fileUploadRef.value.$forceUpdate()
  }
}

// Обработчики событий FileUpload
const handleSelect = async (event) => {
  const allFiles = Array.from(event.files)
  
  // Фильтруем только новые файлы (которых еще нет в fileObjects)
  const newFiles = allFiles.filter(file => {
    return !fileObjects.value.some(existingFile =>
      existingFile.name === file.name &&
      existingFile.size === file.size &&
      existingFile.type === file.type &&
      existingFile.lastModified === file.lastModified
    )
  })
  
  // Если нет новых файлов, ничего не делаем
  if (newFiles.length === 0) {
    return
  }
  
  if (!validateFiles(newFiles)) {
    return
  }
  
  try {
    const newFileObjects = await convertFilesToObjects(newFiles)
    
    // Сразу обновляем v-model после добавления файлов
    updateModelValue()
    
    emit('files-selected', newFiles)
    
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

// ИСПРАВЛЕННЫЙ метод удаления файла по индексу
const removeFileByIndex = async (index) => {
  if (index >= 0 && index < fileObjects.value.length) {
    const removedFile = fileObjects.value[index]
    
    // Удаляем из нашего состояния
    fileObjects.value.splice(index, 1)
    
    // Синхронизируем с FileUpload
    await syncWithFileUpload()
    
    // Если синхронизация не помогла, принудительно перерисовываем
    if (fileUploadRef.value && fileUploadRef.value.files && fileUploadRef.value.files.length !== fileObjects.value.length) {
      fileUploadKey.value++
    }
    
    updateModelValue()
    emit('files-removed', removedFile)
  }
}

// Альтернативный метод удаления с полной перерисовкой
const removeFileByIndexForced = (index) => {
  if (index >= 0 && index < fileObjects.value.length) {
    const removedFile = fileObjects.value.splice(index, 1)[0]
    
    // Принудительная перерисовка
    fileUploadKey.value++
    
    updateModelValue()
    emit('files-removed', removedFile)
  }
}

// Обновление v-model
const updateModelValue = () => {
  emit('update:modelValue', [...fileObjects.value])
}

// Публичные методы компонента
const clear = () => {
  handleClear()
  fileUploadKey.value++
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
    await syncWithFileUpload()
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

// Принудительное обновление файлов (для внешнего управления)
const updateFiles = (newFiles) => {
  fileObjects.value = [...newFiles]
  fileUploadKey.value++
  updateModelValue()
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
  removeFileByIndex,
  removeFileByIndexForced,
  updateFiles,
  createImagePreview,
  getFileAsBlob,
  totalFiles,
  formatFileSize,
  getFileIcon,
  syncWithFileUpload
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