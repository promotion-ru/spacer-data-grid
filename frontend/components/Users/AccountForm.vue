<template>
  <form class="space-y-4" @submit.prevent="onFormSubmitInternal">
    <div class="field">
      <label :for="`${mode}-name`" class="font-medium">Имя *</label>
      <InputText
        :id="`${mode}-name`"
        v-model="formData.name"
        :class="{ 'p-invalid': formErrorsComputed.name }"
        aria-describedby="name-error-message"
        placeholder="Введите имя"
      />
      <small v-if="formErrorsComputed.name" :id="`name-error-message`" class="p-error">{{formErrorsComputed.name }}</small>
    </div>
    
    <div class="field">
      <label :for="`${mode}-surname`" class="font-medium">Фамилия</label>
      <InputText
        :id="`${mode}-surname`"
        v-model="formData.surname"
        :class="{ 'p-invalid': formErrorsComputed.surname }"
        aria-describedby="surname-error-message"
        placeholder="Введите фамилию"
      />
      <small v-if="formErrorsComputed.surname" :id="`surname-error-message`"
             class="p-error">{{ formErrorsComputed.surname }}</small>
    </div>
    
    <div class="field">
      <label :for="`${mode}-email`" class="font-medium">Email *</label>
      <InputText
        :id="`${mode}-email`"
        v-model="formData.email"
        :class="{ 'p-invalid': formErrorsComputed.email }"
        aria-describedby="email-error-message"
        placeholder="Введите email"
        type="email"
      />
      <small v-if="formErrorsComputed.email" :id="`email-error-message`" class="p-error">{{formErrorsComputed.email }}</small>
    </div>
    
    <div class="field">
      <label :for="`${mode}-password`" class="font-medium">
        {{ isEditing ? 'Новый пароль' : 'Пароль' }} {{ !isEditing ? '*' : '' }}
      </label>
      <Password
        :id="`${mode}-password`"
        v-model="formData.password"
        :class="{ 'p-invalid': formErrorsComputed.password }"
        :feedback="!isEditing"
        :placeholder="isEditing ? 'Введите новый пароль (оставьте пустым, чтобы не менять)' : 'Введите пароль'"
        aria-describedby="password-error-message"
        inputClass="w-full"
        panelClass="min-w-full"
        toggle-mask
      />
      <small v-if="formErrorsComputed.password" :id="`password-error-message`"
             class="p-error">{{ formErrorsComputed.password }}</small>
      <small v-if="isEditing && !formErrorsComputed.password && !formData.password" class="text-gray-600">Оставьте
        пустым, если не хотите менять пароль</small>
    </div>
    
    <div class="field">
      <label :for="`${mode}-password-confirmation`" class="font-medium">
        {{ isEditing ? 'Подтверждение нового пароля' : 'Подтверждение пароля' }}
        {{ (!isEditing || formData.password) ? '*' : '' }}
      </label>
      <Password
        :id="`${mode}-password-confirmation`"
        v-model="formData.password_confirmation"
        :class="{ 'p-invalid': formErrorsComputed.password_confirmation }"
        :feedback="false"
        :placeholder="isEditing ? 'Подтвердите новый пароль' : 'Подтвердите пароль'"
        aria-describedby="password-confirm-error-message"
        inputClass="w-full"
        toggle-mask
      />
      <small v-if="formErrorsComputed.password_confirmation" :id="`password-confirm-error-message`"
             class="p-error">{{ formErrorsComputed.password_confirmation }}</small>
    </div>
    
    <div class="field">
      <div v-if="currentAvatarUrl && !formData.avatar && !formData.delete_avatar" class="mb-3">
        <div class="flex items-center gap-3">
          <Avatar
            :image="currentAvatarUrl"
            :label="!currentAvatarUrl && formData.name ? formData.name.charAt(0).toUpperCase() : undefined"
            class="bg-primary-100 text-primary-700"
            shape="circle"
            size="xlarge"
          />
          <div v-if="isEditing && currentAvatarUrl && !formData.delete_avatar">
            <Button
              class="p-button-danger p-button-text p-button-sm"
              icon="pi pi-trash"
              label="Удалить текущий аватар"
              type="button"
              @click="requestAvatarDeletion"
            />
          </div>
        </div>
      </div>
      
      <div v-if="formData.delete_avatar && !formData.avatar" class="mb-3">
        <div class="text-orange-500 text-sm flex items-center gap-2">
          <i class="pi pi-exclamation-triangle"></i>
          Текущий аватар будет удален при сохранении.
          <Button
            class="p-button-text p-button-sm"
            icon="pi pi-undo"
            label="Отменить"
            type="button"
            @click="cancelAvatarDeletion"
          />
        </div>
      </div>
      
      <ImageUploader
        ref="imageUploader"
        v-model="formData.avatar"
        label="Аватар"
        :error="formErrorsComputed.avatar"
        @file-selected="onImageSelected"
        @file-removed="onImageRemoved"
        @error="onImageError"
      />
    
    </div>
    <button class="hidden" type="submit"></button>
  </form>
</template>

<script setup>
const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ['create', 'edit'].includes(value)
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  errors: {
    type: Object,
    default: () => ({})
  },
});

const emit = defineEmits(['submit']);

const imageUploader = ref(null);

const getDefaultFormData = () => ({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: '',
  avatar: null,       // base64 строка для нового аватара
  delete_avatar: false // Flag to delete existing avatar
});

const formData = reactive(getDefaultFormData());
const currentAvatarUrl = ref(null);

const isEditing = computed(() => props.mode === 'edit');

const formErrorsComputed = computed(() => {
  const errorsToShow = {};
  for (const key in props.errors) {
    if (Array.isArray(props.errors[key])) {
      errorsToShow[key] = props.errors[key][0];
    } else {
      errorsToShow[key] = props.errors[key];
    }
  }
  return errorsToShow;
});

const resetFormInternal = () => {
  Object.assign(formData, getDefaultFormData());
  currentAvatarUrl.value = null;
  
  // Сбрасываем компонент загрузки изображений
  if (imageUploader.value) {
    imageUploader.value.reset();
  }
  
  if (isEditing.value && props.initialData && Object.keys(props.initialData).length) {
    fillForm(props.initialData);
  }
};

const fillForm = (data) => {
  formData.name = data.name || '';
  formData.surname = data.surname || '';
  formData.email = data.email || '';
  formData.password = '';
  formData.password_confirmation = '';
  formData.avatar = null;
  formData.delete_avatar = false;
  currentAvatarUrl.value = data.avatar_url || null;
};

// Методы для работы с ImageUploader
const onImageSelected = (imageData) => {
  formData.delete_avatar = false; // Если выбран новый аватар, отменяем удаление старого
};

const onImageRemoved = () => {
  formData.avatar = null;
};

const onImageError = (errorMessage) => {
  // Обработка ошибок от ImageUploader
  console.error('Image upload error:', errorMessage);
};

const requestAvatarDeletion = () => {
  formData.delete_avatar = true;
  formData.avatar = null;
  // Сбрасываем ImageUploader
  if (imageUploader.value) {
    imageUploader.value.reset();
  }
};

const cancelAvatarDeletion = () => {
  formData.delete_avatar = false;
};

const onFormSubmitInternal = () => {
  emit('submit', {...formData});
};

watch(() => props.initialData, (newData) => {
  if (isEditing.value && newData && Object.keys(newData).length) {
    fillForm(newData);
  } else if (!isEditing.value) {
    resetFormInternal();
  }
}, {immediate: true, deep: true});

watch(() => props.errors, (newErrors) => {
  // Если ошибки приходят от родителя, они будут отображены через formErrorsComputed
}, {deep: true});

defineExpose({
  resetForm: resetFormInternal,
  handleSubmit: onFormSubmitInternal // Для вызова из родителя, если нужно
});
</script>

<style scoped>
.field {
  margin-bottom: 1.25rem;
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
}

:deep(.p-password-panel) {
  min-width: calc(100% - 2px);
}
</style>