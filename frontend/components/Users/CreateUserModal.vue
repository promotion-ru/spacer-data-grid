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
    <!-- Убедитесь что UsersAccountForm - это правильное имя вашего компонента -->
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
      body: userData,
    });
    
    emit('user-created', response.data || response);
    toast.add({severity: 'success', summary: 'Успешно', detail: 'Пользователь успешно создан!', life: 3000});
    closeModal();
  } catch (error) {
    console.error('Create user error:', error);
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors;
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.response?.data?.message || 'Не удалось создать пользователя.',
        life: 3000
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>

</style>