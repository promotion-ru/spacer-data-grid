<template>
  <div v-if="description" class="description-wrapper">
    <div class="flex items-start gap-3">
      <p class="flex-1 text-sm lg:text-base text-color-secondary leading-relaxed break-words">
        {{ isTruncated ? truncatedText : description }}
        <span v-if="isTruncated" class="text-color-secondary opacity-60">...</span>
      </p>
      
      <Button
        v-if="isTruncated"
        v-tooltip.top="'Показать полное описание'"
        text
        rounded
        size="small"
        icon="pi pi-eye"
        severity="secondary"
        class="eye-button flex-shrink-0 min-w-[2rem] h-8"
        @click="showModal = true"
      />
    </div>
    
    <!-- Модальное окно для полного описания -->
    <Dialog
      v-model:visible="showModal"
      :header="modalTitle"
      :modal="true"
      :closable="true"
      :closeOnEscape="true"
      :dismissableMask="true"
      :draggable="false"
      class="w-full max-w-2xl"
      :breakpoints="{ '960px': '75vw', '641px': '95vw' }"
    >
      <div class="description-modal-content space-y-6">
        <div>
          <label class="block text-sm font-medium mb-3 form-label">Полное описание</label>
          <div class="p-4 rounded-lg border bg-surface-100 dark:bg-surface-200">
            <p class="text-sm leading-relaxed whitespace-pre-wrap text-color">{{ description }}</p>
          </div>
        </div>
        
        <div v-if="showMetaInfo" class="text-xs text-color-secondary border-t border-surface-border pt-4 space-y-1">
          <p>Символов: {{ description?.length || 0 }}</p>
          <p v-if="wordsCount">Слов: {{ wordsCount }}</p>
        </div>
      </div>
      
      <template #footer>
        <div class="flex justify-end space-x-3">
          <Button
            outlined
            icon="pi pi-times"
            label="Закрыть"
            severity="secondary"
            @click="showModal = false"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
const props = defineProps({
  description: {
    type: String,
    default: ''
  },
  maxLength: {
    type: Number,
    default: 120
  },
  modalTitle: {
    type: String,
    default: 'Описание таблицы'
  },
  showMetaInfo: {
    type: Boolean,
    default: true
  }
})

const showModal = ref(false)

const isTruncated = computed(() => {
  return props.description && props.description.length > props.maxLength
})

const truncatedText = computed(() => {
  if (!props.description || props.description.length <= props.maxLength) {
    return props.description
  }
  
  // Обрезаем по словам, чтобы не разрывать слова посередине
  const words = props.description.slice(0, props.maxLength).split(' ')
  words.pop() // Удаляем последнее слово, которое может быть обрезано
  return words.join(' ')
})

const wordsCount = computed(() => {
  if (!props.description) return 0
  return props.description.trim().split(/\s+/).filter(word => word.length > 0).length
})
</script>

<style scoped>
.description-wrapper {
  position: relative;
}

.eye-button {
  transition: all 0.2s ease-in-out;
}

.eye-button:hover {
  background-color: var(--primary-color-50);
  color: var(--primary-color);
  transform: scale(1.05);
}

.eye-button:active {
  transform: scale(0.95);
}

.description-modal-content {
  max-height: 65vh;
  overflow-y: auto;
}

.description-modal-content::-webkit-scrollbar {
  width: 6px;
}

.description-modal-content::-webkit-scrollbar-track {
  background: var(--surface-100);
  border-radius: 3px;
}

.description-modal-content::-webkit-scrollbar-thumb {
  background: var(--surface-400);
  border-radius: 3px;
}

.description-modal-content::-webkit-scrollbar-thumb:hover {
  background: var(--surface-500);
}

@media (max-width: 768px) {
  .description-modal-content {
    max-height: 50vh;
  }
  
  .eye-button {
    min-width: 28px;
    min-height: 28px;
  }
}

@media (max-width: 480px) {
  .description-modal-content {
    max-height: 40vh;
  }
}
</style>