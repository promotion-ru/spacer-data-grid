<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold" style="color: var(--text-primary)">Пользователи</h1>
      <Button
        class="p-button-success"
        icon="pi pi-plus"
        label="Добавить пользователя"
        @click="openCreateModal"
      />
    </div>
    
    <!-- Компонент фильтров -->
    <UsersDataFilters
      ref="filtersRef"
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
          :value="usersList"
          class="p-datatable-sm users-table mobile-responsive-table"
          lazy
          paginator
          responsiveLayout="scroll"
          stripedRows
          @page="onPageEvent"
          @sort="onSortEvent"
        >
          <Column class="w-20 text-center" field="avatar_url" header="Аватар">
            <template #body="slotProps">
              <Avatar
                :image="slotProps.data.avatar_url || undefined"
                :label="!slotProps.data.avatar_url && slotProps.data.name ? slotProps.data.name.charAt(0).toUpperCase() : undefined"
                class="bg-primary-100 text-primary-700"
                shape="circle"
                size="large"
              />
            </template>
          </Column>
          
          <Column class="min-w-[10rem]" field="name" header="Имя" sortable>
            <template #body="slotProps">
              <div>
                <div class="font-semibold">{{ slotProps.data.name }}</div>
                <div v-if="slotProps.data.surname" class="text-sm" style="color: var(--text-secondary)">
                  {{ slotProps.data.surname }}
                </div>
              </div>
            </template>
          </Column>
          
          <Column class="min-w-[15rem]" field="email" header="Email" sortable/>
          
          <Column class="min-w-[9rem]" field="created_at" header="Создан" sortable>
            <template #body="slotProps">
              {{ formatDate(slotProps.data.created_at) }}
            </template>
          </Column>
          
          <Column class="min-w-[9rem]" field="updated_at" header="Обновлен" sortable>
            <template #body="slotProps">
              {{ formatDate(slotProps.data.updated_at) }}
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
                  class="p-button-rounded p-button-text p-button-sm p-button-danger"
                  icon="pi pi-trash"
                  @click="confirmDelete(slotProps.data)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center p-4" style="color: var(--text-secondary)">
              {{
                hasActiveFilters ? 'Пользователи не найдены. Попробуйте изменить параметры поиска.' : 'Пользователи не найдены.'
              }}
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
    
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
definePageMeta({
  title: 'Управление пользователями',
  middleware: 'admin'
});

const {$api} = useNuxtApp();
const confirm = useConfirm();
const toast = useToast();

const usersList = ref([]);
const isLoading = ref(false);
const selectedUser = ref(null);

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

const fetchUsers = async () => {
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
    
    const response = await $api('/users', {params});
    usersList.value = response.data || [];
    totalRecords.value = response.meta?.total || response.total || 0;
  } catch (error) {
    console.error('Error fetching users:', error);
    toast.add({severity: 'error', summary: 'Ошибка', detail: 'Не удалось загрузить пользователей.', life: 3000});
    usersList.value = [];
    totalRecords.value = 0;
  } finally {
    isLoading.value = false;
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
  
  fetchUsers();
};

const onPageEvent = (event) => {
  currentPage.value = event.page + 1;
  perPage.value = event.rows;
  fetchUsers();
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
  
  fetchUsers();
};

const openCreateModal = () => {
  selectedUser.value = null;
  showCreateModal.value = true;
};

const openEditModal = (userToEdit) => {
  selectedUser.value = {...userToEdit};
  showEditModal.value = true;
};

const onUserCreated = (createdUser) => {
  fetchUsers();
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Пользователь "${createdUser.name}" создан.`,
    life: 3000
  });
};

const onUserUpdated = (updatedUser) => {
  selectedUser.value = null;
  fetchUsers();
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: `Пользователь "${updatedUser.name}" обновлен.`,
    life: 3000
  });
};

const confirmDelete = (userToDelete) => {
  confirm.require({
    group: 'userDeletionConfirmation',
    message: `Вы уверены, что хотите удалить пользователя "${userToDelete.name}"? Это действие необратимо.`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    acceptClass: 'p-button-danger',
    accept: () => deleteUser(userToDelete.id),
  });
};

const deleteUser = async (userId) => {
  try {
    await $api(`/users/${userId}`, {method: 'DELETE'});
    toast.add({severity: 'success', summary: 'Успешно', detail: 'Пользователь удален.', life: 3000});
    if (usersList.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchUsers();
  } catch (error) {
    console.error('Delete user error:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.data?.message || 'Не удалось удалить пользователя.',
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
  fetchUsers();
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
/* Специфические стили для таблицы пользователей */
@media (max-width: 768px) {
  /* Переопределяем общие стили для более красивого макета */
  :deep(.users-table .p-datatable-tbody > tr > td) {
    display: flex !important;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0 !important;
    border-bottom: 1px solid var(--border-color);
    margin: 0 !important;
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td:before) {
    position: static !important;
    width: auto !important;
    flex-shrink: 0;
    margin-right: 12px;
    font-weight: 600;
    color: var(--text-primary);
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td > *) {
    flex: 1;
    text-align: right;
  }
  
  /* Аватар - первая колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(1)) {
    justify-content: center !important;
    text-align: center !important;
    padding-bottom: 16px !important;
    border-bottom: 2px solid var(--border-color) !important;
    margin-bottom: 12px !important;
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(1):before) {
    display: none;
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(1) > *) {
    text-align: center;
  }
  
  /* Имя - вторая колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(2):before) {
    content: "Имя:";
  }
  
  /* Email - третья колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(3):before) {
    content: "Email:";
  }
  
  /* Создан - четвертая колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(4):before) {
    content: "Создан:";
  }
  
  /* Обновлен - пятая колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(5):before) {
    content: "Обновлен:";
  }
  
  /* Действия - последняя колонка */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(6)) {
    flex-direction: column !important;
    padding-top: 16px !important;
    border-top: 2px solid var(--border-color) !important;
    border-bottom: none !important;
    margin-top: 12px !important;
    gap: 8px;
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(6):before) {
    display: none;
  }
  
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(6) > div) {
    display: flex !important;
    gap: 8px;
    width: 100%;
  }
  
  /* Стили для аватаров в мобильной версии */
  :deep(.users-table .p-avatar) {
    width: 64px !important;
    height: 64px !important;
  }
  
  /* Стили для кнопок действий в мобильной версии */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(6) .p-button) {
    flex: 1 !important;
    min-height: 44px !important;
    margin: 0 !important;
    justify-content: center !important;
  }
  
  /* Обеспечиваем правильное выравнивание для сложного контента (имя + фамилия) */
  :deep(.users-table .p-datatable-tbody > tr > td:nth-child(2) > div) {
    text-align: right;
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

/* Дополнительные стили для планшетов */
@media (min-width: 769px) and (max-width: 1024px) {
  :deep(.users-table .p-datatable-tbody > tr > td) {
    padding: 0.5rem;
    font-size: 0.875rem;
  }
}
</style>
