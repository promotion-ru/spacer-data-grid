<!-- components/DataGridTypeAutocomplete.vue -->
<template>
  <div class="w-full">
    <AutoComplete
      v-model="selectedType"
      :suggestions="suggestions"
      option-label="label"
      option-value="id"
      :placeholder="placeholder"
      :loading="loading"
      dropdown
      force-selection
      complete-on-focus
      @complete="searchTypes"
      @item-select="onTypeSelect"
      @input="onInput"
      class="w-full"
      input-class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      panel-class="bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-auto"
    >
      <template #option="{ option }">
        <div class="flex items-center justify-between p-2 hover:bg-gray-50">
          <span class="text-gray-900">{{ option.name }}</span>
          <div class="flex items-center space-x-2">
            <Badge
              v-if="option.is_global"
              value="Глобальный"
              severity="info"
              size="small"
            />
          </div>
        </div>
      </template>
      
      <template #empty>
        <div class="p-4 text-center">
          <p class="text-gray-500 mb-2">Тип не найден</p>
          <Button
            v-if="canCreateNew"
            @click="createNewType"
            label="Создать новый тип"
            size="small"
            outlined
            :loading="creating"
            icon="pi pi-plus"
          />
        </div>
      </template>
    </AutoComplete>
    
    <!-- Дополнительная информация о выбранном типе -->
    <div v-if="selectedTypeInfo" class="mt-2 text-sm text-gray-600">
      <span v-if="selectedTypeInfo.is_global" class="inline-flex items-center">
        <i class="pi pi-globe mr-1"></i>
        Глобальный тип
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'
import Badge from 'primevue/badge'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null
  },
  dataGridId: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Выберите или введите тип...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  limit: {
    type: Number,
    default: 20
  }
})

const emit = defineEmits(['update:modelValue', 'typeCreated', 'error'])

const { $api } = useNuxtApp()
const toast = useToast()


const selectedType = ref(null)
const suggestions = ref([])
const loading = ref(false)
const creating = ref(false)
const searchQuery = ref('')
const selectedTypeInfo = ref(null)

const canCreateNew = computed(() => {
  return searchQuery.value?.length >= 2 &&
    !suggestions.value.some(s => s.name.toLowerCase() === searchQuery.value.toLowerCase())
})

const searchTypes = async (event) => {
  if (!props.dataGridId) return
  
  searchQuery.value = event.query
  loading.value = true
  
  try {
    const response = await $api('/data-grid/types/search', {
      method: 'GET',
      query: {
        data_grid_id: props.dataGridId,
        search: event.query,
        limit: props.limit
      }
    })
    
    suggestions.value = response.data || []
  } catch (error) {
    console.error('Ошибка поиска типов:', error)
    emit('error', 'Ошибка поиска типов')
    suggestions.value = []
  } finally {
    loading.value = false
  }
}

const onTypeSelect = (event) => {
  selectedTypeInfo.value = suggestions.value.find(s => s.id === event.value)
  emit('update:modelValue', event.value)
}

const onInput = (event) => {
  if (!event) {
    selectedTypeInfo.value = null
    emit('update:modelValue', null)
  }
}

const createNewType = async () => {
  if (!searchQuery.value || creating.value) return
  
  creating.value = true
  
  try {
    const response = await $api('/data-grid/types', {
      method: 'POST',
      body: {
        name: searchQuery.value,
        data_grid_id: props.dataGridId
      }
    })
    
    const newType = response.data
    selectedType.value = newType.id
    selectedTypeInfo.value = newType
    emit('update:modelValue', newType.id)
    emit('typeCreated', newType)
    
    // Добавляем новый тип в список
    suggestions.value.unshift(newType)
    
    // Показываем уведомление
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Тип "${newType.name}" создан`,
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка создания типа:', error)
    emit('error', 'Не удалось создать тип')
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.data?.message || 'Не удалось создать тип',
      life: 3000
    })
  } finally {
    creating.value = false
  }
}

// Синхронизация с внешним значением
watch(() => props.modelValue, (newValue) => {
  selectedType.value = newValue
}, { immediate: true })
</script>