<template>
  <div class="records-filters">
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
              placeholder="Поиск по названию записи, описанию, автору..."
              @input="onSearchInput"
            />
            <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-secondary"></i>
          </div>
        </div>
        <Button
          :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
          :severity="hasActiveFilters ? 'info' : 'secondary'"
          outlined
          @click="toggleFilters"
        />
        <Button
          v-if="hasActiveFilters"
          outlined
          severity="danger"
          icon="pi pi-times"
          label="Сбросить"
          @click="resetFilters"
        />
<!--        <Button-->
<!--          :loading="loading"-->
<!--          class="p-button-outlined"-->
<!--          icon="pi pi-refresh"-->
<!--          label="Обновить"-->
<!--          @click="$emit('refresh')"-->
<!--        />-->
      </div>
      
      <!-- Расширенные фильтры -->
      <div v-show="showFilters" class="filters-container">
        <div class="p-4 space-y-4 rounded-lg filter-container">
          <div class="text-sm font-medium mb-3 flex items-center filter-title text-primary">
            <i class="pi pi-sliders-h mr-2"></i>
            Дополнительные фильтры
          </div>
          
          <!-- Основные фильтры -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Автор записи</label>
              <Select
                v-model="selectedOwnerFilter"
                :options="ownerOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все авторы"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Тип операции</label>
              <Select
                v-model="selectedOperationTypeFilter"
                :options="operationTypeOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все типы операций"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Тип записи</label>
              <Select
                v-model="selectedRecordTypeFilter"
                :options="recordTypeOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все типы записей"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">С вложениями</label>
              <Select
                v-model="selectedAttachmentsFilter"
                :options="attachmentsOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все записи"
                showClear
              />
            </div>
          </div>
          
          <!-- Фильтры по датам и суммам -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Период создания</label>
              <DatePicker
                v-model="createdDateRange"
                class="w-full"
                dateFormat="dd.mm.yy"
                placeholder="Выберите период"
                selectionMode="range"
                showButtonBar
                showIcon
              />
              <div v-if="isDateRangePartial(createdDateRange)" class="text-xs mt-1 text-yellow-600">
                Выберите конечную дату для применения фильтра
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Период операции</label>
              <DatePicker
                v-model="operationDateRange"
                class="w-full"
                dateFormat="dd.mm.yy"
                placeholder="Выберите период"
                selectionMode="range"
                showButtonBar
                showIcon
              />
              <div v-if="isDateRangePartial(operationDateRange)" class="text-xs mt-1 text-yellow-600">
                Выберите конечную дату для применения фильтра
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Диапазон сумм</label>
              <div class="flex gap-2 items-center">
                <InputNumber
                  v-model="amountFrom"
                  :useGrouping="false"
                  class="flex-1"
                  placeholder="От"
                />
                <span class="text-secondary">—</span>
                <InputNumber
                  v-model="amountTo"
                  :useGrouping="false"
                  class="flex-1"
                  placeholder="До"
                />
              </div>
            </div>
          </div>
          
          <!-- Сортировка -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium mb-1 filter-label text-secondary">Сортировка</label>
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
            <div class="text-xs font-medium mb-2 text-secondary">Быстрые фильтры:</div>
            <div class="flex flex-wrap gap-2">
              <Button
                :outlined="!isMyRecordsFilter"
                :severity="isMyRecordsFilter ? 'primary' : 'secondary'"
                label="Мои записи"
                size="small"
                @click="applyQuickFilter('my_records')"
              />
              <Button
                :outlined="!isNotMyRecordsFilter"
                :severity="isNotMyRecordsFilter ? 'primary' : 'secondary'"
                label="Не мои записи"
                size="small"
                @click="applyQuickFilter('not_my_records')"
              />
              <Button
                :outlined="!isChangedTodayFilter"
                :severity="isChangedTodayFilter ? 'primary' : 'secondary'"
                label="Изменённые сегодня"
                size="small"
                @click="applyQuickFilter('changed_today')"
              />
              <Button
                :outlined="!isChangedWeekFilter"
                :severity="isChangedWeekFilter ? 'primary' : 'secondary'"
                label="Изменённые за неделю"
                size="small"
                @click="applyQuickFilter('changed_week')"
              />
              <Button
                :outlined="!isChangedMonthFilter"
                :severity="isChangedMonthFilter ? 'primary' : 'secondary'"
                label="Изменённые за месяц"
                size="small"
                @click="applyQuickFilter('changed_month')"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Активные фильтры -->
      <div v-if="activeFilterTags.length" class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-secondary">Активные фильтры:</span>
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
              class="pi pi-times cursor-pointer text-secondary hover:text-red-500 transition-colors duration-200"
              @click="removeFilter(tag.key)"
            ></i>
          </template>
        </Tag>
      </div>
      
      <!-- Статистика -->
      <div v-if="totalRecords !== null" class="text-sm flex items-center justify-between text-secondary">
        <span>
          Найдено {{ totalRecords }} {{ getRecordsWord(totalRecords) }}
          <span v-if="hasActiveFilters" class="ml-2 text-primary">
            (применены фильтры)
          </span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import {computed, nextTick, onMounted, ref, watch} from 'vue'
import {debounce} from 'lodash-es'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  totalRecords: {
    type: Number,
    default: null
  },
  currentUserId: {
    type: Number,
    default: null
  },
  recordTypes: {
    type: Array,
    default: () => []
  },
  gridId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['filtersChanged', 'refresh'])

// Флаг инициализации для предотвращения преждевременных запросов
const isInitialized = ref(false)

// Реактивные данные
const showFilters = ref(false)
const searchQuery = ref('')
const selectedOwnerFilter = ref(null)
const selectedOperationTypeFilter = ref(null)
const selectedRecordTypeFilter = ref(null)
const selectedSortFilter = ref('created_desc')
const selectedAttachmentsFilter = ref(null)
const createdDateRange = ref(null)
const operationDateRange = ref(null)
const amountFrom = ref(null)
const amountTo = ref(null)

// Специальные фильтры для быстрых кнопок
const quickDateFilter = ref(null)

// Опции фильтров
const ownerOptions = ref([
  {label: 'Мои записи', value: 'my'},
  {label: 'Не мои записи', value: 'not_my'}
])

const operationTypeOptions = computed(() => [
  {label: 'Доход', value: 1},
  {label: 'Расход', value: 2},
])

const recordTypeOptions = computed(() =>
  props.recordTypes.map(type => ({
    label: type.name,
    value: type.id
  }))
)

const sortOptions = ref([
  {label: 'По дате создания (новые)', value: 'created_desc'},
  {label: 'По дате создания (старые)', value: 'created_asc'},
  {label: 'По названию (А-Я)', value: 'name_asc'},
  {label: 'По названию (Я-А)', value: 'name_desc'},
  {label: 'По дате операции (новые)', value: 'date_desc'},
  {label: 'По дате операции (старые)', value: 'date_asc'},
  {label: 'По сумме (по возрастанию)', value: 'amount_asc'},
  {label: 'По сумме (по убыванию)', value: 'amount_desc'},
  {label: 'По автору (А-Я)', value: 'creator_asc'},
  {label: 'По автору (Я-А)', value: 'creator_desc'}
])

const attachmentsOptions = ref([
  {label: 'Только с вложениями', value: 'with'},
  {label: 'Только без вложений', value: 'without'}
])

// Вспомогательные функции для проверки полноты диапазонов
const isDateRangeComplete = (range) => {
  return range && Array.isArray(range) && range.length === 2 && range[0] && range[1]
}

const isDateRangePartial = (range) => {
  return range && Array.isArray(range) && range.length >= 1 && range[0] && !range[1]
}

// Вычисляемые свойства
const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value ||
    selectedOwnerFilter.value ||
    selectedOperationTypeFilter.value ||
    selectedRecordTypeFilter.value ||
    selectedSortFilter.value !== 'created_desc' ||
    selectedAttachmentsFilter.value ||
    isDateRangeComplete(createdDateRange.value) ||
    isDateRangeComplete(operationDateRange.value) ||
    (amountFrom.value !== null && amountTo.value !== null) ||
    quickDateFilter.value
  )
})

const isMyRecordsFilter = computed(() => selectedOwnerFilter.value === 'my')
const isNotMyRecordsFilter = computed(() => selectedOwnerFilter.value === 'not_my')
const isChangedTodayFilter = computed(() => quickDateFilter.value === 'changed_today')
const isChangedWeekFilter = computed(() => quickDateFilter.value === 'changed_week')
const isChangedMonthFilter = computed(() => quickDateFilter.value === 'changed_month')

const activeFilterTags = computed(() => {
  const tags = []
  
  try {
    if (selectedOwnerFilter.value) {
      const owner = ownerOptions.value.find(o => o.value === selectedOwnerFilter.value)
      if (owner) {
        tags.push({key: 'owner', label: owner.label})
      }
    }
    
    if (selectedOperationTypeFilter.value) {
      const operationType = operationTypeOptions.value.find(t => t.value === selectedOperationTypeFilter.value)
      if (operationType) {
        tags.push({key: 'operationType', label: `Тип операции: ${operationType.label}`})
      }
    }
    
    if (selectedRecordTypeFilter.value) {
      const recordType = recordTypeOptions.value.find(t => t.value === selectedRecordTypeFilter.value)
      if (recordType) {
        tags.push({key: 'recordType', label: `Тип записи: ${recordType.label}`})
      }
    }
    
    if (selectedSortFilter.value && selectedSortFilter.value !== 'created_desc') {
      const sort = sortOptions.value.find(s => s.value === selectedSortFilter.value)
      if (sort) {
        tags.push({key: 'sort', label: `Сортировка: ${sort.label}`})
      }
    }
    
    if (selectedAttachmentsFilter.value) {
      const attachment = attachmentsOptions.value.find(a => a.value === selectedAttachmentsFilter.value)
      if (attachment) {
        tags.push({key: 'attachments', label: attachment.label})
      }
    }
    
    // Показываем тег только если выбран полный диапазон (оба значения не null)
    if (isDateRangeComplete(createdDateRange.value)) {
      tags.push({
        key: 'createdDate',
        label: `Период создания: ${formatDate(createdDateRange.value[0])} - ${formatDate(createdDateRange.value[1])}`
      })
    }
    
    if (isDateRangeComplete(operationDateRange.value)) {
      tags.push({
        key: 'operationDate',
        label: `Период операции: ${formatDate(operationDateRange.value[0])} - ${formatDate(operationDateRange.value[1])}`
      })
    }
    
    if (amountFrom.value !== null && amountTo.value !== null) {
      tags.push({key: 'amount', label: `Сумма: ${amountFrom.value} - ${amountTo.value}`})
    } else if (amountFrom.value !== null) {
      tags.push({key: 'amount', label: `Сумма от: ${amountFrom.value}`})
    } else if (amountTo.value !== null) {
      tags.push({key: 'amount', label: `Сумма до: ${amountTo.value}`})
    }
    
    if (quickDateFilter.value) {
      const dateLabels = {
        my_records: 'Мои записи',
        not_my_records: 'Не мои записи',
        changed_today: 'Изменённые сегодня',
        changed_week: 'Изменённые за неделю',
        changed_month: 'Изменённые за месяц'
      }
      // Не показываем дублирующий тег если уже есть основной фильтр
      if (!(['my_records', 'not_my_records'].includes(quickDateFilter.value) && selectedOwnerFilter.value)) {
        tags.push({key: 'quickDate', label: dateLabels[quickDateFilter.value]})
      }
    }
  } catch (error) {
    console.warn('Ошибка при формировании тегов фильтров:', error)
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
    case 'my_records':
      if (selectedOwnerFilter.value === 'my') {
        selectedOwnerFilter.value = null
        quickDateFilter.value = null
      } else {
        selectedOwnerFilter.value = 'my'
        quickDateFilter.value = 'my_records'
      }
      break
    
    case 'not_my_records':
      if (selectedOwnerFilter.value === 'not_my') {
        selectedOwnerFilter.value = null
        quickDateFilter.value = null
      } else {
        selectedOwnerFilter.value = 'not_my'
        quickDateFilter.value = 'not_my_records'
      }
      break
    
    case 'changed_today':
      if (isChangedTodayFilter.value) {
        // Сначала сбрасываем quickDateFilter, потом createdDateRange
        quickDateFilter.value = null
        nextTick(() => {
          createdDateRange.value = null
        })
      } else {
        quickDateFilter.value = 'changed_today'
        const today = new Date()
        const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate())
        const endOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59)
        createdDateRange.value = [startOfDay, endOfDay]
      }
      break
    
    case 'changed_week':
      if (isChangedWeekFilter.value) {
        // Сначала сбрасываем quickDateFilter, потом createdDateRange
        quickDateFilter.value = null
        nextTick(() => {
          createdDateRange.value = null
        })
      } else {
        quickDateFilter.value = 'changed_week'
        const weekAgo = new Date()
        weekAgo.setDate(weekAgo.getDate() - 7)
        createdDateRange.value = [weekAgo, new Date()]
      }
      break
    
    case 'changed_month':
      if (isChangedMonthFilter.value) {
        // Сначала сбрасываем quickDateFilter, потом createdDateRange
        quickDateFilter.value = null
        nextTick(() => {
          createdDateRange.value = null
        })
      } else {
        quickDateFilter.value = 'changed_month'
        const monthAgo = new Date()
        monthAgo.setMonth(monthAgo.getMonth() - 1)
        createdDateRange.value = [monthAgo, new Date()]
      }
      break
  }
}

const removeFilter = (filterKey) => {
  switch (filterKey) {
    case 'owner':
      selectedOwnerFilter.value = null
      // Сбрасываем связанный быстрый фильтр
      if (['my_records', 'not_my_records'].includes(quickDateFilter.value)) {
        quickDateFilter.value = null
      }
      break
    case 'operationType':
      selectedOperationTypeFilter.value = null
      break
    case 'recordType':
      selectedRecordTypeFilter.value = null
      break
    case 'sort':
      selectedSortFilter.value = 'created_desc'
      break
    case 'attachments':
      selectedAttachmentsFilter.value = null
      break
    case 'createdDate':
      // Сначала сбрасываем связанные быстрые фильтры, потом дату
      if (['changed_today', 'changed_week', 'changed_month'].includes(quickDateFilter.value)) {
        quickDateFilter.value = null
      }
      nextTick(() => {
        createdDateRange.value = null
      })
      break
    case 'operationDate':
      operationDateRange.value = null
      break
    case 'amount':
      amountFrom.value = null
      amountTo.value = null
      break
    case 'quickDate':
      const currentQuickFilter = quickDateFilter.value
      quickDateFilter.value = null
      
      // Сбрасываем связанные основные фильтры после небольшой задержки
      nextTick(() => {
        if (['changed_today', 'changed_week', 'changed_month'].includes(currentQuickFilter)) {
          createdDateRange.value = null
        }
        if (['my_records', 'not_my_records'].includes(currentQuickFilter)) {
          selectedOwnerFilter.value = null
        }
      })
      break
  }
}

const resetFilters = () => {
  // Сбрасываем все фильтры в правильном порядке
  searchQuery.value = ''
  selectedOwnerFilter.value = null
  selectedOperationTypeFilter.value = null
  selectedRecordTypeFilter.value = null
  selectedSortFilter.value = 'created_desc'
  selectedAttachmentsFilter.value = null
  amountFrom.value = null
  amountTo.value = null
  showFilters.value = false
  
  // Сначала сбрасываем quickDateFilter, потом даты
  quickDateFilter.value = null
  nextTick(() => {
    createdDateRange.value = null
    operationDateRange.value = null
    // После сброса всех значений вызываем emitFilters
    nextTick(() => {
      emitFilters()
    })
  })
}

const onSearchInput = () => {
  debouncedEmitFilters()
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU')
}

const formatDateForAPI = (date) => {
  if (!date) return null
  return new Date(date).toISOString().split('T')[0]
}

const getRecordsWord = (count) => {
  if (count % 10 === 1 && count % 100 !== 11) {
    return 'запись'
  } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
    return 'записи'
  } else {
    return 'записей'
  }
}

const getSortField = (sortValue) => {
  if (!sortValue || sortValue === 'created_desc' || sortValue === 'created_asc') {
    return 'created_at'
  }
  
  const fieldMap = {
    'name_asc': 'name',
    'name_desc': 'name',
    'date_asc': 'date',
    'date_desc': 'date',
    'amount_asc': 'amount',
    'amount_desc': 'amount',
    'creator_asc': 'creator',
    'creator_desc': 'creator'
  }
  
  return fieldMap[sortValue] || 'created_at'
}

const getSortOrder = (sortValue) => {
  if (!sortValue) return 'desc'
  return sortValue.endsWith('_desc') ? 'desc' : 'asc'
}

const emitFilters = () => {
  // Не эмитим события до инициализации и если нет gridId
  if (!isInitialized.value || !props.gridId) {
    return
  }
  
  const filters = {
    search: searchQuery.value || null,
    owner: selectedOwnerFilter.value || null,
    operation_type_id: selectedOperationTypeFilter.value || null,
    type_id: selectedRecordTypeFilter.value || null,
    sort_by: getSortField(selectedSortFilter.value),
    sort_order: getSortOrder(selectedSortFilter.value),
    with_attachments: selectedAttachmentsFilter.value || null,
    // Фильтрация по датам только если выбран полный диапазон (оба значения не null)
    created_from: isDateRangeComplete(createdDateRange.value) ? formatDateForAPI(createdDateRange.value[0]) : null,
    created_to: isDateRangeComplete(createdDateRange.value) ? formatDateForAPI(createdDateRange.value[1]) : null,
    operation_date_from: isDateRangeComplete(operationDateRange.value) ? formatDateForAPI(operationDateRange.value[0]) : null,
    operation_date_to: isDateRangeComplete(operationDateRange.value) ? formatDateForAPI(operationDateRange.value[1]) : null,
    amount_from: amountFrom.value !== null ? amountFrom.value : null,
    amount_to: amountTo.value !== null ? amountTo.value : null,
    current_user_id: props.currentUserId
  }
  
  emit('filtersChanged', filters)
}

// Публичный метод для инициализации (вызывается из родительского компонента)
const initializeFilters = () => {
  isInitialized.value = true
  nextTick(() => {
    emitFilters()
  })
}

// Watchers
watch([
  selectedOwnerFilter,
  selectedOperationTypeFilter,
  selectedRecordTypeFilter,
  selectedSortFilter,
  selectedAttachmentsFilter,
  amountFrom,
  amountTo,
  quickDateFilter
], () => {
  if (isInitialized.value) {
    nextTick(() => {
      emitFilters()
    })
  }
}, {deep: true})

// Отдельные watcher'ы для диапазонов дат с проверкой полноты
watch(createdDateRange, (newRange, oldRange) => {
  if (!isInitialized.value) return
  
  // Эмитим только если:
  // 1. Диапазон полный (обе даты выбраны)
  // 2. Или диапазон был сброшен (стал null)
  const isNewComplete = isDateRangeComplete(newRange)
  const wasOldComplete = isDateRangeComplete(oldRange)
  const isReset = newRange === null && oldRange !== null
  
  if (isNewComplete || isReset || (wasOldComplete && !isNewComplete)) {
    nextTick(() => {
      emitFilters()
    })
  }
}, {deep: true})

watch(operationDateRange, (newRange, oldRange) => {
  if (!isInitialized.value) return
  
  // Эмитим только если:
  // 1. Диапазон полный (обе даты выбраны)
  // 2. Или диапазон был сброшен (стал null)
  const isNewComplete = isDateRangeComplete(newRange)
  const wasOldComplete = isDateRangeComplete(oldRange)
  const isReset = newRange === null && oldRange !== null
  
  if (isNewComplete || isReset || (wasOldComplete && !isNewComplete)) {
    nextTick(() => {
      emitFilters()
    })
  }
}, {deep: true})

// Следим за изменением gridId для повторной инициализации
watch(() => props.gridId, (newGridId) => {
  if (newGridId && !isInitialized.value) {
    initializeFilters()
  }
})

// НЕ делаем автоматическую инициализацию в onMounted
onMounted(() => {
  // Инициализируем только если уже есть gridId
  if (props.gridId) {
    initializeFilters()
  }
})

// Экспорт методов для родительского компонента
defineExpose({
  resetFilters,
  initializeFilters
})
</script>

<style scoped>
:deep(.input-search) {
  padding-left: 2.5rem;
}

.filters-container {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 500px;
  }
}
</style>