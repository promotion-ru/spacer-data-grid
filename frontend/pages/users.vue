<template>
  <div class="mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Пользователи</h1>
      <Button
        label="Добавить пользователя"
        icon="pi pi-plus"
        @click="openCreateModal"
        class="p-button-success"
      />
    </div>
    
    <Card>
      <template #content>
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <span class="p-input-icon-left w-full sm:w-80">
                <i class="pi pi-search" />
                <InputText
                  v-model="searchQuery"
                  placeholder="Поиск..."
                  class="w-full"
                  @input="debouncedLoadUsers"
                />
            </span>
          </div>
          <Dropdown
            v-model="perPage"
            :options="perPageOptions"
            optionLabel="label"
            optionValue="value"
            @change="onPerPageChange"
            placeholder="Элементов на странице"
            class="w-full sm:w-auto"
          />
        </div>
        
        <DataTable
          :value="usersList"
          :loading="isLoading"
          responsiveLayout="scroll"
          stripedRows
          class="p-datatable-sm"
          paginator
          :rows="perPage"
          :totalRecords="totalRecords"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          @page="onPageEvent"
          @sort="onSortEvent"
          :sortField="currentSortField"
          :sortOrder="currentSortOrder"
          lazy
        >
          <Column field="avatar_url" header="Аватар" class="w-20 text-center">
            <template #body="slotProps">
              <Avatar
                :image="slotProps.data.avatar_url || undefined"
                :label="!slotProps.data.avatar_url && slotProps.data.name ? slotProps.data.name.charAt(0).toUpperCase() : undefined"
                shape="circle"
                size="large"
                class="bg-primary-100 text-primary-700"
              />
            </template>
          </Column>
          
          <Column field="name" header="Имя" sortable class="min-w-[10rem]">
            <template #body="slotProps">
              <div>
                <div class="font-semibold">{{ slotProps.data.name }}</div>
                <div v-if="slotProps.data.surname" class="text-sm text-gray-600">
                  {{ slotProps.data.surname }}
                </div>
              </div>
            </template>
          </Column>
          
          <Column field="email" header="Email" sortable class="min-w-[15rem]" />
          
          <Column field="created_at" header="Создан" sortable class="min-w-[9rem]">
            <template #body="slotProps">
              {{ formatDate(slotProps.data.created_at) }}
            </template>
          </Column>
          
          <Column header="Действия" class="w-32 text-center">
            <template #body="slotProps">
              <div class="flex gap-2 justify-center">
                <Button
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-sm"
                  @click="openEditModal(slotProps.data)"
                  v-tooltip.top="'Редактировать'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-sm p-button-danger"
                  @click="confirmDelete(slotProps.data)"
                  v-tooltip.top="'Удалить'"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center p-4">Пользователи не найдены.</div>
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
</template>

<script setup>
definePageMeta({
  title: 'Управление пользователями',
  middleware: 'auth' // Если есть
});

const { $api } = useNuxtApp();
const confirm = useConfirm();
const toast = useToast();

const usersList = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedUser = ref(null); // Для редактирования

const showCreateModal = ref(false);
const showEditModal = ref(false);

// Pagination state for DataTable lazy loading
const currentPage = ref(1);
const perPage = ref(10); // Default rows per page
const totalRecords = ref(0);
const currentSortField = ref('created_at');
const currentSortOrder = ref(-1);
let debounceTimer = null;

const perPageOptions = ref([
  { label: '10 на странице', value: 10 },
  { label: '25 на странице', value: 25 },
  { label: '50 на странице', value: 50 },
  { label: '100 на странице', value: 100 }
]);

const fetchUsers = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value.trim() || undefined,
      sort_by: currentSortField.value,
      sort_order: currentSortOrder.value === 1 ? 'asc' : 'desc',
    };
    const response = await $api('/users', { params });
    usersList.value = response.data || []; // Предполагаем, что API возвращает { data: [], meta: { total: ... } }
    totalRecords.value = response.meta?.total || response.total || 0;
  } catch (error) {
    console.error('Error fetching users:', error);
    toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Не удалось загрузить пользователей.', life: 3000 });
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
    return dateString; // Return original if parsing fails
  }
};

const debouncedLoadUsers = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    currentPage.value = 1; // Reset to first page on new search
    fetchUsers();
  }, 500);
};

const onPageEvent = (event) => {
  currentPage.value = event.page + 1; // PrimeVue's page is 0-indexed
  perPage.value = event.rows;
  fetchUsers();
};

const onPerPageChange = () => {
  currentPage.value = 1; // Reset to first page
  fetchUsers();
};

const onSortEvent = (event) => {
  currentSortField.value = event.sortField;
  currentSortOrder.value = event.sortOrder;
  currentPage.value = 1; // Сброс на первую страницу при смене сортировки
  fetchUsers(); // Загружаем данные с новой сортировкой
};

const openCreateModal = () => {
  selectedUser.value = null; // Очищаем на всякий случай
  showCreateModal.value = true;
};

const openEditModal = (userToEdit) => {
  selectedUser.value = { ...userToEdit }; // Клонируем для избежания прямой мутации
  showEditModal.value = true;
};

const onUserCreated = (createdUser) => {
  // showCreateModal.value = false; // Закроется через v-model
  fetchUsers(); // Обновить список
};

const onUserUpdated = (updatedUser) => {
  // showEditModal.value = false; // Закроется через v-model
  selectedUser.value = null;
  fetchUsers(); // Обновить список
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
    await $api(`/users/${userId}`, { method: 'DELETE' });
    toast.add({ severity: 'success', summary: 'Успешно', detail: 'Пользователь удален.', life: 3000 });
    // Обновить список, возможно, текущая страница стала пустой
    if (usersList.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchUsers();
  } catch (error) {
    console.error('Delete user error:', error);
    toast.add({ severity: 'error', summary: 'Ошибка', detail: error.data?.message || 'Не удалось удалить пользователя.', life: 3000 });
  }
};

onMounted(() => {
  fetchUsers();
});
</script>