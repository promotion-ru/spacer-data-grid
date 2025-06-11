<template>
  <div class="mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Типы данных</h1>
      <Button
        class="p-button-success"
        icon="pi pi-plus"
        label="Добавить тип"
        @click="openCreateModal"
      />
    </div>
    
    <!-- Компонент фильтров -->
    <TypesDataFilters
      ref="filtersRef"
      :currentUserId="currentUser?.id"
      :dataGridOptions="dataGridOptions"
      :loading="isLoading"
      :totalCount="totalRecords"
      @filtersChanged="onFiltersChanged"
    />
    
    <Card>
      <template #content>
        <DataTable
          :loading="isLoading"
          :rows="perPage"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          :sortField="currentSortField"
          :sortOrder="currentSortOrder"
          :totalRecords="totalRecords"
          :value="typesList"
          class="p-datatable-sm"
          lazy
          paginator
          responsiveLayout="scroll"
          stripedRows
          @page="onPageEvent"
          @sort="onSortEvent"
        >
          <Column class="min-w-[10rem]" field="name" header="Название" sortable>
            <template #body="slotProps">
              <span class="font-semibold">{{ slotProps.data.name }}</span>
            </template>
          </Column>
          
          <Column class="min-w-[12rem]" field="data_grid_name" header="Таблица" sortable/>
          
          <Column class="min-w-[10rem]" field="creator_name" header="Создатель" sortable>
            <template #body="slotProps">
              <div v-if="slotProps.data.creator_name">
                {{ slotProps.data.creator_name }}
              </div>
              <span v-else class="text-gray-400">Не указан</span>
            </template>
          </Column>
          
          <Column class="min-w-[9rem]" field="created_at" header="Создан" sortable>
            <template #body="slotProps">
              {{ formatDate(slotProps.data.created_at) }}
            </template>
          </Column>
          
          <Column class="w-32 text-center" header="Действия">
            <template #body="slotProps">
              <div class="flex gap-2 justify-center">
                <Button
                  v-tooltip.top="'Редактировать'"
                  class="p-button-rounded p-button-text p-button-sm"
                  icon="pi pi-pencil"
                  @click="openEditModal(slotProps.data)"
                />
                <Button
                  v-tooltip.top="'Удалить'"
                  :disabled="slotProps.data.records_count > 0"
                  class="p-button-rounded p-button-text p-button-sm p-button-danger"
                  icon="pi pi-trash"
                  @click="confirmDelete(slotProps.data)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center p-4">
              {{ hasActiveFilters ? 'Типы не найдены. Попробуйте изменить параметры поиска.' : 'Типы не найдены.' }}
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
    
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
</template>

<script setup>
definePageMeta({
  title: 'Управление типами данных',
  middleware: 'admin'
});

const {$api} = useNuxtApp();
const confirm = useConfirm();
const toast = useToast();

const typesList = ref([]);
const dataGridOptions = ref([]);
const isLoading = ref(false);
const selectedType = ref(null);
const currentUser = ref(null);

const showCreateModal = ref(false);
const showEditModal = ref(false);

// Pagination state for DataTable lazy loading
const currentPage = ref(1);
const perPage = ref(10);
const totalRecords = ref(0);
const currentSortField = ref('created_at');
const currentSortOrder = ref(-1);

// Filters state
const currentFilters = ref({});
const filtersRef = ref(null);

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(currentFilters.value).some(value => value !== null && value !== '' && value !== false)
});

const fetchTypes = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      ...currentFilters.value
    };
    
    // Убираем null/false значения для чистоты запроса
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === false || params[key] === '') {
        delete params[key];
      }
    });
    
    const response = await $api('/data-grid-types', {params});
    typesList.value = response.data || [];
    totalRecords.value = response.meta?.total || response.total || 0;
  } catch (error) {
    console.error('Error fetching types:', error);
    toast.add({severity: 'error', summary: 'Ошибка', detail: 'Не удалось загрузить типы.', life: 3000});
    typesList.value = [];
    totalRecords.value = 0;
  } finally {
    isLoading.value = false;
  }
};

const fetchDataGrids = async () => {
  try {
    const response = await $api('/data-grid', {
      method: 'GET',
    })
    dataGridOptions.value = response.data || [];
  } catch (error) {
    console.error('Error fetching data grids:', error);
    toast.add({severity: 'error', summary: 'Ошибка', detail: 'Не удалось загрузить список таблиц.', life: 3000});
    dataGridOptions.value = [];
  }
};

const fetchCurrentUser = async () => {
  try {
    const response = await $api('/user/profile', {
      method: 'GET',
    })
    currentUser.value = response.data || null;
  } catch (error) {
    console.error('Error fetching current user:', error);
    currentUser.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return new Date(dateString).toLocaleDateString('ru-RU', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    });
  } catch (e) {
    return dateString;
  }
};

const onFiltersChanged = (filters) => {
  currentFilters.value = filters;
  currentPage.value = 1;
  
  // Обновляем сортировку из фильтров
  if (filters.sort_by && filters.sort_order) {
    currentSortField.value = filters.sort_by;
    currentSortOrder.value = filters.sort_order === 'asc' ? 1 : -1;
  }
  
  fetchTypes();
};

const onPageEvent = (event) => {
  currentPage.value = event.page + 1;
  perPage.value = event.rows;
  fetchTypes();
};

const onSortEvent = (event) => {
  currentSortField.value = event.sortField;
  currentSortOrder.value = event.sortOrder;
  currentPage.value = 1;
  
  // Обновляем фильтры с новой сортировкой
  currentFilters.value = {
    ...currentFilters.value,
    sort_by: event.sortField,
    sort_order: event.sortOrder === 1 ? 'asc' : 'desc'
  };
  
  fetchTypes();
};

const openCreateModal = () => {
  selectedType.value = null;
  showCreateModal.value = true;
};

const openEditModal = (typeToEdit) => {
  selectedType.value = {...typeToEdit};
  showEditModal.value = true;
};

const onTypeCreated = (createdType) => {
  fetchTypes();
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Тип "${createdType.name}" создан.`,
    life: 3000
  });
};

const onTypeUpdated = (updatedType) => {
  selectedType.value = null;
  fetchTypes();
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Тип "${updatedType.name}" обновлен.`,
    life: 3000
  });
};

const confirmDelete = (typeToDelete) => {
  if (typeToDelete.records_count > 0) {
    toast.add({
      severity: 'warn',
      summary: 'Внимание',
      detail: 'Тип нельзя удалить, так как он используется в записях.',
      life: 3000
    });
    return;
  }
  
  confirm.require({
    group: 'typeDeletionConfirmation',
    message: `Вы уверены, что хотите удалить тип "${typeToDelete.name}"? Это действие необратимо.`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: () => deleteType(typeToDelete.id),
  });
};

const deleteType = async (typeId) => {
  try {
    await $api(`/data-grid-types/${typeId}`, {method: 'DELETE'});
    toast.add({severity: 'success', summary: 'Успешно', detail: 'Тип удален.', life: 3000});
    if (typesList.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchTypes();
  } catch (error) {
    console.error('Delete type error:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.data?.message || 'Не удалось удалить тип.',
      life: 3000
    });
  }
};

const resetFilters = () => {
  if (filtersRef.value) {
    filtersRef.value.resetFilters();
  }
  currentFilters.value = {};
  currentPage.value = 1;
  currentSortField.value = 'created_at';
  currentSortOrder.value = -1;
  fetchTypes();
};

onMounted(async () => {
  await Promise.all([
    fetchCurrentUser(),
    fetchDataGrids()
  ]);
  await fetchTypes();
});
</script>
