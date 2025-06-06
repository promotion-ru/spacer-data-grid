<template>
  <Drawer
    v-model:visible="isVisible"
    :dismissable="true"
    :modal="isMobile"
    :showCloseIcon="false"
    class="w-80"
    @hide="$emit('hide')"
  >
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full bg-surface-0 dark:bg-surface-900">
        <!-- Заголовок с логотипом -->
        <div
          class="flex items-center justify-between px-6 pt-4 pb-2 shrink-0 border-b border-surface-200 dark:border-surface-700">
          <span class="inline-flex items-center gap-2">
            <span class="font-semibold text-2xl text-primary">Data grid</span>
          </span>
          <Button
            class="ml-2"
            icon="pi pi-times"
            outlined
            rounded
            size="small"
            type="button"
            @click="closeCallback"
          />
        </div>
        
        <!-- Основное меню -->
        <div class="overflow-y-auto flex-1">
          <!-- Секция FAVORITES -->
          <div class="p-4">
            <ul class="list-none p-0 m-0 overflow-hidden">
              <li v-for="item in filteredFavoritesMenu" :key="item.label">
                <a
                  v-ripple
                  class="flex items-center cursor-pointer p-3 rounded-lg text-surface-700 hover:bg-surface-100 dark:text-surface-0 dark:hover:bg-surface-800 duration-150 transition-colors p-ripple relative"
                  @click="navigateToRoute(item.route)"
                >
                  <i :class="item.icon + ' mr-3 text-surface-500'"></i>
                  <span class="font-medium">{{ item.label }}</span>
                  <span
                    v-if="item.badge"
                    class="inline-flex items-center justify-center ml-auto bg-primary text-primary-contrast rounded-full text-xs font-bold px-2 py-1 min-w-6 h-6"
                  >
                      {{ item.badge }}
                    </span>
                </a>
              </li>
            </ul>
          </div>
        </div>
        
        <!-- Профиль пользователя -->
        <div class="mt-auto border-t border-surface-200 dark:border-surface-700">
          <a
            v-ripple
            class="m-4 flex items-center cursor-pointer p-3 gap-3 rounded-lg text-surface-700 hover:bg-surface-100 dark:text-surface-0 dark:hover:bg-surface-800 duration-150 transition-colors p-ripple"
            @click="navigateToProfile"
          >
            <template v-if="user?.avatar_url">
              <Avatar
                :image="user.avatar_url"
                class="w-10 h-10"
                shape="circle"
                size="normal"
              />
            </template>
            <template v-else>
              <Avatar
                class="w-10 h-10"
                image="https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png"
                shape="circle"
                size="normal"
              />
            </template>
            <div class="flex flex-col">
              <span class="font-bold text-sm">{{ user.name }} {{ user.surname }}</span>
              <span class="text-xs text-surface-500 dark:text-surface-400">{{ showUserRoles }}</span>
            </div>
          </a>
        </div>
      </div>
    </template>
  </Drawer>
</template>

<script setup>

const {user, logout} = useAuth()
const { isAdmin } = usePermissions()

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['hide'])

// Реактивное свойство для видимости
const isVisible = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) {
      emit('hide')
    }
  }
})

// Проверка мобильного устройства
const isMobile = ref(false)

// Пункты меню FAVORITES
const favoritesMenu = ref([
  {
    label: 'Дашборд',
    icon: 'pi pi-home',
    route: '/'
  },
  {
    label: 'Активные устройства',
    icon: 'pi pi-sync',
    route: '/tokens',
  },
  {
    label: 'Пользователи',
    icon: 'pi pi-users',
    route: '/users',
    access: () => {
      return isAdmin.value
    }
  },
])

const filteredFavoritesMenu = computed(() => {
  return favoritesMenu.value.filter(item => {
    // Проверяем что элемент существует
    if (!item) {
      return false
    }
    
    if (item.hasOwnProperty('access')) {
      return item.access()
    }
    
    // Проверяем доступ
    return true
  })
})

// Функции навигации
const navigateToRoute = (route) => {
  if (route) {
    navigateTo(route)
    if (isMobile.value) {
      emit('hide')
    }
  }
}

const showUserRoles = computed(() => {
  const roles = []
  if (user.value.roles) {
    user.value.roles.forEach((role) => {
      roles.push(role.name)
    })
  }
  
  return roles.join(', ')
})

const navigateToProfile = () => {
  navigateToRoute('/profile')
  if (isMobile.value) {
    emit('hide')
  }
}

// Проверка размера экрана
const checkScreenSize = () => {
  if (process.client) {
    isMobile.value = window.innerWidth < 768
  }
}

onMounted(() => {
  if (process.client) {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize)
  }
})

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('resize', checkScreenSize)
  }
})
</script>

