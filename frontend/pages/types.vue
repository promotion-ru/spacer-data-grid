<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    <div class="container mx-auto px-4 py-8">
      <UniversalDataTable
        ref="dataTableRef"
        :actions="actions"
        :columns="columns"
        :data="typesList"
        :loading="isLoading"
        :totalRecords="totalRecords"
        addButtonLabel="Добавить тип"
        emptyFilteredMessage="Типы не найдены. Попробуйте изменить параметры поиска."
        emptyMessage="Типы не найдены."
        title="Типы данных"
        @add-clicked="openCreateModal"
        @action-clicked="handleActionClick"
        @page-changed="handlePageChange"
        @sort-changed="handleSortChange"
        @filters-changed="handleFiltersChange"
      >
        <!-- Слот для фильтров -->
        <template #filters="{ loading, totalCount, onFiltersChanged }">
          <TypesDataFilters
            ref="filtersRef"
            :currentUserId="currentUser?.id"
            :dataGridOptions="dataGridOptions"
            :loading="loading"
            :totalCount="totalCount"
            @filtersChanged="onFiltersChanged"
          />
        </template>
        
        <!-- Слот для колонки названия -->
        <template #column-name="{ data }">
          <span class="font-semibold">{{ data.name }}</span>
        </template>
        
        <!-- Слот для колонки создателя -->
        <template #column-creator_name="{ data }">
          <div v-if="data.creator_name">
            {{ data.creator_name }}
          </div>
          <span v-else class="text-gray-400">Не указан</span>
        </template>
        
        <!-- Слот для кастомных действий с условной логикой -->
        <template #actions="{ data }">
          <div class="flex gap-2 justify-center w-full justify-end">
            <Button
              v-tooltip.top="'Редактировать'"
              class="p-button-rounded p-button-text p-button-sm grow-1 md:grow-0"
              icon="pi pi-pencil"
              severity="secondary"
              @click="handleActionClick({ action: 'edit', data })"
            />
            <Button
              v-tooltip.top="data.records_count > 0 ? 'Нельзя удалить - есть связанные записи' : 'Удалить'"
              :disabled="data.records_count > 0"
              class="p-button-rounded p-button-text p-button-sm p-button-danger grow-1 md:grow-0"
              icon="pi pi-trash"
              @click="handleActionClick({ action: 'delete', data })"
            />
          </div>
        </template>
      </UniversalDataTable>
      
      <!-- Модальные окна -->
      <TypesCreateTypeModal
        v-model:visible="showCreateModal"
        :data-grid-options="dataGridOptions"
        @type-created="onTypeCreated"
      />
      
      <TypesEditTypeModal
        v-model:visible="showEditModal"
        :data-grid-options="dataGridOptions"
        :type="selectedType"
        @type-updated="onTypeUpdated"
      />
      
      <ConfirmDialog group="typeDeletionConfirmation"></ConfirmDialog>
    </div>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import UniversalDataTable from '~/components/UniversalDataTable.vue'

definePageMeta({
  title: 'Управление типами данных',
  middleware: 'admin'
})

const {$api} = useNuxtApp()
const confirm = useConfirm()
const toast = useToast()

// Reactive data
const typesList = ref([])
const dataGridOptions = ref([])
const isLoading = ref(false)
const selectedType = ref(null)
const currentUser = ref(null)
const totalRecords = ref(0)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const dataTableRef = ref(null)
const filtersRef = ref(null)

// Конфигурация колонок
const columns = ref([
  {
    field: 'name',
    header: 'Название',
    class: 'min-w-[10rem]',
    sortable: true
  },
  {
    field: 'data_grid_name',
    header: 'Таблица',
    class: 'min-w-[12rem]',
    sortable: true
  },
  {
    field: 'creator_name',
    header: 'Создатель',
    class: 'min-w-[10rem]',
    sortable: true
  },
  {
    field: 'created_at',
    header: 'Создан',
    class: 'min-w-[9rem]',
    sortable: true,
    type: 'date'
  }
])

// Конфигурация действий с условиями
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
    class: 'p-button-rounded p-button-text p-button-sm p-button-danger',
    condition: (data) => data.records_count === 0,
    disabled: (data) => data.records_count > 0
  }
])

// Methods
const fetchTypes = async (params = {}) => {
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
    
    const response = await $api('/data-grid-types', {params: requestParams})
    typesList.value = response.data || []
    totalRecords.value = response.meta?.total || response.total || 0
  } catch (error) {
    console.error('Error fetching types:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить типы.',
      life: 3000
    })
    typesList.value = []
    totalRecords.value = 0
  } finally {
    isLoading.value = false
  }
}

const fetchDataGrids = async () => {
  try {
    const response = await $api('/data-grid', {method: 'GET'})
    dataGridOptions.value = response.data || []
  } catch (error) {
    console.error('Error fetching data grids:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить список таблиц.',
      life: 3000
    })
    dataGridOptions.value = []
  }
}

const fetchCurrentUser = async () => {
  try {
    const response = await $api('/user/profile', {method: 'GET'})
    currentUser.value = response.data || null
  } catch (error) {
    console.error('Error fetching current user:', error)
    currentUser.value = null
  }
}

const handlePageChange = (event) => {
  fetchTypes(event)
}

const handleSortChange = (event) => {
  fetchTypes(event)
}

const handleFiltersChange = (event) => {
  fetchTypes(event)
}

const handleActionClick = ({action, data}) => {
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
  selectedType.value = null
  showCreateModal.value = true
}

const openEditModal = (typeToEdit) => {
  selectedType.value = {...typeToEdit}
  showEditModal.value = true
}

const onTypeCreated = (createdType) => {
  const currentParams = {
    page: dataTableRef.value?.currentPage || 1,
    perPage: dataTableRef.value?.perPage || 10,
    filters: dataTableRef.value?.currentFilters || {}
  }
  fetchTypes(currentParams)
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Тип "${createdType.name}" создан.`,
    life: 3000
  })
}

const onTypeUpdated = (updatedType) => {
  selectedType.value = null
  const currentParams = {
    page: dataTableRef.value?.currentPage || 1,
    perPage: dataTableRef.value?.perPage || 10,
    filters: dataTableRef.value?.currentFilters || {}
  }
  fetchTypes(currentParams)
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Тип "${updatedType.name}" обновлен.`,
    life: 3000
  })
}

const confirmDelete = (typeToDelete) => {
  if (typeToDelete.records_count > 0) {
    toast.add({
      severity: 'warn',
      summary: 'Внимание',
      detail: 'Тип нельзя удалить, так как он используется в записях.',
      life: 3000
    })
    return
  }
  
  confirm.require({
    group: 'typeDeletionConfirmation',
    message: `Вы уверены, что хотите удалить тип "${typeToDelete.name}"? Это действие необратимо.`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: () => deleteType(typeToDelete.id)
  })
}

const deleteType = async (typeId) => {
  try {
    await $api(`/data-grid-types/${typeId}`, {method: 'DELETE'})
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Тип удален.',
      life: 3000
    })
    
    // Проверяем, нужно ли перейти на предыдущую страницу
    if (typesList.value.length === 1 && dataTableRef.value?.currentPage > 1) {
      dataTableRef.value.resetPagination()
    }
    
    const currentParams = {
      page: dataTableRef.value?.currentPage || 1,
      perPage: dataTableRef.value?.perPage || 10,
      filters: dataTableRef.value?.currentFilters || {}
    }
    fetchTypes(currentParams)
  } catch (error) {
    console.error('Delete type error:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.data?.message || 'Не удалось удалить тип.',
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
  fetchTypes()
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchCurrentUser(),
    fetchDataGrids()
  ])
  await fetchTypes()
})
</script>

<style scoped>

</style>