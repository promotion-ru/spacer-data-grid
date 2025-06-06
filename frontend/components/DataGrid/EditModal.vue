<template>
  <Dialog
    v-model:visible="isVisible"
    header="Редактировать таблицу данных"
    :modal="true"
    :closable="true"
    :draggable="false"
    :dismissableMask="true"
    class="w-full max-w-md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-6">
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
        :initial-image-url="initialImageUrl"
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
          label="Сохранить"
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
  visible: Boolean,
  grid: {
    type: Object,
    default: () => null
  }
})

const emit = defineEmits(['update:visible', 'updated'])

const { $api } = useNuxtApp()

// Реактивные данные
const loading = ref(false)
const imageUploader = ref(null)
const initialImageUrl = ref(null)

const form = ref({
  name: '',
  description: '',
  image: null,
  delete_image: false // Флаг для удаления изображения на бэкенде
})

const errors = ref({})

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

watch(() => props.grid, (newGrid) => {
  if (newGrid) {
    console.log(newGrid)
    form.value.name = newGrid.name
    form.value.description = newGrid.description || ''
    form.value.image = null
    form.value.delete_image = false
    initialImageUrl.value = newGrid.image_url || null
    if (imageUploader.value) {
      imageUploader.value.reset()
    }
  }
}, { immediate: true, deep: true })


// Методы для работы с изображением
const onImageSelected = (imageData) => {
  errors.value.image = null
  form.value.delete_image = false // Если выбрано новое, то старое не нужно удалять отдельным флагом
}

const onImageRemoved = () => {
  form.value.image = null
  errors.value.image = null
  // Если пользователь удалил существующее изображение, выставляем флаг
  if (initialImageUrl.value) {
    form.value.delete_image = true
  }
}

const onImageError = (errorMessage) => {
  errors.value.image = errorMessage
}

// Валидация осталась такой же
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

// --- КЛЮЧЕВОЕ ИЗМЕНЕНИЕ: ЛОГИКА ОТПРАВКИ ---
const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    // Подготавливаем данные для PUT-запроса, как ожидает ваш контроллер
    const requestData = {
      name: form.value.name.trim(),
      description: form.value.description ? form.value.description.trim() : null
    }
    if (form.value.delete_image) {
      requestData.delete_image = true;
    }
    if (form.value.image) {
      requestData.new_image = form.value.image;
    }
    
    // Отправляем PUT-запрос с ID таблицы в URL
    const response = await $api(`/data-grid/${props.grid.id}`, {
      method: 'PATCH',
      body: JSON.stringify(requestData),
      headers: {
        'Content-Type': 'application/json'
      }
    })
    
    const updatedDataGrid = response.data
    // Отправляем событие `updated` с обновленными данными
    emit('updated', updatedDataGrid)
    closeModal()
    
  } catch (error) {
    console.error('Ошибка при обновлении таблицы:', error)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    // Здесь можно добавить ваше уведомление об ошибке (Toast)
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
    image: null,
    delete_image: false
  }
  errors.value = {}
  initialImageUrl.value = null
  
  // Сбрасываем компонент загрузки изображений
  if (imageUploader.value) {
    imageUploader.value.reset()
  }
}

// Сброс формы при закрытии модального окна
watch(isVisible, (newValue) => {
  if (!newValue) {
    // Даем анимации закрытия завершиться перед сбросом
    setTimeout(resetForm, 300)
  }
})
</script>