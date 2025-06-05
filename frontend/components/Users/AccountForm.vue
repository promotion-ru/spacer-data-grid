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
      <small v-if="formErrorsComputed.email" :id="`email-error-message`" class="p-error">{{
          formErrorsComputed.email
        }}</small>
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
      <label class="font-medium">Аватар</label>
      <div v-if="currentAvatarUrl || avatarPreview" class="mb-3">
        <div class="flex items-center gap-3">
          <Avatar
            :image="avatarPreview || currentAvatarUrl"
            :label="!avatarPreview && !currentAvatarUrl && formData.name ? formData.name.charAt(0).toUpperCase() : undefined"
            class="bg-primary-100 text-primary-700"
            shape="circle"
            size="xlarge"
          />
          <div v-if="isEditing && (currentAvatarUrl || avatarPreview) && !formData.delete_avatar">
            <Button
              class="p-button-danger p-button-text p-button-sm"
              icon="pi pi-trash"
              label="Удалить текущий аватар"
              type="button"
              @click="requestAvatarDeletion"
            />
          </div>
          <div v-if="formData.delete_avatar" class="text-orange-500 text-sm">
            Текущий аватар будет удален при сохранении.
          </div>
        </div>
      </div>
      
      <FileUpload
        :auto="false"
        :chooseLabel="avatarChooseLabel"
        :class="{'p-invalid': formErrorsComputed.avatar}"
        :maxFileSize="2097152"
        accept="image/*"
        aria-describedby="avatar-error-message"
        customUpload
        mode="basic"
        name="avatar_upload"
        @clear="onFileClear"
        @select="onFileSelect"
      />
      <small class="text-gray-600 block mt-1">Максимальный размер файла: 2MB.</small>
      <small v-if="formErrorsComputed.avatar" :id="`avatar-error-message`"
             class="p-error block">{{ formErrorsComputed.avatar }}</small>
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

const getDefaultFormData = () => ({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: '',
  avatar: null,       // File object for new avatar
  delete_avatar: false // Flag to delete existing avatar
});

const formData = reactive(getDefaultFormData());
const avatarPreview = ref(null);
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

const avatarChooseLabel = computed(() => {
  if (formData.avatar || avatarPreview.value) return 'Заменить изображение';
  if (isEditing.value && currentAvatarUrl.value) return 'Заменить аватар';
  return 'Выбрать изображение';
});


const resetFormInternal = () => {
  Object.assign(formData, getDefaultFormData());
  avatarPreview.value = null;
  currentAvatarUrl.value = null;
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
  avatarPreview.value = null;
};

const onFileSelect = (event) => {
  const file = event.files[0];
  if (file) {
    formData.avatar = file;
    formData.delete_avatar = false; // If new avatar is selected, don't delete old one by flag
    const reader = new FileReader();
    reader.onload = (e) => {
      avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const onFileClear = () => {
  formData.avatar = null;
  avatarPreview.value = null;
};

const requestAvatarDeletion = () => {
  formData.delete_avatar = true;
  formData.avatar = null;
  avatarPreview.value = null;
  // currentAvatarUrl will be handled by backend based on delete_avatar flag
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
  margin-bottom: 1.25rem; /* Увеличил немного отступ */
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
}

/* PrimeVue FileUpload basic mode width fix */
:deep(.p-fileupload-basic .p-button) {
  width: 100%;
}

:deep(.p-password-panel) { /* Ensure password panel does not overflow */
  min-width: calc(100% - 2px); /* Adjust based on borders if necessary */
}
</style>