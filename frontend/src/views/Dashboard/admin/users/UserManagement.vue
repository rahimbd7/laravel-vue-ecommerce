<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User Management</h1>
          <p class="text-sm sm:text-base text-gray-600">Manage all platform users</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            size="small"
            :loading="loading"
            @click="fetchUsers"
            outlined
          />
          <Button 
            icon="pi pi-plus" 
            label="Add User" 
            size="small"
            @click="openCreateDialog"
            severity="success"
          />
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-25 top-5 -translate-y-1/2 text-gray-400"></i>
          <InputText 
            v-model="filters.search" 
            placeholder="Search users..." 
            class="pl-9 w-full"
            size="small"
            @input="onFilterChange"
          />
        </div>
        <Dropdown 
          v-model="filters.role" 
          :options="roleOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Roles"
          class="w-full"
          size="small"
          @change="onFilterChange"
        />
        <Dropdown 
          v-model="filters.status" 
          :options="statusOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Status"
          class="w-full"
          size="small"
          @change="onFilterChange"
        />
        <div class="flex gap-2">
          <Button 
            icon="pi pi-filter-slash" 
            label="Clear" 
            size="small"
            outlined
            @click="clearFilters"
            class="flex-1"
          />
          <Button 
            icon="pi pi-download" 
            size="small"
            outlined
            @click="exportUsers"
            tooltip="Export Users"
          />
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        :value="users"
        lazy
        paginator
        :rows="filters.per_page"
        :totalRecords="pagination?.total || 0"
        :first="first"
        @page="onPageChange"
        @sort="onSort"
        v-model:selection="selectedUsers"
        dataKey="id"
        class="p-datatable-sm"
        :loading="loading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
      >
        <!-- Selection - Hidden on mobile -->
        <Column 
          selectionMode="multiple" 
          style="width: 2.5rem" 
          :frozen="true"
          class="hidden sm:table-cell"
        />
        
        <!-- ID - Hidden on mobile -->
        <Column 
          field="id" 
          header="ID" 
          sortable 
          style="min-width: 50px"
          class="hidden sm:table-cell"
        >
          <template #body="{ data }">
            <span class="text-xs">#{{ data.id }}</span>
          </template>
        </Column>
        
        <!-- User - Always visible -->
        <Column 
          header="User" 
          sortable 
          field="name" 
          style="min-width: 130px"
        >
          <template #body="{ data }">
            <div class="flex items-center gap-1.5">
              <Avatar 
                :label="getInitials(data.name)" 
                size="normal" 
                :style="{ 
                  backgroundColor: getAvatarColor(data.name),
                  width: '28px',
                  height: '28px',
                  fontSize: '11px',
                  fontWeight: 'bold'
                }"
                class="text-white flex-shrink-0"
              />
              <div class="min-w-0">
                <p class="text-xs font-medium text-gray-900 truncate max-w-[60px] sm:max-w-[120px]">{{ data.name || 'N/A' }}</p>
                <p class="text-[10px] text-gray-500 truncate max-w-[60px] sm:max-w-[120px]">{{ data.email }}</p>
              </div>
            </div>
          </template>
        </Column>
        
        <!-- Role - Hidden on mobile -->
        <Column 
          field="role" 
          header="Role" 
          sortable 
          style="min-width: 70px"
          class="hidden sm:table-cell"
        >
          <template #body="{ data }">
            <Tag :value="data.role" :severity="getRoleSeverity(data.role)" size="small" class="text-[10px]" />
          </template>
        </Column>
        
        <!-- Status - Always visible -->
        <Column 
          field="status" 
          header="Status" 
          style="min-width: 65px"
        >
          <template #body="{ data }">
            <Tag 
              :value="data.deleted_at ? 'Inactive' : 'Active'" 
              :severity="data.deleted_at ? 'danger' : 'success'" 
              size="small"
              class="text-[10px]"
            />
          </template>
        </Column>
        
        <!-- Verified - Hidden on tablet and below -->
        <Column 
          field="email_verified_at" 
          header="Verified" 
          sortable 
          style="min-width: 65px"
          class="hidden lg:table-cell"
        >
          <template #body="{ data }">
            <i 
              :class="[
                'pi',
                data.email_verified_at ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500',
                'text-xs'
              ]"
            />
          </template>
        </Column>
        
        <!-- Joined - Hidden on tablet and below -->
        <Column 
          field="created_at" 
          header="Joined" 
          sortable 
          style="min-width: 90px"
          class="hidden lg:table-cell"
        >
          <template #body="{ data }">
            <span class="text-xs">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        
        <!-- Actions - Always visible, compact -->
        <Column 
          header="Actions" 
          style="width: 75px; min-width: 65px; max-width: 85px;" 
          :frozen="true" 
          alignFrozen="right"
        >
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-0.5">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small" 
                @click="viewUser(data)" 
                tooltip="View"
                class="!w-5 !h-5 sm:!w-6 sm:!h-6 !p-0"
              />
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small" 
                @click="openEditDialog(data)" 
                tooltip="Edit"
                class="!w-5 !h-5 sm:!w-6 sm:!h-6 !p-0"
              />
              <Button 
                v-if="!data.deleted_at"
                icon="pi pi-trash" 
                text 
                rounded 
                size="small"
                severity="danger"
                @click="confirmDelete(data)"
                tooltip="Delete"
                class="!w-5 !h-5 sm:!w-6 sm:!h-6 !p-0"
              />
              <Button 
                v-else
                icon="pi pi-refresh" 
                text 
                rounded 
                size="small"
                severity="success"
                @click="confirmRestore(data)"
                tooltip="Restore"
                class="!w-5 !h-5 sm:!w-6 sm:!h-6 !p-0"
              />
            </div>
          </template>
        </Column>
        
        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-users text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">No users found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedUsers.length > 0" class="bg-white rounded-lg shadow-sm p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-sm text-gray-600">
          {{ selectedUsers.length }} user(s) selected
        </span>
        <div class="flex flex-wrap gap-2">
          <Dropdown 
            v-model="bulkAction" 
            :options="bulkActionOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="Bulk Action"
            class="w-40"
            size="small"
          />
          <Button 
            label="Apply" 
            size="small"
            :disabled="!bulkAction"
            @click="applyBulkAction"
            severity="primary"
          />
          <Button 
            icon="pi pi-times" 
            size="small"
            outlined
            @click="selectedUsers = []"
          />
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
      v-model:visible="dialogVisible" 
      :header="dialogMode === 'create' ? 'Add New User' : 'Edit User'"
      :style="{ width: windowWidth < 640 ? '95%' : '550px' }"
      modal
      class="p-fluid"
    >
      <div class="space-y-4">
        <div class="field">
          <label class="text-sm font-medium text-gray-700">Name *</label>
          <InputText v-model="form.name" class="w-full" />
          <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
        </div>
        <div class="field">
          <label class="text-sm font-medium text-gray-700">Email *</label>
          <InputText v-model="form.email" type="email" class="w-full" />
          <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
        </div>
        <div class="field">
          <label class="text-sm font-medium text-gray-700">Role *</label>
          <Dropdown 
            v-model="form.role" 
            :options="roleOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="Select Role"
            class="w-full"
          />
          <small v-if="errors.role" class="text-red-500">{{ errors.role }}</small>
        </div>
        <div class="field">
          <label class="text-sm font-medium text-gray-700">Status</label>
          <Dropdown 
            v-model="form.status" 
            :options="statusOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="Select Status"
            class="w-full"
          />
        </div>
        <div v-if="dialogMode === 'create'" class="field">
          <label class="text-sm font-medium text-gray-700">Password *</label>
          <Password 
            v-model="form.password" 
            toggleMask
            class="w-full"
            placeholder="Enter password"
            :feedback="false"
          />
          <small v-if="errors.password" class="text-red-500">{{ errors.password }}</small>
        </div>
        <div v-if="dialogMode === 'create' && form.password" class="field">
          <label class="text-sm font-medium text-gray-700">Confirm Password *</label>
          <Password 
            v-model="form.password_confirmation" 
            toggleMask
            class="w-full"
            placeholder="Confirm password"
            :feedback="false"
          />
        </div>
        <div v-if="dialogMode === 'edit'" class="field">
          <label class="text-sm font-medium text-gray-700">New Password</label>
          <Password 
            v-model="form.password" 
            toggleMask
            class="w-full"
            placeholder="Leave blank to keep current"
            :feedback="false"
          />
          <small class="text-gray-500">Leave blank to keep current password</small>
        </div>
        <div v-if="dialogMode === 'edit' && form.password" class="field">
          <label class="text-sm font-medium text-gray-700">Confirm Password</label>
          <Password 
            v-model="form.password_confirmation" 
            toggleMask
            class="w-full"
            placeholder="Confirm new password"
            :feedback="false"
          />
        </div>
        <div class="field">
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.email_verified" :binary="true" />
            <label class="text-sm text-gray-700">Email Verified</label>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="dialogVisible = false" />
        <Button 
          :label="dialogMode === 'create' ? 'Create' : 'Update'" 
          icon="pi pi-save" 
          :loading="saving"
          @click="saveUser"
          severity="primary"
        />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog 
      v-model:visible="deleteDialogVisible" 
      header="Confirm Delete" 
      :style="{ width: windowWidth < 640 ? '95%' : '400px' }"
      modal
    >
      <div class="text-center py-4">
        <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
        <p class="text-gray-700">
          Are you sure you want to delete user <strong>{{ selectedUser?.name }}</strong>?
        </p>
        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="deleteDialogVisible = false" />
        <Button 
          label="Delete" 
          icon="pi pi-trash" 
          severity="danger" 
          :loading="deleting"
          @click="deleteUser"
        />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import Password from 'primevue/password'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const windowWidth = ref(window.innerWidth)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUsers()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

// State
const users = ref<any[]>([])
const selectedUsers = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const pagination = ref<any>(null)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const dialogMode = ref<'create' | 'edit'>('create')
const selectedUser = ref<any>(null)
const bulkAction = ref('')
const first = ref(0)

const filters = reactive({
  search: '',
  role: '',
  status: '',
  per_page: 10,
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

const form = reactive({
  name: '',
  email: '',
  role: 'customer',
  status: 'active',
  password: '',
  password_confirmation: '',
  email_verified: false,
})

const errors = ref<Record<string, string>>({})

// Options
const roleOptions = [
  { label: 'All Roles', value: '' },
  { label: 'Customers', value: 'customer' },
  { label: 'Vendors', value: 'vendor' },
  { label: 'Admins', value: 'admin' },
]

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  
]

const bulkActionOptions = [
  { label: 'Delete Selected', value: 'delete' },
  { label: 'Activate Selected', value: 'activate' },
  { label: 'Deactivate Selected', value: 'deactivate' },
]

// Methods
const fetchUsers = async () => {
  loading.value = true
  try {
    const response = await adminApi.getUsersList(filters)
    users.value = response.data.data || []
    pagination.value = {
      total: response.data.meta?.total || 0,
      current_page: response.data.meta?.current_page || 1,
      last_page: response.data.meta?.last_page || 1,
      per_page: response.data.meta?.per_page || filters.per_page,
    }
    first.value = (filters.page - 1) * filters.per_page
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load users',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  first.value = event.first
  fetchUsers()
}

const onSort = (event: any) => {
  filters.sort_by = event.sortField
  filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc'
  filters.page = 1
  first.value = 0
  fetchUsers()
}

const onFilterChange = () => {
  filters.page = 1
  first.value = 0
  fetchUsers()
}

const clearFilters = () => {
  filters.search = ''
  filters.role = ''
  filters.status = ''
  filters.page = 1
  first.value = 0
  fetchUsers()
}

const openCreateDialog = () => {
  dialogMode.value = 'create'
  resetForm()
  errors.value = {}
  dialogVisible.value = true
}

const openEditDialog = (user: any) => {
  dialogMode.value = 'edit'
  selectedUser.value = user
  form.name = user.name || ''
  form.email = user.email || ''
  form.role = user.role || 'customer'
  form.status = user.deleted_at ? 'inactive' : 'active'
  form.password = ''
  form.password_confirmation = ''
  form.email_verified = !!user.email_verified_at
  errors.value = {}
  dialogVisible.value = true
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.role = 'customer'
  form.status = 'active'
  form.password = ''
  form.password_confirmation = ''
  form.email_verified = false
}

const saveUser = async () => {
  errors.value = {}
  saving.value = true

  try {
    const payload: {
      name: string
      email: string
      role: string
      password?: string
      password_confirmation?: string
      email_verified?: boolean
    } = {
      name: form.name,
      email: form.email,
      role: form.role,
    }

    payload.email_verified = form.email_verified === true

    if (dialogMode.value === 'create') {
      if (!form.password) {
        errors.value = { password: 'Password is required' }
        saving.value = false
        return
      }
      
      if (form.password.length < 8) {
        errors.value = { password: 'Password must be at least 8 characters' }
        saving.value = false
        return
      }
      
      if (form.password !== form.password_confirmation) {
        errors.value = { password_confirmation: 'Password confirmation does not match' }
        saving.value = false
        return
      }
      
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
      
      await adminApi.createUser(payload)
      toast.add({
        severity: 'success',
        summary: 'Created',
        detail: 'User created successfully',
        life: 3000
      })
    } else {
      if (form.password) {
        if (form.password.length < 8) {
          errors.value = { password: 'Password must be at least 8 characters' }
          saving.value = false
          return
        }
        if (form.password !== form.password_confirmation) {
          errors.value = { password_confirmation: 'Password confirmation does not match' }
          saving.value = false
          return
        }
        payload.password = form.password
        payload.password_confirmation = form.password_confirmation
      }
      
      await adminApi.updateUser(selectedUser.value.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Updated',
        detail: 'User updated successfully',
        life: 3000
      })
    }

    dialogVisible.value = false
    
    filters.search = ''
    filters.page = 1
    first.value = 0
    await fetchUsers()
    
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save user',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const confirmDelete = (user: any) => {
  selectedUser.value = user
  deleteDialogVisible.value = true
}

const confirmRestore = (user: any) => {
  confirm.require({
    message: `Are you sure you want to restore user "${user.name}"?`,
    header: 'Confirm Restore',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.restoreUser(user.id)
        toast.add({
          severity: 'success',
          summary: 'Restored',
          detail: 'User restored successfully',
          life: 3000
        })
        await fetchUsers()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to restore user',
          life: 3000
        })
      }
    }
  })
}

const deleteUser = async () => {
  deleting.value = true
  try {
    await adminApi.deleteUser(selectedUser.value.id)
    deleteDialogVisible.value = false
    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: 'User deleted successfully',
      life: 3000
    })
    await fetchUsers()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete user',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const applyBulkAction = async () => {
  if (!bulkAction.value || selectedUsers.value.length === 0) return

  const userIds = selectedUsers.value.map(u => u.id)
  
  try {
    if (bulkAction.value === 'delete') {
      await adminApi.bulkDeleteUsers({ user_ids: userIds })
      toast.add({
        severity: 'success',
        summary: 'Deleted',
        detail: `${userIds.length} users deleted successfully`,
        life: 3000
      })
    } else if (bulkAction.value === 'activate') {
      await adminApi.bulkUpdateStatus({ user_ids: userIds, status: 'active' })
      toast.add({
        severity: 'success',
        summary: 'Activated',
        detail: `${userIds.length} users activated successfully`,
        life: 3000
      })
    } else if (bulkAction.value === 'deactivate') {
      await adminApi.bulkUpdateStatus({ user_ids: userIds, status: 'inactive' })
      toast.add({
        severity: 'success',
        summary: 'Deactivated',
        detail: `${userIds.length} users deactivated successfully`,
        life: 3000
      })
    }
    
    selectedUsers.value = []
    bulkAction.value = ''
    await fetchUsers()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to perform bulk action',
      life: 3000
    })
  }
}

const viewUser = (user: any) => {
  router.push(`/dashboard/admin/users/${user.id}`)
}

const exportUsers = () => {
  toast.add({
    severity: 'info',
    summary: 'Exporting',
    detail: 'User data export started',
    life: 3000
  })
}

const getInitials = (name: string): string => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getAvatarColor = (name: string): string => {
  const colors = ['#00685F', '#8B5CF6', '#3B82F6', '#EF4444', '#F59E0B', '#10B981', '#EC4899', '#6B7280']
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

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// Lifecycle
onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
/* =========================
   TABLE DEFAULT
========================= */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f9fafb;
  font-weight: 600;
  font-size: 0.7rem;
  text-transform: uppercase;
  color: #6b7280;
  padding: 0.5rem 0.4rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.4rem 0.3rem;
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f3f4f6;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
  padding: 0.5rem !important;
}

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
  min-width: 2rem !important;
  height: 2rem !important;
  font-size: 0.75rem !important;
}

:deep(.p-datatable .p-paginator .p-paginator-first),
:deep(.p-datatable .p-paginator .p-paginator-prev),
:deep(.p-datatable .p-paginator .p-paginator-next),
:deep(.p-datatable .p-paginator .p-paginator-last) {
  min-width: 2rem !important;
  height: 2rem !important;
}

:deep(.p-datatable .p-tag) {
  font-size: 0.65rem !important;
  padding: 0.1rem 0.4rem !important;
}

/* =========================
   MOBILE (below 640px)
========================= */
@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.55rem !important;
    padding: 0.3rem 0.15rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.2rem 0.1rem !important;
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    min-width: 1.2rem !important;
    height: 1.2rem !important;
    padding: 0.05rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.5rem !important;
    padding: 0.05rem 0.2rem !important;
  }
  
  /* Sticky action column on mobile */
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child),
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child) {
    position: sticky !important;
    right: 0 !important;
    background: white !important;
    z-index: 3 !important;
  }
}

/* =========================
   EXTRA SMALL (below 480px)
========================= */
@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.5rem !important;
    padding: 0.2rem 0.1rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.15rem 0.08rem !important;
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    min-width: 1rem !important;
    height: 1rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.4rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.4rem !important;
    height: 1.4rem !important;
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.4rem !important;
    height: 1.4rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.4rem !important;
    padding: 0.05rem 0.15rem !important;
  }
}
</style>
