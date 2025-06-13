<!-- components/InvitationNotifications.vue -->
<template>
  <div v-if="invitations?.length" class="mb-6">
    <Card class="border-l-4 border-l-blue-500" style="background-color: var(--tertiary-bg)">
      <template #content>
        <div class="space-y-4">
          <div class="flex items-center space-x-2">
            <i class="pi pi-envelope text-blue-600 text-xl"></i>
            <h3 class="text-lg font-semibold" style="color: var(--text-primary)">
              Приглашения в таблицы ({{ invitations.length }})
            </h3>
          </div>
          
          <div class="space-y-3">
            <div
              v-for="invitation in invitations"
              :key="invitation.id"
              class="rounded-lg p-4 border"
              style="background-color: var(--secondary-bg); border-color: var(--border-color)"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h4 class="font-medium" style="color: var(--text-primary)">{{ invitation.data_grid.name }}</h4>
                  <p class="text-sm mt-1" style="color: var(--text-secondary)">
                    {{ invitation.invited_by.name }} пригласил вас в таблицу
                  </p>
                  <p v-if="invitation.data_grid.description" class="text-sm mt-1" style="color: var(--text-secondary)">
                    {{ invitation.data_grid.description }}
                  </p>
                  
                  <!-- Права доступа -->
                  <div class="flex flex-wrap gap-1 mt-2">
                    <Tag
                      v-for="permission in invitation.permissions"
                      :key="permission"
                      :value="getPermissionLabel(permission)"
                      class="text-xs"
                      severity="info"
                    />
                  </div>
                </div>
                
                <div class="flex space-x-2 ml-4">
                  <Button
                    :loading="processing === invitation.id"
                    class="p-button-sm p-button-success"
                    icon="pi pi-check"
                    label="Принять"
                    @click="acceptInvitation(invitation)"
                  />
                  <Button
                    :loading="processing === invitation.id"
                    class="p-button-sm p-button-outlined p-button-danger"
                    icon="pi pi-times"
                    label="Отклонить"
                    @click="declineInvitation(invitation)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
const props = defineProps({
  invitations: Array
})

const emit = defineEmits(['updated'])

const {$api} = useNuxtApp()
const toast = useToast()

// Реактивные данные
const processing = ref(null)

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

const acceptInvitation = async (invitation) => {
  processing.value = invitation.id
  
  try {
    await $api(`/invitations/${invitation.token}/accept`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('updated')
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Приглашение принято',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.response?.data?.message || 'Не удалось принять приглашение',
      life: 3000
    })
  } finally {
    processing.value = null
  }
}

const declineInvitation = async (invitation) => {
  processing.value = invitation.id
  
  try {
    await $api(`/invitations/${invitation.token}/decline`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    emit('updated')
    
    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: 'Приглашение отклонено',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: error.response?.data?.message || 'Не удалось отклонить приглашение',
      life: 3000
    })
  } finally {
    processing.value = null
  }
}
</script>