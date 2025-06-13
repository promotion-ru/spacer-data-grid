<template>
  <!-- Увеличиваем вертикальный отступ между блоками для лучшей читаемости -->
  <form class="space-y-6" @submit.prevent="onFormSubmitInternal">
    <!-- Группа: Имя и Фамилия -->
    <div class="field">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :for="`${mode}-name`" class="font-medium" style="color: var(--text-primary)">Имя *</label>
          <InputText
            :id="`${mode}-name`"
            ref="firstInput"
            v-model="formData.name"
            :class="{ 'p-invalid': formErrorsComputed.name }"
            placeholder="Например, Иван"
          />
          <small v-if="formErrorsComputed.name" class="p-error">{{ formErrorsComputed.name }}</small>
        </div>
        <div>
          <label :for="`${mode}-surname`" class="font-medium" style="color: var(--text-primary)">Фамилия</label>
          <InputText
            :id="`${mode}-surname`"
            v-model="formData.surname"
            :class="{ 'p-invalid': formErrorsComputed.surname }"
            placeholder="Например, Иванов"
          />
          <small v-if="formErrorsComputed.surname" class="p-error">{{ formErrorsComputed.surname }}</small>
        </div>
      </div>
    </div>
    
    <!-- Поле: Email -->
    <div class="field">
      <label :for="`${mode}-email`" class="font-medium" style="color: var(--text-primary)">Email *</label>
      <InputText
        :id="`${mode}-email`"
        v-model="formData.email"
        :class="{ 'p-invalid': formErrorsComputed.email }"
        placeholder="user@example.com"
        type="email"
      />
      <small v-if="formErrorsComputed.email" class="p-error">{{ formErrorsComputed.email }}</small>
    </div>
    
    <!-- Группа: Пароль и Подтверждение -->
    <div class="field">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :for="`${mode}-password`" class="font-medium" style="color: var(--text-primary)">
            {{ isEditing ? 'Новый пароль' : 'Пароль' }} {{ !isEditing ? '*' : '' }}
          </label>
          <div class="p-inputgroup">
            <Password
              :id="`${mode}-password`"
              v-model="formData.password"
              :class="{ 'p-invalid': formErrorsComputed.password }"
              :feedback="!isEditing"
              :placeholder="isEditing ? 'Оставьте пустым, чтобы не менять' : 'Введите пароль'"
              inputClass="w-full"
              panelClass="min-w-full"
              toggle-mask
            />
            <Button v-if="!isEditing" icon="pi pi-shield" class="p-button-rounded" @click="generateAndSetPassword" v-tooltip.top="'Сгенерировать пароль'"/>
          </div>
          <small v-if="formErrorsComputed.password" class="p-error">{{ formErrorsComputed.password }}</small>
          <small v-else-if="isEditing && !formData.password" style="color: var(--text-secondary)">Оставьте пустым, если не хотите менять пароль.</small>
          <small v-else style="color: var(--text-secondary)">Минимум 8 символов.</small>
        </div>
        <div>
          <label :for="`${mode}-password-confirmation`" class="font-medium" style="color: var(--text-primary)">
            Подтверждение {{ (!isEditing || formData.password) ? '*' : '' }}
          </label>
          <Password
            :id="`${mode}-password-confirmation`"
            v-model="formData.password_confirmation"
            :class="{ 'p-invalid': formErrorsComputed.password_confirmation }"
            :feedback="false"
            placeholder="Подтвердите пароль"
            inputClass="w-full"
            toggle-mask
          />
          <small v-if="formErrorsComputed.password_confirmation" class="p-error">{{ formErrorsComputed.password_confirmation }}</small>
        </div>
      </div>
    </div>
    
    <!-- Группа: Аватар -->
    <div class="field">
      <label class="font-medium" style="color: var(--text-primary)">Аватар</label>
      <div class="flex items-center gap-4">
        <Avatar
          v-if="!formData.avatar && currentAvatarUrl"
          :image="currentAvatarUrl"
          :label="!currentAvatarUrl && formData.name ? formData.name.charAt(0).toUpperCase() : undefined"
          class="bg-primary-100 text-primary-700"
          shape="circle"
          size="xlarge"
        />
        <Button
          v-if="isEditing && currentAvatarUrl && !formData.delete_avatar"
          class="p-button-danger p-button-text"
          icon="pi pi-trash"
          type="button"
          @click="requestAvatarDeletion"
          v-tooltip.top="'Удалить текущий аватар'"
        />
      </div>
      <div v-if="formData.delete_avatar" class="mt-2 text-sm flex items-center gap-2" style="color: #f59e0b">
        <i class="pi pi-exclamation-triangle"></i>
        Текущий аватар будет удален.
        <Button
          class="p-button-text p-button-sm p-0 ml-2"
          label="Отменить"
          type="button"
          severity="secondary"
          @click="cancelAvatarDeletion"
        />
      </div>
      
      <div class="flex-grow mt-4">
        <ImageUploader
          ref="imageUploader"
          v-model="formData.avatar"
          :error="formErrorsComputed.avatar"
          @file-selected="onImageSelected"
          @file-removed="onImageRemoved"
        />
      </div>
    </div>
    
    <!-- Группа: Активность -->
    <div class="field">
      <div class="flex items-center">
        <Checkbox
          :inputId="`${mode}-active`"
          v-model="formData.active"
          :binary="true"
        />
        <label :for="`${mode}-active`" class="ml-2 font-medium" style="color: var(--text-primary); cursor: pointer;">Активный пользователь</label>
      </div>
      <small style="color: var(--text-secondary)">Неактивные пользователи не могут входить в систему.</small>
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
const toast = useToast();

const imageUploader = ref(null);
const firstInput = ref(null); // Ref для первого поля ввода

const getDefaultFormData = () => ({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: '',
  active: true,
  avatar: null,
  delete_avatar: false
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

// Функция для генерации пароля
const generateAndSetPassword = () => {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
  let password = '';
  for (let i = 0; i < 12; i++) {
    password += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  formData.password = password;
  formData.password_confirmation = password;
  
  toast.add({ severity: 'info', summary: 'Пароль сгенерирован', detail: 'Не забудьте сохранить его, если это необходимо.', life: 4000 });
};


const resetFormInternal = () => {
  Object.assign(formData, getDefaultFormData());
  currentAvatarUrl.value = null;
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
  // Более надежный парсинг, который вы использовали, очень хорош. Оставим его.
  const parseActiveValue = (value) => {
    if (value === undefined || value === null) return true;
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value === 1;
    if (typeof value === 'string') {
      const lowerValue = value.toLowerCase().trim();
      return lowerValue === 'true' || lowerValue === '1' || lowerValue === 'yes';
    }
    return Boolean(value);
  };
  formData.active = parseActiveValue(data.active);
  formData.avatar = null;
  formData.delete_avatar = false;
  currentAvatarUrl.value = data.avatar_url || null;
};

const onImageSelected = () => {
  formData.delete_avatar = false;
};

const onImageRemoved = () => {
  formData.avatar = null;
};

const requestAvatarDeletion = () => {
  formData.delete_avatar = true;
  formData.avatar = null;
  if (imageUploader.value) {
    imageUploader.value.reset();
  }
};

const cancelAvatarDeletion = () => {
  formData.delete_avatar = false;
};

const onFormSubmitInternal = () => {
  emit('submit', { ...formData });
};

watch(() => props.initialData, (newData) => {
  if (isEditing.value && newData && Object.keys(newData).length) {
    fillForm(newData);
  } else if (!isEditing.value) {
    resetFormInternal();
  }
}, { immediate: true, deep: true });

defineExpose({
  resetForm: resetFormInternal,
  handleSubmit: onFormSubmitInternal,
});
</script>

<style scoped>

</style>