<template>
  <Dialog
    v-model:visible="isVisible"
    header="Создать таблицу данных"
    :modal="true"
    :closable="true"
    :draggable="false"
    :dismissableMask="true"
    class="w-full max-w-md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Название таблицы -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
          Название таблицы *
        </label>
        <InputText
          id="name"
          v-model="form.name"
          placeholder="Введите название таблицы"
          class="w-full"
          :class="{ 'p-invalid': errors.name }"
        />
        <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
      </div>
      
      <!-- Описание -->
      <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
          Описание
        </label>
        <Textarea
          id="description"
          v-model="form.description"
          placeholder="Опишите назначение таблицы"
          rows="3"
          class="w-full"
          :class="{ 'p-invalid': errors.description }"
        />
        <small v-if="errors.description" class="p-error">{{ errors.description }}</small>
      </div>
      
      <ImageUploader
        ref="imageUploader"
        v-model="form.image"
        label="Изображение"
        :grayscale="true"
        :error="errors.image"
        @file-selected="onImageSelected"
        @file-removed="onImageRemoved"
        @error="onImageError"
      />
      
    </form>
    
    <template #footer>
      <div class="flex justify-end space-x-3">
        <Button
          label="Отмена"
          class="p-button-outlined"
          @click="closeModal"
          :disabled="loading"
        />
        <Button
          label="Создать"
          icon="pi pi-check"
          @click="handleSubmit"
          :loading="loading"
        />
      </div>
    </template>
    
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: Boolean
})

const emit = defineEmits(['update:visible', 'created'])

const { $api } = useNuxtApp()

// Реактивные данные
const loading = ref(false)
const imageUploader = ref(null)

const form = ref({
  name: '',
  description: '',
  image: null
})

const errors = ref({})

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

// Методы для работы с изображением
const onImageSelected = (imageData) => {
  errors.value.image = null
}

const onImageRemoved = () => {
  form.value.image = null
  errors.value.image = null
}

const onImageError = (errorMessage) => {
  errors.value.image = errorMessage
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.name.trim()) {
    errors.value.name = 'Название таблицы обязательно'
  }
  
  if (form.value.description && form.value.description.length > 1000) {
    errors.value.description = 'Описание не должно превышать 1000 символов'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    // Подготавливаем JSON данные
    const requestData = {
      name: form.value.name.trim(),
      description: form.value.description ? form.value.description.trim() : null,
      image: form.value.image // base64 строка или null
    }
    console.log('Отправляемые данные:', requestData)
    // Отправляем JSON вместо FormData
    const response = await $api('/data-grid', {
      method: 'POST',
      body: JSON.stringify(requestData),
      headers: {
        'Content-Type': 'application/json'
      }
    })
    console.log('response', response)
    const createdDataGrid = response.data.data
    emit('created', createdDataGrid)
    closeModal()
    
    // useToast().add({
    //   severity: 'success',
    //   summary: 'Успешно',
    //   detail: `Таблица "${createdDataGrid.name}" создана`,
    //   life: 3000
    // })
  } catch (error) {
    console.error('Ошибка при создании таблицы:', error)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    // useToast().add({
    //   severity: 'error',
    //   summary: 'Ошибка',
    //   detail: error.response?.data?.message || 'Не удалось создать таблицу',
    //   life: 3000
    // })
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  isVisible.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    image: null
  }
  errors.value = {}
  
  // Сбрасываем компонент загрузки изображений
  if (imageUploader.value) {
    imageUploader.value.reset()
  }
}

// Сброс формы при закрытии модального окна
watch(isVisible, (newValue) => {
  if (!newValue) {
    resetForm()
  }
})
</script>