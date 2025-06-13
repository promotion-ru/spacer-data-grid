<!-- components/GridMembersModal.vue -->
<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :draggable="false"
    :modal="true"
    :closeOnEscape="true"
    class="w-[95vw] sm:w-[85vw] md:w-[75vw] lg:w-[65vw] xl:max-w-4xl mx-4 sm:mx-0"
    header="Управление участниками"
  >
    <!-- Загрузка -->
    <div v-if="loading" class="flex justify-center py-6 sm:py-8">
      <ProgressSpinner/>
    </div>
    
    <!-- Контент -->
    <div v-else class="space-y-4 sm:space-y-6">
      <!-- Участники -->
      <div>
        <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4" >
          Участники ({{ members?.length || 0 }})
        </h3>
        
        <div v-if="members?.length" class="space-y-2 sm:space-y-3">
          <div
            v-for="member in members"
            :key="member.id"
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 rounded-lg border space-y-3 sm:space-y-0"
            style="background-color: var(--tertiary-bg); border-color: var(--border-color)"
          >
            <!-- Информация о пользователе -->
            <div class="flex items-center space-x-3 min-w-0 flex-1">
              <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--primary-color)">
                <span class="font-medium text-sm sm:text-base" style="color: var(--white)">
                  {{ member.user.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-sm sm:text-base truncate" >{{ member.user.name }}</p>
                <p class="text-xs sm:text-sm truncate text-secondary" >{{ member.user.email }}</p>
                <p class="text-xs hidden sm:block text-secondary" >
                  Присоединился {{ member.joined_at }} • Пригласил {{ member.invited_by.name }}
                </p>
                <!-- Мобильная версия информации -->
                <p class="text-xs sm:hidden text-secondary" >
                  {{ member.joined_at }}
                </p>
              </div>
            </div>
            
            <!-- Права и действия -->
            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-3 sm:flex-shrink-0">
              <!-- Права доступа -->
              <div class="flex flex-wrap gap-1 sm:gap-2">
                <Tag
                  v-for="permission in member.permissions"
                  :key="permission"
                  :value="getPermissionLabel(permission)"
                  class="text-xs sm:text-sm py-1 px-2"
                  severity="info"
                />
              </div>
              
              <!-- Действия -->
              <div class="flex space-x-2 justify-end sm:justify-start">
                <Button
                  v-tooltip.top="'Изменить права'"
                  outlined
                  size="small"
                  class="flex-shrink-0 flex-1 p-2"
                  icon="pi pi-pencil"
                  @click="editMember(member)"
                />
                <Button
                  v-tooltip.top="'Удалить'"
                  outlined
                  severity="danger"
                  size="small"
                  class="flex-shrink-0 flex-1 p-2"
                  icon="pi pi-trash"
                  @click="confirmRemoveMember(member)"
                />
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-6 sm:py-8">
          <i class="pi pi-users text-3xl sm:text-4xl mb-2" style="color: var(--text-muted)"></i>
          <p class="text-sm sm:text-base text-secondary" >Участников пока нет</p>
        </div>
      </div>
      
      <!-- Ожидающие приглашения -->
      <div v-if="pendingInvitations?.length">
        <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4" >
          Ожидающие приглашения ({{ pendingInvitations.length }})
        </h3>
        
        <div class="space-y-2 sm:space-y-3">
          <div
            v-for="invitation in pendingInvitations"
            :key="invitation.id"
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 rounded-lg border space-y-3 sm:space-y-0"
            style="background-color: var(--warning-light); border-color: var(--warning-border)"
          >
            <!-- Информация о приглашении -->
            <div class="min-w-0 flex-1">
              <p class="font-medium text-sm sm:text-base truncate" >{{ invitation.email }}</p>
              <p class="text-xs sm:text-sm truncate text-secondary" >
                Приглашен {{ invitation.invited_by }} • {{ invitation.created_at }}
              </p>
              <div class="flex flex-wrap gap-1 sm:gap-2 mt-2">
                <Tag
                  v-for="permission in invitation.permissions"
                  :key="permission"
                  :value="getPermissionLabel(permission)"
                  class="text-xs sm:text-sm py-1 px-2"
                  severity="warning"
                />
              </div>
            </div>
            
            <!-- Действия -->
            <div class="flex justify-end sm:justify-start">
              <Button
                v-tooltip.top="'Отменить'"
                :loading="processing === `cancel-${invitation.id}`"
                outlined
                severity="danger"
                size="small"
                class="flex-1 p-2"
                icon="pi pi-times"
                @click="cancelInvitation(invitation)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end pt-3 sm:pt-0">
        <Button
          outlined
          class="w-full sm:w-auto px-4 py-2 sm:px-6"
          label="Закрыть"
          @click="closeModal"
        />
      </div>
    </template>
  </Dialog>
  
  <!-- Модальное окно редактирования участника -->
  <Dialog
    v-model:visible="showEditMemberModal"
    :closable="true"
    :modal="true"
    :closeOnEscape="true"
    class="w-[95vw] sm:w-[80vw] md:w-[60vw] lg:w-[40vw] xl:max-w-md mx-4 sm:mx-0"
    header="Изменить права участника"
  >
    <div v-if="editingMember" class="space-y-4 sm:space-y-6">
      <!-- Информация о пользователе -->
      <div class="text-center p-3 sm:p-4 rounded-lg" style="background-color: var(--tertiary-bg)">
        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: var(--primary-color)">
          <span class="font-medium text-base sm:text-lg" style="color: var(--white)">
            {{ editingMember.user.name.charAt(0).toUpperCase() }}
          </span>
        </div>
        <p class="font-medium text-sm sm:text-base" >{{ editingMember.user.name }}</p>
        <p class="text-xs sm:text-sm break-all text-secondary" >{{ editingMember.user.email }}</p>
      </div>
      
      <!-- Права доступа -->
      <div>
        <label class="block text-sm sm:text-base font-medium mb-3 sm:mb-4" >
          Права доступа
        </label>
        <div class="space-y-3 sm:space-y-4">
          <div class="flex items-center">
            <Checkbox
              id="edit-view"
              v-model="editForm.permissions"
              :disabled="true"
              class="mr-3"
              value="view"
            />
            <label class="text-sm sm:text-base cursor-pointer" for="edit-view" >
              Просмотр
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-create"
              v-model="editForm.permissions"
              class="mr-3"
              value="create"
            />
            <label class="text-sm sm:text-base cursor-pointer" for="edit-create" >
              Создание записей
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-update"
              v-model="editForm.permissions"
              class="mr-3"
              value="update"
            />
            <label class="text-sm sm:text-base cursor-pointer" for="edit-update" >
              Редактирование
            </label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-delete"
              v-model="editForm.permissions"
              class="mr-3"
              value="delete"
            />
            <label class="text-sm sm:text-base cursor-pointer" for="edit-delete" >
              Удаление
            </label>
          </div>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="flex flex-col flex-row justify-end space-y-2 sm:space-x-3 pt-3 sm:pt-0">
        <Button
          outlined
          class="w-full mb-0 mr-2"
          label="Отмена"
          @click="showEditMemberModal = false"
        />
        <Button
          :loading="updateLoading"
          icon="pi pi-check"
          label="Сохранить"
          class="w-full sm:w-auto pr-5! pl-5!"
          @click="updateMember"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
const props = defineProps({
  visible: Boolean,
  grid: Object
})

const emit = defineEmits(['update:visible'])

const {$api} = useNuxtApp()
const confirm = useConfirm()
const toast = useToast()

// Реактивные данные
const loading = ref(false)
const processing = ref(null)
const updateLoading = ref(false)
const members = ref([])
const pendingInvitations = ref([])
const showEditMemberModal = ref(false)
const editingMember = ref(null)
const editForm = ref({
  permissions: []
})

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

// Методы
const getPermissionLabel = (permission) => {
  const labels = {
    view: 'Просмотр',
    create: 'Создание',
    update: 'Редактирование',
    delete: 'Удаление'
  }
  return labels[permission] || permission
}

const loadMembers = async () => {
  if (!props.grid?.id) return
  
  loading.value = true
  try {
    const response = await $api(`/data-grid/${props.grid.id}/members`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    members.value = response.data.members
    pendingInvitations.value = response.data.pending_invitations
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить участников',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const editMember = (member) => {
  editingMember.value = member
  editForm.value = {
    permissions: [...member.permissions]
  }
  showEditMemberModal.value = true
}

const updateMember = async () => {
  if (!editingMember.value) return
  
  updateLoading.value = true
  try {
    await $api(`/data-grid/${props.grid.id}/members/${editingMember.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(editForm.value),
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    await loadMembers()
    showEditMemberModal.value = false
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Права участника обновлены',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось обновить права',
      life: 3000
    })
  } finally {
    updateLoading.value = false
  }
}

const confirmRemoveMember = (member) => {
  confirm.require({
    message: `Удалить ${member.user.name} из таблицы?`,
    header: 'Подтверждение удаления',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Удалить',
    rejectLabel: 'Отмена',
    accept: () => removeMember(member)
  })
}

const removeMember = async (member) => {
  try {
    await $api(`/data-grid/${props.grid.id}/members/${member.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    await loadMembers()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Участник удален',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось удалить участника',
      life: 3000
    })
  }
}

const cancelInvitation = async (invitation) => {
  processing.value = `cancel-${invitation.id}`
  try {
    await $api(`/data-grid/${props.grid.id}/invitations/${invitation.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    await loadMembers()
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Приглашение отменено',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось отменить приглашение',
      life: 3000
    })
  } finally {
    processing.value = null
  }
}

const closeModal = () => {
  isVisible.value = false
}

// Загрузка данных при открытии модального окна
watch(isVisible, (newValue) => {
  if (newValue) {
    loadMembers()
  }
})
</script>

<style scoped>
/* Дополнительные стили для улучшения touch-интерфейса */
@media (max-width: 640px) {
  :deep(.p-dialog .p-dialog-header) {
    padding: 1rem 1.5rem;
  }
  
  :deep(.p-dialog .p-dialog-content) {
    padding: 0 1.5rem 1rem 1.5rem;
  }
  
  :deep(.p-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem;
  }
  
  :deep(.p-button.p-button-sm) {
    min-height: 44px;
    min-width: 44px;
  }
  
  :deep(.p-checkbox) {
    width: 20px;
    height: 20px;
  }
  
  :deep(.p-tag) {
    min-height: 28px;
  }
}

/* Улучшение отображения на планшетах */
@media (min-width: 641px) and (max-width: 1024px) {
  :deep(.p-dialog .p-dialog-header) {
    padding: 1.25rem 2rem;
  }
  
  :deep(.p-dialog .p-dialog-content) {
    padding: 0 2rem 1.25rem 2rem;
  }
  
  :deep(.p-dialog .p-dialog-footer) {
    padding: 1.25rem 2rem;
  }
}
</style>