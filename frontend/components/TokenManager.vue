<template>
  <div class="bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Активные устройства</h3>
    
    <div v-if="loading" class="text-center py-4">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
    </div>
    
    <div v-else-if="tokens.length === 0" class="text-gray-500 text-center py-4">
      Нет активных токенов
    </div>
    
    <div v-else class="space-y-4">
      <div
        v-for="token in tokens"
        :key="token.id"
        class="border rounded-lg p-4"
        :class="token.is_current ? 'border-green-200 bg-green-50' : 'border-gray-200'"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-2">
              <span class="text-sm font-medium text-gray-900">
                {{ token.name }}
              </span>
              <span v-if="token.is_current" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Текущее устройство
              </span>
              <span v-if="token.is_expired" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                Истек
              </span>
            </div>
            <p class="text-xs text-gray-400 mt-1">
              Создан: {{ formatDate(token.created_at) }}
            </p>
            <p class="text-xs text-gray-400">
              Последнее использование: {{ formatDate(token.last_used_at) }}
            </p>
            <p v-if="token.expires_at" class="text-xs text-gray-400">
              Истекает: {{ formatDate(token.expires_at) }}
            </p>
          </div>
          <button
            v-if="!token.is_current"
            @click="handleRevokeToken(token.id)"
            class="ml-4 text-red-600 hover:text-red-800 text-sm font-medium"
          >
            Отозвать
          </button>
        </div>
      </div>
      
      <div v-if="tokens.length > 1" class="pt-4 border-t space-y-2">
        <button
          @click="handleLogoutOthers"
          class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 text-sm font-medium"
        >
          Выйти с других устройств
        </button>
        <button
          @click="handleRefreshToken"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-sm font-medium"
        >
          Обновить токен
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { getTokens, revokeToken, logoutOtherDevices, refreshToken } = useAuth()

const tokens = ref([])
const loading = ref(true)

const loadTokens = async () => {
  loading.value = true
  tokens.value = await getTokens()
  loading.value = false
}

const handleRevokeToken = async (tokenId: number) => {
  const result = await revokeToken(tokenId)
  if (result.success) {
    await loadTokens()
  }
}

const handleLogoutOthers = async () => {
  const result = await logoutOtherDevices()
  if (result.success) {
    await loadTokens()
  }
}

const handleRefreshToken = async () => {
  const result = await refreshToken()
  if (result.success) {
    await loadTokens()
  }
}

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'Никогда'
  return new Date(dateString).toLocaleString('ru-RU')
}

onMounted(() => {
  loadTokens()
})
</script>