<template>
  <div class="w-full">
    <AutoComplete
      v-model="selectedType"
      :loading="loading"
      :placeholder="placeholder"
      :suggestions="suggestions"
      class="w-full"
      complete-on-focus
      dropdown
      force-selection
      input-class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      option-label="label"
      panel-class="bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-auto"
      @complete="searchTypes"
      @input="onInput"
      @item-select="onTypeSelect"
    >
      <template #option="{ option }">
        <div class="flex items-center justify-between p-2 hover:bg-gray-50">
          <span class="text-gray-900">{{ option.name }}</span>
          <div class="flex items-center space-x-2">
            <Badge
              v-if="option.is_global"
              severity="info"
              size="small"
              value="Глобальный"
            />
          </div>
        </div>
      </template>
      
      <template #empty>
        <div class="p-4 text-center">
          <p class="text-gray-500 mb-2">Тип не найден</p>
          <Button
            v-if="canCreateNew"
            :loading="creating"
            icon="pi pi-plus"
            label="Создать новый тип"
            outlined
            size="small"
            @click="createNewType"
          />
        </div>
      </template>
      
      <!-- Добавляем footer для кнопки создания когда есть результаты -->
      <template #footer>
        <div v-if="canCreateNew && suggestions.length > 0" class="p-2 border-t border-gray-200">
          <Button
            :label="`Создать тип ${searchQuery}`"
            :loading="creating"
            class="w-full"
            icon="pi pi-plus"
            outlined
            size="small"
            @click="createNewType"
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

const {$api} = useNuxtApp()
const toast = useToast()

const selectedType = ref(null)
const suggestions = ref([])
const loading = ref(false)
const creating = ref(false)
const searchQuery = ref('')
const selectedTypeInfo = ref(null)
const initialLoadDone = ref(false)

// ИСПРАВЛЕННАЯ логика - проверяем ТОЧНОЕ совпадение имени
const canCreateNew = computed(() => {
  return searchQuery.value?.length >= 2 &&
    !suggestions.value.some(s => s.name.toLowerCase().trim() === searchQuery.value.toLowerCase().trim())
})

// Функция загрузки конкретного типа по ID
const loadTypeById = async (typeId) => {
  if (!typeId || !props.dataGridId) return null
  
  try {
    const response = await $api(`/data-grid/types/${typeId}`, {
      method: 'GET',
      query: {
        data_grid_id: props.dataGridId
      }
    })
    
    return response.data
  } catch (error) {
    console.error('Ошибка загрузки типа:', error)
    return null
  }
}

const searchTypes = async (event) => {
  if (!props.dataGridId) {
    return
  }
  
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
    
    // Если есть выбранный тип, но его нет в результатах - добавляем
    if (selectedType.value && selectedTypeInfo.value) {
      const existsInSuggestions = suggestions.value.some(s => s.id === selectedType.value)
      if (!existsInSuggestions) {
        suggestions.value.unshift(selectedTypeInfo.value)
      }
    }
  } catch (error) {
    console.error('Ошибка поиска типов:', error)
    emit('error', 'Ошибка поиска типов')
    suggestions.value = []
  } finally {
    loading.value = false
  }
}

const onTypeSelect = (event) => {
  const selectedObject = event.value
  const typeId = selectedObject.id
  
  selectedTypeInfo.value = selectedObject
  
  // Устанавливаем selectedType как объект для отображения в AutoComplete
  selectedType.value = selectedObject
  
  console.log('Emitting typeId:', typeId, 'type:', typeof typeId)
  emit('update:modelValue', typeId) // Передаем только ID в форму
}

const onInput = (event) => {
  console.log('onInput event:', event)
  if (!event || event === '') {
    selectedType.value = null
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
    
    selectedType.value = newType
    selectedTypeInfo.value = newType
    
    // Передаем ID родительскому компоненту
    emit('update:modelValue', newType.id)
    emit('typeCreated', newType)
    
    // Очищаем поисковый запрос после создания
    searchQuery.value = ''
    
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

// Инициализация компонента
onMounted(async () => {
  if (props.modelValue && !selectedTypeInfo.value) {
    const typeData = await loadTypeById(props.modelValue)
    if (typeData) {
      selectedType.value = typeData // Устанавливаем объект для отображения
      selectedTypeInfo.value = typeData
      // Добавляем в suggestions для корректного отображения
      suggestions.value = [typeData]
    }
  }
  initialLoadDone.value = true
})

// Синхронизация selectedTypeInfo при изменении selectedType
watch(selectedType, async (newValue) => {
  console.log('selectedType changed to:', newValue, 'type:', typeof newValue)
  
  if (newValue && typeof newValue === 'object' && newValue.id) {
    // selectedType содержит объект - это нормально для отображения
    selectedTypeInfo.value = newValue
  } else if (!newValue || newValue === null || newValue === undefined) {
    selectedTypeInfo.value = null
  }
})

// Синхронизация с внешним значением (form.type_id)
watch(() => props.modelValue, async (newValue) => {
  console.log('props.modelValue changed to:', newValue, 'type:', typeof newValue)
  
  if (newValue && typeof newValue !== 'object' && initialLoadDone.value) {
    // Пришел ID - нужно найти/загрузить объект для selectedType
    let typeObject = suggestions.value.find(s => s.id === newValue)
    
    if (!typeObject) {
      typeObject = await loadTypeById(newValue)
      if (typeObject) {
        // Добавляем в suggestions
        const existsInSuggestions = suggestions.value.some(s => s.id === typeObject.id)
        if (!existsInSuggestions) {
          suggestions.value.unshift(typeObject)
        }
      }
    }
    
    if (typeObject) {
      selectedType.value = typeObject
      selectedTypeInfo.value = typeObject
    }
  } else if (!newValue || newValue === null || newValue === undefined) {
    selectedType.value = null
    selectedTypeInfo.value = null
  }
}, {immediate: true})
</script>