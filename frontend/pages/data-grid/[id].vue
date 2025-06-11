<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Основной контент -->
      <div v-if="grid">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center space-x-4">
            <Button
              class="p-button-outlined"
              icon="pi pi-arrow-left"
              @click="$router.push('/')"
            />
            <div>
              <div class="flex items-center space-x-2">
                <h1 class="text-3xl font-bold text-gray-900">{{ grid.name }}</h1>
                <Tag v-if="!grid.is_owner" severity="warning" value="Общая"/>
              </div>
              <p v-if="grid.description" class="text-gray-600 mt-1">{{ grid.description }}</p>
              <p v-if="!grid.is_owner && grid.owner_name" class="text-sm text-gray-500 mt-1">
                Владелец: {{ grid.owner_name }}
              </p>
            </div>
          </div>
          
          <div class="flex space-x-3">
            <!-- Кнопка добавления записи (только если есть права) -->
            <Button
              v-if="hasPermission('create')"
              icon="pi pi-plus"
              label="Добавить запись"
              @click="showCreateRecordModal = true"
            />
            
            <!-- Кнопки управления для владельца -->
            <template v-if="grid.is_owner">
              <Button
                class="p-button-outlined"
                icon="pi pi-share-alt"
                label="Поделиться"
                @click="showShareModal = true"
              />
              <Button
                class="p-button-outlined"
                icon="pi pi-cog"
                label="Настройка таблицы"
                @click="showMembersModal = true"
              />
            </template>
            
            <!-- Кнопка покинуть таблицу для участников -->
            <Button
              v-else
              class="p-button-outlined p-button-danger"
              icon="pi pi-sign-out"
              label="Покинуть таблицу"
              @click="confirmLeaveGrid"
            />
          </div>
        </div>
        
        <!-- Права доступа для участников -->
        <div v-if="!grid.is_owner && grid.permissions" class="mb-6">
          <Card class="border-l-4 border-l-blue-500 bg-blue-50">
            <template #content>
              <div class="flex items-center space-x-4">
                <i class="pi pi-shield text-blue-600 text-xl"></i>
                <div>
                  <h3 class="font-semibold text-blue-900">Ваши права в этой таблице:</h3>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <Tag
                      v-for="permission in grid.permissions"
                      :key="permission"
                      :value="getPermissionLabel(permission)"
                      class="text-sm"
                      severity="info"
                    />
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
        
        <!-- Фильтры -->
        <DataGridRecordFilters
          ref="filtersRef"
          :loading="recordsLoading"
          :total-records="totalRecords"
          :current-user-id="currentUserId"
          :record-types="recordTypes"
          :grid-id="grid.id"
          @filters-changed="onFiltersChanged"
          @refresh="loadRecords"
        />
        
        <!-- Таблица записей -->
        <Card class="shadow-sm">
          <template #content>
            <DataTable
              :loading="recordsLoading"
              :paginator="true"
              :rows="20"
              :value="records"
              :totalRecords="totalRecords"
              :lazy="true"
              class="p-datatable-sm"
              dataKey="id"
              @page="onPage"
              @sort="onSort"
            >
              <template #header>
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold">Записи ({{ totalRecords }})</h3>
                </div>
              </template>
              
              <template #empty>
                <div class="text-center py-8">
                  <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                  <p class="text-gray-500">
                    {{ hasActiveFilters ? 'По заданным фильтрам записи не найдены' : 'В таблице пока нет записей' }}
                  </p>
                  <Button
                    v-if="hasPermission('create') && !hasActiveFilters"
                    class="p-button-outlined mt-4"
                    icon="pi pi-plus"
                    label="Добавить первую запись"
                    @click="showCreateRecordModal = true"
                  />
                </div>
              </template>
              
              <Column field="name" header="Название" sortable>
                <template #body="{ data }">
                  <div class="font-medium">{{ data.name }}</div>
                  <div v-if="data.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                    {{ data.description }}
                  </div>
                </template>
              </Column>
              
              <Column field="date" header="Дата операции" sortable class="w-32">
                <template #body="{ data }">
                  <div class="text-sm">{{ data.date }}</div>
                </template>
              </Column>
              
              <Column field="operation_type_id" header="Тип операции" sortable class="w-32">
                <template #body="{ data }">
                  <Tag
                    :value="data.operation_type_id === 1 ? 'Доход' : 'Расход'"
                    :severity="data.operation_type_id === 1 ? 'success' : 'danger'"
                    class="text-xs"
                  />
                </template>
              </Column>
              
              <Column field="amount" header="Сумма" sortable class="w-28">
                <template #body="{ data }">
                  <div class="text-sm font-medium">{{ formatAmount(data.amount) }}</div>
                </template>
              </Column>
              
              <Column field="type.name" header="Тип записи" class="w-32">
                <template #body="{ data }">
                  <Tag
                    v-if="data.type"
                    :value="data.type.name"
                    class="text-xs"
                    severity="info"
                  />
                  <span v-else class="text-gray-400 text-sm">—</span>
                </template>
              </Column>
              
              <Column class="w-32" header="Вложения">
                <template #body="{ data }">
                  <div v-if="data.attachments?.length" class="flex flex-wrap gap-1">
                    <Tag
                      v-for="attachment in data.attachments.slice(0, 2)"
                      :key="attachment.id"
                      :value="attachment.name"
                      class="text-xs"
                      severity="info"
                    />
                    <Tag
                      v-if="data.attachments.length > 2"
                      :value="`+${data.attachments.length - 2}`"
                      class="text-xs"
                      severity="secondary"
                    />
                  </div>
                  <span v-else class="text-gray-400 text-sm">—</span>
                </template>
              </Column>
              
              <Column class="w-36" field="creator.name" header="Автор" sortable>
                <template #body="{ data }">
                  <div class="text-sm">{{ data.creator.name }}</div>
                </template>
              </Column>
              
              <Column class="w-32" field="created_at" header="Создано" sortable>
                <template #body="{ data }">
                  <div class="text-sm text-gray-500">{{ data.created_at }}</div>
                </template>
              </Column>
              
              <!-- Действия с учетом прав -->
              <Column class="w-24" header="Действия">
                <template #body="{ data }">
                  <div class="flex space-x-2">
                    <Button
                      v-tooltip.top="'Просмотр'"
                      class="p-button-outlined p-button-sm p-button-info"
                      icon="pi pi-eye"
                      @click="viewRecord(data)"
                    />
                    <Button
                      v-if="hasPermission('update')"
                      v-tooltip.top="'Редактировать'"
                      class="p-button-outlined p-button-sm"
                      icon="pi pi-pencil"
                      @click="editRecord(data)"
                    />
                    <Button
                      v-if="hasPermission('view')"
                      v-tooltip.top="'История изменений'"
                      class="p-button-outlined p-button-sm p-button-secondary"
                      icon="pi pi-history"
                      @click="viewRecordLogs(data)"
                    />
                    <Button
                      v-if="hasPermission('delete')"
                      v-tooltip.top="'Удалить'"
                      class="p-button-outlined p-button-sm p-button-danger"
                      icon="pi pi-trash"
                      @click="confirmDeleteRecord(data)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
      
      <!-- Ошибка 404 -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-6xl text-red-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Таблица не найдена</h3>
        <p class="text-gray-500 mb-6">Возможно, таблица была удалена или у вас нет доступа к ней</p>
        <Button
          icon="pi pi-home"
          label="Вернуться на главную"
          @click="$router.push('/')"
        />
      </div>
    </div>
    
    <!-- Модальные окна -->
    <DataGridRecordViewModal
      v-model:visible="showViewRecordModal"
      :record="selectedRecord"
      @update:visible="onViewModalVisibilityChange"
    />
    
    <DataGridRecordCreateModal
      v-if="hasPermission('create')"
      v-model:visible="showCreateRecordModal"
      :grid-id="grid?.id"
      @created="onRecordCreated"
      @update:visible="onCreateModalVisibilityChange"
    />
    
    <DataGridRecordEditModal
      v-if="hasPermission('update')"
      v-model:visible="showEditRecordModal"
      :record="selectedRecord"
      @updated="onRecordUpdated"
      @update:visible="onEditModalVisibilityChange"
    />
    
    <DataGridShareModal
      v-if="grid?.is_owner"
      v-model:visible="showShareModal"
      :grid="grid"
      @invited="onUserInvited"
    />
    
    <DataGridRecordLogsModal
      v-model:visible="showRecordLogsModal"
      :record="selectedRecord"
      :grid-id="grid?.id"
    />
    
    <DataGridMembersModal
      v-if="grid?.is_owner"
      v-model:visible="showMembersModal"
      :grid="grid"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth',
  title: 'Таблица данных'
})

useSeoMeta({
  title: 'Таблица данных'
})

const route = useRoute()
const {$api} = useNuxtApp()
const confirm = useConfirm()
const toast = useToast()
const { user } = useAuth()

// Реактивные данные
const recordsLoading = ref(false)
const showViewRecordModal = ref(false)
const showCreateRecordModal = ref(false)
const showEditRecordModal = ref(false)
const showShareModal = ref(false)
const showMembersModal = ref(false)
const showRecordLogsModal = ref(false)
const selectedRecord = ref(null)

// Данные для таблицы с пагинацией и фильтрацией
const records = ref([])
const totalRecords = ref(0)
const currentPage = ref(1)
const recordsPerPage = ref(20)
const currentFilters = ref({})
const currentSort = ref({ field: 'created_at', order: 'desc' })

// Дополнительные данные
const recordTypes = ref([])
const currentUserId = computed(() => {
  return user.value?.id || null
})

// Флаги для управления инициализацией
const isDataLoaded = ref(false)
const filtersRef = ref(null)

// Вычисляемое свойство для проверки активных фильтров
const hasActiveFilters = computed(() => {
  if (!currentFilters.value || typeof currentFilters.value !== 'object') {
    return false
  }
  
  return Object.keys(currentFilters.value).some(key => {
    const value = currentFilters.value[key]
    return value !== null && value !== undefined && value !== '' && value !== false
  })
})

// Загрузка данных грида
const {data: grid, pending: gridLoading, refresh: refreshGrid} = await useLazyAsyncData(`dataGrid-${route.params.id}`, () =>
  $api(`/data-grid/${route.params.id}`, {
    method: 'GET'
  }).then(res => res.data)
)

// Методы проверки прав
const hasPermission = (permission) => {
  return grid.value?.permissions?.includes(permission) || false
}

const getPermissionLabel = (permission) => {
  const labels = {
    view: 'Просмотр',
    create: 'Создание',
    update: 'Редактирование',
    delete: 'Удаление',
    manage: 'Управление'
  }
  return labels[permission] || permission
}

// Загрузка записей с фильтрацией
const loadRecords = async (forceReload = false) => {
  if (!grid.value?.id) {
    console.warn('loadRecords: grid.id не найден')
    return
  }
  
  // Защита от множественных одновременных запросов
  if (recordsLoading.value && !forceReload) {
    console.warn('loadRecords: уже выполняется загрузка')
    return
  }
  
  recordsLoading.value = true
  
  try {
    const params = {
      page: currentPage.value,
      per_page: recordsPerPage.value,
      sort_by: currentSort.value?.field || 'created_at',
      sort_order: currentSort.value?.order || 'desc',
      ...(currentFilters.value || {})
    }
    
    console.log('loadRecords: отправляем запрос с параметрами', params)
    
    const response = await $api(`/data-grid/${grid.value.id}/records`, {
      method: 'GET',
      params
    })
    
    records.value = response.data || []
    totalRecords.value = response?.pagination?.total || 0
    
    if (response.record_types) {
      recordTypes.value = response.record_types
    }
    
    // Отмечаем что данные загружены
    isDataLoaded.value = true
    
    console.log('loadRecords: данные установлены', {
      recordsCount: records.value.length,
      totalRecords: totalRecords.value
    })
    
  } catch (error) {
    console.error('Ошибка загрузки записей:', error)
    // Сбрасываем данные при ошибке
    records.value = []
    totalRecords.value = 0
    
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить записи',
      life: 3000
    })
  } finally {
    recordsLoading.value = false
  }
}

// Обработчики событий
const onFiltersChanged = (filters) => {
  console.log('onFiltersChanged:', filters)
  currentFilters.value = filters || {}
  currentPage.value = 1 // Сбрасываем на первую страницу при изменении фильтров
  loadRecords(true) // Принудительная перезагрузка при изменении фильтров
}

const onPage = (event) => {
  currentPage.value = event.page + 1 // PrimeVue считает с 0
  loadRecords()
}

const onSort = (event) => {
  if (event && typeof event === 'object') {
    currentSort.value = {
      field: event.sortField || 'created_at',
      order: event.sortOrder === 1 ? 'asc' : 'desc'
    }
  } else {
    currentSort.value = { field: 'created_at', order: 'desc' }
  }
  loadRecords()
}

// Инициализация после загрузки grid
const initializeData = async () => {
  if (!grid.value?.id) return
  
  console.log('initializeData: начинаем инициализацию', grid.value.id)
  
  // Инициализируем фильтры
  if (filtersRef.value && filtersRef.value.initializeFilters) {
    console.log('initializeData: инициализируем фильтры')
    filtersRef.value.initializeFilters()
  } else {
    // Если фильтры еще не готовы, загружаем данные напрямую
    console.log('initializeData: фильтры не готовы, загружаем данные напрямую')
    await loadRecords(true)
  }
}

// Методы для обработки событий модальных окон
const viewRecord = (record) => {
  selectedRecord.value = record
  showViewRecordModal.value = true
}

const onViewModalVisibilityChange = (visible) => {
  showViewRecordModal.value = visible
  if (!visible) {
    selectedRecord.value = null
  }
}

const onCreateModalVisibilityChange = (visible) => {
  showCreateRecordModal.value = visible
}

const onEditModalVisibilityChange = (visible) => {
  showEditRecordModal.value = visible
  if (!visible) {
    selectedRecord.value = null
  }
}

const viewRecordLogs = (record) => {
  selectedRecord.value = record
  showRecordLogsModal.value = true
}

// Обработчики событий для совместного использования
const onUserInvited = () => {
  showShareModal.value = false
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: 'Приглашение отправлено',
    life: 3000
  })
}

const confirmLeaveGrid = () => {
  confirm.require({
    message: 'Вы уверены, что хотите покинуть эту таблицу?',
    header: 'Подтверждение',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Покинуть',
    rejectLabel: 'Отмена',
    accept: () => leaveGrid()
  })
}

const leaveGrid = async () => {
  try {
    await $api(`/data-grid/${grid.value.id}/leave`, {
      method: 'POST'
    })
    
    $router.push('/')
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Вы покинули таблицу',
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка при выходе из таблицы:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось покинуть таблицу',
      life: 3000
    })
  }
}

// Методы для обработки CRUD операций
const onRecordCreated = async (newRecord) => {
  showCreateRecordModal.value = false
  await loadRecords(true) // Принудительная перезагрузка
  
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: 'Запись создана',
    life: 3000
  })
}

const onRecordUpdated = async (updatedRecord) => {
  showEditRecordModal.value = false
  selectedRecord.value = null
  await loadRecords(true) // Принудительная перезагрузка
  
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: 'Запись обновлена',
    life: 3000
  })
}

const editRecord = (record) => {
  selectedRecord.value = record
  showEditRecordModal.value = true
}

const confirmDeleteRecord = (record) => {
  confirm.require({
    message: `Вы уверены, что хотите удалить запись "${record.name}"?`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    accept: () => deleteRecord(record)
  })
}

const deleteRecord = async (record) => {
  try {
    await $api(`/data-grid/${grid.value.id}/records/${record.id}`, {
      method: 'DELETE'
    })
    
    await loadRecords(true) // Принудительная перезагрузка
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись удалена',
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка при удалении записи:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить запись',
      life: 3000
    })
  }
}

// Вспомогательные методы
const formatAmount = (amount) => {
  if (!amount) return '—'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

// Единственный watcher для инициализации
watch(() => grid.value, async (newGrid, oldGrid) => {
  if (newGrid?.id && newGrid.id !== oldGrid?.id) {
    console.log('grid изменился, инициализируем данные', newGrid.id)
    await nextTick() // Ждем обновления DOM
    await initializeData()
  }
}, { immediate: true })

// onMounted для дополнительной защиты
onMounted(async () => {
  console.log('onMounted вызван')
  // Дополнительная проверка если данные не загрузились
  if (grid.value?.id && !isDataLoaded.value) {
    console.log('onMounted: данные не загружены, инициализируем')
    await nextTick()
    await initializeData()
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>