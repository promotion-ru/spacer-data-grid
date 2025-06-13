<template>
  <div class="w-full">
    <div class="relative">
      <AutoComplete
        v-model="selectedType"
        :loading="loading"
        :placeholder="placeholder"
        :suggestions="suggestions"
        :pt="{
          root: { style: 'width: 100%' },
          input: { style: 'background-color: var(--surface-0); border-color: var(--border-color); color: var(--text-primary)' },
          panel: { style: 'background-color: var(--surface-0); border-color: var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1)' },
          list: { style: 'padding: 0.5rem' },
          item: { style: 'border-radius: 6px; margin-bottom: 2px' }
        }"
        class="w-full"
        complete-on-focus
        dropdown
        force-selection
        option-label="label"
        @complete="searchTypes"
        @input="onInput"
        @item-select="onTypeSelect"
      >
        <!-- Кнопка быстрого создания рядом с поиском -->
        <template #loader>
          <div class="absolute right-12 top-1/2 transform -translate-y-1/2 z-10">
            <Button
              v-if="canCreateNew && searchQuery.length >= 2"
              :loading="creating"
              icon="pi pi-plus"
              size="small"
              severity="success"
              rounded
              outlined
              v-tooltip.top="`Создать тип '${searchQuery}'`"
              @click="createNewType"
            />
          </div>
        </template>
        <template #option="{ option }">
          <div class="flex items-center justify-between p-3 transition-colors duration-200" style="border-radius: 6px" 
               @mouseenter="$event.currentTarget.style.backgroundColor = 'var(--primary-50)'"
               @mouseleave="$event.currentTarget.style.backgroundColor = 'transparent'">
            <div class="flex items-center gap-2 flex-1">
              <i class="pi pi-tag" style="color: var(--primary-color)"></i>
              <span class="font-medium" >{{ option.name }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Tag
                v-if="option.is_global"
                value="Глобальный"
                severity="info"
                size="small"
                class="text-xs"
              />
              <i class="pi pi-arrow-right text-xs text-secondary" ></i>
            </div>
          </div>
        </template>
      
        <template #empty>
          <div class="p-6 text-center" style="background-color: var(--surface-50); border-radius: 8px; margin: 8px">
            <i class="pi pi-search text-3xl mb-3 text-secondary" ></i>
            <p class="mb-3 font-medium text-secondary" >Тип не найден</p>
            <Button
              v-if="canCreateNew"
              :loading="creating"
              :label="`Создать '${searchQuery}'`"
              icon="pi pi-plus"
              severity="success"
              size="small"
              class="w-full"
              @click="createNewType"
            />
            <p v-else class="text-xs mt-2 text-secondary" >Введите минимум 2 символа для создания</p>
          </div>
        </template>
      
        <!-- Footer для кнопки создания когда есть результаты -->
        <template #footer>
          <div v-if="canCreateNew && suggestions.length > 0" class="p-3" style="border-top: 1px solid var(--border-color); background-color: var(--surface-100)">
            <Button
              :label="`Создать тип '${searchQuery}'`"
              :loading="creating"
              class="w-full"
              icon="pi pi-plus"
              severity="success"
              outlined
              size="small"
              @click="createNewType"
            />
          </div>
        </template>
      </AutoComplete>
    </div>
    
    <!-- Дополнительная информация о выбранном типе -->
    <div v-if="selectedTypeInfo" class="mt-3">
      <div class="flex items-center gap-2 p-2 rounded-lg" style="background-color: var(--primary-50); border: 1px solid var(--primary-200)">
        <i class="pi pi-info-circle" style="color: var(--primary-color)"></i>
        <div class="flex-1">
          <div class="text-sm font-medium" style="color: var(--primary-700)">Выбранный тип: {{ selectedTypeInfo.name }}</div>
          <div v-if="selectedTypeInfo.is_global" class="text-xs flex items-center gap-1 mt-1" style="color: var(--primary-600)">
            <i class="pi pi-globe"></i>
            <span>Глобальный тип (доступен во всех таблицах)</span>
          </div>
          <div v-else class="text-xs mt-1" style="color: var(--primary-600)">
            Локальный тип (только в этой таблице)
          </div>
        </div>
      </div>
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