<template>
  <Dialog
    v-model:visible="isVisible"
    header="Редактировать пользователя"
    modal
    :dismissableMask="true"
    class="p-fluid"
    :style="{ width: '500px' }"
    @hide="onDialogHide"
  >
    <UsersAccountForm
      v-if="props.user"
      ref="userFormRef"
      mode="edit"
      :initial-data="props.user"
      :errors="formErrors"
      @submit="handleFormSubmit"
    />
    <div v-else class="p-text-center">Загрузка данных пользователя...</div>
    
    <template #footer>
      <Button
        label="Отмена"
        icon="pi pi-times"
        class="p-button-text"
        @click="closeModal"
        type="button"
        :disabled="isSubmitting"
      />
      <Button
        label="Обновить"
        icon="pi pi-check"
        @click="triggerFormSubmit"
        :loading="isSubmitting"
        type="button"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import AccountForm from './AccountForm.vue';

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

const { $api } = useNuxtApp();
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
  if (!props.user || !props.user.id) {
    toast.add({ severity: 'warn', summary: 'Внимание', detail: 'Нет данных пользователя для обновления.', life: 3000 });
    return;
  }
  
  isSubmitting.value = true;
  formErrors.value = {};
  
  const userData = {
    name: formData.name,
    surname: formData.surname || '',
    email: formData.email,
    avatar: formData.avatar,
    delete_avatar: formData.delete_avatar,
  };
  
  if (formData.password) {
    userData.password = formData.password;
    userData.password_confirmation = formData.password_confirmation;
  }
  
  try {
    
    const response = await $api(`/users/${props.user.id}`, {
      method: 'PATCH',
      body: JSON.stringify(userData),
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('user-updated', response.data || response);
    toast.add({ severity: 'success', summary: 'Успешно', detail: 'Данные пользователя обновлены!', life: 3000 });
    closeModal();
  } catch (error) {
    console.error('Update user error:', error);
    if (error.data?.errors) {
      formErrors.value = error.data.errors;
    } else {
      toast.add({ severity: 'error', summary: 'Ошибка', detail: error.data?.message || 'Не удалось обновить данные пользователя.', life: 3000 });
    }
  } finally {
    isSubmitting.value = false;
  }
};

watch(() => props.visible, (newValue) => {
  if (newValue) {
    resetLocalState();
  }
});
</script>