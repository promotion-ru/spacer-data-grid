<template>
  <div class="universal-datatable">
    <!-- Заголовок и кнопка добавления -->
    <div class="flex justify-between items-center mb-6">
      <h1 v-if="title" :style="{ color: 'var(--text-primary)' }" class="text-3xl font-bold">
        {{ title }}
      </h1>
      <slot name="header-actions">
        <Button
          v-if="showAddButton"
          :icon="addButtonIcon"
          :label="addButtonLabel"
          class="p-button-success"
          @click="$emit('add-clicked')"
        />
      </slot>
    </div>
    
    <!-- Слот для фильтров -->
    <slot
      :loading="loading"
      :onFiltersChanged="onFiltersChanged"
      :totalCount="totalRecords"
      name="filters"
    />
    
    <DataTable
      :class="tableClass"
      :loading="loading"
      :rows="perPage"
      :rowsPerPageOptions="rowsPerPageOptions"
      :sortField="currentSortField"
      :sortOrder="currentSortOrder"
      :totalRecords="totalRecords"
      :value="data"
      lazy
      paginator
      responsiveLayout="scroll"
      stripedRows
      @page="onPageEvent"
      @sort="onSortEvent"
    >
      <!-- Динамические колонки через слоты -->
      <template v-for="(column, columnIndex) in columns" :key="column.field">
        <Column
          :class="column.class"
          :field="column.field"
          :header="column.header"
          :pt="{
             root: { 'data-label': column.header },
          }"
          :sortable="column.sortable"
          :style="column.style"
        >
          <template #body="slotProps">
            <div :data-label="column.header">
              <slot
                :column="column"
                :data="slotProps.data"
                :index="slotProps.index"
                :name="`column-${column.field}`"
                data-test="test"
              >
                <!-- Дефолтное отображение -->
                <span v-if="column.type === 'date'">
                  {{ formatDateForBackend(slotProps.data[column.field]) }}
                </span>
                <span v-else-if="column.type === 'boolean'">
                      <Tag
                        :severity="slotProps.data[column.field] ? 'success' : 'danger'"
                        :value="slotProps.data[column.field] ? 'Да' : 'Нет'"
                      />
                    </span>
                <span v-else-if="column.type === 'badge' && column.badgeConfig">
                      <Tag
                        :severity="getBadgeSeverity(slotProps.data[column.field], column.badgeConfig)"
                        :value="getBadgeValue(slotProps.data[column.field], column.badgeConfig)"
                      />
                    </span>
                <span v-else>
                      {{ slotProps.data[column.field] }}
                    </span>
              </slot>
            </div>
          </template>
        </Column>
      </template>
      
      <!-- Колонка действий -->
      <Column v-if="actions.length > 0" :class="actionsColumnClass" header="Действия">
        <template #body="slotProps">
          <div data-label="Действия" class="w-full">
            <slot
              :data="slotProps.data"
              :defaultActions="getDefaultActions(slotProps.data)"
              :index="slotProps.index"
              name="actions"
            >
              <div class="flex gap-2 justify-center w-full justify-end">
                <Button
                  v-for="action in getAvailableActions(slotProps.data)"
                  :key="action.key"
                  v-tooltip.top="action.tooltip"
                  :class="action.class"
                  :disabled="action.disabled && action.disabled(slotProps.data)"
                  :icon="action.icon"
                  :severity="action.severity ? action.severity: 'action.severity'"
                  @click="$emit('action-clicked', { action: action.key, data: slotProps.data })"
                />
              </div>
            </slot>
          </div>
        </template>
      </Column>
      
      <!-- Слот для пустого состояния -->
      <template #empty>
        <div :style="{ color: 'var(--text-secondary)' }" class="text-center p-4">
          <slot :hasActiveFilters="hasActiveFilters" name="empty">
            {{ hasActiveFilters ? emptyFilteredMessage : emptyMessage }}
          </slot>
        </div>
      </template>
    </DataTable>
  
  </div>
</template>

<script setup>
const props = defineProps({
  // Основные данные
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  totalRecords: {
    type: Number,
    default: 0
  },
  
  // Конфигурация таблицы
  title: {
    type: String,
    default: ''
  },
  columns: {
    type: Array,
    required: true,
    validator: (columns) => {
      return columns.every(col => col.field && col.header)
    }
  },
  actions: {
    type: Array,
    default: () => []
  },
  
  // Настройки пагинации
  rowsPerPageOptions: {
    type: Array,
    default: () => [10, 25, 50, 100]
  },
  defaultPerPage: {
    type: Number,
    default: 10
  },
  
  // Настройки сортировки
  defaultSortField: {
    type: String,
    default: 'created_at'
  },
  defaultSortOrder: {
    type: Number,
    default: -1
  },
  
  // Кнопка добавления
  showAddButton: {
    type: Boolean,
    default: true
  },
  addButtonLabel: {
    type: String,
    default: 'Добавить'
  },
  addButtonIcon: {
    type: String,
    default: 'pi pi-plus'
  },
  
  // Сообщения
  emptyMessage: {
    type: String,
    default: 'Данные не найдены.'
  },
  emptyFilteredMessage: {
    type: String,
    default: 'Данные не найдены. Попробуйте изменить параметры поиска.'
  },
  
  // CSS классы
  tableClass: {
    type: String,
    default: 'p-datatable-sm universal-responsive-table'
  },
  actionsColumnClass: {
    type: String,
    default: 'min-w-[9rem]'
  },
  
  // Внешние фильтры
  externalFilters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits([
  'page-changed',
  'sort-changed',
  'filters-changed',
  'add-clicked',
  'action-clicked'
])

const {
  formatDateForBackend,
} = useDate()

// Reactive data
const currentPage = ref(1)
const perPage = ref(props.defaultPerPage)
const currentSortField = ref(props.defaultSortField)
const currentSortOrder = ref(props.defaultSortOrder)
const currentFilters = ref({})

// Computed
const hasActiveFilters = computed(() => {
  const allFilters = {...currentFilters.value, ...props.externalFilters}
  return Object.values(allFilters).some(value =>
    value !== null && value !== '' && value !== false && value !== undefined
  )
})

// Methods
const getBadgeValue = (value, badgeConfig) => {
  const config = badgeConfig.find(item => item.value === value)
  return config ? config.label : value
}

const getBadgeSeverity = (value, badgeConfig) => {
  const config = badgeConfig.find(item => item.value === value)
  return config ? config.severity : 'info'
}

const getAvailableActions = (rowData) => {
  return props.actions.filter(action => {
    return !action.condition || action.condition(rowData)
  })
}

const getDefaultActions = (rowData) => {
  return {
    edit: () => emit('action-clicked', {action: 'edit', data: rowData}),
    delete: () => emit('action-clicked', {action: 'delete', data: rowData}),
    view: () => emit('action-clicked', {action: 'view', data: rowData})
  }
}

const onFiltersChanged = (filters) => {
  currentFilters.value = filters
  currentPage.value = 1
  
  // Обновляем сортировку из фильтров если есть
  if (filters.sort_by && filters.sort_order) {
    currentSortField.value = filters.sort_by
    currentSortOrder.value = filters.sort_order === 'asc' ? 1 : -1
  }
  
  emit('filters-changed', {
    filters,
    page: currentPage.value,
    perPage: perPage.value,
    sortField: currentSortField.value,
    sortOrder: currentSortOrder.value
  })
}

const onPageEvent = (event) => {
  currentPage.value = event.page + 1
  perPage.value = event.rows
  
  emit('page-changed', {
    page: currentPage.value,
    perPage: perPage.value,
    filters: currentFilters.value,
    sortField: currentSortField.value,
    sortOrder: currentSortOrder.value
  })
}

const onSortEvent = (event) => {
  currentSortField.value = event.sortField
  currentSortOrder.value = event.sortOrder
  currentPage.value = 1
  
  const sortOrder = event.sortOrder === 1 ? 'asc' : 'desc'
  
  // Обновляем фильтры с новой сортировкой
  currentFilters.value = {
    ...currentFilters.value,
    sort_by: event.sortField,
    sort_order: sortOrder
  }
  
  emit('sort-changed', {
    sortField: event.sortField,
    sortOrder: sortOrder,
    page: currentPage.value,
    perPage: perPage.value,
    filters: currentFilters.value
  })
}

// Методы для внешнего управления
const resetPagination = () => {
  currentPage.value = 1
}

const resetFilters = () => {
  currentFilters.value = {}
  currentPage.value = 1
  currentSortField.value = props.defaultSortField
  currentSortOrder.value = props.defaultSortOrder
}

// Watchers
watch(() => props.externalFilters, (newFilters) => {
  onFiltersChanged(newFilters)
}, {deep: true})

// Expose methods for parent component
defineExpose({
  resetPagination,
  resetFilters,
  currentPage,
  perPage,
  currentSortField,
  currentSortOrder,
  currentFilters
})
</script>

<style scoped>
/* Базовые стили для адаптивности */
.universal-datatable {
  width: 100%;
}

:deep(.universal-responsive-table .p-datatable-table thead tr th:last-child .p-datatable-column-header-content) {
  justify-content: flex-end;
}

:deep(.universal-responsive-table .p-datatable-table-container) {
  border-radius: 5px;
  margin-bottom: 5px;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 768px) {
  :deep(.universal-responsive-table .p-datatable-thead) {
    display: none;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr) {
    display: block;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    background: var(--surface-card);
    margin: 5px;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td) {
    display: flex !important;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0 !important;
    border: none !important;
    border-bottom: 1px solid var(--border-color) !important;
    margin: 0 !important;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child) {
    border-bottom: none !important;
    padding-top: 12px !important;
    justify-content: center;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:before) {
    content: attr(data-label);
    font-weight: 600;
    color: var(--text-primary);
    margin-right: 12px;
    flex-shrink: 0;
    min-width: 100px;
  }
  
  /* Скрываем data-label для колонки действий */
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child:before) {
    display: none;
  }
  
  /* Стили для действий в мобильной версии */
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child .flex) {
    width: 100%;
    justify-content: center;
    gap: 12px;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child .p-button) {
    min-height: 40px;
    min-width: 40px;
  }
}

/* Улучшенная адаптивность для заголовка */
@media (max-width: 640px) {
  .flex.justify-between.items-center {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  h1 {
    text-align: center;
    font-size: 1.875rem;
  }
}

/* Стили для планшетов */
@media (min-width: 769px) and (max-width: 1024px) {
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td) {
    padding: 0.5rem;
    font-size: 0.875rem;
  }
}
</style>