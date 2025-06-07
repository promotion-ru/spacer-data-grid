<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-4xl"
    header="Просмотр записи"
    :closeOnEscape="true"
    @hide="onDialogHide"
  >
    <div class="space-y-6">
      <!-- Основная информация -->
      <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ record?.name }}</h3>
        <p v-if="record?.description" class="text-gray-600">{{ record.description }}</p>
        <div class="mt-3 text-sm text-gray-500">
          <p>Создано: {{ record?.created_at }}</p>
          <p>Автор: {{ record?.creator?.name }}</p>
        </div>
      </div>
      
      <!-- Вложения -->
      <div v-if="existingAttachments.length > 0">
        <h4 class="text-md font-semibold text-gray-700 mb-3">
          Вложения ({{ existingAttachments.length }})
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <!-- Превью для изображений -->
            <div v-if="attachment.mime_type && attachment.mime_type.startsWith('image/')" class="mb-3">
              <img
                :alt="attachment.name || attachment.file_name"
                :src="attachment.url || attachment.original_url"
                class="w-full h-32 object-cover rounded border cursor-pointer"
                @click="previewImage(attachment)"
              />
            </div>
            
            <!-- Иконка для других файлов -->
            <div v-else class="mb-3 flex justify-center">
              <i :class="getFileIcon(attachment.mime_type)" class="text-4xl text-gray-500"></i>
            </div>
            
            <!-- Информация о файле -->
            <div class="space-y-2">
              <h5 :title="attachment.name || attachment.file_name" class="text-sm font-medium text-gray-900 truncate">
                {{ attachment.name || attachment.file_name }}
              </h5>
              <div class="flex justify-between items-center text-xs text-gray-500">
                <span>{{ attachment.human_readable_size || formatFileSize(attachment.size) }}</span>
                <span>{{ getFileTypeLabel(attachment.mime_type) }}</span>
              </div>
            </div>
            
            <!-- Кнопка скачивания -->
            <div class="mt-3">
              <Button
                class="p-button-sm w-full"
                icon="pi pi-download"
                label="Скачать"
                type="button"
                @click="downloadFile(attachment)"
              />
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-8 text-gray-500">
        <i class="pi pi-file text-4xl mb-2"></i>
        <p>Нет вложений</p>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end">
        <Button
          class="p-button-outlined"
          label="Закрыть"
          @click="closeModal"
        />
      </div>
    </template>
  </Dialog>
  
  <!-- Модальное окно для превью изображений -->
  <Dialog
    v-model:visible="previewVisible"
    :dismissableMask="true"
    :modal="true"
    :closeOnEscape="true"
    class="w-auto max-w-4xl"
    header="Предпросмотр изображения"
  >
    <img
      v-if="previewImageUrl"
      :alt="previewImageName"
      :src="previewImageUrl"
      class="max-w-full max-h-96 object-contain"
    />
    
    <template #footer>
      <div class="flex gap-3">
        <Button
          v-if="previewDownloadUrl"
          icon="pi pi-download"
          label="Скачать"
          @click="downloadPreviewImage"
        />
        <Button
          class="p-button-outlined"
          label="Закрыть"
          @click="previewVisible = false"
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

const emit = defineEmits(['update:visible'])

const toast = useToast()

// Реактивные данные
const existingAttachments = ref([])

// Превью изображений
const previewVisible = ref(false)
const previewImageUrl = ref('')
const previewImageName = ref('')
const previewDownloadUrl = ref('')
const previewAttachmentId = ref(null)

const isVisible = computed({
  get: () => props.visible,
  set: (value) => {
    emit('update:visible', value)
  }
})

// Скачивание файлов
const downloadFile = async (attachment) => {
  try {
    const { token } = useAuthStore()
    if (!token) {
      throw new Error('Токен авторизации не найден')
    }
    // TODO решить проблему с localhost
    const baseUrl = `http://localhost:8000/api/data-grid/${props.record.data_grid_id}/records/${props.record.id}/media/${attachment.id}/download`
    const downloadUrl = `${baseUrl}?token=${encodeURIComponent(token)}`
    
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = attachment.name || attachment.file_name || 'file'
    link.target = '_blank'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    toast.add({
      severity: 'success',
      summary: 'Скачивание начато',
      detail: `Файл ${attachment.name || attachment.file_name} начал скачиваться`,
      life: 3000
    })
    
  } catch (error) {
    console.error('Ошибка при скачивании файла:', error)
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка скачивания',
      detail: error.message,
      life: 5000
    })
    
    // Fallback
    window.open(attachment.url || attachment.original_url, '_blank')
  }
}

const downloadPreviewImage = async () => {
  if (previewAttachmentId.value) {
    try {
      const { token } = useAuthStore()
      if (!token) {
        throw new Error('Токен авторизации не найден')
      }
      // TODO решить проблему с localhost
      const baseUrl = `http://localhost:8000/api/data-grid/${props.record.data_grid_id}/records/${props.record.id}/media/${attachment.id}/download`
      const downloadUrl = `${baseUrl}?token=${encodeURIComponent(token)}`
      
      const link = document.createElement('a')
      link.href = downloadUrl
      link.download = previewImageName.value || 'image'
      link.target = '_blank'
      
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      toast.add({
        severity: 'success',
        summary: 'Скачивание начато',
        detail: `Изображение ${previewImageName.value} начало скачиваться`,
        life: 3000
      })
      
    } catch (error) {
      console.error('Ошибка при скачивании изображения:', error)
      
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.message,
        life: 5000
      })
      
      window.open(previewDownloadUrl.value, '_blank')
    }
  }
}

// Превью изображений
const previewImage = (attachment) => {
  previewImageUrl.value = attachment.url || attachment.original_url
  previewImageName.value = attachment.name || attachment.file_name
  previewDownloadUrl.value = attachment.url || attachment.original_url
  previewAttachmentId.value = attachment.id
  previewVisible.value = true
}

// Utility функции
const getFileIcon = (mimeType) => {
  if (!mimeType) return 'pi pi-file'
  if (mimeType.startsWith('image/')) return 'pi pi-image'
  if (mimeType.includes('pdf')) return 'pi pi-file-pdf'
  if (mimeType.includes('word')) return 'pi pi-file-word'
  if (mimeType.includes('excel')) return 'pi pi-file-excel'
  if (mimeType.includes('zip')) return 'pi pi-file-archive'
  return 'pi pi-file'
}

const getFileTypeLabel = (mimeType) => {
  if (!mimeType) return 'Документ'
  if (mimeType.startsWith('image/')) return 'Изображение'
  if (mimeType.includes('pdf')) return 'PDF'
  if (mimeType.includes('word')) return 'Word'
  if (mimeType.includes('excel')) return 'Excel'
  if (mimeType.includes('zip')) return 'Архив'
  return 'Документ'
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Обработчики
const onDialogHide = () => {
  if (isVisible.value) {
    closeModal()
  }
}

const closeModal = () => {
  isVisible.value = false
  resetData()
}

const resetData = () => {
  existingAttachments.value = []
  previewVisible.value = false
  previewImageUrl.value = ''
  previewImageName.value = ''
  previewDownloadUrl.value = ''
  previewAttachmentId.value = null
}

const loadRecordData = () => {
  if (!props.record) return
  
  existingAttachments.value = [...(props.record.attachments || [])]
}

watch([isVisible, () => props.record], ([visible, record]) => {
  if (visible && record) {
    loadRecordData()
  } else if (!visible) {
    nextTick(() => {
      resetData()
    })
  }
}, {immediate: true})
</script>