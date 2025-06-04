<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
      <div v-if="!imagePreview" class="space-y-2">
        <i class="pi pi-image text-3xl text-gray-400"></i>
        <div>
          <FileUpload
            mode="basic"
            @select="onFileSelect"
            customUpload
            auto
            severity="secondary"
            class="p-button-outlined"
            :accept="accept"
            :maxFileSize="maxFileSize"
            :chooseLabel="chooseLabel"
            :disabled="disabled"
          />
        </div>
        <p class="text-xs text-gray-500">{{ hint }}</p>
      </div>
      
      <div v-else class="relative">
        <img
          :src="imagePreview"
          :alt="altText"
          class="shadow-md rounded-xl w-full sm:w-64 max-h-32 object-cover mx-auto"
          :style="grayscale ? 'filter: grayscale(100%)' : ''"
          loading="lazy"
        />
        <Button
          icon="pi pi-times"
          class="p-button-rounded p-button-sm p-button-danger absolute -top-2 -right-2"
          @click="removeImage"
          type="button"
          :disabled="disabled"
        />
      </div>
    </div>
    
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: [Object, String],
    default: null
  },
  label: {
    type: String,
    default: null
  },
  accept: {
    type: String,
    default: 'image/*'
  },
  maxFileSize: {
    type: Number,
    default: 5242880 // 5MB
  },
  hint: {
    type: String,
    default: 'PNG, JPG, WebP до 5MB'
  },
  chooseLabel: {
    type: String,
    default: 'Выбрать изображение'
  },
  altText: {
    type: String,
    default: 'Превью изображения'
  },
  grayscale: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'file-selected', 'file-removed', 'error'])

// Функция для извлечения превью из modelValue
const getPreviewFromModelValue = (value) => {
  if (!value) return null
  
  // Если это строка (base64 или URL)
  if (typeof value === 'string') {
    return value
  }
  
  // Если это объект с data
  if (typeof value === 'object' && value.data) {
    return value.data
  }
  
  return null
}

const imagePreview = ref(getPreviewFromModelValue(props.modelValue))

// Обновляем превью при изменении modelValue
watch(() => props.modelValue, (newValue) => {
  imagePreview.value = getPreviewFromModelValue(newValue)
}, { immediate: true })

const onFileSelect = (event) => {
  const file = event.files[0]
  if (!file) return
  
  // Валидация типа файла
  if (!file.type.startsWith('image/')) {
    emit('error', 'Выберите изображение')
    return
  }
  
  // Валидация размера файла
  if (file.size > props.maxFileSize) {
    const maxSizeMB = (props.maxFileSize / 1024 / 1024).toFixed(0)
    emit('error', `Размер файла не должен превышать ${maxSizeMB}MB`)
    return
  }
  
  // Очищаем ошибку
  emit('error', null)
  
  // Создание превью и конвертация в base64
  const reader = new FileReader()
  reader.onload = (e) => {
    const base64String = e.target.result
    imagePreview.value = base64String
    
    const fileData = {
      data: base64String,
      name: file.name,
      type: file.type,
      size: file.size
    }
    
    emit('update:modelValue', fileData)
    emit('file-selected', {
      file,
      base64: base64String,
      preview: base64String
    })
  }
  reader.readAsDataURL(file)
}

const removeImage = () => {
  imagePreview.value = null
  emit('update:modelValue', null)
  emit('file-removed')
}

// Метод для сброса компонента
const reset = () => {
  removeImage()
}

// Экспортируем методы для родительского компонента
defineExpose({
  reset,
  removeImage
})
</script>