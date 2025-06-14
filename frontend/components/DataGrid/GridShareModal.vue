<!-- components/GridShareModal.vue -->
<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :draggable="false"
    :modal="true"
    :closeOnEscape="true"
    class="w-full max-w-md"
    header="Поделиться таблицей"
  >
    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Email пользователя -->
      <div>
        <label class="block text-sm font-medium mb-2" for="email" >
          Email пользователя *
        </label>
        <InputText
          id="email"
          v-model="form.email"
          :class="{ 'p-invalid': errors.email }"
          class="w-full"
          placeholder="example@email.com"
        />
        <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
      </div>
      
      <!-- Права доступа -->
      <div>
        <label class="block text-sm font-medium mb-3" >
          Права доступа *
        </label>
        <div class="space-y-3">
          <div class="flex items-center">
            <Checkbox
              id="view"
              v-model="form.permissions"
              :disabled="true"
              class="mr-2"
              value="view"
            />
            <label class="text-sm" for="view" >
              <span class="font-medium">Просмотр</span>
              <span class="block text-xs text-secondary" >Просмотр таблицы и записей</span>
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="create"
              v-model="form.permissions"
              class="mr-2"
              value="create"
            />
            <label class="text-sm" for="create" >
              <span class="font-medium">Создание записей</span>
              <span class="block text-xs text-secondary" >Добавление новых записей</span>
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="update"
              v-model="form.permissions"
              class="mr-2"
              value="update"
            />
            <label class="text-sm" for="update" >
              <span class="font-medium">Редактирование</span>
              <span class="block text-xs text-secondary" >Изменение существующих записей</span>
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="delete"
              v-model="form.permissions"
              class="mr-2"
              value="delete"
            />
            <label class="text-sm" for="delete" >
              <span class="font-medium">Удаление</span>
              <span class="block text-xs text-secondary" >Удаление записей</span>
            </label>
          </div>
        </div>
        <small v-if="errors.permissions" class="p-error">{{ errors.permissions }}</small>
      </div>
    </form>
    
    <template #footer>
      <div class="flex justify-end space-x-3">
        <Button
          :disabled="loading"
          outlined
          label="Отмена"
          @click="closeModal"
        />
        <Button
          :loading="loading"
          icon="pi pi-send"
          label="Пригласить"
          @click="handleSubmit"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: Boolean,
  grid: Object
})

const emit = defineEmits(['update:visible', 'invited'])

const {$api} = useNuxtApp()
const toast = useToast()

// Реактивные данные
const loading = ref(false)

const form = ref({
  email: '',
  permissions: ['view']
})

const errors = ref({})

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

// Методы
const validateForm = () => {
  errors.value = {}
  
  if (!form.value.email.trim()) {
    errors.value.email = 'Email обязателен для заполнения'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Введите корректный email'
  }
  
  if (!form.value.permissions.length) {
    errors.value.permissions = 'Выберите хотя бы одно разрешение'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    const requestData = {
      email: form.value.email.trim(),
      permissions: form.value.permissions
    }
    const response = await $api(`/data-grid/${props.grid.id}/invite`, {
      method: 'POST',
      body: JSON.stringify(requestData),
      headers: {
        'Content-Type': 'application/json'
      }
    })
    
    emit('invited', response.data)
    closeModal()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Приглашение отправлено',
      life: 3000
    })
  } catch (error) {
    let errorMessage = 'Не удалось отправить приглашение'
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
  isVisible.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    email: '',
    permissions: ['view']
  }
  errors.value = {}
}

// Сброс формы при закрытии модального окна
watch(isVisible, (newValue) => {
  if (!newValue) {
    resetForm()
  }
})
</script>