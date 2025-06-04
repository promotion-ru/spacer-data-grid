<template>
  <div class="min-h-screen bg-surface-ground">
    <!-- Верхний тулбар -->
    <AppToolbar @toggle-sidebar="toggleSidebar"/>
    
    <!-- Боковое меню -->
    <AppSidebar :visible="sidebarVisible" @hide="sidebarVisible = false"/>
    
    <!-- Основной контент -->
    <main :class="{ 'ml-0': !sidebarVisible }">
      <slot/>
    </main>
  </div>
</template>

<script setup>
// Состояние бокового меню
const sidebarVisible = ref(false)

// Функция переключения бокового меню
const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
}

// Автоматическое скрытие меню на мобильных устройствах
const checkScreenSize = () => {
  if (process.client && window.innerWidth < 768) {
    sidebarVisible.value = false
  }
}

// Слушатель изменения размера экрана
onMounted(() => {
  if (process.client) {
    window.addEventListener('resize', checkScreenSize)
    checkScreenSize()
  }
})

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('resize', checkScreenSize)
  }
})
</script>