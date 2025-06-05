<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Загрузка -->
      <div v-if="pending" class="flex justify-center py-12">
        <ProgressSpinner/>
      </div>
      
      <!-- Основной контент -->
      <div v-else-if="grid">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center space-x-4">
            <Button
              class="p-button-outlined"
              icon="pi pi-arrow-left"
              @click="$router.push('/')"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-900">{{ grid.name }}</h1>
              <p v-if="grid.description" class="text-gray-600 mt-1">{{ grid.description }}</p>
            </div>
          </div>
          
          <Button
            icon="pi pi-plus"
            label="Добавить запись"
            @click="showCreateRecordModal = true"
          />
        </div>
        
        <!-- Таблица записей -->
        <Card class="shadow-sm">
          <template #content>
            <DataTable
              :globalFilterFields="['name', 'description', 'creator.name']"
              :loading="recordPending || recordsLoading"
              :paginator="true"
              :rows="20"
              :value="record"
              class="p-datatable-sm"
              dataKey="id"
              :globalFilter="globalFilter"
            >
              <template #header>
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold">Записи ({{ record?.length || 0 }})</h3>
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
              
              <Column class="w-24" header="Действия">
                <template #body="{ data }">
                  <div class="flex space-x-2">
                    <Button
                      v-tooltip.top="'Редактировать'"
                      class="p-button-outlined p-button-sm"
                      icon="pi pi-pencil"
                      @click="editRecord(data)"
                    />
                    <Button
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
    
    <!-- Модальное окно создания записи -->
    <DataGridRecordCreateModal
      v-model:visible="showCreateRecordModal"
      :grid-id="grid?.id"
      @created="onRecordCreated"
      @update:visible="onCreateModalVisibilityChange"
    />
    
    <!-- Модальное окно редактирования записи -->
    <DataGridRecordEditModal
      v-model:visible="showEditRecordModal"
      :record="selectedRecord"
      @updated="onRecordUpdated"
      @update:visible="onEditModalVisibilityChange"
    />
    
    <!-- Диалог подтверждения удаления -->
    <ConfirmDialog/>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const {$api} = useNuxtApp()
const confirm = useConfirm()
const toast = useToast()

// Реактивные данные
const recordsLoading = ref(false)
const showCreateRecordModal = ref(false)
const showEditRecordModal = ref(false)
const selectedRecord = ref(null)
const globalFilter = ref('')

// Загрузка данных
const {data: grid, pending, refresh} = await useLazyAsyncData(`dataGrid-${route.params.id}`, () =>
  $api(`/data-grid/${route.params.id}`, {
    method: 'GET'
  }).then(res => res.data)
)

const {data: record, pending: recordPending, refresh: recordRefresh} = await useLazyAsyncData(`dataGrid-${route.params.id}-records`, () =>
  $api(`/data-grid/${route.params.id}/records`, {
    method: 'GET'
  }).then(res => res.data)
)

// Методы для обработки событий модальных окон
const onCreateModalVisibilityChange = (visible) => {
  showCreateRecordModal.value = visible
  if (!visible) {
    // Дополнительная очистка при закрытии модального окна
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

// Методы для обработки CRUD операций
const onRecordCreated = async (newRecord) => {
  try {
    recordsLoading.value = true
    showCreateRecordModal.value = false
    
    // Обновляем список записей после создания
    await recordRefresh()
    
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
    await recordRefresh()
    
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
    await recordRefresh()
    
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
    await recordRefresh()
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

// Закрытие модальных окон по Escape
const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    if (showCreateRecordModal.value) {
      showCreateRecordModal.value = false
    }
    if (showEditRecordModal.value) {
      showEditRecordModal.value = false
    }
  }
}

// Добавляем слушатель клавиш при монтировании
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

// Удаляем слушатель при размонтировании
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
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