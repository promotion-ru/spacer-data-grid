<template>
  <div class="sticky top-0 z-50 bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-700">
    <Toolbar
      class="border-0 rounded-none px-6 py-3"
      style="background: transparent"
    >
      <template #start>
        <div class="flex items-center gap-4">
          <!-- Кнопка меню (гамбургер) -->
          <Button
            icon="pi pi-bars"
            @click="$emit('toggle-sidebar')"
            text
            rounded
            size="large"
            class="mr-2 lg:hidden"
            aria-label="Toggle sidebar"
          />
          
          <!-- Логотип и навигация -->
          <div class="flex items-center gap-4">
            <NuxtLink to="/" class="flex items-center gap-3 no-underline">
              <span class="font-bold text-xl text-surface-900 dark:text-surface-0 hidden sm:block">
                Data grid
              </span>
            </NuxtLink>
          </div>
        </div>
      </template>
      
      <template #end>
        <div class="flex items-center gap-3">
<!--          <Button-->
<!--            @click="toggleDark()"-->
<!--            :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"-->
<!--            severity="secondary"-->
<!--            text-->
<!--            rounded-->
<!--            :aria-label="isDark ? 'Переключить на светлую тему' : 'Переключить на темную тему'"-->
<!--            class="w-10 h-10 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"-->
<!--          />-->
          <!-- Профиль пользователя -->
          <div class="relative">
            <Button
              @click="toggleUserMenu"
              text
              rounded
              class="p-1"
              aria-label="User menu"
            >
              <template v-if="user?.avatar_url">
                <Avatar
                  :image="user.avatar_url"
                  size="normal"
                  shape="circle"
                  class="w-8 h-8"
                />
              </template>
              <template v-else>
                <Avatar
                  image="https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png"
                  size="normal"
                  shape="circle"
                  class="w-8 h-8"
                />
              </template>
            </Button>
            
            <!-- Выпадающее меню пользователя -->
            <Menu
              ref="userMenu"
              :model="userMenuItems"
              :popup="true"
              class="mt-2"
            />
          </div>
        </div>
      </template>
    </Toolbar>
  </div>
</template>

<script setup>
const { user, logout } = useAuth()
const { isDark, toggleDark } = useTheme()

const emit = defineEmits(['toggle-sidebar'])
const userMenu = ref()

// Пункты меню пользователя
const userMenuItems = ref([
  {
    label: 'Профиль',
    icon: 'pi pi-user',
    command: () => navigateTo('/profile')
  },
  {
    separator: true
  },
  {
    label: 'Выйти',
    icon: 'pi pi-sign-out',
    command: () => handleLogout()
  }
])

const toggleUserMenu = (event) => {
  userMenu.value.toggle(event)
}

const handleLogout = async () => {
  await logout()
}

</script>