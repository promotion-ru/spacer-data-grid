<template>
  <div class="min-h-screen bg-gray-50">
    <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 p-4">
      <h2 class="text-2xl font-bold mb-4">Добро пожаловать!</h2>
      
      <p class="text-gray-600">Вы успешно авторизованы через Laravel Sanctum.</p>
      
      <div class="mt-4 p-4 bg-gray-100 rounded">
        <h3 class="font-semibold">Информация о пользователе:</h3>
        <pre class="mt-2 text-sm">{{ JSON.stringify(user, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
definePageMeta({
  middleware: 'auth'
})

const {user, logout, loading} = useAuth()

</script>