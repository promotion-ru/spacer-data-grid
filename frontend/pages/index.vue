<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    <div class="container mx-auto px-4 py-8">
      <!-- Приглашения -->
      <InvitationNotifications
        :invitations="invitations"
        @updated="refreshData"
      />
      
      <!-- Заголовок и кнопка создания -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold" style="color: var(--text-primary)">Мои таблицы данных</h1>
          <p class="mt-2" style="color: var(--text-secondary)">Управляйте своими данными в удобном формате</p>
        </div>
        <Button
          class="p-button-lg"
          icon="pi pi-plus"
          label="Создать таблицу"
          @click="showCreateModal = true"
        />
      </div>
      
      <!-- Фильтры -->
      <DataGridFilters
        ref="filtersRef"
        :loading="pending || invitationsPending"
        :totalCount="grids?.length || 0"
        @filtersChanged="onFiltersChanged"
      />
      
      <!-- Загрузка -->
      <div v-if="pending || invitationsPending" class="flex justify-center py-12">
        <ProgressSpinner/>
      </div>
      
      <!-- Список таблиц -->
      <div v-else-if="filteredGrids?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <Card
          v-for="grid in filteredGrids"
          :key="grid.id"
          class="cursor-pointer"
          @click="navigateToGrid(grid)"
        >
          <template #header>
            <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500">
              <img
                v-if="grid.image_url"
                :alt="grid.name"
                :src="grid.image_url"
                class="w-full h-full object-cover"
              />
              <div v-else class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
                <i class="pi pi-table text-4xl text-white"></i>
              </div>
              
              <!-- Индикатор чужой таблицы -->
              <div v-if="!grid.is_owner" class="absolute top-2 left-2">
                <Tag class="text-xs" severity="warning" value="Общая"/>
              </div>
            </div>
          </template>
          
          <template #title>
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <h3 class="text-lg font-semibold truncate" style="color: var(--text-primary)">{{ grid.name }}</h3>
                <p v-if="!grid.is_owner && grid.owner_name" class="text-sm" style="color: var(--text-secondary)">
                  Владелец: {{ grid.owner_name }}
                </p>
              </div>
              <Button
                class="p-button-text p-button-sm"
                icon="pi pi-ellipsis-v"
                @click.stop="toggleGridMenu($event, grid)"
              />
            </div>
          </template>
          
          <template #content>
            <div class="space-y-3">
              <p v-if="grid.description" class="text-sm line-clamp-2" style="color: var(--text-secondary)">
                {{ grid.description }}
              </p>
              
              <div class="flex items-center justify-between text-sm" style="color: var(--text-secondary)">
                <span class="flex items-center">
                  <i class="pi pi-list mr-1"></i>
                  {{ grid.records_count }} записей
                </span>
                <span>{{ grid.created_at }}</span>
              </div>
              
              <!-- Показатель последнего обновления -->
              <div v-if="grid.updated_at && grid.updated_at !== grid.created_at" class="text-xs" style="color: var(--text-secondary)">
                <i class="pi pi-clock mr-1"></i>
                Обновлено: {{ grid.updated_at }}
              </div>
              
              <!-- Права доступа для чужих таблиц -->
              <div v-if="!grid.is_owner && grid.permissions" class="flex flex-wrap gap-1">
                <Tag
                  v-for="permission in grid.permissions"
                  :key="permission"
                  :value="getPermissionLabel(permission)"
                  class="text-xs"
                  severity="info"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
      
      <!-- Пустое состояние -->
      <div v-else class="text-center py-12">
        <i class="pi pi-table text-6xl mb-4" style="color: var(--text-secondary)"></i>
        <h3 class="text-xl font-semibold mb-2" style="color: var(--text-primary)">
          {{ hasActiveFilters ? 'Таблицы не найдены' : 'У вас пока нет таблиц данных' }}
        </h3>
        <p class="mb-6" style="color: var(--text-secondary)">
          {{
            hasActiveFilters
              ? 'Попробуйте изменить параметры поиска или сбросить фильтры'
              : 'Создайте свою первую таблицу для управления данными'
          }}
        </p>
        <div class="space-x-3">
          <Button
            v-if="!hasActiveFilters"
            icon="pi pi-plus"
            label="Создать таблицу"
            @click="showCreateModal = true"
          />
          <Button
            v-if="hasActiveFilters"
            class="p-button-outlined"
            icon="pi pi-times"
            label="Сбросить фильтры"
            @click="resetFilters"
          />
        </div>
      </div>
    </div>
    
    <!-- Модальное окно создания -->
    <DataGridCreateModal
      v-model:visible="showCreateModal"
      @created="onGridCreated"
    />
    
    <!-- Модальное окно редактирования -->
    <DataGridEditModal
      v-model:visible="showEditModal"
      :grid="selectedGrid"
      @updated="onGridUpdated"
    />
    
    <!-- Модальное окно совместного использования -->
    <DataGridShareModal
      v-if="selectedGrid?.is_owner"
      v-model:visible="showShareModal"
      :grid="selectedGrid"
      @invited="onUserInvited"
    />
    
    <!-- Модальное окно управления участниками -->
    <DataGridMembersModal
      v-if="selectedGrid?.is_owner"
      v-model:visible="showMembersModal"
      :grid="selectedGrid"
    />
    
    <!-- Модальное окно истории изменений -->
    <DataGridActivityLogsModal
      v-model:visible="showActivityLogsModal"
      :grid="selectedGrid"
    />
    
    <!-- Контекстное меню -->
    <ContextMenu ref="gridMenu" :model="gridMenuItems"/>
    
    <!-- Попап подтверждения удаления -->
    <ConfirmPopup/>
    
    <!-- Toast для уведомлений -->
    <Toast/>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth',
  title: 'Дашборд',
})

useSeoMeta({
  title: 'Дашборд'
})

const {$api} = useNuxtApp()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// Реактивные данные
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showShareModal = ref(false)
const showMembersModal = ref(false)
const showActivityLogsModal = ref(false)
const gridMenu = ref()
const selectedGrid = ref(null)
const filtersRef = ref(null)
const currentFilters = ref({})

// Загрузка данных таблиц
const {data: grids, pending, refresh} = await useLazyAsyncData('dataGrids', async () => {
  const params = new URLSearchParams()
  
  // Применяем текущие фильтры
  if (currentFilters.value.search) {
    params.append('search', currentFilters.value.search)
  }
  if (currentFilters.value.ownership) {
    params.append('ownership', currentFilters.value.ownership)
  }
  if (currentFilters.value.activity) {
    params.append('activity', currentFilters.value.activity)
  }
  if (currentFilters.value.sort) {
    params.append('sort', currentFilters.value.sort)
  }
  if (currentFilters.value.created_from) {
    params.append('created_from', currentFilters.value.created_from)
  }
  if (currentFilters.value.created_to) {
    params.append('created_to', currentFilters.value.created_to)
  }
  
  const queryString = params.toString()
  const url = `/data-grid${queryString ? '?' + queryString : ''}`
  
  const response = await $api(url, {
    method: 'GET'
  })
  return response.data
})

// Загрузка приглашений
const {
  data: invitations,
  pending: invitationsPending,
  refresh: refreshInvitations
} = await useLazyAsyncData('invitations', async () => {
  try {
    const response = await $api('/invitations', {
      method: 'GET'
    })
    return response.data
  } catch (error) {
    console.error('Ошибка загрузки приглашений:', error)
    return []
  }
})

// Вычисляемые свойства
const filteredGrids = computed(() => {
  return grids.value || []
})

const hasActiveFilters = computed(() => {
  return Object.values(currentFilters.value).some(value => value !== null && value !== '')
})

// Методы
const navigateToGrid = (grid) => {
  router.push(`/data-grid/${grid.id}`)
}

const toggleGridMenu = (event, grid) => {
  selectedGrid.value = grid
  gridMenu.value.toggle(event)
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

const onFiltersChanged = (filters) => {
  currentFilters.value = filters
  refresh()
}

const resetFilters = () => {
  if (filtersRef.value) {
    filtersRef.value.resetFilters()
  }
  currentFilters.value = {}
  refresh()
}

// Обработчики событий
const onGridCreated = (newGrid) => {
  showCreateModal.value = false
  refresh()
  navigateToGrid(newGrid)
}

const onGridUpdated = (updatedGrid) => {
  showEditModal.value = false
  selectedGrid.value = null
  refresh()
  
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Таблица "${updatedGrid.name}" обновлена`,
    life: 3000
  })
}

const onUserInvited = () => {
  showShareModal.value = false
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: 'Приглашение отправлено',
    life: 3000
  })
}

const refreshData = async () => {
  await Promise.all([refresh(), refreshInvitations()])
}

const editGrid = () => {
  if (selectedGrid.value) {
    showEditModal.value = true
  }
}

const shareGrid = () => {
  if (selectedGrid.value?.is_owner) {
    showShareModal.value = true
  }
}

const manageGridMembers = () => {
  if (selectedGrid.value?.is_owner) {
    showMembersModal.value = true
  }
}

const showActivityLogs = () => {
  if (selectedGrid.value?.is_owner) {
    showActivityLogsModal.value = true
  }
}

const leaveGrid = async () => {
  if (!selectedGrid.value || selectedGrid.value.is_owner) return
  
  try {
    await $api(`/data-grid/${selectedGrid.value.id}/leave`, {
      method: 'POST'
    })
    
    await refreshData()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Вы покинули таблицу',
      life: 3000
    })
    
    selectedGrid.value = null
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

const confirmDeleteGrid = (event) => {
  if (!selectedGrid.value) {
    return
  }
  
  confirm.require({
    target: event.currentTarget,
    message: `Вы действительно хотите удалить таблицу "${selectedGrid.value.name}"? Это действие нельзя отменить.`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    accept: () => {
      deleteGrid()
    },
    reject: () => {
      selectedGrid.value = null
    }
  })
}

const deleteGrid = async () => {
  if (!selectedGrid.value) {
    return
  }
  
  try {
    await $api(`/data-grid/${selectedGrid.value.id}`, {
      method: 'DELETE'
    })
    await refresh()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: `Таблица "${selectedGrid.value.name}" удалена`,
      life: 3000
    })
    
    selectedGrid.value = null
  } catch (error) {
    console.error('Ошибка при удалении таблицы:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить таблицу',
      life: 3000
    })
  }
}

// Элементы контекстного меню
const gridMenuItems = computed(() => {
  if (!selectedGrid.value) return []
  
  const baseItems = []
  
  if (selectedGrid.value.is_owner) {
    baseItems.push(
      {
        label: 'Поделиться',
        icon: 'pi pi-share-alt',
        command: shareGrid
      },
      {
        label: 'Настройка таблицы',
        icon: 'pi pi-cog',
        command: manageGridMembers
      },
      {
        label: 'История изменений',
        icon: 'pi pi-history',
        command: showActivityLogs
      },
      {
        label: 'Редактировать',
        icon: 'pi pi-pencil',
        command: editGrid
      },
      {
        label: 'Удалить',
        icon: 'pi pi-trash',
        command: confirmDeleteGrid
      }
    )
  } else {
    baseItems.push(
      {
        label: 'Покинуть таблицу',
        icon: 'pi pi-sign-out',
        command: leaveGrid
      }
    )
  }
  
  return baseItems
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