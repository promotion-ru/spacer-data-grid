<template>
  <Drawer
    v-model:visible="isVisible"
    @hide="$emit('hide')"
    :modal="isMobile"
    :dismissable="true"
    :showCloseIcon="false"
    class="w-80"
  >
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full bg-surface-0 dark:bg-surface-900">
        <!-- Заголовок с логотипом -->
        <div class="flex items-center justify-between px-6 pt-4 pb-2 shrink-0 border-b border-surface-200 dark:border-surface-700">
          <span class="inline-flex items-center gap-2">
            <span class="font-semibold text-2xl text-primary">Data grid</span>
          </span>
          <Button
            type="button"
            @click="closeCallback"
            icon="pi pi-times"
            rounded
            outlined
            size="small"
            class="ml-2"
          />
        </div>
        
        <!-- Основное меню -->
        <div class="overflow-y-auto flex-1">
          <!-- Секция FAVORITES -->
          <div class="p-4">
            <ul class="list-none p-0 m-0 overflow-hidden">
              <li v-for="item in favoritesMenu" :key="item.label">
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
            <Avatar
              image="https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png"
              shape="circle"
              size="normal"
              class="w-10 h-10"
            />
            <div class="flex flex-col">
              <span class="font-bold text-sm">Amy Elsner</span>
              <span class="text-xs text-surface-500 dark:text-surface-400">Administrator</span>
            </div>
          </a>
        </div>
      </div>
    </template>
  </Drawer>
</template>

<script setup>
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
])

// Функции навигации
const navigateToRoute = (route) => {
  if (route) {
    navigateTo(route)
    if (isMobile.value) {
      emit('hide')
    }
  }
}

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

