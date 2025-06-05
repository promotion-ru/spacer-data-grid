<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-2xl"
    header="Добавить запись"
    @hide="onDialogHide"
  >
    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Название записи -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
          Название записи *
        </label>
        <InputText
          id="name"
          v-model="form.name"
          :class="{ 'p-invalid': errors.name }"
          class="w-full"
          placeholder="Введите название записи"
        />
        <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
      </div>
      
      <!-- Описание -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
          Описание
        </label>
        <Textarea
          id="description"
          v-model="form.description"
          :class="{ 'p-invalid': errors.description }"
          class="w-full"
          placeholder="Добавьте описание к записи"
          rows="4"
        />
        <small v-if="errors.description" class="p-error">{{ errors.description }}</small>
      </div>
      
      <!-- Загрузка файлов через новый компонент -->
      <MultiFileUpload
        ref="fileUploadRef"
        v-model="attachmentFiles"
        :auto-convert="true"
        :error-message="errors.new_attachments"
        :max-file-size="10485760"
        :max-files="10"
        accepted-types="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
        empty-text="Перетащите файлы сюда или нажмите для выбора"
        hint-text="Поддерживаемые форматы: изображения, документы, архивы (до 10MB каждый)"
        label="Вложения"
        @files-selected="onFilesSelected"
        @files-converted="onFilesConverted"
        @files-removed="onFilesRemoved"
        @validation-error="onValidationError"
      >
        <!-- Кастомное пустое состояние -->
        <template #empty>
          <div class="flex flex-col items-center justify-center space-y-2 p-6">
            <i class="pi pi-cloud-upload text-4xl text-blue-400"></i>
            <span class="text-gray-600 font-medium">Добавьте файлы к записи</span>
            <p class="text-xs text-gray-500 text-center">
              Перетащите файлы сюда или нажмите для выбора<br>
              Максимум 10 файлов по 10MB каждый
            </p>
          </div>
        </template>
        
        <!-- Кастомное отображение файлов -->
        <template #content="{ files, removeFile, formatFileSize, getFileIcon }">
          <div v-if="files.length > 0">
            <h5 class="text-sm font-medium text-gray-700 mb-3">Выбранные файлы ({{ files.length }})</h5>
            <div class="max-h-64 overflow-y-auto space-y-3">
              <div
                v-for="(fileObj, index) of files"
                :key="fileObj.id"
                :class="{
                  'bg-green-50 border-green-200': fileObj.data,
                  'bg-orange-50 border-orange-200': !fileObj.data
                }"
                class="flex items-center justify-between p-3 rounded border"
              >
                <div class="flex items-center space-x-3">
                  <!-- Превью для изображений -->
                  <div v-if="fileObj.type.startsWith('image/') && fileObj.data" class="flex-shrink-0">
                    <img
                      :alt="fileObj.name"
                      :src="getImagePreview(fileObj)"
                      class="w-12 h-12 object-cover rounded border"
                    />
                  </div>
                  <!-- Иконка для других файлов -->
                  <div v-else class="flex-shrink-0">
                    <i
                      :class="getFileIcon(fileObj.type)"
                      class="text-2xl"
                    ></i>
                  </div>
                  
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate text-wrap">{{ fileObj.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(fileObj.size) }}</p>
                    
                    <!-- Статус файла -->
                    <div class="flex items-center mt-1">
                      <i
                        :class="{
                          'pi pi-check text-green-500': fileObj.data,
                          'pi pi-clock text-orange-500': !fileObj.data
                        }"
                        class="mr-1 text-xs"
                      ></i>
                      <span
                        :class="{
                          'text-green-600': fileObj.data,
                          'text-orange-600': !fileObj.data
                        }"
                        class="text-xs"
                      >
                        {{ fileObj.data ? 'Готов к отправке' : 'Обрабатывается...' }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <Button
                  class="p-button-rounded p-button-sm p-button-text p-button-danger"
                  icon="pi pi-times"
                  type="button"
                  @click="removeFile(index)"
                />
              </div>
            </div>
            
            <!-- Сводная информация -->
            <div class="mt-3 p-3 bg-blue-50 rounded text-sm">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <span class="text-blue-700 font-medium">Всего файлов:</span>
                  <span class="ml-2">{{ files.length }}</span>
                </div>
                <div>
                  <span class="text-green-700 font-medium">Готово:</span>
                  <span class="ml-2">{{ getReadyFilesCount() }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </MultiFileUpload>
    </form>
    
    <template #footer>
      <div class="flex justify-end space-x-3">
        <Button
          :disabled="loading"
          class="p-button-outlined"
          label="Отмена"
          @click="closeModal"
        />
        <Button
          :loading="loading"
          icon="pi pi-check"
          label="Сохранить"
          @click="handleSubmit"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: Boolean,
  gridId: String
})

const {$api} = useNuxtApp()
const toast = useToast()

const emit = defineEmits(['update:visible', 'created'])

// Реактивные данные
const loading = ref(false)
const fileUploadRef = ref(null)
const attachmentFiles = ref([]) // Массив объектов файлов

const form = ref({
  name: '',
  description: ''
})

const errors = ref({})

const isVisible = computed({
  get: () => props.visible,
  set: (value) => {
    emit('update:visible', value)
  }
})

// Обработчик закрытия диалога
const onDialogHide = () => {
  if (isVisible.value) {
    closeModal()
  }
}

// Вспомогательные методы
const getImagePreview = (fileObj) => {
  if (fileObj.data && fileObj.type.startsWith('image/')) {
    return `data:${fileObj.type};base64,${fileObj.data}`
  }
  return null
}

const getReadyFilesCount = () => {
  return attachmentFiles.value.filter(file => file.data).length
}

// Отслеживание изменений в v-model
watch(attachmentFiles, (newFiles) => {
  console.log('v-model обновлен:', {
    totalFiles: newFiles.length,
    readyFiles: newFiles.filter(f => f.data).length,
    files: newFiles.map(f => ({
      name: f.name,
      hasBase64: !!f.data
    }))
  })
}, {deep: true})

// Обработчики событий файлового компонента
const onFilesSelected = (files) => {
  console.log('Файлы выбраны:', files.length)
  // Очищаем ошибки валидации
  delete errors.value.new_attachments
}

const onFilesConverted = (convertedFiles) => {
  console.log('Файлы сконвертированы:', convertedFiles.length)
  
  toast.add({
    severity: 'success',
    summary: 'Готово',
    detail: `Обработано файлов: ${convertedFiles.length}`,
    life: 3000
  })
}

const onFilesRemoved = (removedFile) => {
  console.log('Файл удален:', removedFile?.name)
}

const onValidationError = (validationErrors) => {
  errors.value.new_attachments = validationErrors.join(', ')
  
  toast.add({
    severity: 'warn',
    summary: 'Ошибка валидации',
    detail: validationErrors.join(', '),
    life: 3000
  })
}

// Валидация основной формы
const validateForm = () => {
  errors.value = {}
  
  if (!form.value.name.trim()) {
    errors.value.name = 'Название записи обязательно'
  }
  
  if (form.value.description && form.value.description.length > 2000) {
    errors.value.description = 'Описание не должно превышать 2000 символов'
  }
  
  return Object.keys(errors.value).length === 0
}

// Обработка отправки формы
const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    // Подготавливаем данные для отправки
    const jsonData = {
      name: form.value.name.trim(),
      description: form.value.description ? form.value.description.trim() : null,
      new_attachments: attachmentFiles.value
    }
    
    // Отправка данных
    const response = await $api(`/data-grid/${props.gridId}/records`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(jsonData)
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const result = await response.json()
    
    // Сначала эмитим событие создания
    emit('created', result.data)
    
    // Затем закрываем модальное окно
    closeModal()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись создана',
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка при создании записи:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.message || 'Не удалось создать запись',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  // Устанавливаем visible в false
  isVisible.value = false
  // Сбрасываем форму
  resetForm()
}

const resetForm = () => {
  form.value = {
    name: '',
    description: ''
  }
  errors.value = {}
  attachmentFiles.value = []
  
  // Очищаем файловый компонент
  if (fileUploadRef.value) {
    fileUploadRef.value.clear()
  }
}

// Сброс формы при закрытии модального окна через watcher
watch(isVisible, (newValue) => {
  if (!newValue) {
    nextTick(() => {
      resetForm()
    })
  }
})

// Закрытие по Escape
const handleKeydown = (event) => {
  if (event.key === 'Escape' && isVisible.value) {
    closeModal()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>