<template>
  <Dialog
    v-model:visible="isVisible"
    header="Редактировать тип"
    modal
    :dismissableMask="true"
    class="p-fluid"
    :closeOnEscape="true"
    :style="{ width: '500px' }"
    @hide="onDialogHide"
  >
    <TypesTypeForm
      v-if="props.type"
      ref="typeFormRef"
      mode="edit"
      :initial-data="props.type"
      :data-grid-options="dataGridOptions"
      :errors="formErrors"
      @submit="handleFormSubmit"
    />
    <div v-else class="p-text-center">Загрузка данных типа...</div>
    
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
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  type: {
    type: Object,
    default: null
  },
  dataGridOptions: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'type-updated']);

const { $api } = useNuxtApp();
const toast = useToast();
const typeFormRef = ref(null);
const isSubmitting = ref(false);
const formErrors = ref({});

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

const resetLocalState = () => {
  isSubmitting.value = false;
  formErrors.value = {};
  if (typeFormRef.value) {
    typeFormRef.value.resetForm();
  }
};

const onDialogHide = () => {
  resetLocalState();
};

const closeModal = () => {
  isVisible.value = false;
};

const triggerFormSubmit = () => {
  if (typeFormRef.value) {
    typeFormRef.value.handleSubmit();
  }
};

const handleFormSubmit = async (formData) => {
  if (!props.type || !props.type.id) {
    toast.add({ severity: 'warn', summary: 'Внимание', detail: 'Нет данных типа для обновления.', life: 3000 });
    return;
  }
  
  isSubmitting.value = true;
  formErrors.value = {};
  
  const typeData = {
    name: formData.name,
    data_grid_id: formData.data_grid_id
  };
  
  try {
    const response = await $api(`/data-grid-types/${props.type.id}`, {
      method: 'PATCH',
      body: JSON.stringify(typeData),
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('type-updated', response.data || response);
    toast.add({ severity: 'success', summary: 'Успешно', detail: 'Данные типа обновлены!', life: 3000 });
    closeModal();
  } catch (error) {
    console.error('Update type error:', error);
    if (error.data?.errors) {
      formErrors.value = error.data.errors;
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.data?.message || 'Не удалось обновить данные типа.',
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
  }
});
</script>