<template>
  <Dialog
    v-model:visible="isVisible"
    header="Редактировать запись"
    :modal="true"
    :closable="true"
    :draggable="false"
    :dismissableMask="true"
    class="w-full max-w-4xl"
    @hide="onDialogHide"
  >
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Основные поля -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Название записи -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
            Название записи *
          </label>
          <InputText
            id="name"
            v-model="form.name"
            placeholder="Введите название записи"
            class="w-full"
            :class="{ 'p-invalid': errors.name }"
          />
          <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
        </div>
        
        <!-- Статистика файлов -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-semibold text-gray-700 mb-2">Статистика вложений</h4>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-600">Текущих:</span>
              <span class="ml-2 font-medium">{{ currentAttachmentsCount }}</span>
            </div>
            <div>
              <span class="text-gray-600">Новых:</span>
              <span class="ml-2 font-medium text-blue-600">{{ newAttachmentFiles.length }}</span>
            </div>
            <div>
              <span class="text-gray-600">К удалению:</span>
              <span class="ml-2 font-medium text-red-600">{{ filesToRemove.length }}</span>
            </div>
            <div>
              <span class="text-gray-600">Итого:</span>
              <span class="ml-2 font-medium text-green-600">{{ totalFilesAfterSave }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Описание -->
      <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
          Описание
        </label>
        <Textarea
          id="description"
          v-model="form.description"
          placeholder="Добавьте описание к записи"
          rows="4"
          class="w-full"
          :class="{ 'p-invalid': errors.description }"
        />
        <small v-if="errors.description" class="p-error">{{ errors.description }}</small>
      </div>
      
      <!-- Существующие вложения -->
      <div v-if="existingAttachments.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Текущие вложения ({{ existingAttachments.length }})
        </label>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="group relative bg-white border rounded-lg p-4 hover:shadow-md transition-shadow"
            :class="{
              'border-red-300 bg-red-50': isMarkedForRemoval(attachment.id),
              'border-gray-200': !isMarkedForRemoval(attachment.id)
            }"
          >
            <!-- Превью для изображений -->
            <div v-if="attachment.mime_type.startsWith('image/')" class="mb-3">
              <img
                :src="attachment.url"
                :alt="attachment.name"
                class="w-full h-32 object-cover rounded border cursor-pointer"
                @click="previewImage(attachment)"
              />
            </div>
            
            <!-- Иконка для других файлов -->
            <div v-else class="mb-3 flex justify-center">
              <i
                :class="getFileIcon(attachment.mime_type)"
                class="text-4xl text-gray-500"
              ></i>
            </div>
            
            <!-- Информация о файле -->
            <div class="space-y-2">
              <h4 class="text-sm font-medium text-gray-900 truncate text-wrap" :title="attachment.name">
                {{ attachment.name }}
              </h4>
              <div class="flex justify-between items-center text-xs text-gray-500">
                <span>{{ attachment.human_readable_size }}</span>
                <span>{{ getFileTypeLabel(attachment.mime_type) }}</span>
              </div>
            </div>
            
            <!-- Действия -->
            <div class="mt-3 flex justify-between">
              <Button
                icon="pi pi-download"
                class="p-button-rounded p-button-sm p-button-outlined"
                @click="downloadFile(attachment)"
                v-tooltip.top="'Скачать'"
                type="button"
              />
              
              <Button
                v-if="!isMarkedForRemoval(attachment.id)"
                icon="pi pi-trash"
                class="p-button-rounded p-button-sm p-button-outlined p-button-danger"
                @click="markForRemoval(attachment.id)"
                v-tooltip.top="'Удалить'"
                type="button"
              />
              
              <Button
                v-else
                icon="pi pi-undo"
                class="p-button-rounded p-button-sm p-button-outlined p-button-success"
                @click="unmarkForRemoval(attachment.id)"
                v-tooltip.top="'Отменить удаление'"
                type="button"
              />
            </div>
            
            <!-- Индикатор удаления -->
            <div
              v-if="isMarkedForRemoval(attachment.id)"
              class="absolute inset-0 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center"
            >
              <div class="bg-red-600 text-white px-3 py-1 rounded text-sm font-medium">
                Будет удален
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Добавление новых вложений -->
      <div>
        <MultiFileUpload
          ref="fileUploadRef"
          v-model="newAttachmentFiles"
          label="Добавить новые вложения"
          :max-files="10"
          :max-file-size="10485760"
          accepted-types="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
          :auto-convert="true"
          empty-text="Перетащите файлы сюда или нажмите для выбора"
          hint-text="Поддерживаемые форматы: изображения, документы, архивы (до 10MB каждый)"
          selected-title="Новые файлы"
          :error-message="errors.attachments"
          @files-selected="onNewFilesSelected"
          @files-converted="onNewFilesConverted"
          @files-removed="onNewFilesRemoved"
          @validation-error="onValidationError"
        >
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
            </div>
          </template>
        </MultiFileUpload>
      </div>
    </form>
    
    <template #footer>
      <div class="flex justify-between items-center">
        <!-- Информация об изменениях -->
        <div v-if="hasChanges" class="text-sm text-gray-600">
          <span v-if="newAttachmentFiles.length > 0" class="text-blue-600">
            +{{ newAttachmentFiles.length }} новых
          </span>
          <span v-if="newAttachmentFiles.length > 0 && filesToRemove.length > 0"> | </span>
          <span v-if="filesToRemove.length > 0" class="text-red-600">
            -{{ filesToRemove.length }} удалено
          </span>
        </div>
        
        <!-- Кнопки -->
        <div class="flex space-x-3">
          <Button
            label="Отмена"
            class="p-button-outlined"
            @click="closeModal"
            :disabled="loading"
          />
          <Button
            label="Сохранить изменения"
            icon="pi pi-check"
            @click="handleSubmit"
            :loading="loading"
            :disabled="!hasChanges"
          />
        </div>
      </div>
    </template>
  </Dialog>
  
  <!-- Модальное окно для превью изображений -->
  <Dialog
    v-model:visible="previewVisible"
    header="Предпросмотр изображения"
    :modal="true"
    :dismissableMask="true"
    class="w-auto max-w-4xl"
  >
    <img
      v-if="previewImageUrl"
      :src="previewImageUrl"
      :alt="previewImageName"
      class="max-w-full max-h-96 object-contain"
    />
    
    <template #footer>
      <div class="flex gap-3">
        <Button
          v-if="previewDownloadUrl"
          label="Скачать"
          icon="pi pi-download"
          @click="downloadPreviewImage"
        />
        <Button
          label="Закрыть"
          @click="previewVisible = false"
          class="p-button-outlined"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: Boolean,
  record: Object
})

const emit = defineEmits(['update:visible', 'updated'])

const { $api } = useNuxtApp()
const toast = useToast()

// Реактивные данные
const loading = ref(false)
const fileUploadRef = ref(null)
const newAttachmentFiles = ref([]) // Новые файлы через MultiFileUpload
const filesToRemove = ref([]) // ID файлов для удаления
const existingAttachments = ref([]) // Существующие вложения

// Превью изображений
const previewVisible = ref(false)
const previewImageUrl = ref('')
const previewImageName = ref('')
const previewDownloadUrl = ref('')

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

// Computed свойства для статистики
const currentAttachmentsCount = computed(() => {
  return existingAttachments.value.length
})

const totalFilesAfterSave = computed(() => {
  return currentAttachmentsCount.value + newAttachmentFiles.value.length - filesToRemove.value.length
})

const hasChanges = computed(() => {
  if (!props.record) return false
  
  const formChanged = form.value.name !== (props.record.name || '') ||
    form.value.description !== (props.record.description || '')
  
  return formChanged || newAttachmentFiles.value.length > 0 || filesToRemove.value.length > 0
})

// Методы для работы с файлами
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

const getFileTypeLabel = (mimeType) => {
  if (mimeType.startsWith('image/')) return 'Изображение'
  if (mimeType.includes('pdf')) return 'PDF'
  if (mimeType.includes('word')) return 'Word'
  if (mimeType.includes('excel')) return 'Excel'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'Архив'
  if (mimeType.startsWith('video/')) return 'Видео'
  if (mimeType.startsWith('audio/')) return 'Аудио'
  return 'Документ'
}

const getImagePreview = (fileObj) => {
  if (fileObj.data && fileObj.type.startsWith('image/')) {
    return `data:${fileObj.type};base64,${fileObj.data}`
  }
  return null
}

// Управление существующими файлами
const isMarkedForRemoval = (attachmentId) => {
  return filesToRemove.value.includes(attachmentId)
}

const markForRemoval = (attachmentId) => {
  if (!filesToRemove.value.includes(attachmentId)) {
    filesToRemove.value.push(attachmentId)
  }
}

const unmarkForRemoval = (attachmentId) => {
  const index = filesToRemove.value.indexOf(attachmentId)
  if (index > -1) {
    filesToRemove.value.splice(index, 1)
  }
}

const downloadFile = (attachment) => {
  window.open(attachment.url, '_blank')
}

// Превью изображений
const previewImage = (attachment) => {
  previewImageUrl.value = attachment.url
  previewImageName.value = attachment.name
  previewDownloadUrl.value = attachment.url
  previewVisible.value = true
}

const previewNewImage = (fileObj) => {
  previewImageUrl.value = getImagePreview(fileObj)
  previewImageName.value = fileObj.name
  previewDownloadUrl.value = null // Новые файлы нельзя скачать напрямую
  previewVisible.value = true
}

const downloadPreviewImage = () => {
  if (previewDownloadUrl.value) {
    window.open(previewDownloadUrl.value, '_blank')
  }
}

// Обработчики событий MultiFileUpload
const onNewFilesSelected = (files) => {
  console.log('Новые файлы выбраны:', files.length)
  delete errors.value.attachments
}

const onNewFilesConverted = (convertedFiles) => {
  console.log('Новые файлы сконвертированы:', convertedFiles.length)
  
  toast.add({
    severity: 'success',
    summary: 'Готово',
    detail: `Обработано новых файлов: ${convertedFiles.length}`,
    life: 3000
  })
}

const onNewFilesRemoved = (removedFile) => {
  console.log('Новый файл удален:', removedFile?.name)
}

const onValidationError = (validationErrors) => {
  errors.value.attachments = validationErrors.join(', ')
  
  toast.add({
    severity: 'warn',
    summary: 'Ошибка валидации',
    detail: validationErrors.join(', '),
    life: 3000
  })
}

// Валидация и отправка
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

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    // Подготавливаем JSON данные
    const jsonData = {
      name: form.value.name.trim(),
      description: form.value.description ? form.value.description.trim() : null,
      new_attachments: newAttachmentFiles.value, // Новые файлы с base64
      remove_attachments: filesToRemove.value // ID файлов для удаления
    }
    
    console.log('Отправляем обновления:', {
      name: jsonData.name,
      description: jsonData.description,
      newAttachments: jsonData.new_attachments.length,
      removeAttachments: jsonData.remove_attachments.length
    })
    
    // Отправка JSON данных
    const response = await $api(
      `/data-grid/${props.record.data_grid_id}/records/${props.record.id}`,
      {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
      }
    )
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const result = await response.json()
    
    // Сначала эмитим событие обновления
    emit('updated', result.data)
    
    // Затем закрываем модальное окно
    closeModal()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись обновлена',
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка при обновлении записи:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.message || 'Не удалось обновить запись',
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
  newAttachmentFiles.value = []
  filesToRemove.value = []
  existingAttachments.value = []
  
  // Закрываем превью
  previewVisible.value = false
  previewImageUrl.value = ''
  previewImageName.value = ''
  previewDownloadUrl.value = ''
  
  // Очищаем компонент загрузки
  if (fileUploadRef.value) {
    fileUploadRef.value.clear()
  }
}

const loadRecordData = () => {
  if (!props.record) return
  
  form.value = {
    name: props.record.name || '',
    description: props.record.description || ''
  }
  
  existingAttachments.value = [...(props.record.attachments || [])]
  filesToRemove.value = []
  newAttachmentFiles.value = []
}

// Загрузка данных записи при открытии модального окна
watch([isVisible, () => props.record], ([visible, record]) => {
  if (visible && record) {
    loadRecordData()
  } else if (!visible) {
    nextTick(() => {
      resetForm()
    })
  }
}, { immediate: true })

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