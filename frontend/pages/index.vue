<template>
  <div class="min-h-screen">
    <div class="border-4 border-dashed p-4 m-4">
      <h2 class="text-2xl font-bold mb-4">Добро пожаловать!</h2>
      
      <p>Вы успешно авторизованы.</p>
      
      <div class="mt-4 p-4 rounded">
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

const handleLogout = async () => {
  await logout()
}
</script>