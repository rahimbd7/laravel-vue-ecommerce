<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">My Orders</h1>
      <p class="text-sm sm:text-base text-gray-600">View and manage your store orders</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Status Filter -->
        <div class="flex flex-col gap-1">
          <label class="text-xs sm:text-sm font-medium text-gray-700">Status</label>
          <Dropdown 
            v-model="filters.status" 
            :options="statusOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="All Status" 
            class="w-full"
            @change="applyFilters"
            showClear
          />
        </div>
        
        <!-- From Date -->
        <div class="flex flex-col gap-1">
          <label class="text-xs sm:text-sm font-medium text-gray-700">From Date</label>
          <Calendar 
            v-model="filters.from_date" 
            placeholder="From Date" 
            class="w-full"
            @update:modelValue="applyFilters"
            showIcon
            iconDisplay="input"
            dateFormat="yy-mm-dd"
          />
        </div>
        
        <!-- To Date -->
        <div class="flex flex-col gap-1">
          <label class="text-xs sm:text-sm font-medium text-gray-700">To Date</label>
          <Calendar 
            v-model="filters.to_date" 
            placeholder="To Date" 
            class="w-full"
            @update:modelValue="applyFilters"
            showIcon
            iconDisplay="input"
            dateFormat="yy-mm-dd"
          />
        </div>
        
        <!-- Search -->
        <div class="flex flex-col gap-1">
          <label class="text-xs sm:text-sm font-medium text-gray-700">Search</label>
          <InputText 
            v-model="filters.search" 
            placeholder="Order # or Customer..." 
            class="w-full text-sm"
            @keyup.enter="applyFilters"
          />
        </div>
        
        <!-- Actions -->
        <div class="flex flex-col gap-1 justify-end">
          <Button 
            label="Reset Filters" 
            icon="pi pi-refresh" 
            severity="secondary"
            outlined
            @click="resetFilters"
            class="w-full text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading.orders" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="orders.length === 0" class="bg-white rounded-lg shadow-sm p-8 sm:p-12 text-center">
      <i class="pi pi-shopping-cart text-5xl sm:text-6xl text-gray-300 mb-4 sm:mb-6 block"></i>
      <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2">No Orders Yet</h3>
      <p class="text-sm sm:text-base text-gray-600">You haven't received any orders yet</p>
    </div>

    <!-- Orders Table -->
    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable 
        :value="orders" 
        class="p-datatable-sm"
        paginator
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        lazy
        @page="onPageChange"
        responsiveLayout="scroll"
        scrollable
        scrollHeight="flex"
      >
        <!-- Order # - Column 1 -->
        <Column field="order_number" header="Order #" style="min-width: 80px; max-width: 120px;">
          <template #body="{ data }">
            <span class="font-medium text-gray-900 text-xs sm:text-sm">{{ data.order_number }}</span>
          </template>
        </Column>
        
        <!-- Customer - Column 2 (Hidden on mobile) -->
        <Column field="customer_name" header="Customer" style="min-width: 100px; max-width: 150px;">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-900 text-xs sm:text-sm truncate">{{ data.customer_name }}</p>
              <p class="text-xs text-gray-500 truncate hidden sm:block">{{ data.customer_email }}</p>
            </div>
          </template>
        </Column>

        <!-- Total - Column 3 -->
        <Column field="grand_total" header="Total" style="min-width: 70px; max-width: 100px;">
          <template #body="{ data }">
            <span class="font-bold text-[#00685F] text-xs sm:text-sm">{{ formatPrice(data.grand_total) }}</span>
          </template>
        </Column>

        <!-- Status - Column 4 -->
        <Column field="status" header="Status" style="min-width: 70px; max-width: 100px;">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" class="text-xs px-1 sm:px-2" />
          </template>
        </Column>

        <!-- Payment - Column 5 (Hidden on mobile) -->
        <Column field="payment_status" header="Payment" style="min-width: 70px; max-width: 100px;">
          <template #body="{ data }">
            <Tag :value="formatPaymentStatus(data.payment_status)" :severity="getPaymentSeverity(data.payment_status)" class="text-xs px-1 sm:px-2" />
          </template>
        </Column>

        <!-- Date - Column 6 (Hidden on mobile) -->
        <Column field="created_at" header="Date" style="min-width: 80px; max-width: 110px;">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions - Column 7 (Always Visible) -->
        <Column header="Actions" style="min-width: 70px; max-width: 120px;">
          <template #body="{ data }">
            <div class="flex gap-1 items-center flex-nowrap">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small" 
                @click="router.push({ name: 'VendorOrderDetail', params: { id: data.id } })" 
                tooltip="View Order Details"
                class="p-button-sm !w-6 !h-6 sm:!w-7 sm:!h-7"
              />
              
              <!-- Only show status update for non-final statuses -->
              <Dropdown 
                v-if="canUpdateStatus(data.status)"
                v-model="selectedStatus[data.id]"
                :options="getAvailableStatuses(data.status)"
                optionLabel="label"
                optionValue="value"
                placeholder="Update"
                size="small"
                class="w-16 sm:w-20"
                @change="updateOrderStatus(data.id, selectedStatus[data.id] as string)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'

const router = useRouter()
const toast = useToast()
const dashboardStore = useVendorDashboardStore()

// ✅ Selected status for each order
const selectedStatus = reactive<Record<number, string>>({})

// ✅ Filters
const filters = ref({
  status: null as string | null,
  from_date: null as Date | null,
  to_date: null as Date | null,
  search: null as string | null
})

// ✅ Status options with proper labels
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Cancelled', value: 'cancelled' }
]

// ✅ Check if vendor can update this order status
const canUpdateStatus = (status: string): boolean => {
  return ['pending', 'processing', 'confirmed'].includes(status)
}

// ✅ Get available statuses based on current status
const getAvailableStatuses = (currentStatus: string) => {
  const statusMap: Record<string, any[]> = {
    pending: [{ label: 'Processing', value: 'processing' }],
    processing: [{ label: 'Confirmed', value: 'confirmed' }],
    confirmed: [{ label: 'Shipped', value: 'shipped' }]
  }
  return statusMap[currentStatus] || []
}

const orders = computed(() => dashboardStore.orders)
const pagination = computed(() => dashboardStore.orderPagination)
const loading = computed(() => dashboardStore.loading)

// ✅ Format date for API (YYYY-MM-DD)
const formatDateForApi = (date: Date | null): string | null => {
  if (!date) return null
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// ✅ Fetch orders with filters
const fetchOrders = () => {
  const params: any = {
    page: pagination.value.current_page || 1,
    per_page: pagination.value.per_page || 15
  }
  
  if (filters.value.status) {
    params.status = filters.value.status
  }
  
  if (filters.value.search) {
    params.search = filters.value.search
  }
  
  const fromDate = formatDateForApi(filters.value.from_date)
  const toDate = formatDateForApi(filters.value.to_date)
  
  if (fromDate) {
    params.from_date = fromDate
  }
  
  if (toDate) {
    params.to_date = toDate
  }
  
  dashboardStore.setFilters(params)
  dashboardStore.fetchOrders(params.page, params.per_page, true)
}

// ✅ Apply filters
const applyFilters = () => {
  fetchOrders()
}

// ✅ Reset filters
const resetFilters = () => {
  filters.value = {
    status: null,
    from_date: null,
    to_date: null,
    search: null
  }
  
  Object.keys(selectedStatus).forEach(key => {
    selectedStatus[Number(key)] = ''
  })
  
  dashboardStore.resetFilters()
  fetchOrders()
  
  toast.add({
    severity: 'info',
    summary: 'Filters Reset',
    detail: 'All filters have been cleared',
    life: 2000
  })
}

const onPageChange = (event: any) => {
  const page = Math.floor(event.first / event.rows) + 1
  dashboardStore.fetchOrders(page, event.rows)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ✅ Update order status with tracking number prompt
const updateOrderStatus = async (orderId: number, status: string) => {
  try {
    if (status === 'shipped') {
      const trackingNumber = prompt('Please enter tracking number:')
      if (!trackingNumber) {
        toast.add({
          severity: 'warn',
          summary: 'Cancelled',
          detail: 'Tracking number is required for shipping',
          life: 3000
        })
        selectedStatus[orderId] = ''
        return
      }
      await dashboardStore.updateOrderStatus(orderId, status, trackingNumber)
    } else {
      await dashboardStore.updateOrderStatus(orderId, status)
    }
    
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'Order status updated successfully',
      life: 3000
    })
    
    selectedStatus[orderId] = ''
    
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to update order status',
      life: 3000
    })
  }
}

// ✅ Format helpers
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { 
    style: 'currency', 
    currency: 'USD' 
  }).format(parseFloat(String(price)) || 0)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
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

onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

:deep(.p-dropdown .p-dropdown-label) {
  font-size: 0.875rem;
}

:deep(.p-calendar) {
  width: 100%;
}

:deep(.p-calendar .p-inputtext) {
  font-size: 0.875rem;
}

.flex-col label {
  font-weight: 500;
  font-size: 0.875rem;
}

/* ✅ Action column always visible - sticky at end */
:deep(.p-datatable .p-datatable-tbody > tr > td:last-child),
:deep(.p-datatable .p-datatable-thead > tr > th:last-child) {
  position: sticky !important;
  right: 0 !important;
  background: white !important;
  z-index: 2 !important;
}

/* ✅ Desktop (1024px and above) - All columns fit */
@media (min-width: 1024px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.7rem !important;
    font-size: 0.8rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.7rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.8rem !important;
  }
}

/* ✅ Tablet (768px to 1023px) - All columns visible but smaller */
@media (min-width: 768px) and (max-width: 1023px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.3rem 0.4rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.3rem 0.4rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.3rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.6rem !important;
    padding: 0.1rem 0.3rem !important;
  }
  
  :deep(.p-datatable .p-dropdown) {
    min-width: 3rem !important;
    max-width: 3.5rem !important;
  }
  
  :deep(.p-datatable .p-dropdown .p-dropdown-label) {
    font-size: 0.55rem !important;
    padding: 0.1rem 0.2rem !important;
  }
}

/* ✅ Mobile (below 768px) - Hide columns 2, 5, 6 (Customer, Payment, Date) */
@media (max-width: 767px) {
  /* ✅ Hide Customer (col 2), Payment (col 5), Date (col 6) */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(6)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(6)) {
    display: none !important;
  }
  
  /* ✅ Ensure Order # (col 1), Total (col 3), Status (col 4), Actions (col 7) visible */
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
  
  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.3rem 0.25rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    padding: 0.15rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-dropdown) {
    min-width: 3rem !important;
    max-width: 3.5rem !important;
  }
  
  :deep(.p-datatable .p-dropdown .p-dropdown-label) {
    font-size: 0.55rem !important;
    padding: 0.15rem 0.2rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.55rem !important;
    padding: 0.1rem 0.3rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child),
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child) {
    position: sticky !important;
    right: 0 !important;
    background: white !important;
    z-index: 3 !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.25rem !important;
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
    font-size: 0.6rem !important;
  }
}

/* Extra small devices (below 480px) */
@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.2rem 0.15rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.55rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    padding: 0.1rem !important;
    min-width: 1.1rem !important;
    height: 1.1rem !important;
  }
  
  :deep(.p-datatable .p-dropdown) {
    min-width: 2.5rem !important;
    max-width: 3rem !important;
  }
  
  :deep(.p-datatable .p-dropdown .p-dropdown-label) {
    font-size: 0.45rem !important;
    padding: 0.1rem 0.15rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.45rem !important;
    padding: 0.05rem 0.2rem !important;
  }
}

/* General table styles */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  white-space: nowrap;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:last-child .flex) {
  flex-wrap: nowrap;
}

/* Calendar responsive fix */
@media (max-width: 640px) {
  :deep(.p-calendar .p-inputtext) {
    font-size: 0.75rem !important;
    padding: 0.4rem !important;
  }
  
  :deep(.p-calendar .p-button) {
    padding: 0.4rem !important;
  }
}
</style>
