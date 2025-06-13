<template>
  <div class="sticky top-0 z-50 border-b" style="background-color: var(--secondary-bg); border-color: var(--border-color)">
    <Toolbar
      class="border-0 rounded-none px-6 py-3"
      style="background: transparent"
    >
      <template #start>
        <div class="flex items-center gap-4">
          <!-- Кнопка меню (гамбургер) -->
          <Button
            aria-label="Toggle sidebar"
            class="mr-2 lg:hidden"
            icon="pi pi-bars"
            rounded
            size="large"
            text
            @click="$emit('toggle-sidebar')"
          />
          
          <!-- Логотип и навигация -->
          <div class="flex items-center gap-4">
            <NuxtLink class="flex items-center gap-3 no-underline" to="/">
              <span class="font-bold text-xl hidden sm:block" style="color: var(--text-primary)">
                Мои таблицы
              </span>
            </NuxtLink>
          </div>
        </div>
      </template>
      
      <template #end>
        <div class="flex items-center gap-3">
          <Button
            :aria-label="isDark ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
            :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
            class="w-10 h-10"
            rounded
            severity="secondary"
            text
            @click="toggleDark()"
          />
          <!-- Профиль пользователя -->
          <div class="relative">
            <Button
              aria-label="User menu"
              class="p-1"
              rounded
              text
              @click="toggleUserMenu"
            >
              <template v-if="user?.avatar_url">
                <Avatar
                  :image="user.avatar_url"
                  class="w-8 h-8"
                  shape="circle"
                  size="normal"
                />
              </template>
              <template v-else>
                <Avatar
                  class="w-8 h-8"
                  image="https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png"
                  shape="circle"
                  size="normal"
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
const {user, logout} = useAuth()
const {isDark, toggleDark} = useTheme()

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