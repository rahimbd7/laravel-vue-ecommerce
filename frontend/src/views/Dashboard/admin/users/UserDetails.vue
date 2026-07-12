<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <Button 
            icon="pi pi-arrow-left" 
            text 
            rounded 
            @click="goBack"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User Details</h1>
            <p class="text-sm text-gray-600">View user information</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            icon="pi pi-pencil" 
            label="Edit" 
            size="small"
            @click="openEditDialog"
            severity="primary"
          />
          <Button 
            v-if="!user?.deleted_at"
            icon="pi pi-trash" 
            label="Delete" 
            size="small"
            severity="danger"
            outlined
            @click="confirmDelete"
          />
          <Button 
            v-else
            icon="pi pi-refresh" 
            label="Restore" 
            size="small"
            severity="success"
            outlined
            @click="confirmRestore"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- User Content -->
    <template v-else-if="user">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- User Profile Card -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 text-center">
            <Avatar 
              :label="getInitials(user.name)" 
              size="xlarge" 
              :style="{ 
                backgroundColor: getAvatarColor(user.name), 
                width: '80px', 
                height: '80px',
                fontSize: '2rem',
                fontWeight: 'bold'
              }"
              class="mx-auto text-white"
            />
            
            <h3 class="text-lg font-semibold text-gray-900 mt-3">{{ user.name || 'N/A' }}</h3>
            <p class="text-sm text-gray-500">{{ user.email }}</p>
            
            <div class="flex flex-wrap justify-center gap-2 mt-3">
              <Tag :value="user.role" :severity="getRoleSeverity(user.role)" size="small" />
              <Tag 
                :value="user.deleted_at ? 'Inactive' : 'Active'" 
                :severity="user.deleted_at ? 'danger' : 'success'" 
                size="small"
              />
            </div>

            <Divider />

            <div class="space-y-2 text-left">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">User ID</span>
                <span class="font-mono text-gray-900">{{ user.uuid?.slice(0, 8) || user.id || 'N/A' }}</span>
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
                  <i 
                    :class="[
                      'pi',
                      user.email_verified_at ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'
                    ]"
                  />
                  {{ user.email_verified_at ? 'Yes' : 'No' }}
                </span>
              </div>
              <div v-if="user.deleted_at" class="flex justify-between text-sm">
                <span class="text-gray-500">Deleted At</span>
                <span class="text-red-600">{{ formatDate(user.deleted_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- User Details -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- User Statistics -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-4">User Statistics</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Total Orders</p>
                <p class="text-lg font-bold text-gray-900">{{ stats.total_orders || 0 }}</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Total Spent</p>
                <p class="text-lg font-bold text-[#00685F]">${{ stats.total_spent?.toFixed(2) || '0.00' }}</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Reviews</p>
                <p class="text-lg font-bold text-gray-900">{{ stats.total_reviews || 0 }}</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Wishlist</p>
                <p class="text-lg font-bold text-gray-900">{{ stats.wishlist_count || 0 }}</p>
              </div>
            </div>
          </div>

          <!-- Recent Orders -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
              <h4 class="font-semibold text-gray-900">Recent Orders</h4>
              <Button 
                label="View All" 
                icon="pi pi-arrow-right" 
                iconPos="right" 
                size="small"
                text
                @click="viewAllOrders"
              />
            </div>

            <div v-if="orders.length === 0" class="text-center py-8">
              <i class="pi pi-shopping-cart text-4xl text-gray-300"></i>
              <p class="text-gray-500 mt-2">No orders found</p>
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="order in orders" 
                :key="order.id"
                class="border border-gray-100 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow"
              >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div>
                    <p class="font-medium text-gray-900 text-sm">
                      Order #{{ order.order_number }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ formatDate(order.created_at) }}
                    </p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <Tag 
                      :value="order.status" 
                      :severity="getStatusSeverity(order.status)" 
                      size="small"
                    />
                    <span class="text-sm font-bold text-[#00685F]">
                      ${{ order.grand_total?.toFixed(2) || '0.00' }}
                    </span>
                    <Button 
                      icon="pi pi-eye" 
                      text 
                      rounded 
                      size="small"
                      @click="viewOrder(order.id)"
                      tooltip="View Order"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Activity Log -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-4">Activity Log</h4>
            
            <div v-if="activities.length === 0" class="text-center py-8">
              <i class="pi pi-clock text-4xl text-gray-300"></i>
              <p class="text-gray-500 mt-2">No activities found</p>
            </div>

            <div v-else class="space-y-4 max-h-64 overflow-y-auto">
              <div 
                v-for="activity in activities" 
                :key="activity.id"
                class="flex gap-3"
              >
                <div class="flex-shrink-0 mt-1">
                  <div 
                    class="w-8 h-8 rounded-full flex items-center justify-center"
                    :class="getActivityColorClass(activity.color || 'gray')"
                  >
                    <i :class="[activity.icon || 'pi pi-circle', 'text-white text-xs']"></i>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900">
                    {{ activity.description }}
                  </p>
                  <div class="flex flex-wrap items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500">
                      <i class="pi pi-user mr-1"></i>
                      {{ activity.user_name || 'System' }}
                    </span>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-400">
                      {{ formatTime(activity.created_at) }}
                    </span>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-400">
                      {{ getTimeAgo(activity.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit Dialog -->
    <Dialog 
      v-model:visible="dialogVisible" 
      header="Edit User"
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

        <div class="field">
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

        <div v-if="form.password" class="field">
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
          label="Update" 
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
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Password from 'primevue/password'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const userId = route.params.id as string

const windowWidth = ref(window.innerWidth)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

// ✅ Prevent navigation to edit routes - Navigation Guard
router.beforeEach((to, from, next) => {
  // If trying to go to edit page, redirect back to current page
  if (to.path.includes('/edit') && from.path.includes('/admin/users/')) {
    next(false) // Cancel navigation
    return
  }
  next()
})

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUserDetails()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

// State
const user = ref<any>(null)
const stats = ref<any>({
  total_orders: 0,
  total_spent: 0,
  total_reviews: 0,
  wishlist_count: 0
})
const orders = ref<any[]>([])
const activities = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Dialog state
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const selectedUser = ref<any>(null)

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
  { label: 'Suspended', value: 'suspended' },
]

// Methods
const fetchUserDetails = async () => {
  loading.value = true
  try {
    const response = await adminApi.getUsersList({ per_page: 100 })
    const foundUser = response.data.data?.find((u: any) => u.id === parseInt(userId))
    user.value = foundUser || null
    
    if (user.value) {
      form.name = user.value.name || ''
      form.email = user.value.email || ''
      form.role = user.value.role || 'customer'
      form.status = user.value.deleted_at ? 'inactive' : 'active'
      form.email_verified = !!user.value.email_verified_at
    }
    
    // Fetch stats from API
    try {
      const statsResponse = await adminApi.getUserStats(userId)
      stats.value = statsResponse.data.data || stats.value
    } catch (error) {
      console.log('Stats not available yet')
    }
    
    // Fetch orders
    try {
      const ordersResponse = await adminApi.getUserOrders(userId, { per_page: 5 })
      orders.value = ordersResponse.data.data || []
    } catch (error) {
      console.log('Orders not available yet')
    }
    
    // Fetch activities
    try {
      const activitiesResponse = await adminApi.getUserActivities(userId)
      activities.value = activitiesResponse.data.data || []
    } catch (error) {
      console.log('Activities not available yet')
    }
    
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load user details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// ✅ Opens modal - NO REDIRECT
const openEditDialog = () => {
  form.name = user.value.name || ''
  form.email = user.value.email || ''
  form.role = user.value.role || 'customer'
  form.status = user.value.deleted_at ? 'inactive' : 'active'
  form.password = ''
  form.password_confirmation = ''
  form.email_verified = !!user.value.email_verified_at
  errors.value = {}
  dialogVisible.value = true
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

    await adminApi.updateUser(userId, payload)
    
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'User updated successfully',
      life: 3000
    })
    
    dialogVisible.value = false
    await fetchUserDetails()
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update user',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const confirmDelete = () => {
  selectedUser.value = user.value
  deleteDialogVisible.value = true
}

const deleteUser = async () => {
  deleting.value = true
  try {
    await adminApi.deleteUser(userId)
    deleteDialogVisible.value = false
    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: 'User deleted successfully',
      life: 3000
    })
    router.push('/dashboard/admin/users')
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

const confirmRestore = () => {
  confirm.require({
    message: `Are you sure you want to restore user "${user.value?.name}"?`,
    header: 'Confirm Restore',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.restoreUser(userId)
        toast.add({
          severity: 'success',
          summary: 'Restored',
          detail: 'User restored successfully',
          life: 3000
        })
        await fetchUserDetails()
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

const goBack = () => {
  router.push('/dashboard/admin/users')
}

const viewAllOrders = () => {
  router.push(`/dashboard/admin/orders?user_id=${userId}`)
}

const viewOrder = (orderId: number) => {
  router.push(`/dashboard/admin/orders/${orderId}`)
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

const getStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'warning',
    processing: 'info',
    confirmed: 'info',
    shipped: 'info',
    delivered: 'success',
    completed: 'success',
    cancelled: 'danger',
    refunded: 'secondary',
    failed: 'danger'
  }
  return map[status] || 'info'
}

const getActivityColorClass = (color: string): string => {
  const map: Record<string, string> = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    indigo: 'bg-indigo-500',
    teal: 'bg-teal-500',
    yellow: 'bg-yellow-500',
    pink: 'bg-pink-500',
    gray: 'bg-gray-500',
  }
  return map[color] || 'bg-gray-500'
}

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatTime = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getTimeAgo = (date: string): string => {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diff = Math.floor((now.getTime() - past.getTime()) / 1000)
  
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
  return formatDate(date)
}

// Lifecycle
onMounted(() => {
  fetchUserDetails()
})
</script>

<style scoped>
.max-h-64 {
  max-height: 16rem;
}

@media (max-width: 640px) {
  :deep(.p-dialog) {
    margin: 0.5rem !important;
  }
  
  :deep(.p-dialog .p-dialog-header) {
    padding: 0.75rem !important;
  }
  
  :deep(.p-dialog .p-dialog-content) {
    padding: 0.75rem !important;
  }
  
  :deep(.p-dialog .p-dialog-footer) {
    padding: 0.75rem !important;
  }
  
  :deep(.p-dialog .p-dialog-footer .p-button) {
    font-size: 0.75rem !important;
    padding: 0.3rem 0.75rem !important;
  }
}
</style>