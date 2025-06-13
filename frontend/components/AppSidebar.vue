<template>
  <Drawer
    v-model:visible="isVisible"
    :dismissable="true"
    :showCloseIcon="false"
    class="w-80"
    @hide="$emit('hide')"
  >
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full" style="background-color: var(--secondary-bg)">
        <div
          class="flex items-center justify-between px-6 pt-4 pb-2 shrink-0 border-b"
          style="border-color: var(--border-color)">
          <span class="inline-flex items-center gap-2">
            <span class="font-semibold text-2xl" style="color: var(--text-primary)">Data grid</span>
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
                <NuxtLink
                  v-ripple
                  :to="item.route"
                  class="flex items-center cursor-pointer p-3 rounded-lg p-ripple relative transition-all duration-200"
                  :class="{
                    'bg-primary/10 border-l-4 border-primary': isActiveRoute(item.route),
                    'hover:bg-surface-hover': !isActiveRoute(item.route)
                  }"
                  :style="isActiveRoute(item.route) ? 'color: var(--primary-color)' : 'color: var(--text-primary)'"
                  @click="closeMobileSidebar(item.route)"
                >
                  <i 
                    :class="item.icon + ' mr-3'" 
                    :style="isActiveRoute(item.route) ? 'color: var(--primary-color)' : 'color: var(--text-secondary)'"
                  ></i>
                  <span class="font-medium">{{ item.label }}</span>
                  <span
                    v-if="item.badge"
                    class="inline-flex items-center justify-center ml-auto bg-primary text-primary-contrast rounded-full text-xs font-bold px-2 py-1 min-w-6 h-6"
                  >
                      {{ item.badge }}
                    </span>
                </NuxtLink>
              </li>
            </ul>
          </div>
        </div>
        
        <!-- Профиль пользователя -->
        <div class="mt-auto border-t" style="border-color: var(--border-color)">
          <NuxtLink
            v-ripple
            to="/profile"
            class="m-4 flex items-center cursor-pointer p-3 gap-3 rounded-lg p-ripple transition-all duration-200"
            :class="{
              'bg-primary/10 border-l-4 border-primary': isActiveRoute('/profile'),
              'hover:bg-surface-hover': !isActiveRoute('/profile')
            }"
            :style="isActiveRoute('/profile') ? 'color: var(--primary-color)' : 'color: var(--text-primary)'"
            @click="closeMobileSidebar"
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
              <span class="font-bold text-sm" style="color: var(--text-primary)">{{ user.name }} {{ user.surname }}</span>
              <span class="text-xs" style="color: var(--text-secondary)">{{ showUserRoles }}</span>
            </div>
          </NuxtLink>
        </div>
      </div>
    </template>
  </Drawer>
</template>

<script setup>

const {user, logout} = useAuth()
const {isAdmin} = usePermissions()
const { isMobile } = useDevice()
const route = useRoute()

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
  {
    label: 'Типы',
    icon: 'pi pi-users',
    route: '/types',
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

const closeMobileSidebar = () => {
  if (isMobile) {
    emit('hide')
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

// Функция для определения активного маршрута
const isActiveRoute = (routePath) => {
  if (routePath === '/') {
    return route.path === '/'
  }
  return route.path.startsWith(routePath)
}
</script>

