<template>
  <Dialog
    v-model:visible="isVisible"
    :closeOnEscape="true"
    :dismissableMask="true"
    class="p-fluid"
    header="Редактировать пользователя"
    modal
    @hide="onDialogHide"
  >
    <!-- Состояние загрузки -->
    <div v-if="isLoadingUser" class="flex justify-center items-center py-8">
      <ProgressSpinner size="50" strokeWidth="4"/>
      <span class="ml-3">Загрузка данных пользователя...</span>
    </div>
    
    <!-- Ошибка загрузки -->
    <div v-else-if="loadError" class="text-center py-8">
      <div class="text-red-600 mb-4">
        <i class="pi pi-exclamation-triangle text-2xl"></i>
        <p class="mt-2">Ошибка загрузки данных пользователя</p>
        <p class="text-sm">{{ loadError }}</p>
      </div>
      <Button
        class="p-button-outlined"
        icon="pi pi-refresh"
        label="Попробовать снова"
        @click="loadUserData"
      />
    </div>
    
    <!-- Форма -->
    <UsersAccountForm
      v-else-if="currentUserData"
      ref="userFormRef"
      :errors="formErrors"
      :initial-data="currentUserData"
      mode="edit"
      @submit="handleFormSubmit"
    />
    
    <template #footer>
      <Button
        :disabled="isSubmitting || isLoadingUser"
        class="p-button-text"
        icon="pi pi-times"
        label="Отмена"
        type="button"
        @click="closeModal"
      />
      <Button
        :disabled="isLoadingUser || loadError"
        :loading="isSubmitting"
        icon="pi pi-check"
        label="Обновить"
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
  },
  user: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:visible', 'user-updated']);

const {$api} = useNuxtApp();
const toast = useToast();
const userFormRef = ref(null);
const isSubmitting = ref(false);
const isLoadingUser = ref(false);
const formErrors = ref({});
const currentUserData = ref(null);
const loadError = ref(null);

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

const resetLocalState = () => {
  isSubmitting.value = false;
  isLoadingUser.value = false;
  formErrors.value = {};
  currentUserData.value = null;
  loadError.value = null;
  
  if (userFormRef.value) {
    userFormRef.value.resetForm();
  }
};

const loadUserData = async () => {
  if (!props.user?.id) {
    loadError.value = 'Не указан ID пользователя';
    return;
  }
  
  isLoadingUser.value = true;
  loadError.value = null;
  
  try {
    const response = await $api(`/users/${props.user.id}`);
    currentUserData.value = response.data || response;
  } catch (error) {
    console.error('Ошибка загрузки пользователя:', error);
    loadError.value = error.data?.message || 'Не удалось загрузить данные пользователя';
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить актуальные данные пользователя',
      life: 3000
    });
  } finally {
    isLoadingUser.value = false;
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
  if (!currentUserData.value?.id) {
    toast.add({severity: 'warn', summary: 'Внимание', detail: 'Нет данных пользователя для обновления.', life: 3000});
    return;
  }
  
  isSubmitting.value = true;
  formErrors.value = {};
  
  const userData = {
    name: formData.name,
    surname: formData.surname || '',
    email: formData.email,
    active: formData.active ? 1 : 0,
    avatar: formData.avatar,
    delete_avatar: formData.delete_avatar,
  };
  
  if (formData.password) {
    userData.password = formData.password;
    userData.password_confirmation = formData.password_confirmation;
  }
  
  try {
    const response = await $api(`/users/${currentUserData.value.id}`, {
      method: 'PATCH',
      body: JSON.stringify(userData),
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('user-updated', response.data || response);
    toast.add({severity: 'success', summary: 'Успешно', detail: 'Данные пользователя обновлены!', life: 3000});
    closeModal();
  } catch (error) {
    console.error('Update user error:', error);
    if (error.data?.errors) {
      formErrors.value = error.data.errors;
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.data?.message || 'Не удалось обновить данные пользователя.',
        life: 3000
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

watch(() => props.visible, (newValue) => {
  if (newValue) {
    resetLocalState();
    loadUserData();
  }
});
</script>