<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Заголовок и кнопка создания -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Мои таблицы данных</h1>
          <p class="text-gray-600 mt-2">Управляйте своими данными в удобном формате</p>
        </div>
        <Button
          label="Создать таблицу"
          icon="pi pi-plus"
          class="p-button-lg"
          @click="showCreateModal = true"
        />
      </div>
      
      <!-- Загрузка -->
      <div v-if="pending" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>
      
      <!-- Список таблиц -->
      <div v-else-if="grids?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <Card
          v-for="grid in grids"
          :key="grid.id"
          class="hover:shadow-lg transition-shadow duration-300 cursor-pointer"
          @click="navigateToGrid(grid)"
        >
          <template #header>
            <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500">
              <img
                v-if="grid.image_url"
                :src="grid.image_url"
                :alt="grid.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
                <i class="pi pi-table text-4xl text-white"></i>
              </div>
            </div>
          </template>
          
          <template #title>
            <div class="flex justify-between items-start">
              <h3 class="text-lg font-semibold text-gray-900 truncate">{{ grid.name }}</h3>
              <Button
                icon="pi pi-ellipsis-v"
                class="p-button-text p-button-sm"
                @click.stop="toggleGridMenu($event, grid)"
              />
            </div>
          </template>
          
          <template #content>
            <div class="space-y-3">
              <p v-if="grid.description" class="text-gray-600 text-sm line-clamp-2">
                {{ grid.description }}
              </p>
              
              <div class="flex items-center justify-between text-sm text-gray-500">
                <span class="flex items-center">
                  <i class="pi pi-list mr-1"></i>
                  {{ grid.records_count }} записей
                </span>
                <span>{{ grid.created_at }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
      
      <!-- Пустое состояние -->
      <div v-else class="text-center py-12">
        <i class="pi pi-table text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">У вас пока нет таблиц данных</h3>
        <p class="text-gray-500 mb-6">Создайте свою первую таблицу для управления данными</p>
        <Button
          label="Создать таблицу"
          icon="pi pi-plus"
          @click="showCreateModal = true"
        />
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
    
    <!-- Контекстное меню -->
    <ContextMenu ref="gridMenu" :model="gridMenuItems" />
    <!-- Toast для уведомлений -->
    <Toast />
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

const { $api } = useNuxtApp()
const router = useRouter()
const toast = useToast()

// Реактивные данные
const showCreateModal = ref(false)
const showEditModal = ref(false)
const gridMenu = ref()
const selectedGrid = ref(null)

// Загрузка данных
const { data: grids, pending, refresh } = await useLazyAsyncData('dataGrids', async () => {
    const response = await $api('/data-grid', {
      method: 'GET'
    })
    return response.data
  }
)

// Методы
const navigateToGrid = (grid) => {
  router.push(`/data-grid/${grid.id}`)
}

const toggleGridMenu = (event, grid) => {
  selectedGrid.value = grid
  gridMenu.value.toggle(event)
}

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

const editGrid = () => {
  if (selectedGrid.value) {
    showEditModal.value = true
  }
}

const deleteGrid = async () => {
  console.log(selectedGrid.value)
  if (!selectedGrid.value) {
    return
  }
  
  try {
    await $api(`/data-grid/${selectedGrid.value.id}`, {
      method: 'DELETE'
    })
    await refresh()
    selectedGrid.value = null
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Таблица удалена',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить таблицу',
      life: 3000
    })
  }
}

// Элементы контекстного меню
const gridMenuItems = [
  {
    label: 'Редактировать',
    icon: 'pi pi-pencil',
    command: editGrid
  },
  {
    label: 'Удалить',
    icon: 'pi pi-trash',
    command: deleteGrid
  }
]
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>