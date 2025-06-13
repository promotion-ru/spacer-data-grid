<template>
  <div class="theme-toggle">
    <Button
      :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
      :aria-label="isDark ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
      class="theme-toggle-btn"
      severity="secondary"
      text
      rounded
      @click="toggleDark()"
    />
  </div>
</template>

<script setup lang="ts">
const { isDark, toggleDark } = useTheme()
</script>

<style scoped>
</style>