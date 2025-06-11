<template>
  <div class="types-filters">
    <!-- Поиск и кнопка фильтров -->
    <div class="mb-4 space-y-3">
      <!-- Верхняя строка: поиск + кнопка фильтров -->
      <div class="flex items-center gap-3">
        <div class="flex-1">
          <div class="relative">
            <InputText
              v-model="searchQuery"
              :loading="loading"
              class="w-full pl-10 input-search"
              placeholder="Поиск по названию типа, таблице, создателю..."
            />
            <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
        </div>
        <Button
          :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
          :severity="hasActiveFilters ? 'info' : 'secondary'"
          class="p-button-outlined"
          @click="toggleFilters"
        />
        <Button
          v-if="hasActiveFilters"
          class="p-button-outlined p-button-danger"
          icon="pi pi-times"
          label="Сбросить"
          @click="resetFilters"
        />
      </div>
      
      <!-- Расширенные фильтры -->
      <div v-show="showFilters" class="filters-container">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
          <div class="text-sm font-medium text-gray-700 mb-3 flex items-center">
            <i class="pi pi-sliders-h mr-2"></i>
            Дополнительные фильтры
          </div>
          
          <!-- Основные фильтры -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Таблица данных</label>
              <Select
                v-model="selectedDataGrid"
                :options="dataGridOptions"
                class="w-full"
                optionLabel="name"
                optionValue="id"
                placeholder="Все таблицы"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Создано с</label>
              <DatePicker
                v-model="createdFrom"
                class="w-full"
                dateFormat="dd.mm.yy"
                placeholder="Выберите дату"
                showButtonBar
                showIcon
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Создано до</label>
              <DatePicker
                v-model="createdTo"
                class="w-full"
                dateFormat="dd.mm.yy"
                placeholder="Выберите дату"
                showButtonBar
                showIcon
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Сортировка</label>
              <Select
                v-model="selectedSortFilter"
                :options="sortOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="По дате создания"
              />
            </div>
          
          </div>
          
          <!-- Быстрые фильтры -->
          <div class="border-t pt-4">
            <div class="text-xs font-medium text-gray-600 mb-2">Быстрые фильтры:</div>
            <div class="flex flex-wrap gap-2">
              <Button
                :outlined="!isMyTypesFilter"
                :severity="isMyTypesFilter ? 'primary' : 'secondary'"
                label="Мои типы"
                size="small"
                @click="applyQuickFilter('my_types')"
              />
              <Button
                :outlined="!isLastWeekFilter"
                :severity="isLastWeekFilter ? 'primary' : 'secondary'"
                label="За последнюю неделю"
                size="small"
                @click="applyQuickFilter('last_week')"
              />
              <Button
                :outlined="!isLastMonthFilter"
                :severity="isLastMonthFilter ? 'primary' : 'secondary'"
                label="За последний месяц"
                size="small"
                @click="applyQuickFilter('last_month')"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Активные фильтры -->
      <div v-if="activeFilterTags.length" class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-gray-600">Активные фильтры:</span>
        <Tag
          v-for="tag in activeFilterTags"
          :key="tag.key"
          :value="tag.label"
          class="text-xs"
          severity="info"
        >
          <template #default>
            <span class="mr-1">{{ tag.label }}</span>
            <i
              class="pi pi-times cursor-pointer hover:text-red-600"
              @click="removeFilter(tag.key)"
            ></i>
          </template>
        </Tag>
      </div>
      
      <!-- Статистика -->
      <div v-if="totalCount !== null" class="text-sm text-gray-600 flex items-center justify-between">
        <span>
          Найдено {{ totalCount }} {{ getTypesWord(totalCount) }}
          <span v-if="hasActiveFilters" class="ml-2 text-blue-600">
            (применены фильтры)
          </span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import {debounce} from 'lodash-es'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  totalCount: {
    type: Number,
    default: null
  },
  dataGridOptions: {
    type: Array,
    default: () => []
  },
  currentUserId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['filtersChanged'])

// Реактивные данные
const showFilters = ref(false)
const searchQuery = ref('')
const selectedDataGrid = ref(null)
const selectedSortFilter = ref('created_desc')
const createdFrom = ref(null)
const createdTo = ref(null)

// Специальные фильтры для быстрых кнопок
const quickDateFilter = ref(null) // 'last_week' | 'last_month' | null
const myTypesFilter = ref(false) // true | false

// Опции сортировки
const sortOptions = ref([
  {label: 'По дате создания (новые)', value: 'created_desc'},
  {label: 'По дате создания (старые)', value: 'created_asc'},
  {label: 'По названию (А-Я)', value: 'name_asc'},
  {label: 'По названию (Я-А)', value: 'name_desc'},
  {label: 'По таблице (А-Я)', value: 'data_grid_name_asc'},
  {label: 'По таблице (Я-А)', value: 'data_grid_name_desc'},
  {label: 'По создателю (А-Я)', value: 'creator_name_asc'},
  {label: 'По создателю (Я-А)', value: 'creator_name_desc'}
])

// Вычисляемые свойства
const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value ||
    selectedDataGrid.value ||
    selectedSortFilter.value !== 'created_desc' ||
    createdFrom.value ||
    createdTo.value ||
    quickDateFilter.value ||
    myTypesFilter.value
  )
})

const isMyTypesFilter = computed(() => myTypesFilter.value)
const isLastWeekFilter = computed(() => quickDateFilter.value === 'last_week')
const isLastMonthFilter = computed(() => quickDateFilter.value === 'last_month')

const activeFilterTags = computed(() => {
  const tags = []
  
  if (selectedDataGrid.value) {
    const dataGrid = props.dataGridOptions.find(dg => dg.id === selectedDataGrid.value)
    if (dataGrid) {
      tags.push({key: 'dataGrid', label: `Таблица: ${dataGrid.name}`})
    }
  }
  
  if (selectedSortFilter.value && selectedSortFilter.value !== 'created_desc') {
    const sort = sortOptions.value.find(s => s.value === selectedSortFilter.value)
    if (sort) {
      tags.push({key: 'sort', label: `Сортировка: ${sort.label}`})
    }
  }
  
  if (createdFrom.value) {
    tags.push({key: 'createdFrom', label: `Создано с: ${formatDate(createdFrom.value)}`})
  }
  
  if (createdTo.value) {
    tags.push({key: 'createdTo', label: `Создано до: ${formatDate(createdTo.value)}`})
  }
  
  if (myTypesFilter.value) {
    tags.push({key: 'myTypes', label: 'Мои типы'})
  }
  
  if (quickDateFilter.value) {
    const dateLabels = {
      last_week: 'За последнюю неделю',
      last_month: 'За последний месяц'
    }
    tags.push({key: 'quickDate', label: dateLabels[quickDateFilter.value]})
  }
  
  return tags
})

// Debounced поиск
const debouncedEmitFilters = debounce(() => {
  emitFilters()
}, 500)

// Методы
const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const applyQuickFilter = (filterType) => {
  switch (filterType) {
    case 'my_types':
      myTypesFilter.value = !myTypesFilter.value
      break
    
    case 'last_week':
      if (quickDateFilter.value === 'last_week') {
        quickDateFilter.value = null
        createdFrom.value = null
        createdTo.value = null
      } else {
        quickDateFilter.value = 'last_week'
        const weekAgo = new Date()
        weekAgo.setDate(weekAgo.getDate() - 7)
        createdFrom.value = weekAgo
        createdTo.value = new Date()
      }
      break
    
    case 'last_month':
      if (quickDateFilter.value === 'last_month') {
        quickDateFilter.value = null
        createdFrom.value = null
        createdTo.value = null
      } else {
        quickDateFilter.value = 'last_month'
        const monthAgo = new Date()
        monthAgo.setMonth(monthAgo.getMonth() - 1)
        createdFrom.value = monthAgo
        createdTo.value = new Date()
      }
      break
  }
}

const removeFilter = (filterKey) => {
  switch (filterKey) {
    case 'dataGrid':
      selectedDataGrid.value = null
      break
    case 'sort':
      selectedSortFilter.value = 'created_desc'
      break
    case 'createdFrom':
      createdFrom.value = null
      if (quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        quickDateFilter.value = null
      }
      break
    case 'createdTo':
      createdTo.value = null
      if (quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        quickDateFilter.value = null
      }
      break
    case 'myTypes':
      myTypesFilter.value = false
      break
    case 'quickDate':
      quickDateFilter.value = null
      if (quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        createdFrom.value = null
        createdTo.value = null
      }
      break
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedDataGrid.value = null
  selectedSortFilter.value = 'created_desc'
  createdFrom.value = null
  createdTo.value = null
  quickDateFilter.value = null
  myTypesFilter.value = false
  showFilters.value = false
  emitFilters()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const formatDateForAPI = (date) => {
  return new Date(date).toISOString().split('T')[0]
}

const getTypesWord = (count) => {
  if (count % 10 === 1 && count % 100 !== 11) {
    return 'тип'
  } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
    return 'типа'
  } else {
    return 'типов'
  }
}

const emitFilters = () => {
  const filters = {
    search: searchQuery.value || null,
    data_grid_id: selectedDataGrid.value || null,
    sort_by: getSortField(selectedSortFilter.value),
    sort_order: getSortOrder(selectedSortFilter.value),
    created_from: createdFrom.value ? formatDateForAPI(createdFrom.value) : null,
    created_to: createdTo.value ? formatDateForAPI(createdTo.value) : null,
    my_types: myTypesFilter.value || null
  }
  
  emit('filtersChanged', filters)
}

const getSortField = (sortValue) => {
  if (!sortValue || sortValue === 'created_desc' || sortValue === 'created_asc') {
    return 'created_at'
  }
  
  const fieldMap = {
    'name_asc': 'name',
    'name_desc': 'name',
    'data_grid_name_asc': 'data_grid_name',
    'data_grid_name_desc': 'data_grid_name',
    'creator_name_asc': 'creator_name',
    'creator_name_desc': 'creator_name'
  }
  
  return fieldMap[sortValue] || 'created_at'
}

const getSortOrder = (sortValue) => {
  if (!sortValue) return 'desc'
  
  return sortValue.endsWith('_asc') ? 'asc' : 'desc'
}

// Watchers
watch(searchQuery, () => {
  debouncedEmitFilters()
})

watch([
  selectedDataGrid,
  selectedSortFilter,
  createdFrom,
  createdTo,
  quickDateFilter,
  myTypesFilter
], () => {
  emitFilters()
})

// Экспорт методов для родительского компонента
defineExpose({
  resetFilters
})
</script>

<style scoped>
:deep(.input-search) {
  padding-left: 30px;
}
</style>