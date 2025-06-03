<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold">Приложение</h1>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-gray-700">Привет, {{ user?.name }}</span>
            <button
              @click="handleLogout"
              :disabled="loading"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
            >
              {{ loading ? 'Выход...' : 'Выйти' }}
            </button>
          </div>
        </div>
      </div>
    </nav>
    
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 p-4">
          <h2 class="text-2xl font-bold mb-4">Добро пожаловать!</h2>
          <p class="text-gray-600">Вы успешно авторизованы через Laravel Sanctum.</p>
          
          <div class="mt-4 p-4 bg-gray-100 rounded">
            <h3 class="font-semibold">Информация о пользователе:</h3>
            <pre class="mt-2 text-sm">{{ JSON.stringify(user, null, 2) }}</pre>
          </div>
        </div>
      </div>
      
      <token-manager />
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const { user, logout, loading } = useAuth()

const handleLogout = async () => {
  await logout()
}
</script>