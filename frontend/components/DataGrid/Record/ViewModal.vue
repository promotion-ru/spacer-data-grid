<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :closeOnEscape="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-5xl"
    header="Просмотр записи"
    @hide="onDialogHide"
  >
    <div class="space-y-6">
      <!-- Основная информация -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-100">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ record?.name }}</h3>
            <div class="flex items-center space-x-4 text-sm">
              <!-- Тип операции -->
              <div class="flex items-center space-x-2">
                <i
                  :class="{
                    'pi pi-arrow-up text-green-600': record?.operation_type_id === 1,
                    'pi pi-arrow-down text-red-600': record?.operation_type_id === 2
                  }"
                  class="text-lg"
                ></i>
                <span
                  :class="{
                    'text-green-700 bg-green-100': record?.operation_type_id === 1,
                    'text-red-700 bg-red-100': record?.operation_type_id === 2
                  }"
                  class="px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ getOperationTypeName(record?.operation_type_id) }}
                </span>
              </div>
              
              <!-- Дата -->
              <div class="flex items-center space-x-1 text-gray-600">
                <i class="pi pi-calendar text-gray-500"></i>
                <span>{{ record?.date }}</span>
              </div>
            </div>
          </div>
          
          <!-- Сумма -->
          <div class="text-right">
            <div
              :class="{
                'text-green-600': record?.operation_type_id === 1,
                'text-red-600': record?.operation_type_id === 2
              }"
              class="text-2xl font-bold"
            >
              {{ formatAmount(record?.amount, record?.operation_type_id) }}
            </div>
            <div class="text-sm text-gray-500">{{ record?.amount_formatted }}</div>
          </div>
        </div>
        
        <!-- Описание -->
        <div v-if="record?.description" class="mb-4">
          <p class="text-gray-700 leading-relaxed">{{ record.description }}</p>
        </div>
        
        <!-- Дополнительная информация -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-blue-200">
          <!-- Тип записи -->
          <div class="flex items-center space-x-2">
            <i class="pi pi-tag text-gray-500"></i>
            <div>
              <span class="text-xs text-gray-500 uppercase tracking-wide">Тип записи</span>
              <p class="text-sm font-medium text-gray-900">
                {{ record?.type_name || 'Не указан' }}
                <span v-if="record?.type?.is_global" class="ml-1 text-xs text-blue-600">(Глобальный)</span>
              </p>
            </div>
          </div>
          
          <!-- Автор и дата создания -->
          <div class="flex items-center space-x-2">
            <i class="pi pi-user text-gray-500"></i>
            <div>
              <span class="text-xs text-gray-500 uppercase tracking-wide">Создано</span>
              <p class="text-sm font-medium text-gray-900">
                {{ record?.creator?.name || 'Неизвестно' }}
              </p>
              <p class="text-xs text-gray-500">{{ record?.created_at }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Статистика -->
      <div v-if="record" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ existingAttachments.length }}</div>
          <div class="text-sm text-gray-500">Вложений</div>
        </div>
        
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
          <div class="text-2xl font-bold text-gray-700">
            {{ formattedDate }}
          </div>
          <div class="text-sm text-gray-500">Дней назад</div>
        </div>
        
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
          <div class="text-2xl font-bold text-purple-600">
            {{ record?.updated_at !== record?.created_at ? '✓' : '—' }}
          </div>
          <div class="text-sm text-gray-500">Изменялась</div>
        </div>
      </div>
      
      <!-- Вложения -->
      <div v-if="existingAttachments.length > 0">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-lg font-semibold text-gray-700 flex items-center space-x-2">
            <i class="pi pi-paperclip text-gray-500"></i>
            <span>Вложения ({{ existingAttachments.length }})</span>
          </h4>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="group bg-white border rounded-lg p-4 hover:shadow-lg hover:border-blue-300 transition-all duration-200"
          >
            <!-- Превью для изображений -->
            <div v-if="attachment.mime_type && attachment.mime_type.startsWith('image/')" class="mb-3">
              <div class="relative">
                <Image
                  :alt="attachment.name || attachment.file_name"
                  :src="attachment.url || attachment.original_url"
                  class="rounded-lg"
                  preview
                  width="250"
                />
                <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                  {{ getFileTypeLabel(attachment.mime_type) }}
                </div>
              </div>
            </div>
            
            <!-- Иконка для других файлов -->
            <div v-else class="mb-3 flex justify-center">
              <div class="relative">
                <i :class="getFileIcon(attachment.mime_type)" class="text-6xl text-gray-400"></i>
                <div
                  class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 bg-gray-600 text-white text-xs px-2 py-1 rounded">
                  {{ getFileTypeLabel(attachment.mime_type) }}
                </div>
              </div>
            </div>
            
            <!-- Информация о файле -->
            <div class="space-y-2">
              <h5
                :title="attachment.name || attachment.file_name"
                class="text-sm font-medium text-gray-900 truncate leading-tight"
              >
                {{ attachment.name || attachment.file_name }}
              </h5>
              
              <div class="flex justify-between items-center text-xs text-gray-500">
                <span class="font-medium">{{ attachment.human_readable_size || formatFileSize(attachment.size) }}</span>
                <span class="px-2 py-1 bg-gray-100 rounded">{{ getShortFileType(attachment.mime_type) }}</span>
              </div>
            </div>
            
            <!-- Кнопки действий -->
            <div class="mt-3 flex space-x-2">
              <Button
                class="p-button-sm flex-1"
                icon="pi pi-download"
                label="Скачать"
                type="button"
                @click="downloadFile(attachment)"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Нет вложений -->
      <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <i class="pi pi-file text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-500 mb-2">Нет вложений</h3>
        <p class="text-sm text-gray-400">К этой записи не прикреплено файлов</p>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-between items-center">
        <Button
          class="p-button-outlined"
          label="Закрыть"
          @click="closeModal"
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
const {
  getFileIcon,
  getFileTypeLabel,
  getShortFileType,
  formatFileSize,
} = useFileUtils()
const { getRelativeTime } = useDate();

// Реактивные данные
const existingAttachments = ref([])

const isVisible = computed({
  get: () => props.visible,
  set: (value) => {
    emit('update:visible', value)
  }
})

// Utility функции для типа операции
const getOperationTypeName = (operationTypeId) => {
  switch (operationTypeId) {
    case 1:
      return 'Доход'
    case 2:
      return 'Расход'
    default:
      return 'Не указано'
  }
}

// Форматирование суммы
const formatAmount = (amount, operationTypeId) => {
  if (!amount) return '0 ₽'
  
  const formatted = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount)
  
  const prefix = operationTypeId === 1 ? '+' : operationTypeId === 2 ? '−' : ''
  return prefix + formatted
}


const formattedDate = computed(() => {
  const relative = getRelativeTime(props.record.date);
  if (relative.includes('секунд') || relative.includes('минут') || relative.includes('час')) {
    return 'Сегодня';
  }
  if (relative === 'день назад') {
    return '1';
  }
  
  const match = relative.match(/(\d+)\s+д(ень|ня|ней)\s+назад/);
  if (match && match[1]) {
    return match[1];
  }
  return relative;
});

// Скачивание файлов
const downloadFile = async (attachment) => {
  try {
    const {token} = useAuthStore()
    if (!token) {
      throw new Error('Токен авторизации не найден')
    }
    
    const baseUrl = `${useRuntimeConfig().public.apiBase}/data-grid/${props.record.data_grid_id}/records/${props.record.id}/media/${attachment.id}/download`
    const downloadUrl = `${baseUrl}?token=${encodeURIComponent(token)}`
    
    console.log(downloadUrl)
    
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