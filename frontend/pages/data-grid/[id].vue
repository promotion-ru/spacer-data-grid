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
        
        <!-- Таблица записей -->
        <Card class="shadow-sm">
          <template #content>
            <DataTable
              :globalFilter="globalFilter"
              :globalFilterFields="['name', 'description', 'creator.name']"
              :loading="pending || recordsLoading"
              :paginator="true"
              :rows="20"
              :value="grid.records"
              class="p-datatable-sm"
              dataKey="id"
            >
              <template #header>
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold">Записи ({{ grid?.records?.length || 0 }})</h3>
                  <span class="p-input-icon-left">
                    <i class="pi pi-search"/>
                    <InputText
                      v-model="globalFilter"
                      class="p-inputtext-sm"
                      placeholder="Поиск записей..."
                    />
                  </span>
                </div>
              </template>
              
              <template #empty>
                <div class="text-center py-8">
                  <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                  <p class="text-gray-500">В таблице пока нет записей</p>
                  <Button
                    v-if="hasPermission('create')"
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
    
    <!-- Модальное окно просмотра записи -->
    <DataGridRecordViewModal
      v-model:visible="showViewRecordModal"
      :record="selectedRecord"
      @update:visible="onViewModalVisibilityChange"
    />
    
    <!-- Модальное окно создания записи -->
    <DataGridRecordCreateModal
      v-if="hasPermission('create')"
      v-model:visible="showCreateRecordModal"
      :grid-id="grid?.id"
      @created="onRecordCreated"
      @update:visible="onCreateModalVisibilityChange"
    />
    
    <!-- Модальное окно редактирования записи -->
    <DataGridRecordEditModal
      v-if="hasPermission('update')"
      v-model:visible="showEditRecordModal"
      :record="selectedRecord"
      @updated="onRecordUpdated"
      @update:visible="onEditModalVisibilityChange"
    />
    
    <!-- Модальное окно совместного использования -->
    <DataGridShareModal
      v-if="grid?.is_owner"
      v-model:visible="showShareModal"
      :grid="grid"
      @invited="onUserInvited"
    />
    
    <!-- Модальное окно управления участниками -->
    <DataGridMembersModal
      v-if="grid?.is_owner"
      v-model:visible="showMembersModal"
      :grid="grid"
    />
    
    <!-- Диалог подтверждения удаления -->
    <ConfirmDialog/>
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

// Реактивные данные
const recordsLoading = ref(false)
const showViewRecordModal = ref(false)

const showCreateRecordModal = ref(false)
const showEditRecordModal = ref(false)
const showShareModal = ref(false)
const showMembersModal = ref(false)
const selectedRecord = ref(null)
const globalFilter = ref('')

// Загрузка данных
const {data: grid, pending, refresh} = await useLazyAsyncData(`dataGrid-${route.params.id}`, () =>
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

// Методы для обработки событий модальных окон
const viewRecord = (record) => {
  selectedRecord.value = record
  showViewRecordModal.value = true
}

const onViewModalVisibilityChange = (visible) => {
  showViewRecordModal.value = visible
  if (!visible) {
    selectedRecord.value = null
    console.log('Модальное окно просмотра закрыто')
  }
}
const onCreateModalVisibilityChange = (visible) => {
  showCreateRecordModal.value = visible
  if (!visible) {
    console.log('Модальное окно создания закрыто')
  }
}

const onEditModalVisibilityChange = (visible) => {
  showEditRecordModal.value = visible
  if (!visible) {
    selectedRecord.value = null
    console.log('Модальное окно редактирования закрыто')
  }
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
  try {
    recordsLoading.value = true
    showCreateRecordModal.value = false
    
    // Обновляем список записей после создания
    await refresh()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись создана и список обновлен',
      life: 3000
    })
    
    console.log('Новая запись создана:', newRecord)
  } catch (error) {
    console.error('Ошибка при обновлении списка после создания:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось обновить список записей',
      life: 3000
    })
  } finally {
    recordsLoading.value = false
  }
}

const onRecordUpdated = async (updatedRecord) => {
  try {
    recordsLoading.value = true
    showEditRecordModal.value = false
    selectedRecord.value = null
    
    // Обновляем список записей после редактирования
    await refresh()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись обновлена и список обновлен',
      life: 3000
    })
    
    console.log('Запись обновлена:', updatedRecord)
  } catch (error) {
    console.error('Ошибка при обновлении списка после редактирования:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось обновить список записей',
      life: 3000
    })
  } finally {
    recordsLoading.value = false
  }
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
    recordsLoading.value = true
    
    await $api(`/data-grid/${grid.value.id}/records/${record.id}`, {
      method: 'DELETE'
    })
    
    // Обновляем список записей после удаления
    await refresh()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Запись удалена и список обновлен',
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
  } finally {
    recordsLoading.value = false
  }
}

// Дополнительная функция для ручного обновления списка
const forceRefreshRecords = async () => {
  try {
    recordsLoading.value = true
    await refresh()
    toast.add({
      severity: 'info',
      summary: 'Обновлено',
      detail: 'Список записей обновлен',
      life: 2000
    })
  } catch (error) {
    console.error('Ошибка при обновлении:', error)
  } finally {
    recordsLoading.value = false
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>