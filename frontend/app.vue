<!-- app.vue -->
<template>
  <div>
    <div v-if="!initialized && hasToken" class="loading-overlay">
      <div class="loading-spinner">
        Загрузка...
      </div>
    </div>
    
    <NuxtLayout v-else>
      <NuxtPage/>
    </NuxtLayout>
  </div>
</template>

<script setup>
const {initialized, hasToken} = useAuth()
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>