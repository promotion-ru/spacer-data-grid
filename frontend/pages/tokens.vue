<template>
  <div class="min-h-screen">
    <!-- Основной контент -->
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Заголовок страницы -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold mb-2">
                Управление устройствами
              </h1>
              <p>
                Просматривайте и управляйте активными сессиями ваших устройств
              </p>
            </div>
            <Button
              :loading="refreshing"
              icon="pi pi-refresh"
              label="Обновить"
              outlined
              severity="secondary"
              @click="refreshData"
            />
          </div>
        </div>
        
        <!-- Статистика -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card class="text-center">
            <template #content>
              <div class="flex flex-col items-center">
                <div class="bg-blue-100 rounded-full p-3 mb-3">
                  <i class="pi pi-mobile text-2xl text-blue-600"></i>
                </div>
                <div class="text-2xl font-bold mb-1">
                  {{ deviceStats.total }}
                </div>
                <div class="text-sm">
                  Всего устройств
                </div>
              </div>
            </template>
          </Card>
          
          <Card class="text-center">
            <template #content>
              <div class="flex flex-col items-center">
                <div class="bg-green-100 rounded-full p-3 mb-3">
                  <i class="pi pi-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="text-2xl font-bold mb-1">
                  {{ deviceStats.active }}
                </div>
                <div class="text-sm">
                  Активных сейчас
                </div>
              </div>
            </template>
          </Card>
          
          <Card class="text-center">
            <template #content>
              <div class="flex flex-col items-center">
                <div class="bg-orange-100 rounded-full p-3 mb-3">
                  <i class="pi pi-clock text-2xl text-orange-600"></i>
                </div>
                <div class="text-2xl font-bold mb-1">
                  {{ deviceStats.expired }}
                </div>
                <div class="text-sm">
                  Истекших токенов
                </div>
              </div>
            </template>
          </Card>
        </div>
        
        <!-- Компонент активных устройств -->
        <ActiveDevices @devices-updated="updateStats"/>
        
        <!-- Дополнительная информация -->
        <Card class="mt-8">
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-info-circle text-primary"></i>
              <span>Информация о безопасности</span>
            </div>
          </template>
          
          <template #content>
            <div class="space-y-4 text-sm">
              <div class="flex items-start gap-3">
                <i class="pi pi-shield text-blue-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1">Автоматический выход</p>
                  <p>Неактивные сессии автоматически завершаются через 30 дней бездействия.</p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <i class="pi pi-eye text-green-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1">Мониторинг активности</p>
                  <p>Мы отслеживаем последнюю активность каждого устройства для вашей безопасности.</p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-orange-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1">Подозрительная активность</p>
                  <p>Если вы видите неизвестные устройства, немедленно отзовите их токены и смените пароль.</p>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
    
    <!-- Toast для уведомлений -->
    <Toast/>
  </div>
</template>

<script setup>
// Мета-данные страницы
definePageMeta({
  title: 'Активные устройства',
  middleware: 'auth'
})

// Состояние
const refreshing = ref(false)
const deviceStats = ref({
  total: 0,
  active: 0,
  expired: 0
})

// Композаблы
const {getTokens} = useAuth()
const toast = useToast()

// Обновление статистики устройств
const updateStats = async (tokens) => {
  try {
    const deviceTokens = tokens || await getTokens()
    
    deviceStats.value = {
      total: deviceTokens.length,
      active: deviceTokens.filter(token => !token.is_expired).length,
      expired: deviceTokens.filter(token => token.is_expired).length
    }
  } catch (error) {
    console.error('Ошибка обновления статистики:', error)
  }
}

// Обновление данных
const refreshData = async () => {
  try {
    refreshing.value = true
    await updateStats()
    
    toast.add({
      severity: 'success',
      summary: 'Данные обновлены',
      detail: 'Информация об устройствах успешно обновлена',
      life: 3000
    })
  } catch (error) {
    console.error('Ошибка обновления данных:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось обновить данные',
      life: 3000
    })
  } finally {
    refreshing.value = false
  }
}

// Инициализация
onMounted(() => {
  updateStats()
})
</script>