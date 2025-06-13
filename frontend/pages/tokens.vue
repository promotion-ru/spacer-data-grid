<template>
  <div class="min-h-screen" style="background-color: var(--primary-bg)">
    <!-- Основной контент -->
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Заголовок страницы -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold mb-2" style="color: var(--text-primary)">
                Управление устройствами
              </h1>
              <p style="color: var(--text-secondary)">
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
                <div class="text-2xl font-bold mb-1" style="color: var(--text-primary)">
                  {{ deviceStats.total }}
                </div>
                <div class="text-sm" style="color: var(--text-secondary)">
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
                <div class="text-2xl font-bold mb-1" style="color: var(--text-primary)">
                  {{ deviceStats.active }}
                </div>
                <div class="text-sm" style="color: var(--text-secondary)">
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
                <div class="text-2xl font-bold mb-1" style="color: var(--text-primary)">
                  {{ deviceStats.expired }}
                </div>
                <div class="text-sm" style="color: var(--text-secondary)">
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
              <span style="color: var(--text-primary)">Информация о безопасности</span>
            </div>
          </template>
          
          <template #content>
            <div class="space-y-4 text-sm">
              <div class="flex items-start gap-3">
                <i class="pi pi-shield text-blue-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1" style="color: var(--text-primary)">Автоматический выход</p>
                  <p style="color: var(--text-secondary)">Неактивные сессии автоматически завершаются через 30 дней бездействия.</p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <i class="pi pi-eye text-green-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1" style="color: var(--text-primary)">Мониторинг активности</p>
                  <p style="color: var(--text-secondary)">Мы отслеживаем последнюю активность каждого устройства для вашей безопасности.</p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-orange-500 mt-0.5"></i>
                <div>
                  <p class="font-medium mb-1" style="color: var(--text-primary)">Подозрительная активность</p>
                  <p style="color: var(--text-secondary)">Если вы видите неизвестные устройства, немедленно отзовите их токены и смените пароль.</p>
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

useSeoMeta({
  title: 'Активные устройства',
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

<style scoped>
/* Responsive improvements for mobile devices */
@media (max-width: 768px) {
  /* Improve header layout on mobile */
  :deep(.flex.items-center.justify-between) {
    flex-direction: column !important;
    gap: 16px !important;
    align-items: flex-start !important;
  }
  
  :deep(.flex.items-center.justify-between > div:first-child) {
    width: 100% !important;
  }
  
  :deep(.flex.items-center.justify-between .p-button) {
    width: 100% !important;
    justify-content: center !important;
  }
  
  /* Statistics cards responsive layout */
  :deep(.grid.grid-cols-1.md\\:grid-cols-3) {
    grid-template-columns: 1fr !important;
    gap: 16px !important;
  }
  
  /* Card content improvements */
  :deep(.p-card .flex.flex-col.items-center) {
    padding: 20px 16px !important;
  }
  
  /* Icon circles responsive sizing */
  :deep(.rounded-full.p-3) {
    padding: 16px !important;
    margin-bottom: 16px !important;
  }
  
  :deep(.rounded-full.p-3 i) {
    font-size: 1.75rem !important;
  }
  
  /* Statistics text sizing */
  :deep(.text-2xl.font-bold) {
    font-size: 1.875rem !important;
    margin-bottom: 8px !important;
  }
  
  /* Container padding adjustments */
  .container.mx-auto.px-4.py-8 {
    padding: 16px !important;
  }
  
  /* Page title responsive sizing */
  h1 {
    font-size: 1.875rem !important;
    line-height: 2.25rem !important;
    margin-bottom: 12px !important;
  }
  
  /* Security info section improvements */
  :deep(.mt-8) {
    margin-top: 24px !important;
  }
  
  :deep(.space-y-4) {
    gap: 16px !important;
  }
  
  :deep(.flex.items-start.gap-3) {
    gap: 12px !important;
    align-items: flex-start !important;
  }
  
  :deep(.flex.items-start.gap-3 i) {
    margin-top: 2px !important;
    font-size: 16px !important;
  }
  
  /* Better text readability on mobile */
  :deep(.text-sm) {
    font-size: 14px !important;
    line-height: 1.5 !important;
  }
}

/* Improve card transitions and hover effects */
:deep(.p-card) {
  transition: all 0.3s ease !important;
}

:deep(.p-card:hover) {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

/* Better button styling */
:deep(.p-button) {
  transition: all 0.2s ease !important;
}

/* Improve max-width for better readability on large screens */
.max-w-4xl {
  max-width: 56rem !important;
}

/* Statistics card icons color coordination */
:deep(.bg-blue-100) {
  background-color: rgba(59, 130, 246, 0.1) !important;
}

:deep(.bg-green-100) {
  background-color: rgba(16, 185, 129, 0.1) !important;
}

:deep(.bg-orange-100) {
  background-color: rgba(245, 158, 11, 0.1) !important;
}

/* Dark theme adjustments for icons */
[data-theme="dark"] :deep(.bg-blue-100) {
  background-color: rgba(59, 130, 246, 0.2) !important;
}

[data-theme="dark"] :deep(.bg-green-100) {
  background-color: rgba(16, 185, 129, 0.2) !important;
}

[data-theme="dark"] :deep(.bg-orange-100) {
  background-color: rgba(245, 158, 11, 0.2) !important;
}
</style>