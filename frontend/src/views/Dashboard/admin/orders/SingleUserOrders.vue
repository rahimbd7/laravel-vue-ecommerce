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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User Orders</h1>
            <p class="text-sm text-gray-600">
              Orders for <span class="font-semibold">{{ user?.name || 'Loading...' }}</span>
            </p>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            size="small"
            :loading="loading"
            @click="fetchOrders"
            outlined
            class="text-xs sm:text-sm"
          />
          <Button 
            icon="pi pi-download" 
            label="Export" 
            size="small"
            @click="exportOrders"
            severity="success"
            class="text-xs sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- User Summary Card -->
    <div v-if="user" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <Avatar 
          :label="getInitials(user.name)" 
          size="large" 
          :style="{ 
            backgroundColor: getAvatarColor(user.name),
            width: '48px',
            height: '48px',
            fontSize: '1.2rem',
            fontWeight: 'bold'
          }"
          class="text-white flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">{{ user.name }}</h3>
            <Tag :value="user.role" :severity="getRoleSeverity(user.role)" size="small" />
            <Tag 
              :value="user.deleted_at ? 'Inactive' : 'Active'" 
              :severity="user.deleted_at ? 'danger' : 'success'" 
              size="small"
            />
          </div>
          <p class="text-xs sm:text-sm text-gray-500 truncate">{{ user.email }}</p>
          <div class="flex flex-wrap items-center gap-3 sm:gap-4 mt-2 text-xs sm:text-sm">
            <span>
              <span class="text-gray-500">Total Orders:</span>
              <span class="font-semibold text-gray-900">{{ pagination?.total || 0 }}</span>
            </span>
            <span>
              <span class="text-gray-500">Total Spent:</span>
              <span class="font-semibold text-[#00685F]">{{ formatPrice(totalSpent) }}</span>
            </span>
            <Button 
              icon="pi pi-user" 
              label="View Profile" 
              size="small"
              text
              @click="viewUserProfile"
              class="text-xs"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
          <InputText 
            v-model="filters.search" 
            placeholder="Search orders..." 
            class="pl-8 w-full text-xs sm:text-sm"
            size="small"
            @input="onFilterChange"
          />
        </div>
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
            class="flex-1 text-xs sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        :value="orders"
        lazy
        paginator
        :rows="filters.per_page"
        :totalRecords="pagination?.total || 0"
        :first="first"
        @page="onPageChange"
        @sort="onSort"
        dataKey="id"
        class="p-datatable-sm"
        :loading="loading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 15, 25, 50]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        responsiveLayout="scroll"
      >
        <!-- Order # - Always visible -->
        <Column field="order_number" header="Order #" sortable style="min-width: 100px" class="min-w-[80px] sm:min-w-[120px]">
          <template #body="{ data }">
            <span class="font-medium text-xs sm:text-sm">{{ data.order_number }}</span>
          </template>
        </Column>

        <!-- Vendor - Hidden on mobile -->
        <Column header="Vendor" sortable field="vendor.name" style="min-width: 120px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ data.vendor?.name || 'N/A' }}</span>
          </template>
        </Column>

        <!-- Total - Always visible -->
        <Column field="grand_total" header="Total" sortable style="min-width: 80px" class="min-w-[70px] sm:min-w-[100px]">
          <template #body="{ data }">
            <span class="font-bold text-[#00685F] text-xs sm:text-sm">{{ formatPrice(data.grand_total) }}</span>
          </template>
        </Column>

        <!-- Status - Always visible -->
        <Column field="status" header="Status" sortable style="min-width: 80px" class="min-w-[70px] sm:min-w-[100px]">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" size="small" class="text-[10px] sm:text-xs" />
          </template>
        </Column>

        <!-- Payment - Hidden on mobile -->
        <Column field="payment_status" header="Payment" sortable style="min-width: 90px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <Tag :value="formatPaymentStatus(data.payment_status)" :severity="getPaymentSeverity(data.payment_status)" size="small" class="text-[10px] sm:text-xs" />
          </template>
        </Column>

        <!-- Date - Hidden on tablet and mobile -->
        <Column field="created_at" header="Date" sortable style="min-width: 110px" class="hidden md:table-cell">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions - Always visible, sticky -->
        <Column header="Actions" style="width: 60px; min-width: 50px; max-width: 70px;" :frozen="true" alignFrozen="right">
          <template #body="{ data }">
            <div class="flex items-center gap-0.5 justify-end">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewOrder(data.id)"
                tooltip="View"
                class="!w-5 !h-5 sm:!w-7 sm:!h-7 !p-0"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-shopping-cart text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">No orders found for this user</p>
          </div>
        </template>
      </DataTable>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const userId = route.params.id as string

const windowWidth = ref(window.innerWidth)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUser()
  fetchOrders()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

// State
const user = ref<any>(null)
const orders = ref<any[]>([])
const loading = ref(false)
const pagination = ref<any>(null)
const first = ref(0)
const totalSpent = ref(0)

const filters = reactive({
  search: '',
  status: '',
  per_page: 15,
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

// Options
const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Refunded', value: 'refunded' },
  { label: 'Failed', value: 'failed' },
]

// Methods
const fetchUser = async () => {
  try {
    const response = await adminApi.getUser(userId)
    user.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch user:', error)
  }
}

const fetchOrders = async () => {
  loading.value = true
  try {
    const response = await adminApi.getUserOrders(userId, filters)
    orders.value = response.data.data || []
    pagination.value = {
      total: response.data.meta?.total || 0,
      current_page: response.data.meta?.current_page || 1,
      last_page: response.data.meta?.last_page || 1,
      per_page: response.data.meta?.per_page || filters.per_page,
    }
    first.value = (filters.page - 1) * filters.per_page
    
    // Calculate total spent
    totalSpent.value = orders.value.reduce((sum, order) => {
      return sum + (order.payment_status === 'paid' ? parseFloat(order.grand_total || 0) : 0)
    }, 0)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load orders',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onFilterChange = () => {
  filters.page = 1
  first.value = 0
  fetchOrders()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.page = 1
  first.value = 0
  fetchOrders()
}

const onPageChange = (event: any) => {
  const newPage = Math.floor(event.first / event.rows) + 1
  filters.page = newPage
  filters.per_page = event.rows
  first.value = event.first
  fetchOrders()
}

const onSort = (event: any) => {
  filters.sort_by = event.sortField
  filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc'
  filters.page = 1
  first.value = 0
  fetchOrders()
}

const viewOrder = (id: number) => {
  router.push(`/dashboard/admin/orders/${id}`)
}

const viewUserProfile = () => {
  router.push(`/dashboard/admin/users/${userId}`)
}

const goBack = () => {
  router.push(`/dashboard/admin/users/${userId}`)
}

const exportOrders = async () => {
  try {
    const params = { ...filters, user_id: userId }
    const response = await adminApi.exportOrders(params)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `user_${userId}_orders_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({
      severity: 'success',
      summary: 'Export Started',
      detail: 'Orders exported successfully',
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export orders',
      life: 3000
    })
  }
}

// Helper functions
const formatPrice = (price: any): string => {
  if (!price) return '0.00'
  const num = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(num) ? '0.00' : num.toFixed(2)
}

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    confirmed: 'Confirmed',
    shipped: 'Shipped',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled',
    refunded: 'Refunded',
    failed: 'Failed'
  }
  return map[status] || status
}

const formatPaymentStatus = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'Pending',
    paid: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded',
    partially_refunded: 'Partially Refunded'
  }
  return map[status] || status
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

const getPaymentSeverity = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    failed: 'danger',
    refunded: 'secondary',
    partially_refunded: 'warning'
  }
  return map[status] || 'info'
}

const getRoleSeverity = (role: string): string => {
  const map: Record<string, string> = {
    admin: 'danger',
    vendor: 'success',
    customer: 'info',
  }
  return map[role] || 'secondary'
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

:deep(.p-datatable .p-paginator .p-paginator-element) {
  cursor: pointer !important;
  user-select: none !important;
}

:deep(.p-datatable .p-paginator .p-paginator-element:hover) {
  background: #e5e7eb !important;
  border-radius: 50% !important;
}

:deep(.p-datatable .p-paginator .p-paginator-element.p-disabled) {
  cursor: not-allowed !important;
  opacity: 0.5 !important;
}

:deep(.p-datatable .p-tag) {
  font-size: 0.6rem !important;
  padding: 0.1rem 0.3rem !important;
}

/* =========================
   TABLET (768px to 1024px)
========================= */
@media (min-width: 768px) and (max-width: 1024px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.65rem !important;
    padding: 0.4rem 0.3rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.65rem !important;
    padding: 0.35rem 0.25rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    min-width: 1.4rem !important;
    height: 1.4rem !important;
    padding: 0.1rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.8rem !important;
    height: 1.8rem !important;
    font-size: 0.7rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.8rem !important;
    height: 1.8rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.55rem !important;
    padding: 0.05rem 0.25rem !important;
  }
}

/* =========================
   MOBILE (below 768px)
========================= */
@media (max-width: 767px) {
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(6)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(6)) {
    display: none !important;
  }
  
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(1)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(1)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(3)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(3)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(4)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(7)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(7)) {
    display: table-cell !important;
  }

  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.55rem !important;
    padding: 0.3rem 0.2rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.55rem !important;
    padding: 0.25rem 0.15rem !important;
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
    font-size: 0.45rem !important;
    padding: 0.05rem 0.2rem !important;
  }
  
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
    padding: 0.2rem 0.15rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.5rem !important;
    padding: 0.15rem 0.1rem !important;
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

/* Filter responsive */
@media (max-width: 640px) {
  :deep(.p-dropdown .p-dropdown-label) {
    font-size: 0.75rem !important;
    padding: 0.3rem 0.5rem !important;
  }
  
  :deep(.p-inputtext) {
    font-size: 0.75rem !important;
    padding: 0.3rem 0.5rem !important;
  }
}
</style>
