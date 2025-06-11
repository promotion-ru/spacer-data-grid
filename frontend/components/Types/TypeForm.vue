<template>
  <form class="space-y-4" @submit.prevent="onFormSubmitInternal">
    <div class="field">
      <label :for="`${mode}-name`" class="font-medium">Название типа *</label>
      <InputText
        :id="`${mode}-name`"
        v-model="formData.name"
        :class="{ 'p-invalid': formErrorsComputed.name }"
        aria-describedby="name-error-message"
        placeholder="Введите название типа"
      />
      <small v-if="formErrorsComputed.name" :id="`name-error-message`" class="p-error">{{ formErrorsComputed.name }}</small>
    </div>
    
    <div class="field">
      <label :for="`${mode}-data_grid_id`" class="font-medium">Таблица данных *</label>
      <Select
        :id="`${mode}-data_grid_id`"
        v-model="formData.data_grid_id"
        :options="dataGridOptions"
        optionLabel="name"
        optionValue="id"
        :class="{ 'p-invalid': formErrorsComputed.data_grid_id }"
        aria-describedby="data-grid-error-message"
        placeholder="Выберите таблицу"
        class="w-full"
      />
      <small v-if="formErrorsComputed.data_grid_id" :id="`data-grid-error-message`" class="p-error">{{ formErrorsComputed.data_grid_id }}</small>
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
  dataGridOptions: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['submit']);

const getDefaultFormData = () => ({
  name: '',
  data_grid_id: null
});

const formData = reactive(getDefaultFormData());

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

const resetFormInternal = () => {
  Object.assign(formData, getDefaultFormData());
  
  if (isEditing.value && props.initialData && Object.keys(props.initialData).length) {
    fillForm(props.initialData);
  }
};

const fillForm = (data) => {
  formData.name = data.name || '';
  formData.data_grid_id = data.data_grid_id || null;
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
  // Ошибки отображаются через formErrorsComputed
}, {deep: true});

defineExpose({
  resetForm: resetFormInternal,
  handleSubmit: onFormSubmitInternal
});
</script>

<style scoped>
.field {
  margin-bottom: 1.25rem;
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
}
</style>