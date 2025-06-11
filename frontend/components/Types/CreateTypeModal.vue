<template>
  <Dialog
    v-model:visible="isVisible"
    :dismissableMask="true"
    :style="{ width: '500px' }"
    :closeOnEscape="true"
    class="p-fluid"
    header="Создать тип"
    modal
    @hide="onDialogHide"
  >
    <TypesTypeForm
      ref="typeFormRef"
      :errors="formErrors"
      :data-grid-options="dataGridOptions"
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
  },
  dataGridOptions: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'type-created']);

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
  isSubmitting.value = true;
  formErrors.value = {};
  
  const typeData = {
    name: formData.name,
    data_grid_id: formData.data_grid_id
  };
  
  try {
    const response = await $api('/data-grid-types', {
      method: 'POST',
      body: JSON.stringify(typeData),
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('type-created', response.data || response);
    toast.add({ severity: 'success', summary: 'Успешно', detail: 'Тип успешно создан!', life: 3000 });
    closeModal();
  } catch (error) {
    console.error('Create type error:', error);
    if (error.data?.errors) {
      formErrors.value = error.data.errors;
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: error.data?.message || 'Не удалось создать тип.',
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