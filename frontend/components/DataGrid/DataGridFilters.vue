<template>
  <div class="data-grid-filters">
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
              placeholder="Поиск по названию, описанию, владельцу..."
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
              <label class="block text-xs font-medium text-gray-600 mb-1">Статус активности</label>
              <Select
                v-model="selectedActivityFilter"
                :options="activityOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все статусы"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Период создания</label>
              <DatePicker
                v-model="createdDateRange"
                :manualInput="false"
                class="w-full"
                dateFormat="dd.mm.yy"
                placeholder="Выберите период"
                selectionMode="range"
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
                :outlined="!isOwnerFilter"
                :severity="isOwnerFilter ? 'primary' : 'secondary'"
                label="Мои таблицы"
                size="small"
                @click="applyQuickFilter('my_grids')"
              />
              <Button
                :outlined="!isSharedFilter"
                :severity="isSharedFilter ? 'primary' : 'secondary'"
                label="Общие таблицы"
                size="small"
                @click="applyQuickFilter('shared_grids')"
              />
              <Button
                :outlined="!isLastWeekFilter"
                :severity="isLastWeekFilter ? 'primary' : 'secondary'"
                label="За последнюю неделю"
                size="small"
                @click="applyQuickFilter('last_week')"
              />
              <Button
                :outlined="!isLastMonthChangesFilter"
                :severity="isLastMonthChangesFilter ? 'primary' : 'secondary'"
                label="Изменения за месяц"
                size="small"
                @click="applyQuickFilter('last_month_changes')"
              />
              <Button
                :outlined="!isLastWeekChangesFilter"
                :severity="isLastWeekChangesFilter ? 'primary' : 'secondary'"
                label="Изменения за неделю"
                size="small"
                @click="applyQuickFilter('last_week_changes')"
              />
              <Button
                :outlined="!isLastDayChangesFilter"
                :severity="isLastDayChangesFilter ? 'primary' : 'secondary'"
                label="Изменения за день"
                size="small"
                @click="applyQuickFilter('last_day_changes')"
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
          Найдено {{ totalCount }} {{ getTableWord(totalCount) }}
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
  }
})

const emit = defineEmits(['filtersChanged'])

// Реактивные данные
const showFilters = ref(false)
const searchQuery = ref('')
const selectedActivityFilter = ref(null)
const selectedSortFilter = ref('created_desc')
const createdDateRange = ref(null) // Заменяем createdFrom и createdTo на range

// Специальные фильтры для быстрых кнопок
const ownerFilter = ref(null) // 'own' | 'shared' | null
const quickDateFilter = ref(null) // 'last_week' | 'last_month_changes' | 'last_week_changes' | 'last_day_changes' | null

// Опции фильтров
const activityOptions = ref([
  {label: 'Только активные', value: 'active'},
  {label: 'Только неактивные', value: 'inactive'}
])

const sortOptions = ref([
  {label: 'По дате создания (новые)', value: 'created_desc'},
  {label: 'По дате создания (старые)', value: 'created_asc'},
  {label: 'По дате обновления (новые)', value: 'updated_desc'},
  {label: 'По дате обновления (старые)', value: 'updated_asc'},
  {label: 'По названию (А-Я)', value: 'name_asc'},
  {label: 'По названию (Я-А)', value: 'name_desc'},
  {label: 'По количеству записей (больше)', value: 'records_desc'},
  {label: 'По количеству записей (меньше)', value: 'records_asc'}
])

// Вычисляемые свойства для извлечения дат из range
const createdFrom = computed(() => {
  if (createdDateRange.value && Array.isArray(createdDateRange.value) && createdDateRange.value[0]) {
    return createdDateRange.value[0]
  }
  return null
})

const createdTo = computed(() => {
  if (createdDateRange.value && Array.isArray(createdDateRange.value) && createdDateRange.value[1]) {
    return createdDateRange.value[1]
  }
  return null
})

// Вычисляемые свойства
const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value ||
    selectedActivityFilter.value ||
    selectedSortFilter.value !== 'created_desc' ||
    createdDateRange.value ||
    ownerFilter.value ||
    quickDateFilter.value
  )
})

const isOwnerFilter = computed(() => ownerFilter.value === 'own')
const isSharedFilter = computed(() => ownerFilter.value === 'shared')
const isLastWeekFilter = computed(() => quickDateFilter.value === 'last_week')
const isLastMonthChangesFilter = computed(() => quickDateFilter.value === 'last_month_changes')
const isLastWeekChangesFilter = computed(() => quickDateFilter.value === 'last_week_changes')
const isLastDayChangesFilter = computed(() => quickDateFilter.value === 'last_day_changes')

const activeFilterTags = computed(() => {
  const tags = []
  
  if (selectedActivityFilter.value) {
    const activity = activityOptions.value.find(a => a.value === selectedActivityFilter.value)
    if (activity) {
      tags.push({key: 'activity', label: activity.label})
    }
  }
  
  if (selectedSortFilter.value && selectedSortFilter.value !== 'created_desc') {
    const sort = sortOptions.value.find(s => s.value === selectedSortFilter.value)
    if (sort) {
      tags.push({key: 'sort', label: `Сортировка: ${sort.label}`})
    }
  }
  
  // Объединенный тег для диапазона дат
  if (createdDateRange.value && Array.isArray(createdDateRange.value)) {
    const fromDate = createdDateRange.value[0]
    const toDate = createdDateRange.value[1]
    
    if (fromDate && toDate) {
      tags.push({
        key: 'createdRange',
        label: `Создано: ${formatDate(fromDate)} - ${formatDate(toDate)}`
      })
    } else if (fromDate) {
      tags.push({
        key: 'createdRange',
        label: `Создано с: ${formatDate(fromDate)}`
      })
    }
  }
  
  if (ownerFilter.value) {
    const ownershipLabels = {own: 'Мои таблицы', shared: 'Общие таблицы'}
    tags.push({key: 'ownerQuick', label: ownershipLabels[ownerFilter.value]})
  }
  
  if (quickDateFilter.value) {
    const dateLabels = {
      last_week: 'За последнюю неделю',
      last_month_changes: 'Изменения за месяц',
      last_week_changes: 'Изменения за неделю',
      last_day_changes: 'Изменения за день'
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
    case 'my_grids':
      if (ownerFilter.value === 'own') {
        ownerFilter.value = null
      } else {
        ownerFilter.value = 'own'
      }
      break
    
    case 'shared_grids':
      if (ownerFilter.value === 'shared') {
        ownerFilter.value = null
      } else {
        ownerFilter.value = 'shared'
      }
      break
    
    case 'last_week':
      if (quickDateFilter.value === 'last_week') {
        quickDateFilter.value = null
        createdDateRange.value = null
      } else {
        quickDateFilter.value = 'last_week'
        const weekAgo = new Date()
        weekAgo.setDate(weekAgo.getDate() - 7)
        createdDateRange.value = [weekAgo, new Date()]
      }
      break
    
    case 'last_month_changes':
    case 'last_week_changes':
    case 'last_day_changes':
      if (quickDateFilter.value === filterType) {
        quickDateFilter.value = null
      } else {
        quickDateFilter.value = filterType
      }
      break
  }
}

const removeFilter = (filterKey) => {
  switch (filterKey) {
    case 'activity':
      selectedActivityFilter.value = null
      break
    case 'sort':
      selectedSortFilter.value = 'created_desc'
      break
    case 'createdRange':
      createdDateRange.value = null
      if (quickDateFilter.value === 'last_week') {
        quickDateFilter.value = null
      }
      break
    case 'ownerQuick':
      ownerFilter.value = null
      break
    case 'quickDate':
      quickDateFilter.value = null
      if (quickDateFilter.value === 'last_week') {
        createdDateRange.value = null
      }
      break
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedActivityFilter.value = null
  selectedSortFilter.value = 'created_desc'
  createdDateRange.value = null
  ownerFilter.value = null
  quickDateFilter.value = null
  showFilters.value = false
  emitFilters()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const formatDateForAPI = (date) => {
  return new Date(date).toISOString().split('T')[0]
}

const getTableWord = (count) => {
  if (count % 10 === 1 && count % 100 !== 11) {
    return 'таблица'
  } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
    return 'таблицы'
  } else {
    return 'таблиц'
  }
}

const emitFilters = () => {
  const filters = {
    search: searchQuery.value || null,
    ownership: ownerFilter.value || null,
    activity: selectedActivityFilter.value || null,
    sort: selectedSortFilter.value === 'created_desc' ? null : selectedSortFilter.value,
    // Разбиваем range обратно на отдельные параметры для совместимости с backend
    created_from: createdFrom.value ? formatDateForAPI(createdFrom.value) : null,
    created_to: createdTo.value ? formatDateForAPI(createdTo.value) : null
  }
  
  emit('filtersChanged', filters)
}

// Watchers
watch(searchQuery, () => {
  debouncedEmitFilters()
})

watch([
  selectedActivityFilter,
  selectedSortFilter,
  createdDateRange, // Заменяем createdFrom, createdTo на createdDateRange
  ownerFilter,
  quickDateFilter
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

/* Стили для range date picker */
:deep(.p-datepicker-input-icon-container) {
  right: 8px;
}

:deep(.p-datepicker-input) {
  padding-right: 2.5rem;
}

/* Улучшаем внешний вид range picker */
:deep(.p-datepicker-range .p-datepicker-input) {
  text-align: center;
}
</style>