<template>
  <DataTable 
    :value="users" 
    :loading="loading"
    :rows="10"
    :paginator="true"
    :rowsPerPageOptions="[10, 25, 50]"
    responsiveLayout="scroll"
    class="p-datatable-sm"
  >
    <Column field="uuid" header="User ID" sortable style="min-width: 120px">
      <template #body="{ data }">
        <span class="text-xs font-mono text-gray-600">{{ data.uuid?.slice(0, 8) || 'N/A' }}</span>
      </template>
    </Column>

    <Column field="name" header="Name" sortable style="min-width: 140px">
      <template #body="{ data }">
        <div class="flex items-center gap-2">
          <Avatar 
            :label="getInitials(data.name)" 
            size="normal" 
            :style="{ backgroundColor: getAvatarColor(data.name) }"
            class="text-white text-xs"
          />
          <span class="font-medium">{{ data.name || 'N/A' }}</span>
        </div>
      </template>
    </Column>

    <Column field="email" header="Email" sortable style="min-width: 180px">
      <template #body="{ data }">
        <a :href="`mailto:${data.email}`" class="text-[#00685F] hover:underline">
          {{ data.email || 'N/A' }}
        </a>
      </template>
    </Column>

    <Column field="role" header="Role" sortable style="min-width: 100px">
      <template #body="{ data }">
        <Tag :value="data.role" :severity="getRoleSeverity(data.role)" size="small" />
      </template>
    </Column>

    <Column field="status" header="Status" sortable style="min-width: 100px">
      <template #body="{ data }">
        <Tag :value="data.status || 'Active'" :severity="getStatusSeverity(data.status)" size="small" />
      </template>
    </Column>

    <Column field="created_at" header="Joined" sortable style="min-width: 130px">
      <template #body="{ data }">
        <span class="text-sm">{{ formatDate(data.created_at) }}</span>
      </template>
    </Column>

    <Column field="last_login_at" header="Last Login" sortable style="min-width: 130px">
      <template #body="{ data }">
        <span class="text-sm">{{ formatDate(data.last_login_at) || 'Never' }}</span>
      </template>
    </Column>

    <Column field="email_verified_at" header="Verified" style="min-width: 80px">
      <template #body="{ data }">
        <i 
          :class="[
            'pi',
            data.email_verified_at ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'
          ]"
        />
      </template>
    </Column>

    <Column header="Actions" style="min-width: 100px" :frozen="true" alignFrozen="right">
      <template #body="{ data }">
        <div class="flex items-center gap-1">
          <Button 
            icon="pi pi-eye" 
            text 
            rounded 
            size="small"
            @click="viewUser(data)"
            tooltip="View Details"
          />
          <Button 
            icon="pi pi-user-edit" 
            text 
            rounded 
            size="small"
            @click="editUser(data)"
            tooltip="Edit User"
          />
          <Button 
            v-if="data.role === 'vendor'"
            icon="pi pi-store" 
            text 
            rounded 
            size="small"
            @click="viewVendor(data)"
            tooltip="View Vendor"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'

const props = defineProps<{
  users: any[]
  loading: boolean
}>()

const emit = defineEmits(['refresh'])

const toast = useToast()
const router = useRouter()

const getInitials = (name: string): string => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getAvatarColor = (name: string): string => {
  const colors = [
    '#00685F', '#8B5CF6', '#3B82F6', '#EF4444', '#F59E0B',
    '#10B981', '#EC4899', '#6B7280', '#14B8A6', '#F97316'
  ]
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

const viewUser = (user: any) => {
  router.push(`/dashboard/admin/users/${user.uuid}`)
}

const editUser = (user: any) => {
  router.push(`/dashboard/admin/users/${user.uuid}/edit`)
}

const viewVendor = (user: any) => {
  // Navigate to vendor details
  toast.add({
    severity: 'info',
    summary: 'Vendor',
    detail: 'Viewing vendor details...',
    life: 3000
  })
}
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #F9FAFB;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6B7280;
  padding: 0.75rem 0.5rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.5rem;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #F3F4F6;
}

/* Mobile responsive */
@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.25rem;
    font-size: 0.65rem;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.375rem 0.25rem;
    font-size: 0.75rem;
  }
}
</style>