<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :closeOnEscape="true"
    :dismissableMask="true"
    :draggable="false"
    :modal="true"
    class="w-full max-w-2xl"
    header="Добавить запись"
    @hide="onDialogHide"
  >
    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Основные поля в сетке -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        
        <!-- Дата -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2" for="date">
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
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Тип операции *
        </label>
        <div class="flex space-x-6">
          <div class="flex items-center">
            <RadioButton
              id="income"
              v-model="form.operation_type_id"
              :class="{ 'p-invalid': errors.operation_type_id }"
              :value="1"
              name="operation_type"
            />
            <label class="ml-2 text-sm text-gray-700" for="income">Доход</label>
          </div>
          <div class="flex items-center">
            <RadioButton
              id="expense"
              v-model="form.operation_type_id"
              :class="{ 'p-invalid': errors.operation_type_id }"
              :value="2"
              name="operation_type"
            />
            <label class="ml-2 text-sm text-gray-700" for="expense">Расход</label>
          </div>
        </div>
        <small v-if="errors.operation_type_id" class="p-error">{{ errors.operation_type_id }}</small>
      </div>
      
      <!-- Тип записи -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Тип записи *
        </label>
        <DataGridTypeAutocomplete
          v-model="form.type_id"
          :class="{ 'p-invalid': errors.type_id }"
          :data-grid-id="gridId"
          placeholder="Выберите или создайте тип..."
          @error="onTypeError"
          @type-created="onTypeCreated"
        />
        <small v-if="errors.type_id" class="p-error">{{ errors.type_id }}</small>
      </div>
      
      <!-- Сумма -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2" for="amount">
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
        empty-text="Перетащите файлы сюда или нажмите для выбора"
        hint-text="Поддерживаемые форматы: изображения, документы, архивы (до 10MB каждый)"
        label="Вложения"
        @files-selected="onFilesSelected"
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
const {
  formatDateForBackend,
} = useDate()

const emit = defineEmits(['update:visible', 'created'])

// Реактивные данные
const loading = ref(false)
const fileUploadRef = ref(null)
const attachmentFiles = ref([]) // Массив объектов файлов

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

// Вспомогательные методы
const getImagePreview = (fileObj) => {
  if (fileObj.data && fileObj.type.startsWith('image/')) {
    return `data:${fileObj.type};base64,${fileObj.data}`
  }
  return null
}

// Обработчики событий файлового компонента
const onFilesSelected = (files) => {
  delete errors.value.new_attachments
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

// Валидация основной формы
const validateForm = () => {
  errors.value = {}
  
  if (!form.value.name.trim()) {
    errors.value.name = 'Название записи обязательно'
  }
  
  if (!form.value.date) {
    errors.value.date = 'Дата обязательна'
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
  
  return Object.keys(errors.value).length === 0
}

// Обработка отправки формы
const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }
  
  loading.value = true
  
  try {
    // Подготавливаем данные для отправки
    const jsonData = {
      name: form.value.name.trim(),
      date: formatDateForBackend(form.value.date), // Форматируем дату правильно
      operation_type_id: form.value.operation_type_id,
      type_id: form.value.type_id,
      description: form.value.description ? form.value.description.trim() : null,
      amount: form.value.amount,
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
    
    if (!response.success) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    // Сначала эмитим событие создания
    emit('created', response.data)
    
    // Затем закрываем модальное окно
    closeModal()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись создана',
      life: 3000
    })
  } catch (error) {
    let errorMessage = 'Не удалось создать запись'
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
</script>