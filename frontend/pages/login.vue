<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-slate-900 to-black">
    <div class="relative max-w-md w-full mx-4">
      <Card class="bg-gray-800/50 backdrop-blur-xl shadow-2xl rounded-2xl border border-gray-700/50 p-8">
        <template #header>
          <div class="text-center mb-4">
            <div
              class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
              <i class="pi pi-lock text-white" style="font-size: 2rem"></i>
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">
              Добро пожаловать
            </h2>
            <p class="text-gray-400 text-sm">
              Войдите в свой аккаунт для продолжения
            </p>
          </div>
        </template>
        <template #content>
          <form class="space-y-6" @submit.prevent="handleLogin">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-300" for="email">Email</label>
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
              <label class="block text-sm font-medium text-gray-300" for="password">Пароль</label>
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
              class="w-full justify-center group relative flex py-3 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-gray-800 disabled:opacity-50 transform transition-all duration-200 hover:scale-[1.02] hover:shadow-xl shadow-lg"
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