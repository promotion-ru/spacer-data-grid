<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-4xl"
    header="Просмотр записи"
    @hide="onDialogHide"
  >
    <div v-if="record" class="space-y-6">
      <!-- Основная информация -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Название записи -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Название записи
          </label>
          <div class="w-full p-3 border border-gray-300 rounded-md bg-gray-50 text-gray-900">
            {{ record.name || 'Не указано' }}
          </div>
        </div>
        
        <!-- Информация о записи -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-semibold text-gray-700 mb-3">Информация о записи</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Автор:</span>
              <span class="font-medium">{{ record.creator?.name || 'Неизвестно' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Создано:</span>
              <span class="font-medium">{{ record.created_at || 'Неизвестно' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Обновлено:</span>
              <span class="font-medium">{{ record.updated_at || 'Не обновлялось' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Вложений:</span>
              <span class="font-medium text-blue-600">{{ existingAttachments.length }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Описание -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Описание
        </label>
        <div
          class="w-full p-3 border border-gray-300 rounded-md bg-gray-50 text-gray-900 min-h-[100px] whitespace-pre-wrap">
          {{ record.description || 'Описание не указано' }}
        </div>
      </div>
      
      <!-- Существующие вложения -->
      <div v-if="existingAttachments.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Вложения ({{ existingAttachments.length }})
        </label>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="group relative bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <!-- Превью для изображений -->
            <div v-if="attachment.mime_type.startsWith('image/')" class="mb-3">
              <img
                :alt="attachment.name"
                :src="attachment.url"
                class="w-full h-32 object-cover rounded border cursor-pointer hover:opacity-75 transition-opacity"
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
              <h4 :title="attachment.name" class="text-sm font-medium text-gray-900 truncate text-wrap">
                {{ attachment.name }}
              </h4>
              <div class="flex justify-between items-center text-xs text-gray-500">
                <span>{{ attachment.human_readable_size }}</span>
                <span>{{ getFileTypeLabel(attachment.mime_type) }}</span>
              </div>
            </div>
            
            <!-- Действия -->
            <div class="mt-3 flex justify-center">
              <Button
                v-tooltip.top="'Скачать'"
                class="p-button-rounded p-button-sm p-button-outlined"
                icon="pi pi-download"
                type="button"
                @click="downloadFile(attachment)"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Сообщение об отсутствии вложений -->
      <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <i class="pi pi-paperclip text-3xl text-gray-400 mb-2"></i>
        <p class="text-gray-500">К этой записи не прикреплены файлы</p>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end">
        <Button
          icon="pi pi-times"
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

// Реактивные данные
const existingAttachments = ref([])

// Превью изображений
const previewVisible = ref(false)
const previewImageUrl = ref('')
const previewImageName = ref('')
const previewDownloadUrl = ref('')

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

const downloadPreviewImage = () => {
  if (previewDownloadUrl.value) {
    window.open(previewDownloadUrl.value, '_blank')
  }
}

const closeModal = () => {
  isVisible.value = false
  resetData()
}

const resetData = () => {
  existingAttachments.value = []
  
  // Закрываем превью
  previewVisible.value = false
  previewImageUrl.value = ''
  previewImageName.value = ''
  previewDownloadUrl.value = ''
}

const loadRecordData = () => {
  if (!props.record) return
  
  existingAttachments.value = [...(props.record.attachments || [])]
}

// Загрузка данных записи при открытии модального окна
watch([isVisible, () => props.record], ([visible, record]) => {
  if (visible && record) {
    loadRecordData()
  } else if (!visible) {
    nextTick(() => {
      resetData()
    })
  }
}, {immediate: true})

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