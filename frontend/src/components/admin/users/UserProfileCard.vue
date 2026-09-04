<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 text-center">
    <Avatar 
      :label="getInitials(user.name)" 
      size="xlarge" 
      :style="{ backgroundColor: getAvatarColor(user.name), width: '80px', height: '80px' }"
      class="mx-auto text-2xl font-bold text-white"
    />
    <h3 class="text-lg font-semibold text-gray-900 mt-3">{{ user.name }}</h3>
    <p class="text-sm text-gray-500">{{ user.email }}</p>
    
    <div class="mt-4 flex justify-center gap-2">
      <Tag :value="user.role" :severity="getRoleSeverity(user.role)" />
      <Tag :value="user.status || 'Active'" :severity="getStatusSeverity(user.status)" />
    </div>

    <Divider />

    <div class="space-y-2 text-left">
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">User ID</span>
        <span class="font-mono text-gray-900">{{ user.uuid?.slice(0, 8) || 'N/A' }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">Joined</span>
        <span class="text-gray-900">{{ formatDate(user.created_at) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">Last Login</span>
        <span class="text-gray-900">{{ formatDate(user.last_login_at) || 'Never' }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">Verified</span>
        <span class="text-gray-900">
          <i :class="user.email_verified_at ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500'" />
          {{ user.email_verified_at ? 'Yes' : 'No' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'

defineProps<{
  user: any
}>()

const getInitials = (name: string): string => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getAvatarColor = (name: string): string => {
  const colors = ['#00685F', '#8B5CF6', '#3B82F6', '#EF4444', '#F59E0B']
  let hash = 0
  if (name) {
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
  }
  return colors[Math.abs(hash) % colors.length] as string
}

const getRoleSeverity = (role: string): string => {
  const map: Record<string, string> = {
    admin: 'danger',
    vendor: 'success',
    customer: 'info',
  }
  return map[role] || 'secondary'
}

const getStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    active: 'success',
    inactive: 'secondary',
    suspended: 'danger',
    pending: 'warning',
  }
  return map[status] || 'info'
}

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
