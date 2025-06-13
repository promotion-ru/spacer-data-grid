<template>
  <Dialog
    v-model:visible="isVisible"
    :dismissableMask="true"
    :closeOnEscape="true"
    class="p-fluid w-[95vw] sm:w-[90vw] md:w-[70vw] lg:w-[50vw] xl:max-w-2xl mx-4 sm:mx-0"
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
    password_confirmation: formData.password_confirmation,
    active: formData.active ? 1 : 0,
    avatar: formData.avatar,
    delete_avatar: formData.delete_avatar,
  };
  
  try {
    const response = await $api('/users', {
      method: 'POST',
      body: JSON.stringify(userData),
      headers: {
        'Content-Type': 'application/json'
      }
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

<style scoped>
/* Адаптивные стили для модального окна */
@media (max-width: 640px) {
  :deep(.p-dialog .p-dialog-header) {
    padding: 1rem 1.5rem;
    text-align: center;
  }
  
  :deep(.p-dialog .p-dialog-content) {
    padding: 0 1.5rem 1rem 1.5rem;
  }
  
  :deep(.p-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.p-dialog .p-dialog-footer .p-button) {
    width: 100%;
    justify-content: center;
  }
}

@media (min-width: 641px) and (max-width: 1024px) {
  :deep(.p-dialog .p-dialog-header) {
    padding: 1.25rem 2rem;
  }
  
  :deep(.p-dialog .p-dialog-content) {
    padding: 0 2rem 1.25rem 2rem;
  }
  
  :deep(.p-dialog .p-dialog-footer) {
    padding: 1.25rem 2rem;
  }
}
</style>