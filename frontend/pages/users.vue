<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    <div class="container mx-auto px-4 py-8">
      <UniversalDataTable
        ref="dataTableRef"
        :data="usersList"
        :loading="isLoading"
        :totalRecords="totalRecords"
        :columns="columns"
        :actions="actions"
        title="Пользователи"
        addButtonLabel="Добавить пользователя"
        emptyFilteredMessage="Пользователи не найдены. Попробуйте изменить параметры поиска."
        emptyMessage="Пользователи не найдены."
        @add-clicked="openCreateModal"
        @action-clicked="handleActionClick"
        @page-changed="handlePageChange"
        @sort-changed="handleSortChange"
        @filters-changed="handleFiltersChange"
      >
        <!-- Слот для фильтров -->
        <template #filters="{ loading, totalCount, onFiltersChanged }">
          <UsersDataFilters
            ref="filtersRef"
            :loading="loading"
            :totalCount="totalCount"
            @filtersChanged="onFiltersChanged"
          />
        </template>
        
        <!-- Слот для колонки аватара -->
        <template #column-avatar_url="{ data, column }">
          <div :data-label="column.header" class="flex justify-center">
            <Avatar
              :image="data.avatar_url || undefined"
              :label="!data.avatar_url && data.name ? data.name.charAt(0).toUpperCase() : undefined"
              class="bg-primary-100 text-primary-700"
              shape="circle"
              size="large"
            />
          </div>
        </template>
        
        <!-- Слот для колонки имени -->
        <template #column-name="{ data, column }">
          <div :data-label="column.header">
            <div class="font-semibold">{{ data.name }}</div>
            <div v-if="data.surname" class="text-sm" style="color: var(--text-secondary)">
              {{ data.surname }}
            </div>
          </div>
        </template>
        
        <!-- Слот для кастомных действий -->
        <template #actions="{ data, defaultActions }">
          <div data-label="Действия" class="flex gap-2 justify-center">
            <Button
              v-tooltip.top="'Редактировать'"
              class="p-button-rounded p-button-text p-button-sm"
              icon="pi pi-pencil"
              @click="defaultActions.edit"
            />
            <Button
              v-tooltip.top="'Удалить'"
              class="p-button-rounded p-button-text p-button-sm p-button-danger"
              icon="pi pi-trash"
              @click="defaultActions.delete"
            />
          </div>
        </template>
      </UniversalDataTable>
      
      <!-- Модальные окна -->
      <UsersCreateUserModal
        v-model:visible="showCreateModal"
        @user-created="onUserCreated"
      />
      
      <UsersEditUserModal
        v-model:visible="showEditModal"
        :user="selectedUser"
        @user-updated="onUserUpdated"
      />
      
      <ConfirmDialog group="userDeletionConfirmation"></ConfirmDialog>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import UniversalDataTable from '~/components/UniversalDataTable.vue'

definePageMeta({
  title: 'Управление пользователями',
  middleware: 'admin'
})

const { $api } = useNuxtApp()
const confirm = useConfirm()
const toast = useToast()

// Reactive data
const usersList = ref([])
const isLoading = ref(false)
const selectedUser = ref(null)
const totalRecords = ref(0)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const dataTableRef = ref(null)
const filtersRef = ref(null)

// Конфигурация колонок
const columns = ref([
  {
    field: 'avatar_url',
    header: 'Аватар',
    class: 'w-20 text-center',
    sortable: false
  },
  {
    field: 'name',
    header: 'Имя',
    class: 'min-w-[10rem]',
    sortable: true
  },
  {
    field: 'email',
    header: 'Email',
    class: 'min-w-[15rem]',
    sortable: true
  },
  {
    field: 'created_at',
    header: 'Создан',
    class: 'min-w-[9rem]',
    sortable: true,
    type: 'date'
  },
  {
    field: 'updated_at',
    header: 'Обновлен',
    class: 'min-w-[9rem]',
    sortable: true,
    type: 'date'
  }
])

// Конфигурация действий
const actions = ref([
  {
    key: 'edit',
    icon: 'pi pi-pencil',
    tooltip: 'Редактировать',
    class: 'p-button-rounded p-button-text p-button-sm'
  },
  {
    key: 'delete',
    icon: 'pi pi-trash',
    tooltip: 'Удалить',
    class: 'p-button-rounded p-button-text p-button-sm p-button-danger'
  }
])

// Computed
const hasActiveFilters = computed(() => {
  if (!filtersRef.value) return false
  const filters = filtersRef.value.currentFilters || {}
  return Object.values(filters).some(value => value !== null && value !== '' && value !== false)
})

// Methods
const fetchUsers = async (params = {}) => {
  isLoading.value = true
  try {
    const requestParams = {
      page: params.page || 1,
      per_page: params.perPage || 10,
      ...params.filters
    }
    
    // Убираем null/false значения для чистоты запроса
    Object.keys(requestParams).forEach(key => {
      if (requestParams[key] === null || requestParams[key] === false || requestParams[key] === '') {
        delete requestParams[key]
      }
    })
    
    const response = await $api('/users', { params: requestParams })
    usersList.value = response.data || []
    totalRecords.value = response.meta?.total || response.total || 0
  } catch (error) {
    console.error('Error fetching users:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить пользователей.',
      life: 3000
    })
    usersList.value = []
    totalRecords.value = 0
  } finally {
    isLoading.value = false
  }
}

const handlePageChange = (event) => {
  fetchUsers(event)
}

const handleSortChange = (event) => {
  fetchUsers(event)
}

const handleFiltersChange = (event) => {
  fetchUsers(event)
}

const handleActionClick = ({ action, data }) => {
  switch (action) {
    case 'edit':
      openEditModal(data)
      break
    case 'delete':
      confirmDelete(data)
      break
    default:
      console.log('Unknown action:', action, data)
  }
}

const openCreateModal = () => {
  selectedUser.value = null
  showCreateModal.value = true
}

const openEditModal = (userToEdit) => {
  selectedUser.value = { ...userToEdit }
  showEditModal.value = true
}

const onUserCreated = (createdUser) => {
  const currentParams = {
    page: dataTableRef.value?.currentPage || 1,
    perPage: dataTableRef.value?.perPage || 10,
    filters: dataTableRef.value?.currentFilters || {}
  }
  fetchUsers(currentParams)
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Пользователь "${createdUser.name}" создан.`,
    life: 3000
  })
}

const onUserUpdated = (updatedUser) => {
  selectedUser.value = null
  const currentParams = {
    page: dataTableRef.value?.currentPage || 1,
    perPage: dataTableRef.value?.perPage || 10,
    filters: dataTableRef.value?.currentFilters || {}
  }
  fetchUsers(currentParams)
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Пользователь "${updatedUser.name}" обновлен.`,
    life: 3000
  })
}

const confirmDelete = (userToDelete) => {
  confirm.require({
    group: 'userDeletionConfirmation',
    message: `Вы уверены, что хотите удалить пользователя "${userToDelete.name}"? Это действие необратимо.`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: () => deleteUser(userToDelete.id)
  })
}

const deleteUser = async (userId) => {
  try {
    await $api(`/users/${userId}`, { method: 'DELETE' })
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Пользователь удален.',
      life: 3000
    })
    
    // Проверяем, нужно ли перейти на предыдущую страницу
    if (usersList.value.length === 1 && dataTableRef.value?.currentPage > 1) {
      dataTableRef.value.resetPagination()
    }
    
    const currentParams = {
      page: dataTableRef.value?.currentPage || 1,
      perPage: dataTableRef.value?.perPage || 10,
      filters: dataTableRef.value?.currentFilters || {}
    }
    fetchUsers(currentParams)
  } catch (error) {
    console.error('Delete user error:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.data?.message || 'Не удалось удалить пользователя.',
      life: 3000
    })
  }
}

const resetFilters = () => {
  if (filtersRef.value) {
    filtersRef.value.resetFilters()
  }
  if (dataTableRef.value) {
    dataTableRef.value.resetFilters()
  }
  fetchUsers()
}

// Lifecycle
onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
/* Кастомные стили для таблицы пользователей */
@media (max-width: 768px) {
  /* Специфические стили для аватаров в мобильной версии */
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:first-child) {
    justify-content: center !important;
    text-align: center !important;
    padding-bottom: 16px !important;
    border-bottom: 2px solid var(--border-color) !important;
    margin-bottom: 12px !important;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:first-child:before) {
    display: none;
  }
  
  :deep(.universal-responsive-table .p-avatar) {
    width: 64px !important;
    height: 64px !important;
  }
  
  /* Стили для колонки действий */
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child) {
    flex-direction: column !important;
    padding-top: 16px !important;
    border-top: 2px solid var(--border-color) !important;
    margin-top: 12px !important;
    gap: 8px;
  }
  
  :deep(.universal-responsive-table .p-datatable-tbody > tr > td:last-child .p-button) {
    flex: 1 !important;
    min-height: 44px !important;
    margin: 0 !important;
    justify-content: center !important;
  }
}
</style>