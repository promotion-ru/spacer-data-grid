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
    delete_avatar: formData.delete_avatar,
    // _method: 'PUT' // Если ваш API ожидает это для POST запросов
  };
  
  if (formData.password) {
    userData.password = formData.password;
    userData.password_confirmation = formData.password_confirmation;
  }
  
  // Для отправки файла нового аватара formData.avatar (File объект)
  // как и в CreateUserModal, требуется FormData или Base64.
  // Текущая реализация отправляет только JSON с флагом delete_avatar.
  // Если formData.avatar есть (новый файл), вам нужно будет решить как его отправить.
  // const payload = new FormData();
  // Object.keys(userData).forEach(key => payload.append(key, userData[key]));
  // if (formData.avatar) {
  //   payload.append('avatar', formData.avatar); // Имя поля 'avatar' или как ожидает бэкэнд
  // }
  // payload.append('_method', 'PUT'); // Laravel часто требует это для FormData с PUT
  // const requestBody = payload;
  // const methodForApi = 'POST'; // При использовании FormData и _method: 'PUT'
  
  const requestBody = userData; // Отправляем JSON
  const methodForApi = 'PUT';
  
  try {
    const response = await $api(`/users/${props.user.id}`, {
      method: methodForApi,
      body: requestBody
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
    resetLocalState(); // Сброс при открытии
    // UserForm сам обновится через :initial-data
  }
});
</script>