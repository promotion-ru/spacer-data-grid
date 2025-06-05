<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-slate-900 to-black">
    <!-- Декоративные элементы фона -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -inset-10 opacity-20">
        <div
          class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
        <div
          class="absolute top-3/4 right-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-2000"></div>
      </div>
    </div>
    
    <div class="relative max-w-md w-full mx-4">
      <!-- Основная карточка -->
      <div class="bg-gray-800/50 backdrop-blur-xl shadow-2xl rounded-2xl border border-gray-700/50 p-8">
        <!-- Заголовок -->
        <div class="text-center mb-8">
          <div
            class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2"/>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">
            Добро пожаловать
          </h2>
          <p class="text-gray-400 text-sm">
            Войдите в свой аккаунт для продолжения
          </p>
        </div>
        
        <!-- Форма -->
        <form class="space-y-6" @submit.prevent="handleLogin">
          <!-- Email поле -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-300 block" for="email">
              Email адрес
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                    stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"/>
                </svg>
              </div>
              <input
                id="email"
                v-model="form.email"
                :disabled="loading"
                class="block w-full pl-10 pr-3 py-3 bg-gray-700/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-gray-700/70"
                placeholder="your@email.com"
                required
                type="email"
              >
            </div>
          </div>
          
          <!-- Password поле -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-300 block" for="password">
              Пароль
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"/>
                </svg>
              </div>
              <input
                id="password"
                v-model="form.password"
                :disabled="loading"
                class="block w-full pl-10 pr-3 py-3 bg-gray-700/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:bg-gray-700/70"
                placeholder="••••••••"
                required
                type="password"
              >
            </div>
          </div>
          
          <!-- Сообщение об ошибке -->
          <div
            v-if="errorMessage"
            class="bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-red-400 text-sm text-center backdrop-blur-sm"
          >
            <svg class="inline h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"/>
            </svg>
            {{ errorMessage }}
          </div>
          
          <!-- Кнопка входа -->
          <button
            :disabled="loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transform transition-all duration-200 hover:scale-[1.02] hover:shadow-xl shadow-lg"
            type="submit"
          >
            <span v-if="loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      fill="currentColor"></path>
              </svg>
            </span>
            {{ loading ? 'Выполняется вход...' : 'Войти в аккаунт' }}
          </button>
        </form>
      </div>
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
.animation-delay-2000 {
  animation-delay: 2s;
}

/* Дополнительные анимации для загрузки */
@keyframes pulse {
  0%, 100% {
    opacity: 0.3;
  }
  50% {
    opacity: 0.8;
  }
}
</style>