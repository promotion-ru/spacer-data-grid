<template>
  <div class="users-filters">
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
              placeholder="Поиск по имени, фамилии, email..."
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
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Статус активности</label>
              <Select
                v-model="selectedActivityFilter"
                :options="activityOptions"
                class="w-full"
                optionLabel="label"
                optionValue="value"
                placeholder="Все пользователи"
                showClear
              />
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Зарегистрирован с</label>
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
              <label class="block text-xs font-medium text-gray-600 mb-1">Зарегистрирован до</label>
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
                placeholder="По дате регистрации"
              />
            </div>
          
          </div>
          
          <!-- Быстрые фильтры -->
          <div class="border-t pt-4">
            <div class="text-xs font-medium text-gray-600 mb-2">Быстрые фильтры:</div>
            <div class="flex flex-wrap gap-2">
              <Button
                :outlined="!isNewUsersFilter"
                :severity="isNewUsersFilter ? 'primary' : 'secondary'"
                label="Новые пользователи"
                size="small"
                @click="applyQuickFilter('new_users')"
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
              <Button
                :outlined="!isRecentlyActiveFilter"
                :severity="isRecentlyActiveFilter ? 'primary' : 'secondary'"
                label="Недавно активные"
                size="small"
                @click="applyQuickFilter('recently_active')"
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
          Найдено {{ totalCount }} {{ getUsersWord(totalCount) }}
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
const createdFrom = ref(null)
const createdTo = ref(null)

// Специальные фильтры для быстрых кнопок
const quickDateFilter = ref(null) // 'new_users' | 'last_week' | 'last_month' | 'recently_active' | null

// Опции фильтров
const activityOptions = ref([
  {label: 'Только активные', value: 'active'},
  {label: 'Только неактивные', value: 'inactive'}
])

// Опции сортировки
const sortOptions = ref([
  {label: 'По дате регистрации (новые)', value: 'created_desc'},
  {label: 'По дате регистрации (старые)', value: 'created_asc'},
  {label: 'По имени (А-Я)', value: 'name_asc'},
  {label: 'По имени (Я-А)', value: 'name_desc'},
  {label: 'По email (А-Я)', value: 'email_asc'},
  {label: 'По email (Я-А)', value: 'email_desc'},
  {label: 'По статусу (активные первые)', value: 'active_desc'},
  {label: 'По статусу (неактивные первые)', value: 'active_asc'},
  {label: 'По дате обновления (новые)', value: 'updated_desc'},
  {label: 'По дате обновления (старые)', value: 'updated_asc'}
])

// Вычисляемые свойства
const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value ||
    selectedActivityFilter.value ||
    selectedSortFilter.value !== 'created_desc' ||
    createdFrom.value ||
    createdTo.value ||
    quickDateFilter.value
  )
})

const isNewUsersFilter = computed(() => quickDateFilter.value === 'new_users')
const isLastWeekFilter = computed(() => quickDateFilter.value === 'last_week')
const isLastMonthFilter = computed(() => quickDateFilter.value === 'last_month')
const isRecentlyActiveFilter = computed(() => quickDateFilter.value === 'recently_active')

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
  
  if (createdFrom.value) {
    tags.push({key: 'createdFrom', label: `Зарегистрирован с: ${formatDate(createdFrom.value)}`})
  }
  
  if (createdTo.value) {
    tags.push({key: 'createdTo', label: `Зарегистрирован до: ${formatDate(createdTo.value)}`})
  }
  
  if (quickDateFilter.value) {
    const dateLabels = {
      new_users: 'Новые пользователи',
      last_week: 'За последнюю неделю',
      last_month: 'За последний месяц',
      recently_active: 'Недавно активные'
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
    case 'new_users':
      if (quickDateFilter.value === 'new_users') {
        quickDateFilter.value = null
        createdFrom.value = null
        createdTo.value = null
      } else {
        quickDateFilter.value = 'new_users'
        const threeDaysAgo = new Date()
        threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
        createdFrom.value = threeDaysAgo
        createdTo.value = new Date()
      }
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
    
    case 'recently_active':
      if (quickDateFilter.value === 'recently_active') {
        quickDateFilter.value = null
      } else {
        quickDateFilter.value = 'recently_active'
        // Для "недавно активные" не устанавливаем даты, это отдельный фильтр
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
    case 'createdFrom':
      createdFrom.value = null
      if (quickDateFilter.value === 'new_users' || quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        quickDateFilter.value = null
      }
      break
    case 'createdTo':
      createdTo.value = null
      if (quickDateFilter.value === 'new_users' || quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        quickDateFilter.value = null
      }
      break
    case 'quickDate':
      quickDateFilter.value = null
      if (quickDateFilter.value === 'new_users' || quickDateFilter.value === 'last_week' || quickDateFilter.value === 'last_month') {
        createdFrom.value = null
        createdTo.value = null
      }
      break
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedActivityFilter.value = null
  selectedSortFilter.value = 'created_desc'
  createdFrom.value = null
  createdTo.value = null
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

const getUsersWord = (count) => {
  if (count % 10 === 1 && count % 100 !== 11) {
    return 'пользователь'
  } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
    return 'пользователя'
  } else {
    return 'пользователей'
  }
}

const emitFilters = () => {
  const filters = {
    search: searchQuery.value || null,
    active: selectedActivityFilter.value || null,
    sort_by: getSortField(selectedSortFilter.value),
    sort_order: getSortOrder(selectedSortFilter.value),
    created_from: createdFrom.value ? formatDateForAPI(createdFrom.value) : null,
    created_to: createdTo.value ? formatDateForAPI(createdTo.value) : null,
    recently_active: quickDateFilter.value === 'recently_active' || null
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
    'email_asc': 'email',
    'email_desc': 'email',
    'active_asc': 'active',
    'active_desc': 'active',
    'updated_asc': 'updated_at',
    'updated_desc': 'updated_at'
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
  selectedActivityFilter,
  selectedSortFilter,
  createdFrom,
  createdTo,
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
</style>