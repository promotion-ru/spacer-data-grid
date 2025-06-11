<template>
  <Dialog
    v-model:visible="visible"
    :dismissableMask="true"
    :style="{ width: '95vw', maxWidth: '1200px' }"
    header="История изменений"
    modal
  >
    <div class="activity-logs-modal">
      <!-- Поиск и кнопка фильтров -->
      <div class="mb-4 space-y-3">
        <!-- Верхняя строка: поиск + кнопка фильтров -->
        <div class="flex items-center gap-3">
          <div class="flex-1">
            <div class="relative">
              <InputText
                v-model="searchQuery"
                :loading="loading"
                class="w-full pl-20 input-search"
                placeholder="Поиск по описанию, пользователю..."
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
        
        <!-- Сворачиваемые фильтры -->
        <div v-show="showFilters" class="filters-container">
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
            <div class="text-sm font-medium text-gray-700 mb-3 flex items-center">
              <i class="pi pi-sliders-h mr-2"></i>
              Дополнительные фильтры
            </div>
            
            <!-- Основные фильтры -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Конкретное действие</label>
                <Select
                  v-model="selectedActionFilter"
                  :options="actionFilterOptions"
                  class="w-full"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Выберите действие"
                  showClear
                />
              </div>
              
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Тип действий</label>
                <Select
                  v-model="selectedActionTypeFilter"
                  :options="actionTypeOptions"
                  class="w-full"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Выберите тип"
                  showClear
                />
              </div>
              
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Измененное поле</label>
                <Select
                  v-model="selectedFieldFilter"
                  :loading="loading"
                  :options="availableFields"
                  class="w-full"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Выберите поле"
                  showClear
                />
              </div>
              
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Дата от</label>
                <DatePicker
                  v-model="dateFrom"
                  class="w-full"
                  dateFormat="dd.mm.yy"
                  placeholder="Выберите дату"
                  showButtonBar
                  showIcon
                />
              </div>
              
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Дата до</label>
                <DatePicker
                  v-model="dateTo"
                  class="w-full"
                  dateFormat="dd.mm.yy"
                  placeholder="Выберите дату"
                  showButtonBar
                  showIcon
                />
              </div>
            </div>
            
            <!-- Быстрые фильтры -->
            <div class="border-t pt-3">
              <div class="text-xs font-medium text-gray-600 mb-2">Быстрые фильтры:</div>
              <div class="flex flex-wrap gap-2">
                <Button
                  :outlined="selectedActionTypeFilter !== 'grid'"
                  label="Только изменения таблицы"
                  size="small"
                  @click="applyQuickFilter('grid')"
                />
                <Button
                  :outlined="selectedActionTypeFilter !== 'members'"
                  label="Действия с участниками"
                  size="small"
                  @click="applyQuickFilter('members')"
                />
                <Button
                  :outlined="selectedActionTypeFilter !== 'invitations'"
                  label="Приглашения"
                  size="small"
                  @click="applyQuickFilter('invitations')"
                />
                <Button
                  :outlined="!isLastWeekFilter"
                  label="За последнюю неделю"
                  size="small"
                  @click="applyQuickFilter('last_week')"
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
        <div v-if="pagination" class="text-sm text-gray-600 flex items-center justify-between">
          <span>
            Показано {{ logs.length }} из {{ pagination.total }} записей
            <span v-if="hasActiveFilters" class="ml-2 text-blue-600">
              (применены фильтры)
            </span>
          </span>
          <Button
            v-if="pagination.total > pagination.per_page"
            :label="`Показать по ${pagination.per_page === 50 ? '100' : '50'}`"
            class="p-button-text"
            size="small"
            @click="togglePerPage"
          />
        </div>
      </div>
      
      <!-- Загрузка -->
      <div v-if="loading" class="flex justify-center py-8">
        <ProgressSpinner/>
      </div>
      
      <!-- Список логов -->
      <div v-else-if="logs?.length" class="space-y-4">
        <!-- Контейнер с прокруткой -->
        <div class="max-h-96 overflow-y-auto space-y-4">
          <div
            v-for="log in logs"
            :key="log.id"
            class="border border-gray-200 rounded-lg p-4 bg-white hover:bg-gray-50 transition-colors"
          >
            <!-- Заголовок лога -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center space-x-3">
                <Badge
                  :severity="log.action_badge.severity"
                  :value="log.action_badge.text"
                />
                <span class="font-medium text-gray-900">{{ log.description }}</span>
              </div>
              <div class="text-sm text-gray-500">
                {{ log.created_at }}
              </div>
            </div>
            
            <!-- Информация о пользователях -->
            <div class="mb-3 space-y-1">
              <div class="text-sm text-gray-600">
                <i class="pi pi-user mr-1"></i>
                Пользователь: <span class="font-medium">{{ log.user_name }}</span>
              </div>
              <div v-if="log.target_user" class="text-sm text-gray-600">
                <i class="pi pi-arrow-right mr-1"></i>
                Целевой пользователь:
                <span class="font-medium">{{ log.target_user.name }}</span>
                <span v-if="log.target_user.email" class="text-gray-500 ml-1">({{ log.target_user.email }})</span>
              </div>
            </div>
            
            <!-- Изменения -->
            <div v-if="log.changes?.length" class="space-y-2">
              <h4 class="text-sm font-medium text-gray-700 mb-2">Изменения:</h4>
              <div class="bg-gray-50 rounded-md p-3 space-y-2">
                <div
                  v-for="change in log.changes"
                  :key="change.field"
                  class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm"
                >
                  <span
                    :class="{ 'text-blue-600': selectedFieldFilter && change.field.toLowerCase().includes(getFieldKeyByValue(selectedFieldFilter)?.toLowerCase()) }"
                    class="font-medium text-gray-700"
                  >
                    {{ change.field }}:
                  </span>
                  <div class="flex items-center space-x-2 mt-1 sm:mt-0">
                    <span :title="change.old_value"
                          class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs max-w-32 truncate">
                      {{ change.old_value }}
                    </span>
                    <i class="pi pi-arrow-right text-gray-400 text-xs"></i>
                    <span :title="change.new_value"
                          class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs max-w-32 truncate">
                      {{ change.new_value }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Метаданные -->
            <div v-if="log.metadata && Object.keys(log.metadata).length > 0" class="mt-3">
              <details class="text-sm">
                <summary class="cursor-pointer text-gray-600 hover:text-gray-800">
                  Дополнительная информация
                </summary>
                <div class="mt-2 bg-gray-50 rounded p-2 text-xs">
                  <pre class="whitespace-pre-wrap">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                </div>
              </details>
            </div>
          </div>
        </div>
        
        <!-- Пагинация -->
        <div v-if="pagination && pagination.last_page > 1" class="flex justify-center">
          <Paginator
            v-model:first="currentPageFirst"
            :rows="pagination.per_page"
            :template="{
              '640px': 'PrevPageLink CurrentPageReport NextPageLink',
              '960px': 'FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink',
              '1300px': 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink JumpToPageDropdown'
            }"
            :totalRecords="pagination.total"
            @page="onPageChange"
          />
        </div>
      </div>
      
      <!-- Пустое состояние -->
      <div v-else class="text-center py-8">
        <i class="pi pi-history text-4xl text-gray-300 mb-3"></i>
        <h3 class="text-lg font-medium text-gray-700 mb-1">
          {{ hasActiveFilters ? 'Нет записей' : 'История пуста' }}
        </h3>
        <p class="text-gray-500">
          {{
            hasActiveFilters
              ? 'Не найдено записей с выбранными фильтрами'
              : 'Пока нет записей об изменениях этой таблицы'
          }}
        </p>
        <Button
          v-if="hasActiveFilters"
          class="p-button-text mt-3"
          icon="pi pi-times"
          label="Сбросить фильтры"
          @click="resetFilters"
        />
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500">
          {{ logs.length }} записей загружено
        </div>
        <Button
          class="p-button-text"
          icon="pi pi-times"
          label="Закрыть"
          @click="closeModal"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import {debounce} from 'lodash-es'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  grid: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:visible'])

const {$api} = useNuxtApp()
const toast = useToast()

// Реактивные данные
const logs = ref([])
const loading = ref(false)
const showFilters = ref(false)
const searchQuery = ref('')
const selectedActionFilter = ref(null)
const selectedActionTypeFilter = ref(null)
const selectedFieldFilter = ref(null)
const dateFrom = ref(null)
const dateTo = ref(null)
const availableFields = ref([])
const actionTypeOptions = ref([])
const pagination = ref(null)
const currentPage = ref(1)

// Флаги для предотвращения множественных запросов
const isInitializing = ref(false)
const watchersEnabled = ref(true)
const lastFetchParams = ref('')

// Опции фильтров
const actionFilterOptions = ref([
  {label: 'Создание', value: 'created'},
  {label: 'Обновление', value: 'updated'},
  {label: 'Удаление', value: 'deleted'},
  {label: 'Участник добавлен', value: 'member_added'},
  {label: 'Участник удален', value: 'member_removed'},
  {label: 'Права изменены', value: 'member_updated'},
  {label: 'Покинул таблицу', value: 'member_left'},
  {label: 'Приглашение отправлено', value: 'invitation_sent'},
  {label: 'Приглашение принято', value: 'invitation_accepted'},
  {label: 'Приглашение отклонено', value: 'invitation_declined'},
  {label: 'Приглашение отменено', value: 'invitation_cancelled'},
])

// Вычисляемые свойства
const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || selectedActionFilter.value || selectedActionTypeFilter.value || selectedFieldFilter.value || dateFrom.value || dateTo.value)
})

const isLastWeekFilter = computed(() => {
  if (!dateFrom.value) return false
  const weekAgo = new Date()
  weekAgo.setDate(weekAgo.getDate() - 7)
  const fromDate = new Date(dateFrom.value)
  return Math.abs(fromDate.getTime() - weekAgo.getTime()) < 24 * 60 * 60 * 1000 // разница меньше суток
})

const activeFilterTags = computed(() => {
  const tags = []
  
  if (selectedActionFilter.value) {
    const action = actionFilterOptions.value.find(a => a.value === selectedActionFilter.value)
    if (action) {
      tags.push({key: 'action', label: `Действие: ${action.label}`})
    }
  }
  
  if (selectedActionTypeFilter.value) {
    const type = actionTypeOptions.value.find(t => t.value === selectedActionTypeFilter.value)
    if (type) {
      tags.push({key: 'actionType', label: `Тип: ${type.label}`})
    }
  }
  
  if (selectedFieldFilter.value) {
    const field = availableFields.value.find(f => f.value === selectedFieldFilter.value)
    if (field) {
      tags.push({key: 'field', label: `Поле: ${field.label}`})
    }
  }
  
  if (dateFrom.value) {
    tags.push({key: 'dateFrom', label: `С: ${formatDate(dateFrom.value)}`})
  }
  
  if (dateTo.value) {
    tags.push({key: 'dateTo', label: `До: ${formatDate(dateTo.value)}`})
  }
  
  return tags
})

const currentPageFirst = computed({
  get: () => (currentPage.value - 1) * (pagination.value?.per_page || 50),
  set: (value) => {
    currentPage.value = Math.floor(value / (pagination.value?.per_page || 50)) + 1
  }
})

// Генерация уникального ключа для параметров запроса
const generateFetchKey = () => {
  const params = {
    search: searchQuery.value,
    action: selectedActionFilter.value,
    actionType: selectedActionTypeFilter.value,
    field: selectedFieldFilter.value,
    dateFrom: dateFrom.value ? formatDateForAPI(dateFrom.value) : null,
    dateTo: dateTo.value ? formatDateForAPI(dateTo.value) : null,
    page: currentPage.value,
    perPage: pagination.value?.per_page || 50,
    gridId: props.grid?.id
  }
  return JSON.stringify(params)
}

// Основная функция загрузки данных
const fetchLogs = async () => {
  if (!props.grid?.id) {
    return
  }
  
  // Проверяем, не выполняется ли уже запрос с такими же параметрами
  const currentFetchKey = generateFetchKey()
  if (loading.value && lastFetchParams.value === currentFetchKey) {
    console.log('Duplicate request prevented')
    return
  }
  
  loading.value = true
  lastFetchParams.value = currentFetchKey
  
  try {
    const params = new URLSearchParams()
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    if (selectedActionFilter.value) {
      params.append('action', selectedActionFilter.value)
    }
    
    if (selectedActionTypeFilter.value) {
      params.append('action_type', selectedActionTypeFilter.value)
    }
    
    if (selectedFieldFilter.value) {
      params.append('changed_field', selectedFieldFilter.value)
    }
    
    if (dateFrom.value) {
      params.append('date_from', formatDateForAPI(dateFrom.value))
    }
    
    if (dateTo.value) {
      params.append('date_to', formatDateForAPI(dateTo.value))
    }
    
    params.append('page', currentPage.value.toString())
    params.append('per_page', pagination.value?.per_page?.toString() || '50')
    
    const queryString = params.toString()
    const url = `/data-grid/${props.grid.id}/logs${queryString ? '?' + queryString : ''}`
    
    const response = await $api(url)
    logs.value = response.data
    pagination.value = response.pagination
    
    // Обновляем доступные фильтры
    if (response.filters) {
      availableFields.value = response.filters.available_fields || []
      actionTypeOptions.value = response.filters.action_types || []
    }
  } catch (error) {
    console.error('Ошибка загрузки логов:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить историю изменений',
      life: 3000
    })
  } finally {
    loading.value = false
    lastFetchParams.value = ''
  }
}

// Debounced поиск (только для поискового запроса)
const debouncedFetchLogs = debounce(() => {
  if (!isInitializing.value && watchersEnabled.value) {
    currentPage.value = 1
    fetchLogs()
  }
}, 500)

// Обычная загрузка (для фильтров)
const fetchLogsNormal = () => {
  if (!isInitializing.value && watchersEnabled.value) {
    currentPage.value = 1
    fetchLogs()
  }
}

// Методы
const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const applyQuickFilter = (filterType) => {
  // Временно отключаем watchers
  watchersEnabled.value = false
  
  switch (filterType) {
    case 'grid':
    case 'members':
    case 'invitations':
      selectedActionTypeFilter.value = selectedActionTypeFilter.value === filterType ? null : filterType
      break
    case 'last_week':
      if (isLastWeekFilter.value) {
        dateFrom.value = null
        dateTo.value = null
      } else {
        const weekAgo = new Date()
        weekAgo.setDate(weekAgo.getDate() - 7)
        dateFrom.value = weekAgo
        dateTo.value = new Date()
      }
      break
  }
  
  // Включаем watchers обратно и загружаем данные
  nextTick(() => {
    watchersEnabled.value = true
    currentPage.value = 1
    fetchLogs()
  })
}

const removeFilter = (filterKey) => {
  // Временно отключаем watchers
  watchersEnabled.value = false
  
  switch (filterKey) {
    case 'action':
      selectedActionFilter.value = null
      break
    case 'actionType':
      selectedActionTypeFilter.value = null
      break
    case 'field':
      selectedFieldFilter.value = null
      break
    case 'dateFrom':
      dateFrom.value = null
      break
    case 'dateTo':
      dateTo.value = null
      break
  }
  
  // Включаем watchers обратно и загружаем данные
  nextTick(() => {
    watchersEnabled.value = true
    currentPage.value = 1
    fetchLogs()
  })
}

const getFieldKeyByValue = (value) => {
  const fieldItem = availableFields.value.find(f => f.value === value)
  return fieldItem ? fieldItem.value : value
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const formatDateForAPI = (date) => {
  return new Date(date).toISOString().split('T')[0]
}

const togglePerPage = () => {
  if (!pagination.value) return
  
  const newPerPage = pagination.value.per_page === 50 ? 100 : 50
  pagination.value.per_page = newPerPage
  currentPage.value = 1
  fetchLogs()
}

const resetFilters = () => {
  // Отключаем watchers перед сбросом
  watchersEnabled.value = false
  
  searchQuery.value = ''
  selectedActionFilter.value = null
  selectedActionTypeFilter.value = null
  selectedFieldFilter.value = null
  dateFrom.value = null
  dateTo.value = null
  currentPage.value = 1
  
  // Включаем watchers и загружаем данные только если не в процессе инициализации
  nextTick(() => {
    watchersEnabled.value = true
    if (!isInitializing.value) {
      fetchLogs()
    }
  })
}

const closeModal = () => {
  visible.value = false
  showFilters.value = false
  // При закрытии не загружаем данные
  watchersEnabled.value = false
  resetFilters()
}

const onPageChange = (event) => {
  currentPage.value = event.page + 1
  fetchLogs()
}

// Инициализация модала
const initializeModal = async () => {
  if (!props.grid?.id) return
  
  isInitializing.value = true
  watchersEnabled.value = false
  
  // Сбрасываем состояние
  showFilters.value = false
  logs.value = []
  pagination.value = null
  
  // Сбрасываем фильтры
  searchQuery.value = ''
  selectedActionFilter.value = null
  selectedActionTypeFilter.value = null
  selectedFieldFilter.value = null
  dateFrom.value = null
  dateTo.value = null
  currentPage.value = 1
  
  // Ждем следующий tick для завершения сброса
  await nextTick()
  
  // Включаем watchers и загружаем данные
  watchersEnabled.value = true
  isInitializing.value = false
  
  // Загружаем данные один раз
  fetchLogs()
}

// Watchers с проверкой флагов
watch(() => props.visible, (newValue) => {
  if (newValue && props.grid) {
    initializeModal()
  }
})

watch(() => props.grid, (newGrid) => {
  if (newGrid && props.visible) {
    initializeModal()
  }
})

watch(searchQuery, () => {
  if (watchersEnabled.value && !isInitializing.value) {
    debouncedFetchLogs()
  }
})

watch([selectedActionFilter, selectedActionTypeFilter, selectedFieldFilter, dateFrom, dateTo], () => {
  if (watchersEnabled.value && !isInitializing.value) {
    fetchLogsNormal()
  }
})
</script>

<style scoped>
:deep(.input-search) {
  padding-left: 30px;
}
</style>