<template>
  <Dialog
    v-model:visible="isVisible"
    :dismissableMask="true"
    :style="{ width: '500px' }"
    class="p-fluid"
    header="Создать пользователя"
    modal
    @hide="onDialogHide"
  >
    <UsersAccountForm
      ref="userFormRef"
      :errors="formErrors"
      mode="create"
      @submit="handleFormSubmit"
    />
    
    <template #footer>
      <Button
        :disabled="isSubmitting"
        class="p-button-text"
        icon="pi pi-times"
        label="Отмена"
        type="button"
        @click="closeModal"
      />
      <Button
        :loading="isSubmitting"
        icon="pi pi-check"
        label="Создать"
        type="button"
        @click="triggerFormSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:visible', 'user-created']);

const {$api} = useNuxtApp();
const toast = useToast();
const userFormRef = ref(null);
const isSubmitting = ref(false);
const formErrors = ref({});

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

const resetLocalState = () => {
  isSubmitting.value = false;
  formErrors.value = {};
  if (userFormRef.value) {
    userFormRef.value.resetForm();
  }
};

const onDialogHide = () => {
  resetLocalState();
};

const closeModal = () => {
  isVisible.value = false;
};

const triggerFormSubmit = () => {
  if (userFormRef.value) {
    userFormRef.value.handleSubmit();
  }
};

const handleFormSubmit = async (formData) => {
  isSubmitting.value = true;
  formErrors.value = {};
  
  const userData = {
    name: formData.name,
    surname: formData.surname || '',
    email: formData.email,
    password: formData.password,
    password_confirmation: formData.password_confirmation
  };
  
  // Для отправки файла аватара formData.avatar (это File объект)
  // вам нужно использовать FormData, а не чистый JSON, ИЛИ
  // ваш API должен принимать base64 строку (UserForm нужно будет доработать для конвертации).
  // Текущая реализация отправляет только JSON.
  // Если formData.avatar есть, и вы хотите его отправить, то:
  // const payload = new FormData();
  // Object.keys(userData).forEach(key => payload.append(key, userData[key]));
  // if (formData.avatar) {
  //   payload.append('avatar', formData.avatar);
  // }
  // const requestBody = payload; // И $api должен быть способен отправлять FormData
  
  const requestBody = userData; // Отправляем JSON
  
  try {
    const response = await $api('/users', {
      method: 'POST',
      body: requestBody
    });
    
    // Предполагаем, что успешный ответ имеет response.data с данными пользователя
    emit('user-created', response.data || response);
    toast.add({severity: 'success', summary: 'Успешно', detail: 'Пользователь успешно создан!', life: 3000});
    closeModal();
  } catch (error) {
    console.error('Create user error:', error);
    if (error.data?.errors) {
      formErrors.value = error.data.errors;
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.data?.message || 'Не удалось создать пользователя.',
        life: 3000
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

watch(() => props.visible, (newValue) => {
  if (newValue) {
    resetLocalState(); // Сброс при открытии
  }
});
</script>