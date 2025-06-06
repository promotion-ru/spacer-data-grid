<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    
    <div
      class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
      <div v-if="!imagePreview" class="space-y-2">
        <i class="pi pi-image text-3xl text-gray-400"></i>
        <div>
          <FileUpload
            :accept="accept"
            :chooseLabel="chooseLabel"
            :disabled="disabled"
            :maxFileSize="maxFileSize"
            auto
            class="p-button-outlined"
            customUpload
            mode="basic"
            severity="secondary"
            @select="onFileSelect"
          />
        </div>
        <p class="text-xs text-gray-500">{{ hint }}</p>
      </div>
      
      <div v-else class="relative">
        <img
          :alt="altText"
          :src="imagePreview"
          :style="grayscale ? 'filter: grayscale(100%)' : ''"
          class="shadow-md rounded-xl w-full sm:w-64 max-h-32 object-cover mx-auto"
          loading="lazy"
        />
        <Button
          :disabled="disabled"
          class="p-button-rounded p-button-sm p-button-danger absolute -top-2 -right-2"
          icon="pi pi-times"
          type="button"
          @click="removeImage"
        />
      </div>
    </div>
    
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script setup>
import {computed, ref} from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: null
  },
  initialImageUrl: {
    type: String,
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

// Этот флаг будет отслеживать, удалил ли пользователь ИМЕННО начальное изображение
const initialImageRemoved = ref(false)

// `imagePreview` - это вычисляемое свойство для определения того, что нужно показать
const imagePreview = computed(() => {
  // 1. Приоритет у нового выбранного файла (`modelValue`)
  if (props.modelValue && props.modelValue.data) {
    return props.modelValue.data
  }
  
  // 2. Показываем начальное изображение, только если оно есть И его не удаляли
  if (props.initialImageUrl && !initialImageRemoved.value) {
    return props.initialImageUrl
  }
  
  // 3. Во всех остальных случаях - пусто (будет показан блок загрузки)
  return null
})

// Метод, который вызывается при выборе файла
const onFileSelect = (event) => {
  const file = event.files[0]
  if (!file) return
  
  // Валидация типа и размера файла
  if (!file.type.startsWith('image/')) {
    emit('error', 'Выберите изображение');
    return
  }
  if (file.size > props.maxFileSize) {
    const maxSizeMB = (props.maxFileSize / 1024 / 1024).toFixed(0)
    emit('error', `Размер файла не должен превышать ${maxSizeMB}MB`);
    return
  }
  
  emit('error', null)
  
  const reader = new FileReader()
  reader.onload = (e) => {
    const base64String = e.target.result
    
    const fileData = {
      data: base64String,
      name: file.name,
      type: file.type,
      size: file.size
    }
    
    // Если пользователь выбирает новый файл, мы "забываем", что он удалял начальный.
    initialImageRemoved.value = false
    
    emit('update:modelValue', fileData)
    emit('file-selected', {
      file,
      base64: base64String,
      preview: base64String
    })
  }
  reader.readAsDataURL(file)
}

// Метод, который вызывается при нажатии на кнопку удаления
const removeImage = () => {
  // Если удаляется НОВЫЙ файл (он лежит в modelValue)
  if (props.modelValue) {
    emit('update:modelValue', null)
  }
  // Если удаляется НАЧАЛЬНЫЙ файл (modelValue пуст, но есть initialImageUrl)
  else if (props.initialImageUrl) {
    // Взводим внутренний флаг, чтобы computed-свойство скрыло картинку
    initialImageRemoved.value = true
  }
  
  // В любом случае сообщаем родителю, что произошло удаление
  emit('file-removed')
}

// Метод для полного сброса состояния компонента из родителя
const reset = () => {
  emit('update:modelValue', null)
  emit('error', null)
  // При полном сбросе компонента сбрасываем и наш флаг
  initialImageRemoved.value = false
}

// Экспортируем метод `reset` для использования в родительском компоненте
defineExpose({
  reset
})
</script>