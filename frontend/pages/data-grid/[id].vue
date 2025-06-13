<template>
  <div class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
      <!-- Основной контент -->
      <div v-if="grid">
        <!-- Заголовок -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              outlined
              @click="$router.push('/')"
            />
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl lg:text-3xl font-bold text-primary">{{ grid.name }}</h1>
                <Tag v-if="!grid.is_owner" severity="warn" value="Общая"/>
              </div>
              <DataGridRecordDescriptionModal
                :description="grid.description"
                :max-length="100"
                :modal-title="`Описание таблицы: ${grid.name}`"
                :show-meta-info="true"
              />
              <p v-if="!grid.is_owner && grid.owner_name" class="text-sm mt-1 text-secondary">
                Владелец: {{ grid.owner_name }}
              </p>
            </div>
          </div>
          
          <div class="flex flex-wrap gap-3">
            <!-- Кнопка добавления записи (только если есть права) -->
            <Button
              v-if="hasPermission('create')"
              :label="isMobile ? '' : 'Добавить запись'"
              icon="pi pi-plus"
              @click="showCreateRecordModal = true"
            />
            
            <!-- Кнопки управления для владельца -->
            <template v-if="grid.is_owner">
              <Button
                :label="isMobile ? '' : 'Поделиться'"
                icon="pi pi-share-alt"
                outlined
                @click="showShareModal = true"
              />
              <Button
                :label="isMobile ? '' : 'Настройка таблицы'"
                icon="pi pi-cog"
                outlined
                @click="showMembersModal = true"
              />
            </template>
            
            <!-- Кнопка покинуть таблицу для участников -->
            <Button
              v-else
              :label="isMobile ? '' : 'Покинуть таблицу'"
              icon="pi pi-sign-out"
              outlined
              severity="danger"
              @click="confirmLeaveGrid"
            />
          </div>
        </div>
        
        <!-- Права доступа для участников -->
        <div v-if="!grid.is_owner && grid.permissions" class="mb-6">
          <Card class="border-l-4 permissions-card">
            <template #content>
              <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <i class="pi pi-shield text-xl permissions-icon"></i>
                <div class="flex-1">
                  <h3 class="font-semibold mb-2 text-primary">Ваши права в этой таблице:</h3>
                  <div class="flex flex-wrap gap-2">
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
          :current-user-id="currentUserId"
          :grid-id="grid.id"
          :loading="recordsLoading"
          :record-types="recordTypes"
          :total-records="totalRecords"
          @refresh="loadRecords"
          @filters-changed="onFiltersChanged"
        />
        
        <!-- DataView для записей -->
        <Card class="main-card">
          <template #content>
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-primary">Записи ({{ totalRecords }})</h3>
            </div>
            
            <DataView
              :lazy="true"
              :loading="recordsLoading"
              :paginator="true"
              :rows="20"
              :totalRecords="totalRecords"
              :value="records"
              dataKey="id"
              @page="onPage"
            >
              <template #empty>
                <div class="text-center py-12">
                  <i class="pi pi-inbox text-4xl mb-4 text-secondary"></i>
                  <p class="mb-4 text-secondary">
                    {{ hasActiveFilters ? 'По заданным фильтрам записи не найдены' : 'В таблице пока нет записей' }}
                  </p>
                  <Button
                    v-if="hasPermission('create') && !hasActiveFilters"
                    icon="pi pi-plus"
                    label="Добавить первую запись"
                    outlined
                    @click="showCreateRecordModal = true"
                  />
                </div>
              </template>
              
              <template #list="slotProps">
                <div class="grid grid-cols-1 gap-4">
                  <div
                    v-for="(record, index) in slotProps.items"
                    :key="record.id"
                    class="record-card cursor-pointer transition-all duration-200 hover:shadow-md"
                    @click="viewRecordInModal(record)"
                  >
                    <div class="flex justify-between items-start gap-4">
                      <!-- Левая часть: Название, дата, тип -->
                      <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2 mb-2">
                          <h4 class="font-semibold text-base leading-tight record-title">
                            {{ record.name }}
                          </h4>
                          <!-- Меню действий -->
                          <div class="flex items-center gap-2 flex-shrink-0">
                            <Button
                              v-tooltip.top="'Действия'"
                              class="record-actions-btn"
                              icon="pi pi-ellipsis-v"
                              rounded
                              size="small"
                              text
                              @click.stop="toggleActionsMenu($event, record)"
                            />
                          </div>
                        </div>
                        
                        <div class="flex items-center gap-3 mb-2 text-sm record-meta">
                          <div class="flex items-center gap-1 text-secondary">
                            <i class="pi pi-calendar text-xs record-meta-icon"></i>
                            <span>{{ record.date }}</span>
                          </div>
                          
                          <Tag
                            :severity="record.operation_type_id === 1 ? 'success' : 'danger'"
                            :value="record.operation_type_id === 1 ? 'Доход' : 'Расход'"
                            class="text-xs"
                          />
                          
                          <Tag
                            v-if="record.type"
                            :value="record.type.name"
                            class="text-xs"
                            severity="info"
                          />
                        </div>
                        
                        <!-- Краткое описание -->
                        <p
                          v-if="record.description"
                          class="text-sm line-clamp-2 mb-2 record-description"
                        >
                          {{ record.description }}
                        </p>
                        
                        <!-- Дополнительная информация -->
                        <div class="flex items-center gap-4 text-xs record-meta">
                          <div class="flex items-center gap-1">
                            <i class="pi pi-user record-meta-icon"></i>
                            <span>{{ record.creator.name }}</span>
                          </div>
                          
                          <div v-if="record.attachments?.length" class="flex items-center gap-1">
                            <i class="pi pi-paperclip record-meta-icon"></i>
                            <span>{{
                                record.attachments.length
                              }} файл{{
                                record.attachments.length > 1 ? (record.attachments.length > 4 ? 'ов' : 'а') : ''
                              }}</span>
                          </div>
                          
                          <div class="flex items-center gap-1">
                            <i class="pi pi-clock record-meta-icon"></i>
                            <span>{{ record.created_at }}</span>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Правая часть: Сумма -->
                      <div class="flex-shrink-0 text-right">
                        <div class="text-lg font-bold mb-1 text-primary">
                          {{ formatAmount(record.amount) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
              
              <template #footer>
                <div class="text-center text-sm mt-4 text-secondary">
                  Показано {{ records.length }} из {{ totalRecords }} записей
                </div>
              </template>
            </DataView>
          </template>
        </Card>
      </div>
      
      <!-- Ошибка 404 -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-6xl mb-4 error-icon"></i>
        <h3 class="text-xl font-semibold mb-2 error-title">Таблица не найдена</h3>
        <p class="mb-6 error-description">Возможно, таблица была удалена или у вас нет доступа к ней</p>
        <Button
          icon="pi pi-home"
          label="Вернуться на главную"
          @click="$router.push('/')"
        />
      </div>
    </div>
    
    <!-- Меню действий -->
    <Menu
      ref="actionsMenu"
      :model="actionMenuItems"
      :popup="true"
    />
    
    <!-- Модальные окна -->
    <!-- Модалка просмотра записи -->
    <Dialog
      v-model:visible="showDetailModal"
      :breakpoints="{ '960px': '75vw', '641px': '95vw' }"
      :closable="true"
      :header="selectedRecord?.name || 'Просмотр записи'"
      :modal="true"
      :style="{ width: '50vw' }"
    >
      <div v-if="selectedRecord" class="record-detail-content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Основная информация -->
          <div class="space-y-4">
            <div>
              <h4 class="font-semibold mb-2 modal-label">Основная информация</h4>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Название</label>
                  <p class="text-sm modal-content">{{ selectedRecord.name }}</p>
                </div>
                
                <div v-if="selectedRecord.description">
                  <label class="block text-sm font-medium mb-1 modal-label">Описание</label>
                  <p class="text-sm whitespace-pre-wrap modal-content">{{ selectedRecord.description }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Сумма</label>
                  <p class="text-lg font-bold modal-content">{{ formatAmount(selectedRecord.amount) }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Дополнительная информация -->
          <div class="space-y-4">
            <div>
              <h4 class="font-semibold mb-2 modal-label">Детали</h4>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Дата операции</label>
                  <p class="text-sm modal-content">{{ selectedRecord.date }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Тип операции</label>
                  <Tag
                    :severity="selectedRecord.operation_type_id === 1 ? 'success' : 'danger'"
                    :value="selectedRecord.operation_type_id === 1 ? 'Доход' : 'Расход'"
                    class="text-sm"
                  />
                </div>
                
                <div v-if="selectedRecord.type">
                  <label class="block text-sm font-medium mb-1 modal-label">Тип записи</label>
                  <Tag
                    :value="selectedRecord.type.name"
                    class="text-sm"
                    severity="info"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Автор</label>
                  <p class="text-sm modal-content">{{ selectedRecord.creator.name }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1 modal-label">Создано</label>
                  <p class="text-sm modal-content">{{ selectedRecord.created_at }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Вложения -->
        <div v-if="selectedRecord.attachments?.length" class="mt-6">
          <h4 class="font-semibold mb-3 modal-label">Вложения ({{ selectedRecord.attachments.length }})</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="attachment in selectedRecord.attachments"
              :key="attachment.id"
              class="flex items-center gap-2 p-3 rounded-lg modal-attachment-item"
            >
              <i class="pi pi-file text-lg permissions-icon"></i>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate modal-content">{{ attachment.name }}</p>
                <p class="text-xs text-secondary">{{ attachment.size ? formatFileSize(attachment.size) : '' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button
            v-if="hasPermission('update')"
            icon="pi pi-pencil"
            label="Редактировать"
            outlined
            @click="editRecordFromModal"
          />
          <Button
            icon="pi pi-times"
            label="Закрыть"
            outlined
            @click="showDetailModal = false"
          />
        </div>
      </template>
    </Dialog>
    
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
      :grid-id="grid?.id"
      :record="selectedRecord"
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
const {user} = useAuth()
const {isMobile} = useDevice()

// Реактивные данные
const recordsLoading = ref(false)
const showViewRecordModal = ref(false)
const showCreateRecordModal = ref(false)
const showEditRecordModal = ref(false)
const showShareModal = ref(false)
const showMembersModal = ref(false)
const showRecordLogsModal = ref(false)
const showDetailModal = ref(false)
const selectedRecord = ref(null)

// Данные для таблицы с пагинацией и фильтрацией
const records = ref([])
const totalRecords = ref(0)
const currentPage = ref(1)
const recordsPerPage = ref(20)
const currentFilters = ref({})
const currentSort = ref({field: 'created_at', order: 'desc'})

// Дополнительные данные
const recordTypes = ref([])
const currentUserId = computed(() => {
  return user.value?.id || null
})

// Флаги для управления инициализацией
const isDataLoaded = ref(false)
const filtersRef = ref(null)

// Меню действий
const actionsMenu = ref(null)
const actionMenuItems = ref([])

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
const {
  data: grid,
  pending: gridLoading,
  refresh: refreshGrid
} = await useLazyAsyncData(`dataGrid-${route.params.id}`, () =>
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

// Методы для меню действий
const toggleActionsMenu = (event, record) => {
  selectedRecord.value = record
  
  actionMenuItems.value = [
    {
      label: 'Просмотр',
      icon: 'pi pi-eye',
      command: () => viewRecord(record)
    }
  ]
  
  if (hasPermission('update')) {
    actionMenuItems.value.push({
      label: 'Редактировать',
      icon: 'pi pi-pencil',
      command: () => editRecord(record)
    })
  }
  
  if (hasPermission('view')) {
    actionMenuItems.value.push({
      label: 'История изменений',
      icon: 'pi pi-history',
      command: () => viewRecordLogs(record)
    })
  }
  
  if (hasPermission('delete')) {
    actionMenuItems.value.push({
      separator: true
    })
    actionMenuItems.value.push({
      label: 'Удалить',
      icon: 'pi pi-trash',
      class: 'text-red-500',
      command: () => confirmDeleteRecord(record)
    })
  }
  
  actionsMenu.value.toggle(event)
}

// Методы для просмотра записи в модалке
const viewRecordInModal = (record) => {
  selectedRecord.value = record
  showDetailModal.value = true
}

const editRecordFromModal = () => {
  showDetailModal.value = false
  nextTick(() => {
    editRecord(selectedRecord.value)
  })
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
    currentSort.value = {field: 'created_at', order: 'desc'}
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

const formatFileSize = (bytes) => {
  if (!bytes) return ''
  
  const sizes = ['Б', 'КБ', 'МБ', 'ГБ']
  if (bytes === 0) return '0 Б'
  
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

// Единственный watcher для инициализации
watch(() => grid.value, async (newGrid, oldGrid) => {
  if (newGrid?.id && newGrid.id !== oldGrid?.id) {
    console.log('grid изменился, инициализируем данные', newGrid.id)
    await nextTick() // Ждем обновления DOM
    await initializeData()
  }
}, {immediate: true})

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

.record-card {
  transition: all 0.2s ease-in-out;
}

.record-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.record-actions-btn {
  opacity: 0.7;
  transition: opacity 0.2s ease-in-out;
}

.record-card:hover .record-actions-btn {
  opacity: 1;
}

.record-detail-content {
  max-height: 70vh;
  overflow-y: auto;
}
</style>