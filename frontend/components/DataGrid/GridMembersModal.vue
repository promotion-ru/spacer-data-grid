<!-- components/GridMembersModal.vue -->
<template>
  <Dialog
    v-model:visible="isVisible"
    :closable="true"
    :draggable="false"
    :modal="true"
    :closeOnEscape="true"
    class="w-full max-w-4xl"
    header="Управление участниками"
  >
    <!-- Загрузка -->
    <div v-if="loading" class="flex justify-center py-8">
      <ProgressSpinner/>
    </div>
    
    <!-- Контент -->
    <div v-else class="space-y-6">
      <!-- Участники -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Участники ({{ members?.length || 0 }})
        </h3>
        
        <div v-if="members?.length" class="space-y-3">
          <div
            v-for="member in members"
            :key="member.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                <span class="text-white font-medium text-sm">
                  {{ member.user.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ member.user.name }}</p>
                <p class="text-sm text-gray-500">{{ member.user.email }}</p>
                <p class="text-xs text-gray-400">
                  Присоединился {{ member.joined_at }} • Пригласил {{ member.invited_by.name }}
                </p>
              </div>
            </div>
            
            <div class="flex items-center space-x-3">
              <!-- Права доступа -->
              <div class="flex flex-wrap gap-1">
                <Tag
                  v-for="permission in member.permissions"
                  :key="permission"
                  :value="getPermissionLabel(permission)"
                  class="text-xs"
                  severity="info"
                />
              </div>
              
              <!-- Действия -->
              <div class="flex space-x-2">
                <Button
                  v-tooltip.top="'Изменить права'"
                  class="p-button-sm p-button-outlined"
                  icon="pi pi-pencil"
                  @click="editMember(member)"
                />
                <Button
                  v-tooltip.top="'Удалить'"
                  class="p-button-sm p-button-outlined p-button-danger"
                  icon="pi pi-trash"
                  @click="confirmRemoveMember(member)"
                />
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-8">
          <i class="pi pi-users text-4xl text-gray-300 mb-2"></i>
          <p class="text-gray-500">Участников пока нет</p>
        </div>
      </div>
      
      <!-- Ожидающие приглашения -->
      <div v-if="pendingInvitations?.length">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Ожидающие приглашения ({{ pendingInvitations.length }})
        </h3>
        
        <div class="space-y-3">
          <div
            v-for="invitation in pendingInvitations"
            :key="invitation.id"
            class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg border border-yellow-200"
          >
            <div>
              <p class="font-medium text-gray-900">{{ invitation.email }}</p>
              <p class="text-sm text-gray-500">
                Приглашен {{ invitation.invited_by }} • {{ invitation.created_at }}
              </p>
              <div class="flex flex-wrap gap-1 mt-2">
                <Tag
                  v-for="permission in invitation.permissions"
                  :key="permission"
                  :value="getPermissionLabel(permission)"
                  class="text-xs"
                  severity="warning"
                />
              </div>
            </div>
            
            <div class="flex space-x-2">
              <Button
                v-tooltip.top="'Отменить'"
                :loading="processing === `cancel-${invitation.id}`"
                class="p-button-sm p-button-outlined p-button-danger"
                icon="pi pi-times"
                @click="cancelInvitation(invitation)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end">
        <Button
          class="p-button-outlined"
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
    class="w-full max-w-md"
    header="Изменить права участника"
  >
    <div v-if="editingMember" class="space-y-4">
      <div class="text-center">
        <p class="font-medium">{{ editingMember.user.name }}</p>
        <p class="text-sm text-gray-500">{{ editingMember.user.email }}</p>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Права доступа
        </label>
        <div class="space-y-3">
          <div class="flex items-center">
            <Checkbox
              id="edit-view"
              v-model="editForm.permissions"
              :disabled="true"
              class="mr-2"
              value="view"
            />
            <label class="text-sm text-gray-700" for="edit-view">Просмотр</label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-create"
              v-model="editForm.permissions"
              class="mr-2"
              value="create"
            />
            <label class="text-sm text-gray-700" for="edit-create">Создание записей</label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-update"
              v-model="editForm.permissions"
              class="mr-2"
              value="update"
            />
            <label class="text-sm text-gray-700" for="edit-update">Редактирование</label>
          </div>
          
          <div class="flex items-center">
            <Checkbox
              id="edit-delete"
              v-model="editForm.permissions"
              class="mr-2"
              value="delete"
            />
            <label class="text-sm text-gray-700" for="edit-delete">Удаление</label>
          </div>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end space-x-3">
        <Button
          class="p-button-outlined"
          label="Отмена"
          @click="showEditMemberModal = false"
        />
        <Button
          :loading="updateLoading"
          icon="pi pi-check"
          label="Сохранить"
          @click="updateMember"
        />
      </div>
    </template>
  </Dialog>
  
  <!-- Диалог подтверждения удаления -->
  <ConfirmDialog/>
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