<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :closeOnEscape="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-4xl"
    header="Редактировать запись"
    @hide="onDialogHide"
  >
    <!-- Индикатор загрузки данных записи -->
    <div v-if="loadingRecord" class="flex items-center justify-center p-8">
      <div class="flex flex-col items-center space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl" style="color: var(--primary-color)"></i>
        <span style="color: var(--text-secondary)">Загружаем актуальные данные записи...</span>
      </div>
    </div>
    
    <!-- Основная форма -->
    <form v-else class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Основные поля в сетке -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Название записи -->
        <div>
          <label class="block text-sm font-medium mb-2" style="color: var(--text-primary)" for="name">
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
        
        <!-- Дата -->
        <div>
          <label class="block text-sm font-medium mb-2" style="color: var(--text-primary)" for="date">
            Дата *
          </label>
          <DatePicker
            id="date"
            v-model="form.date"
            :class="{ 'p-invalid': errors.date }"
            :manual-input="false"
            class="w-full"
            date-format="dd.mm.yy"
            icon-display="input"
            placeholder="Выберите дату"
            show-icon
          />
          <small v-if="errors.date" class="p-error">{{ errors.date }}</small>
        </div>
      </div>
      
      <!-- Тип операции (радиокнопки) -->
      <div>
        <label class="block text-sm font-medium mb-3" style="color: var(--text-primary)">
          Тип операции *
        </label>
        <div class="flex space-x-6">
          <div class="flex items-center">
            <RadioButton
              id="income_edit"
              v-model="form.operation_type_id"
              :class="{ 'p-invalid': errors.operation_type_id }"
              :value="1"
              name="operation_type_edit"
            />
            <label class="ml-2 text-sm" style="color: var(--text-primary)" for="income_edit">Доход</label>
          </div>
          <div class="flex items-center">
            <RadioButton
              id="expense_edit"
              v-model="form.operation_type_id"
              :class="{ 'p-invalid': errors.operation_type_id }"
              :value="2"
              name="operation_type_edit"
            />
            <label class="ml-2 text-sm" style="color: var(--text-primary)" for="expense_edit">Расход</label>
          </div>
        </div>
        <small v-if="errors.operation_type_id" class="p-error">{{ errors.operation_type_id }}</small>
      </div>
      
      <div class="grid grid-cols-1">
        <!-- Тип записи -->
        <div>
          <label class="block text-sm font-medium mb-2" style="color: var(--text-primary)">
            Тип записи *
          </label>
          <DataGridTypeAutocomplete
            v-model="form.type_id"
            :class="{ 'p-invalid': errors.type_id }"
            :data-grid-id="currentRecord?.data_grid_id"
            placeholder="Выберите или создайте тип..."
            @error="onTypeError"
            @type-created="onTypeCreated"
          />
          <small v-if="errors.type_id" class="p-error">{{ errors.type_id }}</small>
        </div>
      </div>
      
      <div class="grid grid-cols-1">
        <!-- Сумма -->
        <div>
          <label class="block text-sm font-medium mb-2" style="color: var(--text-primary)" for="amount">
            Сумма *
          </label>
          <InputNumber
            id="amount"
            v-model="form.amount"
            :class="{ 'p-invalid': errors.amount }"
            :max="999999999"
            :max-fraction-digits="0"
            :min="0"
            :min-fraction-digits="0"
            class="w-full"
            currency="RUB"
            locale="ru-RU"
            mode="currency"
            placeholder="0"
          />
          <small v-if="errors.amount" class="p-error">{{ errors.amount }}</small>
        </div>
      </div>
      
      <!-- Описание -->
      <div>
        <label class="block text-sm font-medium mb-2" style="color: var(--text-primary)" for="description">
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
      
      <!-- Статистика файлов -->
      <Card class="shadow-sm" style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--surface-0) 100%); border: 1px solid var(--primary-200)">
        <template #content>
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold flex items-center gap-2" style="color: var(--text-primary)">
              <i class="pi pi-chart-bar" style="color: var(--primary-color)"></i>
              Статистика вложений
            </h4>
            <div v-if="hasChanges" class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium" style="background-color: var(--orange-100); color: var(--orange-700)">
              <i class="pi pi-exclamation-circle"></i>
              Есть изменения
            </div>
          </div>
          
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-3 rounded-lg" style="background-color: var(--surface-card); border: 1px solid var(--border-color)">
              <div class="text-2xl font-bold mb-1" style="color: var(--text-primary)">{{ currentAttachmentsCount }}</div>
              <div class="text-xs" style="color: var(--text-secondary)">Текущих файлов</div>
            </div>
            <div class="text-center p-3 rounded-lg" style="background-color: var(--primary-50); border: 1px solid var(--primary-200)">
              <div class="text-2xl font-bold mb-1" style="color: var(--primary-color)">{{ newAttachmentFiles.length }}</div>
              <div class="text-xs" style="color: var(--primary-color)">Новых файлов</div>
            </div>
            <div class="text-center p-3 rounded-lg" style="background-color: var(--red-50); border: 1px solid var(--red-200)">
              <div class="text-2xl font-bold mb-1" style="color: var(--red-500)">{{ filesToRemove.length }}</div>
              <div class="text-xs" style="color: var(--red-500)">К удалению</div>
            </div>
            <div class="text-center p-3 rounded-lg" style="background-color: var(--green-50); border: 1px solid var(--green-200)">
              <div class="text-2xl font-bold mb-1" style="color: var(--green-600)">{{ totalFilesAfterSave }}</div>
              <div class="text-xs" style="color: var(--green-600)">Итого будет</div>
            </div>
          </div>
        </template>
      </Card>
      
      <!-- Существующие вложения -->
      <div v-if="existingAttachments.length > 0">
        <Card class="shadow-sm" style="background-color: var(--surface-card); border: 1px solid var(--border-color)">
          <template #content>
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-lg font-semibold flex items-center gap-2" style="color: var(--text-primary)">
                <i class="pi pi-folder" style="color: var(--primary-color)"></i>
                Текущие вложения
                <Tag :value="existingAttachments.length" severity="info" class="ml-2"/>
              </h4>
              <div v-if="filesToRemove.length > 0" class="text-sm" style="color: var(--red-600)">
                {{ filesToRemove.length }} помечено к удалению
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            :class="{
              'border-red-300 bg-red-50': isMarkedForRemoval(attachment.id),
            }"
            :style="!isMarkedForRemoval(attachment.id) ? {
              'background-color': 'var(--surface-0)',
              'border': '1px solid var(--surface-border)'
            } : {
              'background-color': 'var(--red-50)',
              'border': '1px solid var(--red-300)'
            }"
            class="group relative rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <!-- Превью для изображений -->
            <div v-if="attachment.mime_type && attachment.mime_type.startsWith('image/')"
                 class="mb-3 rounded-lg overflow-hidden shadow-sm" style="border: 2px solid var(--border-color)">
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
              <h4 :title="attachment.name || attachment.file_name"
                  class="text-sm font-medium truncate text-wrap"
                  style="color: var(--text-primary)">
                {{ attachment.name || attachment.file_name }}
              </h4>
              <div class="flex justify-between items-center text-xs" style="color: var(--text-secondary)">
                <span>{{ attachment.human_readable_size || formatFileSize(attachment.size) }}</span>
                <span>{{ getFileTypeLabel(attachment.mime_type) }}</span>
              </div>
            </div>
            
            <!-- Действия -->
            <div class="mt-3 flex justify-between">
              <Button
                v-tooltip.top="'Скачать'"
                icon="pi pi-download"
                outlined
                rounded
                size="small"
                type="button"
                @click="downloadFile(attachment)"
              />
              
              <Button
                v-if="!isMarkedForRemoval(attachment.id)"
                v-tooltip.top="'Удалить'"
                icon="pi pi-trash"
                outlined
                rounded
                severity="danger"
                size="small"
                type="button"
                @click="markForRemoval(attachment.id)"
              />
              
              <Button
                v-else
                v-tooltip.top="'Отменить удаление'"
                icon="pi pi-undo"
                outlined
                rounded
                severity="success"
                size="small"
                type="button"
                @click="unmarkForRemoval(attachment.id)"
              />
            </div>
            
            <!-- Индикатор удаления -->
            <div
              v-if="isMarkedForRemoval(attachment.id)"
              class="absolute inset-0 rounded-lg flex items-center justify-center"
              style="background-color: rgba(239, 68, 68, 0.2)"
            >
              <div class="px-3 py-1 rounded text-sm font-medium"
                   style="background-color: var(--red-600); color: white;">
                Будет удален
              </div>
            </div>
            </div>
          </template>
        </Card>
      </div>
      
      <!-- Добавление новых вложений -->
      <Card class="shadow-sm" style="background-color: var(--surface-card); border: 1px solid var(--border-color)">
        <template #content>
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold flex items-center gap-2" style="color: var(--text-primary)">
              <i class="pi pi-plus-circle" style="color: var(--primary-color)"></i>
              Добавить новые файлы
              <Tag v-if="newAttachmentFiles.length > 0" :value="newAttachmentFiles.length" severity="success" class="ml-2"/>
            </h4>
          </div>
          
        <MultiFileUpload
          ref="fileUploadRef"
          v-model="newAttachmentFiles"
          :auto-convert="true"
          :error-message="errors.attachments"
          :max-file-size="10485760"
          :max-files="10"
          empty-text="Перетащите файлы сюда или нажмите для выбора"
          hint-text="Поддерживаемые форматы: изображения, документы, архивы (до 10MB каждый)"
          label="Добавить новые вложения"
          selected-title="Новые файлы"
          @files-selected="onNewFilesSelected"
          @files-converted="onNewFilesConverted"
          @files-removed="onNewFilesRemoved"
          @validation-error="onValidationError"
        >
          <template #empty>
            <div class="flex flex-col items-center justify-center space-y-2 p-6">
              <i class="pi pi-cloud-upload text-4xl" style="color: var(--primary-color)"></i>
              <span class="font-medium" style="color: var(--text-secondary)">Добавьте файлы к записи</span>
              <p class="text-xs text-center" style="color: var(--text-muted)">
                Перетащите файлы сюда или нажмите для выбора<br>
                Максимум 10 файлов по 10MB каждый
              </p>
            </div>
          </template>
          <template #content="{ files, removeFile, formatFileSize, getFileIcon }">
            <div v-if="files.length > 0">
              <div class="mb-4 p-3 rounded-lg" style="background-color: var(--primary-50); border: 1px solid var(--primary-200)">
                <div class="flex items-center gap-2">
                  <i class="pi pi-info-circle" style="color: var(--primary-color)"></i>
                  <span class="text-sm font-medium" style="color: var(--primary-700)">Выбрано {{ files.length }} новых файлов</span>
                </div>
              </div>
              <div class="max-h-80 overflow-y-auto space-y-3 pr-2" style="scrollbar-width: thin">
                <div
                  v-for="(fileObj, index) of files"
                  :key="fileObj.id"
                  :style="{
                    'background-color': fileObj.data ? 'var(--green-50)' : 'var(--orange-50)',
                    'border': fileObj.data ? '1px solid var(--green-200)' : '1px solid var(--orange-200)'
                  }"
                  class="flex items-center justify-between p-3 rounded"
                >
                  <div class="flex items-center space-x-3">
                    <!-- Превью для изображений -->
                    <div v-if="fileObj.type.startsWith('image/') && fileObj.data" class="flex-shrink-0">
                      <div class="relative">
                        <img
                          :alt="fileObj.name"
                          :src="getImagePreview(fileObj)"
                          class="w-16 h-16 object-cover rounded-lg border-2 shadow-sm" 
                          style="border-color: var(--primary-200)"
                        />
                        <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center" style="background-color: var(--green-500)">
                          <i class="pi pi-check text-xs text-white"></i>
                        </div>
                      </div>
                    </div>
                    <!-- Иконка для других файлов -->
                    <div v-else class="flex-shrink-0">
                      <div class="w-16 h-16 rounded-lg flex items-center justify-center relative" style="background-color: var(--surface-100); border: 2px dashed var(--border-color)">
                        <i
                          :class="getFileIcon(fileObj.type)"
                          class="text-3xl"
                          style="color: var(--primary-color)"
                        ></i>
                        <div v-if="fileObj.data" class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center" style="background-color: var(--green-500)">
                          <i class="pi pi-check text-xs text-white"></i>
                        </div>
                        <div v-else class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center" style="background-color: var(--orange-500)">
                          <i class="pi pi-clock text-xs text-white"></i>
                        </div>
                      </div>
                    </div>
                    
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-medium truncate text-wrap" style="color: var(--text-primary)">{{ fileObj.name }}</p>
                      <p class="text-xs" style="color: var(--text-secondary)">{{ formatFileSize(fileObj.size) }}</p>
                      
                      <!-- Статус файла -->
                      <div class="flex items-center mt-1">
                        <i
                          :class="{
                          'pi pi-check': fileObj.data,
                          'pi pi-clock': !fileObj.data
                        }"
                          :style="{
                            color: fileObj.data ? 'var(--green-500)' : 'var(--orange-500)'
                          }"
                          class="mr-1 text-xs"
                        ></i>
                        <span
                          :style="{
                            color: fileObj.data ? 'var(--green-600)' : 'var(--orange-600)'
                          }"
                          class="text-xs"
                        >
                        {{ fileObj.data ? 'Готов к отправке' : 'Обрабатывается...' }}
                      </span>
                      </div>
                    </div>
                  </div>
                  
                  <Button
                    icon="pi pi-times"
                    rounded
                    severity="danger"
                    size="small"
                    text
                    type="button"
                    @click="removeFile(index)"
                  />
                </div>
              </div>
            </div>
          </template>
        </MultiFileUpload>
        </template>
      </Card>
    </form>
    
    <template #footer>
      <div v-if="!loadingRecord" class="flex justify-between items-center">
        <!-- Информация об изменениях -->
        <div v-if="hasChanges" class="text-sm mr-2" style="color: var(--text-secondary)">
          <span v-if="newAttachmentFiles.length > 0" style="color: var(--primary-color)">
            +{{ newAttachmentFiles.length }} новых
          </span>
          <span v-if="newAttachmentFiles.length > 0 && filesToRemove.length > 0"> | </span>
          <span v-if="filesToRemove.length > 0" style="color: var(--red-600)">
            -{{ filesToRemove.length }} удалено
          </span>
        </div>
        
        <!-- Кнопки -->
        <div class="flex space-x-3">
          <Button
            :disabled="loading"
            label="Отмена"
            outlined
            @click="closeModal"
          />
          <Button
            :disabled="!hasChanges || loading"
            :loading="loading"
            icon="pi pi-check"
            label="Сохранить изменения"
            @click="handleSubmit"
          />
        </div>
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

const {$api} = useNuxtApp()
const {
  getFileIcon,
  getFileTypeLabel,
  formatFileSize,
} = useFileUtils()
const {
  formatDateForBackend,
  parseDateFromBackend,
  isValidDate,
} = useDate()
const toast = useToast()

// Реактивные данные
const loading = ref(false)
const loadingRecord = ref(false) // Загрузка данных записи
const fileUploadRef = ref(null)
const newAttachmentFiles = ref([]) // Новые файлы через MultiFileUpload
const filesToRemove = ref([]) // ID файлов для удаления
const existingAttachments = ref([]) // Существующие вложения
const currentRecord = ref(null) // Текущие актуальные данные записи

const form = ref({
  name: '',
  date: null,
  operation_type_id: null,
  type_id: null,
  description: '',
  amount: null
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

// Улучшенная логика определения изменений
const hasChanges = computed(() => {
  if (!currentRecord.value) return false
  
  // Нормализуем значения для корректного сравнения
  const normalizeString = (value) => (value || '').toString().trim()
  const normalizeNumber = (value) => value || null
  
  const formChanged =
    normalizeString(form.value.name) !== normalizeString(currentRecord.value.name) ||
    normalizeString(form.value.description) !== normalizeString(currentRecord.value.description) ||
    form.value.operation_type_id !== currentRecord.value.operation_type_id ||
    form.value.type_id !== currentRecord.value.type_id ||
    normalizeNumber(form.value.amount) !== normalizeNumber(currentRecord.value.amount) ||
    formatDateForBackend(form.value.date) !== currentRecord.value.date // Сравниваем в том же формате
  
  const hasFileChanges = newAttachmentFiles.value.length > 0 || filesToRemove.value.length > 0
  
  console.log('hasChanges debug:', {
    formChanged,
    hasFileChanges,
    originalValues: {
      name: normalizeString(currentRecord.value.name),
      description: normalizeString(currentRecord.value.description),
      operation_type_id: currentRecord.value.operation_type_id,
      type_id: currentRecord.value.type_id,
      amount: normalizeNumber(currentRecord.value.amount),
      date: currentRecord.value.date // Строка из бэкенда
    },
    currentValues: {
      name: normalizeString(form.value.name),
      description: normalizeString(form.value.description),
      operation_type_id: form.value.operation_type_id,
      type_id: form.value.type_id,
      amount: normalizeNumber(form.value.amount),
      date: formatDateForBackend(form.value.date) // Отформатированная строка
    },
    newFiles: newAttachmentFiles.value.length,
    filesToRemove: filesToRemove.value.length
  })
  
  return formChanged || hasFileChanges
})

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

const downloadFile = async (attachment) => {
  try {
    const {token} = useAuthStore()
    if (!token) {
      throw new Error('Токен авторизации не найден')
    }
    
    const baseUrl = `${useRuntimeConfig().public.apiBase}/data-grid/${props.record.data_grid_id}/records/${props.record.id}/media/${attachment.id}/download`
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

// Функция для загрузки актуальных данных записи с сервера
const fetchRecordData = async () => {
  if (!props.record?.id || !props.record?.data_grid_id) {
    console.error('Недостаточно данных для загрузки записи')
    return
  }
  
  loadingRecord.value = true
  
  try {
    console.log('Загружаем актуальные данные записи:', props.record.id)
    
    const response = await $api(
      `/data-grid/${props.record.data_grid_id}/records/${props.record.id}`,
      {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      }
    )
    
    if (!response.success) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    currentRecord.value = response.data
    console.log('Актуальные данные записи загружены:', currentRecord.value)
    
    // Загружаем данные в форму
    loadRecordDataToForm()
    
  } catch (error) {
    console.error('Ошибка при загрузке данных записи:', error)
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка загрузки',
      detail: error.message || 'Не удалось загрузить данные записи',
      life: 5000
    })
    
    // В случае ошибки используем данные из props как fallback
    currentRecord.value = props.record
    loadRecordDataToForm()
    
  } finally {
    loadingRecord.value = false
  }
}

// Обработчики событий автокомплита типов
const onTypeError = (errorMessage) => {
  toast.add({
    severity: 'error',
    summary: 'Ошибка',
    detail: errorMessage,
    life: 3000
  })
}

const onTypeCreated = (newType) => {
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Тип "${newType.name}" создан`,
    life: 3000
  })
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

// Улучшенная валидация с отладкой
const validateForm = () => {
  errors.value = {}
  
  if (!form.value.name?.trim()) {
    errors.value.name = 'Название записи обязательно'
  }
  
  if (!form.value.date) {
    errors.value.date = 'Дата обязательна'
  } else if (!isValidDate(form.value.date)) {
    errors.value.date = 'Некорректная дата'
  }
  
  if (!form.value.operation_type_id) {
    errors.value.operation_type_id = 'Выберите тип операции'
  }
  
  if (!form.value.type_id) {
    errors.value.type_id = 'Выберите тип записи'
  }
  
  if (!form.value.amount || form.value.amount <= 0) {
    errors.value.amount = 'Укажите сумму больше нуля'
  }
  
  if (form.value.description && form.value.description.length > 2000) {
    errors.value.description = 'Описание не должно превышать 2000 символов'
  }
  
  const isValid = Object.keys(errors.value).length === 0
  
  console.log('Validation result:', {
    isValid,
    errors: errors.value,
    formData: {
      name: form.value.name,
      nameLength: form.value.name?.length,
      date: form.value.date,
      dateFormatted: formatDateForBackend(form.value.date),
      operation_type_id: form.value.operation_type_id,
      type_id: form.value.type_id,
      amount: form.value.amount,
      description: form.value.description
    }
  })
  
  return isValid
}

// Улучшенная функция отправки с защитой от двойного клика
const handleSubmit = async () => {
  console.log('handleSubmit called')
  
  // Проверяем, не идет ли уже отправка
  if (loading.value) {
    console.log('Already loading, preventing double submit')
    return
  }
  
  console.log('hasChanges:', hasChanges.value)
  console.log('validateForm result:', validateForm())
  
  if (!validateForm()) {
    console.log('Validation failed, stopping submit')
    return
  }
  
  loading.value = true
  
  try {
    // Подготавливаем JSON данные
    const jsonData = {
      name: form.value.name.trim(),
      date: formatDateForBackend(form.value.date), // Форматируем дату правильно
      operation_type_id: form.value.operation_type_id,
      type_id: form.value.type_id,
      description: form.value.description ? form.value.description.trim() : null,
      amount: form.value.amount,
      new_attachments: newAttachmentFiles.value, // Новые файлы с base64
      remove_attachments: filesToRemove.value // ID файлов для удаления
    }
    
    console.log('Отправляем обновления:', {
      name: jsonData.name,
      date: jsonData.date, // Теперь это строка в формате dd.mm.yyyy
      operation_type_id: jsonData.operation_type_id,
      type_id: jsonData.type_id,
      amount: jsonData.amount,
      description: jsonData.description,
      newAttachments: jsonData.new_attachments.length,
      removeAttachments: jsonData.remove_attachments.length
    })
    
    // Отправка JSON данных
    const response = await $api(
      `/data-grid/${currentRecord.value.data_grid_id}/records/${currentRecord.value.id}`,
      {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
      }
    )
    
    if (!response.success) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    emit('updated', response.data)
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись обновлена',
      life: 3000
    })
    
    // Закрываем модальное окно только после успешного сохранения
    closeModal()
    
  } catch (error) {
    let errorMessage = 'Ошибка при обновлении записи'
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      errorMessage = error.response?._data?.message || ''
    }
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: errorMessage,
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
    date: null,
    operation_type_id: null,
    type_id: null,
    description: '',
    amount: null
  }
  errors.value = {}
  newAttachmentFiles.value = []
  filesToRemove.value = []
  existingAttachments.value = []
  currentRecord.value = null
  
  // Очищаем компонент загрузки
  if (fileUploadRef.value) {
    fileUploadRef.value.clear()
  }
}

// Улучшенная загрузка данных записи в форму с отладкой
const loadRecordDataToForm = () => {
  if (!currentRecord.value) return
  
  console.log('Loading record data to form:', currentRecord.value)
  
  form.value = {
    name: currentRecord.value.name || '',
    date: parseDateFromBackend(currentRecord.value.date),
    operation_type_id: currentRecord.value.operation_type_id || null,
    type_id: currentRecord.value.type_id || null,
    description: currentRecord.value.description || '',
    amount: currentRecord.value.amount || null
  }
  
  existingAttachments.value = [...(currentRecord.value.attachments || [])]
  filesToRemove.value = []
  newAttachmentFiles.value = []
  
  console.log('Form loaded with data:', {
    form: form.value,
    existingAttachments: existingAttachments.value.length,
    hasChanges: hasChanges.value
  })
}

// Загрузка данных записи при открытии модального окна
watch([isVisible, () => props.record], ([visible, record]) => {
  if (visible && record) {
    fetchRecordData()
  } else if (!visible) {
    nextTick(() => {
      resetForm()
    })
  }
}, {immediate: true})
</script>