<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :closeOnEscape="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-6xl"
    header="Просмотр записи"
    @hide="onDialogHide"
  >
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <!-- Основная информация -->
      <div class="xl:col-span-2 space-y-6">
        <!-- Заголовок и основная инфо -->
        <Card class="shadow-lg" style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--surface-0) 100%); border: 1px solid var(--primary-200)">
          <template #content>
            <div class="space-y-4">
              <!-- Заголовок -->
              <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                <div class="flex-1">
                  <h2 class="text-2xl lg:text-3xl font-bold mb-3" style="color: var(--text-primary)">{{ record?.name }}</h2>
                  
                  <!-- Метаданные -->
                  <div class="flex flex-wrap items-center gap-4">
                    <!-- Тип операции -->
                    <div class="flex items-center gap-2">
                      <i
                        :class="{
                          'pi pi-arrow-up': record?.operation_type_id === 1,
                          'pi pi-arrow-down': record?.operation_type_id === 2
                        }"
                        class="text-xl"
                        :style="{
                          color: record?.operation_type_id === 1 ? 'var(--green-600)' : record?.operation_type_id === 2 ? 'var(--red-600)' : 'var(--text-secondary)'
                        }"
                      ></i>
                      <Tag
                        :value="getOperationTypeName(record?.operation_type_id)"
                        :severity="record?.operation_type_id === 1 ? 'success' : record?.operation_type_id === 2 ? 'danger' : 'secondary'"
                        class="font-medium"
                      />
                    </div>
                    
                    <!-- Дата -->
                    <div class="flex items-center gap-2" style="color: var(--text-secondary)">
                      <i class="pi pi-calendar"></i>
                      <span class="font-medium">{{ record?.date }}</span>
                    </div>
                  </div>
                </div>
                
                <!-- Сумма -->
                <div class="text-center lg:text-right p-4 rounded-lg" style="background-color: var(--surface-card); border: 1px solid var(--border-color)">
                  <div class="text-xs uppercase tracking-wide mb-1" style="color: var(--text-secondary)">Сумма</div>
                  <div
                    class="text-3xl lg:text-4xl font-bold"
                    :style="{
                      color: record?.operation_type_id === 1 ? 'var(--green-600)' : record?.operation_type_id === 2 ? 'var(--red-600)' : 'var(--text-primary)'
                    }"
                  >
                    {{ formatAmount(record?.amount, record?.operation_type_id) }}
                  </div>
                </div>
              </div>
              
              <!-- Описание -->
              <div v-if="record?.description" class="p-4 rounded-lg" style="background-color: var(--surface-100); border-left: 4px solid var(--primary-color)">
                <div class="text-xs uppercase tracking-wide mb-2" style="color: var(--text-secondary)">Описание</div>
                <p class="text-base leading-relaxed" style="color: var(--text-primary)">{{ record.description }}</p>
              </div>
            </div>
          </template>
        </Card>
        
        <!-- Вложения -->
        <Card v-if="existingAttachments.length > 0" class="shadow-lg">
          <template #content>
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary)">
                <i class="pi pi-paperclip" style="color: var(--primary-color)"></i>
                Вложения
                <Tag :value="existingAttachments.length" severity="info"/>
              </h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="attachment in existingAttachments"
                :key="attachment.id"
                class="group border rounded-lg p-4 hover:shadow-lg transition-all duration-200"
                style="background-color: var(--surface-card); border-color: var(--border-color)"
                @mouseenter="$event.currentTarget.style.borderColor = 'var(--primary-300)'"
                @mouseleave="$event.currentTarget.style.borderColor = 'var(--border-color)'"
              >
                <!-- Превью для изображений -->
                <div v-if="attachment.mime_type && attachment.mime_type.startsWith('image/')" class="mb-3 rounded-lg overflow-hidden shadow-sm" style="border: 2px solid var(--border-color)">
                  <div class="relative group">
                    <Image
                      :alt="attachment.name || attachment.file_name"
                      :src="attachment.url || attachment.original_url"
                      preview
                      width="250"
                      class="w-full h-32 object-cover transition-transform group-hover:scale-105"
                    />
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <div class="px-2 py-1 rounded text-xs font-medium" style="background-color: rgba(0,0,0,0.7); color: white">
                        {{ getFileTypeLabel(attachment.mime_type) }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Иконка для других файлов -->
                <div v-else class="mb-3 flex justify-center items-center h-32 rounded-lg" style="background-color: var(--surface-100); border: 2px dashed var(--border-color)">
                  <div class="text-center">
                    <i
                      :class="getFileIcon(attachment.mime_type)"
                      class="text-5xl mb-2"
                      style="color: var(--primary-color)"
                    ></i>
                    <div class="text-xs font-medium px-2 py-1 rounded" style="background-color: var(--primary-100); color: var(--primary-700)">
                      {{ getFileTypeLabel(attachment.mime_type) }}
                    </div>
                  </div>
                </div>
                
                <!-- Информация о файле -->
                <div class="space-y-2">
                  <h5
                    :title="attachment.name || attachment.file_name"
                    class="text-sm font-medium truncate leading-tight"
                    style="color: var(--text-primary)"
                  >
                    {{ attachment.name || attachment.file_name }}
                  </h5>
                  
                  <div class="flex justify-between items-center text-xs" style="color: var(--text-secondary)">
                    <span class="font-medium">{{ attachment.human_readable_size || formatFileSize(attachment.size) }}</span>
                    <span class="px-2 py-1 rounded" style="background-color: var(--surface-100)">{{ getShortFileType(attachment.mime_type) }}</span>
                  </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="mt-3 flex space-x-2">
                  <Button
                    size="small"
                    icon="pi pi-download"
                    label="Скачать"
                    class="flex-1"
                    @click="downloadFile(attachment)"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>
        
        <!-- Пустое состояние вложений -->
        <Card v-else class="shadow-lg">
          <template #content>
            <div class="text-center py-12" style="background-color: var(--surface-50); border: 2px dashed var(--border-color); border-radius: 8px">
              <i class="pi pi-file text-6xl mb-4" style="color: var(--text-secondary)"></i>
              <h3 class="text-lg font-medium mb-2" style="color: var(--text-secondary)">Нет вложений</h3>
              <p class="text-sm" style="color: var(--text-secondary)">К этой записи не прикреплено файлов</p>
            </div>
          </template>
        </Card>
      </div>
      
      <!-- Боковая панель со статистикой -->
      <div class="space-y-6">
        <!-- Метаданные записи -->
        <Card class="shadow-lg">
          <template #content>
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary)">
              <i class="pi pi-info-circle" style="color: var(--primary-color)"></i>
              Информация
            </h3>
            
            <div class="space-y-4">
              <!-- Тип записи -->
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <i class="pi pi-tag" style="color: var(--text-secondary)"></i>
                  <span class="text-xs uppercase tracking-wide font-medium" style="color: var(--text-secondary)">Тип записи</span>
                </div>
                <p class="text-sm font-medium pl-6" style="color: var(--text-primary)">
                  {{ record?.type?.name || 'Не указан' }}
                  <Tag v-if="record?.type?.is_global" value="Глобальный" severity="info" size="small" class="ml-2"/>
                </p>
              </div>
              
              <!-- Автор -->
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <i class="pi pi-user" style="color: var(--text-secondary)"></i>
                  <span class="text-xs uppercase tracking-wide font-medium" style="color: var(--text-secondary)">Автор</span>
                </div>
                <p class="text-sm font-medium pl-6" style="color: var(--text-primary)">
                  {{ record?.creator?.name || 'Неизвестно' }}
                </p>
              </div>
              
              <!-- Дата создания -->
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <i class="pi pi-clock" style="color: var(--text-secondary)"></i>
                  <span class="text-xs uppercase tracking-wide font-medium" style="color: var(--text-secondary)">Создано</span>
                </div>
                <p class="text-sm font-medium pl-6" style="color: var(--text-primary)">{{ record?.created_at }}</p>
              </div>
            </div>
          </template>
        </Card>
        
        <!-- Статистика -->
        <Card v-if="record" class="shadow-lg">
          <template #content>
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary)">
              <i class="pi pi-chart-line" style="color: var(--primary-color)"></i>
              Статистика
            </h3>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 rounded-lg" style="background-color: var(--primary-50); border: 1px solid var(--primary-200)">
                <div class="flex items-center gap-3">
                  <i class="pi pi-paperclip text-xl" style="color: var(--primary-color)"></i>
                  <div>
                    <div class="text-xs" style="color: var(--primary-600)">Вложений</div>
                    <div class="text-lg font-bold" style="color: var(--primary-color)">{{ existingAttachments.length }}</div>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between p-3 rounded-lg" style="background-color: var(--surface-100); border: 1px solid var(--border-color)">
                <div class="flex items-center gap-3">
                  <i class="pi pi-calendar text-xl" style="color: var(--text-secondary)"></i>
                  <div>
                    <div class="text-xs" style="color: var(--text-secondary)">Дней назад</div>
                    <div class="text-lg font-bold" style="color: var(--text-primary)">{{ formattedDate }}</div>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between p-3 rounded-lg" :style="{
                backgroundColor: record?.updated_at !== record?.created_at ? 'var(--green-50)' : 'var(--surface-100)',
                border: record?.updated_at !== record?.created_at ? '1px solid var(--green-200)' : '1px solid var(--border-color)'
              }">
                <div class="flex items-center gap-3">
                  <i class="pi pi-pencil text-xl" :style="{
                    color: record?.updated_at !== record?.created_at ? 'var(--green-600)' : 'var(--text-secondary)'
                  }"></i>
                  <div>
                    <div class="text-xs" :style="{
                      color: record?.updated_at !== record?.created_at ? 'var(--green-600)' : 'var(--text-secondary)'
                    }">Изменялась</div>
                    <div class="text-lg font-bold" :style="{
                      color: record?.updated_at !== record?.created_at ? 'var(--green-600)' : 'var(--text-secondary)'
                    }">{{ record?.updated_at !== record?.created_at ? 'Да' : 'Нет' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-between items-center">
        <Button
          outlined
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