<!-- pages/profile.vue -->

<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    
    <!-- Основной контент -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Общая ошибка -->
      <Message v-if="error && !loading" severity="error" :closable="true" @close="clearError" class="mb-6">
        {{ error }}
      </Message>
      
      <!-- Загрузка -->
      <div v-if="loading && !profile" class="flex justify-center py-16">
        <ProgressSpinner />
      </div>
      
      <!-- Форма профиля -->
      <div v-else-if="profile" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Левая колонка - Аватар и основная информация -->
        <div class="lg:col-span-1">
          <div class="rounded-lg shadow p-6 sticky top-24" style="background-color: var(--secondary-bg)">
            <h3 class="text-lg font-medium mb-6">Фотография профиля</h3>
            
            <!-- Аватар -->
            <div class="flex flex-col items-center space-y-6">
              <div class="relative group">
                <Avatar
                  :image="previewImage || profile.avatar_url"
                  :label="!profile.avatar_url && !previewImage ? getInitials(profile.name) : undefined"
                  size="xlarge"
                  shape="circle"
                  class="w-40 h-40 text-3xl transition-all duration-200"
                />
                
                <!-- Индикатор изменения -->
                <div v-if="selectedFile" class="absolute -top-2 -right-2 bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                  <i class="pi pi-check text-sm"></i>
                </div>
              </div>
              
              <!-- FileUpload компонент -->
              <div class="w-full space-y-3">
                <FileUpload
                  mode="basic"
                  @select="onFileSelect"
                  customUpload
                  auto
                  severity="secondary"
                  class="w-full"
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  :maxFileSize="2000000"
                  :showUploadButton="false"
                  :showCancelButton="false"
                  chooseLabel="Выбрать фото"
                  chooseIcon="pi pi-upload"
                />
                
                <Button
                  v-if="selectedFile || profile.avatar_url"
                  label="Удалить фото"
                  icon="pi pi-trash"
                  outlined
                  severity="danger"
                  @click="removeAvatar"
                  class="w-full"
                />
              </div>
              
              <!-- Информация о файле -->
              <div class="text-center w-full">
                <div v-if="selectedFile" class="border rounded-lg p-3 mb-3" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3)">
                  <p class="text-sm font-medium" style="color: #1e40af">Выбранный файл:</p>
                  <p class="text-sm" style="color: #2563eb">{{ selectedFile.name }}</p>
                  <p class="text-xs" style="color: #3b82f6">{{ formatFileSize(selectedFile.size) }}</p>
                </div>
                
                <small class="block text-secondary" >
                  Поддерживаемые форматы: JPEG, PNG, GIF, WebP<br>
                  Максимальный размер: 2MB
                </small>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Правая колонка - Форма данных -->
        <div class="lg:col-span-2">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Основная информация -->
            <div class="rounded-lg shadow p-6" style="background-color: var(--secondary-bg)">
              <h3 class="text-lg font-medium mb-6" >Основная информация</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Имя -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium" >
                    Имя <span style="color: #ef4444">*</span>
                  </label>
                  <InputText
                    v-model="form.name"
                    placeholder="Введите имя"
                    :class="{ 'p-invalid': formErrors.name }"
                    class="w-full"
                    :disabled="formLoading"
                  />
                  <small v-if="formErrors.name" class="p-error">
                    {{ formErrors.name[0] }}
                  </small>
                </div>
                
                <!-- ФИО -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium" >
                    Фамилия
                  </label>
                  <InputText
                    v-model="form.surname"
                    placeholder="Введите фамилию"
                    :class="{ 'p-invalid': formErrors.surname }"
                    class="w-full"
                    :disabled="formLoading"
                  />
                  <small v-if="formErrors.surname" class="p-error">
                    {{ formErrors.surname[0] }}
                  </small>
                </div>
              </div>
              
              <!-- Email -->
              <div class="space-y-2 mt-6">
                <label class="block text-sm font-medium" >
                  Email <span style="color: #ef4444">*</span>
                </label>
                <InputText
                  v-model="form.email"
                  type="email"
                  placeholder="Введите email"
                  :class="{ 'p-invalid': formErrors.email }"
                  class="w-full"
                  :disabled="formLoading"
                />
                <small v-if="formErrors.email" class="p-error">
                  {{ formErrors.email[0] }}
                </small>
              </div>
            </div>
            
            <!-- Смена пароля -->
            <div class="rounded-lg shadow p-6" style="background-color: var(--secondary-bg)">
              <h3 class="text-lg font-medium mb-6" >Смена пароля</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="block text-sm font-medium" >
                    Новый пароль
                  </label>
                  <Password
                    v-model="form.password"
                    placeholder="Введите новый пароль"
                    :class="{ 'p-invalid': formErrors.password }"
                    class="w-full password-input"
                    toggleMask
                    :feedback="false"
                    :disabled="formLoading"
                  />
                  <small v-if="formErrors.password" class="p-error">
                    {{ formErrors.password[0] }}
                  </small>
                  <small class="text-secondary" v-else >
                    Оставьте пустым, если не хотите менять пароль
                  </small>
                </div>
                
                <div class="space-y-2">
                  <label class="block text-sm font-medium" >
                    Подтверждение пароля
                  </label>
                  <Password
                    v-model="form.password_confirmation"
                    placeholder="Подтвердите пароль"
                    :class="{ 'p-invalid': formErrors.password_confirmation }"
                    class="w-full password-input"
                    :feedback="false"
                    :disabled="formLoading"
                  />
                  <small v-if="formErrors.password_confirmation" class="p-error">
                    {{ formErrors.password_confirmation[0] }}
                  </small>
                </div>
              </div>
            </div>

            <!-- Кнопки управления -->
            <div class="flex justify-end space-x-3">
              <Button
                label="Отмена"
                outlined
                @click="resetForm"
                :disabled="formLoading"
                icon="pi pi-times"
              />
              <Button
                label="Сохранить изменения"
                @click="handleSubmit"
                :loading="formLoading"
                icon="pi pi-save"
              />
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Toast для уведомлений -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
  title: 'Мой профиль',
})

useSeoMeta({
  title: 'Мой профиль',
  description: 'Управление личным профилем пользователя'
})

const { fetchUser } = useAuth()
const toast = useToast()
const {
  profile,
  loading,
  error,
  fetchProfile,
  updateProfile,
  clearError
} = useProfile()
const {
  formatFileSize,
} = useFileUtils()

// Реактивные данные формы
const form = reactive({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const formErrors = ref<Record<string, string[]>>({})
const formLoading = ref(false)
const selectedFile = ref<File | null>(null)
const previewImage = ref<string | null>(null)
const avatarToDelete = ref(false)

// Инициализация формы из профиля
const initializeForm = (): void => {
  if (profile.value) {
    form.name = profile.value.name
    form.surname = profile.value.surname || ''
    form.email = profile.value.email
    form.password = ''
    form.password_confirmation = ''
  }
}

// Сброс формы
const resetForm = (): void => {
  initializeForm()
  formErrors.value = {}
  selectedFile.value = null
  previewImage.value = null
  avatarToDelete.value = false
}

// Получение инициалов для аватара
const getInitials = (name: string): string => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Конвертация файла в base64
const convertFileToBase64 = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => {
      const result = reader.result as string
      // Убираем префикс data:image/jpeg;base64, оставляем только base64
      const base64 = result.split(',')[1]
      resolve(base64)
    }
    reader.onerror = error => reject(error)
  })
}

// Обработка выбора файла через FileUpload
const onFileSelect = (event: any): void => {
  const file = event.files[0]
  
  if (!file) {
    return
  }
  
  // Проверка типа файла
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    toast.add({
      severity: 'warn',
      summary: 'Неверный формат',
      detail: 'Поддерживаются только изображения: JPEG, PNG, GIF, WebP',
      life: 4000
    })
    return
  }
  
  // Проверка размера файла (2MB)
  if (file.size > 2 * 1024 * 1024) {
    toast.add({
      severity: 'warn',
      summary: 'Слишком большой файл',
      detail: 'Размер файла не должен превышать 2MB',
      life: 4000
    })
    return
  }
  
  selectedFile.value = file
  avatarToDelete.value = false
  
  // Создаем превью
  const reader = new FileReader()
  reader.onload = (e) => {
    previewImage.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

// Удаление аватара
const removeAvatar = (): void => {
  if (selectedFile.value) {
    // Если выбран новый файл, просто убираем его
    selectedFile.value = null
    previewImage.value = null
  } else if (profile.value?.avatar_url) {
    // Если есть существующий аватар, помечаем на удаление
    avatarToDelete.value = true
    previewImage.value = null
  }
}

// Обработка отправки формы
const handleSubmit = async (): Promise<void> => {
  formLoading.value = true
  formErrors.value = {}
  
  try {
    const updateData: any = {
      name: form.name.trim(),
      surname: form.surname.trim(),
      email: form.email.trim(),
    }
    
    // Добавляем пароль только если он заполнен
    if (form.password.trim()) {
      updateData.password = form.password
      updateData.password_confirmation = form.password_confirmation
    }
    
    // Обрабатываем аватар
    if (selectedFile.value) {
      // Конвертируем файл в base64
      const base64 = await convertFileToBase64(selectedFile.value)
      updateData.avatar = {
        data: base64,
        name: selectedFile.value.name,
        type: selectedFile.value.type
      }
    } else if (avatarToDelete.value) {
      updateData.delete_avatar = true
    }
    
    await updateProfile(updateData)
    
    await fetchUser()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Профиль успешно обновлен',
      life: 3000
    })
    
    // Очищаем временные данные после успешного обновления
    form.password = ''
    form.password_confirmation = ''
    selectedFile.value = null
    previewImage.value = null
    avatarToDelete.value = false
    
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    }
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: err.response?.data?.message || err.message || 'Ошибка при обновлении профиля',
      life: 5000
    })
  } finally {
    formLoading.value = false
  }
}

// Загрузка профиля при монтировании
onMounted(async () => {
  await fetchProfile()
  initializeForm()
})

// Следим за изменениями профиля
watch(profile, () => {
  if (profile.value) {
    initializeForm()
  }
}, { deep: true })
</script>

<style scoped>

:deep(.password-input input) {
  width: 100% !important;
}

</style>