<template>
  <div class="min-h-screen flex items-center justify-center" style="background-color: var(--primary-bg)">
    <!-- Переключатель темы -->
    <div class="absolute top-6 right-6 z-10">
      <ThemeToggle />
    </div>
    
    <div class="relative max-w-md w-full mx-4">
      <Card class="shadow-xl rounded-lg border p-8">
        <template #header>
          <div class="text-center mb-4">
            <div
              class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
              <i class="pi pi-lock text-white" style="font-size: 2rem"></i>
            </div>
            <h2 class="text-3xl font-bold mb-2" style="color: var(--text-primary)">
              Добро пожаловать
            </h2>
            <p class="text-sm" style="color: var(--text-secondary)">
              Войдите в свой аккаунт для продолжения
            </p>
          </div>
        </template>
        <template #content>
          <form class="space-y-6" @submit.prevent="handleLogin">
            <div class="space-y-2">
              <label class="block text-sm font-medium" style="color: var(--text-primary)" for="email">Email</label>
              <IconField iconPosition="left">
                <InputIcon class="pi pi-envelope"></InputIcon>
                <InputText
                  id="email"
                  v-model="form.email"
                  :disabled="loading"
                  class="w-full"
                  required
                  type="email"
                />
              </IconField>
            </div>
            
            <div class="space-y-2 mt-8">
              <label class="block text-sm font-medium" style="color: var(--text-primary)" for="password">Пароль</label>
              <IconField iconPosition="left">
                <InputIcon class="pi pi-lock"></InputIcon>
                <Password
                  id="password"
                  v-model="form.password"
                  :disabled="loading"
                  :feedback="false"
                  class="w-full"
                  inputClass="w-full"
                  required
                  toggleMask
                />
              </IconField>
            </div>
            
            <Message v-if="errorMessage" :closable="false" severity="error">
              {{ errorMessage }}
            </Message>
            
            <Button
              :label="loading ? 'Выполняется вход...' : 'Войти в аккаунт'"
              :loading="loading"
              class="w-full justify-center py-3 px-4 text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50"
              type="submit"
            />
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script lang="ts" setup>
definePageMeta({
  layout: false,
  middleware: 'guest',
  title: 'Вход в систему',
})

useSeoMeta({
  title: 'Вход в систему',
})

const {login, loading} = useAuth()
const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const errorMessage = ref('')

const handleLogin = async () => {
  errorMessage.value = ''
  
  const result = await login(form)
  
  if (result.success) {
    await router.push('/')
  } else {
    errorMessage.value = result.error || 'Ошибка входа'
  }
}
</script>

<style scoped>
</style>