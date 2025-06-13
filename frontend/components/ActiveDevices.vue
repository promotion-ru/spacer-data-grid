<template>
  <Card class="w-full">
    <template #title>
      <div class="flex items-center gap-2">
        <i class="pi pi-mobile text-primary"></i>
        <span class="text-primary">Активные устройства</span>
      </div>
    </template>
    
    <template #content>
      <div v-if="loading" class="flex justify-center py-8">
        <ProgressSpinner
          style="width: 50px; height: 50px"
          strokeWidth="4"
          fill="transparent"
          animationDuration="1s"
        />
      </div>
      
      <div v-else-if="tokens.length === 0" class="text-center py-8">
        <i class="pi pi-info-circle text-4xl mb-3 text-secondary" ></i>
        <p class="text-lg text-secondary" >Нет активных токенов</p>
      </div>
      
      <div v-else class="space-y-4">
        <Card
          v-for="token in tokens"
          :key="token.id"
          :class="[
            'transition-all duration-200 hover:shadow-md',
            token.is_current ? 'border-2 border-green-200 bg-green-50/30' : 'border'
          ]"
          :style="{
            borderColor: token.is_current ? '#10b981' : 'var(--border-color)',
            backgroundColor: token.is_current ? 'rgba(16, 185, 129, 0.1)' : 'var(--secondary-bg)'
          }"
        >
          <template #content>
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-3">
                  <div class="flex items-center gap-2">
                    <i :class="getDeviceIcon(token.name)" class="text-lg text-primary"></i>
                    <span class="font-semibold text-lg" >
                      {{ token.name }}
                    </span>
                  </div>
                  
                  <Badge
                    v-if="token.is_current"
                    value="Текущее устройство"
                    severity="success"
                    class="ml-2"
                  />
                  
                  <Badge
                    v-if="token.is_expired"
                    value="Истек"
                    severity="danger"
                    class="ml-2"
                  />
                </div>
                
                <div class="space-y-2 text-sm text-secondary" >
                  <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-xs text-secondary" ></i>
                    <span>Создан: {{ formatDate(token.created_at) }}</span>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <i class="pi pi-clock text-xs text-secondary" ></i>
                    <span>Последнее использование: {{ formatDate(token.last_used_at) }}</span>
                  </div>
                  
                  <div v-if="token.expires_at" class="flex items-center gap-2">
                    <i class="pi pi-hourglass text-xs text-secondary" ></i>
                    <span>Истекает: {{ formatDate(token.expires_at) }}</span>
                  </div>
                </div>
              </div>
              
              <Button
                v-if="!token.is_current"
                @click="handleRevokeToken(token.id)"
                label="Отозвать"
                severity="danger"
                outlined
                size="small"
                class="ml-4"
                :loading="revokingTokenId === token.id"
              >
                <template #icon>
                  <i class="pi pi-times"></i>
                </template>
              </Button>
            </div>
          </template>
        </Card>
        
        <Divider v-if="tokens.length > 1" />
        
        <div v-if="tokens.length > 1" class="flex flex-col sm:flex-row gap-3 responsive-buttons">
          <Button
            @click="handleLogoutOthers"
            label="Выйти с других устройств"
            severity="warning"
            class="flex-1"
            :loading="logoutOthersLoading"
          >
            <template #icon>
              <i class="pi pi-sign-out"></i>
            </template>
          </Button>
          
          <Button
            @click="handleRefreshToken"
            label="Обновить токен"
            severity="info"
            class="flex-1"
            :loading="refreshTokenLoading"
          >
            <template #icon>
              <i class="pi pi-refresh"></i>
            </template>
          </Button>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
const { getTokens, revokeToken, logoutOtherDevices, refreshToken } = useAuth()

const toast = useToast()

const tokens = ref([])
const loading = ref(true)
const revokingTokenId = ref(null)
const logoutOthersLoading = ref(false)
const refreshTokenLoading = ref(false)

const loadTokens = async () => {
  try {
    loading.value = true
    const response = await getTokens()
    tokens.value = response || []
  } catch (error) {
    console.error('Ошибка загрузки токенов:', error)
    tokens.value = []
  } finally {
    loading.value = false
  }
}

const handleRevokeToken = async (tokenId) => {
  try {
    revokingTokenId.value = tokenId
    const result = await revokeToken(tokenId)
    if (result.success) {
      await loadTokens()
      toast.add({
        severity: 'success',
        summary: 'Успешно',
        detail: 'Токен отозван',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: result.message || 'Не удалось отозвать токен',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Ошибка отзыва токена:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Произошла ошибка при отзыве токена',
      life: 3000
    })
  } finally {
    revokingTokenId.value = null
  }
}

const handleLogoutOthers = async () => {
  try {
    logoutOthersLoading.value = true
    const result = await logoutOtherDevices()
    if (result.success) {
      await loadTokens()
      toast.add({
        severity: 'success',
        summary: 'Успешно',
        detail: 'Выход с других устройств выполнен',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: result.message || 'Не удалось выйти с других устройств',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Ошибка выхода с других устройств:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Произошла ошибка при выходе с других устройств',
      life: 3000
    })
  } finally {
    logoutOthersLoading.value = false
  }
}

const handleRefreshToken = async () => {
  try {
    refreshTokenLoading.value = true
    const result = await refreshToken()
    if (result.success) {
      await loadTokens()
      toast.add({
        severity: 'success',
        summary: 'Успешно',
        detail: 'Токен обновлен',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Ошибка',
        detail: result.message || 'Не удалось обновить токен',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Ошибка обновления токена:', error)
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Произошла ошибка при обновлении токена',
      life: 3000
    })
  } finally {
    refreshTokenLoading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Никогда'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.abs(Math.floor(diffMs / (1000 * 60 * 60 * 24)))
  
  if (diffDays === 0) {
    return `Сегодня в ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`
  } else if (diffDays === 1) {
    return `Вчера в ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`
  } else if (diffDays > 7) {
    return `${diffDays} дн.`
  } else {
    return date.toLocaleString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const getDeviceIcon = (deviceName) => {
  const name = deviceName.toLowerCase()
  
  if (name.includes('mobile') || name.includes('android') || name.includes('ios')) {
    return 'pi pi-mobile'
  } else if (name.includes('tablet') || name.includes('ipad')) {
    return 'pi pi-tablet'
  } else if (name.includes('desktop') || name.includes('windows') || name.includes('mac')) {
    return 'pi pi-desktop'
  } else if (name.includes('chrome') || name.includes('firefox') || name.includes('safari')) {
    return 'pi pi-globe'
  } else {
    return 'pi pi-cog'
  }
}

onMounted(() => {
  loadTokens()
})
</script>

<style scoped>
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Responsive improvements for mobile devices */
@media (max-width: 768px) {
  /* Stack device information vertically on mobile */
  :deep(.p-card-content > div > .flex) {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 16px !important;
  }
  
  /* Improve token card layout for mobile */
  :deep(.space-y-4 > .p-card) {
    margin-bottom: 16px !important;
  }
  
  /* Make device info more readable on mobile */
  :deep(.space-y-4 > .p-card .flex-1) {
    width: 100% !important;
    margin-bottom: 12px !important;
  }
  
  /* Adjust revoke button placement */
  :deep(.space-y-4 > .p-card .flex > .p-button) {
    margin-left: 0 !important;
    margin-top: 8px !important;
    width: 100% !important;
    max-width: 120px !important;
    align-self: flex-end !important;
  }
  
  /* Responsive buttons styling */
  .responsive-buttons {
    gap: 12px !important;
  }
  
  .responsive-buttons .p-button {
    min-height: 48px !important;
    font-size: 14px !important;
  }
  
  /* Device icon and name layout improvements */
  :deep(.flex.items-center.gap-3) {
    flex-wrap: wrap !important;
  }
  
  /* Badge improvements for mobile */
  :deep(.p-badge) {
    font-size: 11px !important;
    padding: 4px 8px !important;
  }
}

/* Better spacing for device info items */
:deep(.space-y-2 > div) {
  display: flex !important;
  align-items: center !important;
  word-break: break-word !important;
}

/* Icon styling improvements */
:deep(.pi) {
  flex-shrink: 0 !important;
}
</style>