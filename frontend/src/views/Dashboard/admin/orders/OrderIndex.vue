<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Order Management</h1>
          <p class="text-sm sm:text-base text-gray-600">Manage all platform orders</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            size="small"
            :loading="loading"
            @click="fetchOrders"
            outlined
          />
          <Button 
            icon="pi pi-download" 
            label="Export" 
            size="small"
            @click="exportOrders"
            severity="success"
          />
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
      <StatsCard
        title="Total"
        :value="stats.total || 0"
        icon="pi pi-shopping-cart"
        color="blue"
      />
      <StatsCard
        title="Pending"
        :value="stats.pending || 0"
        icon="pi pi-clock"
        color="orange"
      />
      <StatsCard
        title="Processing"
        :value="stats.processing || 0"
        icon="pi pi-spinner"
        color="blue"
      />
      <StatsCard
        title="Shipped"
        :value="stats.shipped || 0"
        icon="pi pi-truck"
        color="green"
      />
      <StatsCard
        title="Delivered"
        :value="stats.delivered || 0"
        icon="pi pi-check-circle"
        color="purple"
      />
      <StatsCard
        title="Revenue"
        :value="formatPrice(stats.total_revenue || 0)"
        icon="pi pi-dollar"
        color="orange"
      />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText 
            v-model="filters.search" 
            placeholder="Search orders..." 
            class="pl-8 w-full text-sm"
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
        <Dropdown 
          v-model="filters.payment_status" 
          :options="paymentStatusOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Payment"
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
            class="flex-1 text-sm"
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
        :rowsPerPageOptions="[10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        responsiveLayout="scroll"
      >
        <!-- Order # -->
        <Column field="order_number" header="Order #" sortable style="min-width: 80px" class="w-5 md:w-32">
          <template #body="{ data }">
            <span class="font-bold text-[10px] md:text-sm">{{ data.order_number }}</span>
          </template>
        </Column>

        <!-- Customer -->
        <Column header="Customer" sortable field="customer_name" style="min-width: 150px" class="w-5 md:w-48">
          <template #body="{ data }">
            <div>
              <p class="text-[10px] md:text-sm font-medium text-gray-900 truncate max-w-[120px] sm:max-w-none">{{ data.customer_name }}</p>
              <p class="text-xs text-gray-500 truncate max-w-[120px] sm:max-w-none">{{ data.customer_email }}</p>
            </div>
          </template>
        </Column>

        <!-- Vendor - Hidden on mobile -->
        <Column header="Vendor" sortable field="vendor.name" style="min-width: 50px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <span class="text-sm">{{ data.vendor?.name || 'N/A' }}</span>
          </template>
        </Column>

        <!-- Total -->
        <Column field="grand_total" header="Total" sortable 
        style="min-width: 30px" class="w-5 md:w-32">
          <template #body="{ data }">
            <span class="font-bold text-[#00685F]">{{ formatPrice(data.grand_total) }}</span>
          </template>
        </Column>

        <!-- Status -->
        <Column field="status" header="Status" sortable style="min-width: 50px">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" size="small" />
          </template>
        </Column>

        <!-- Payment - Hidden on mobile -->
        <Column field="payment_status" header="Payment" sortable style="min-width: 100px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <Tag :value="formatPaymentStatus(data.payment_status)" :severity="getPaymentSeverity(data.payment_status)" size="small" />
          </template>
        </Column>

        <!-- Date - Hidden on tablet -->
        <Column field="created_at" header="Date" sortable style="min-width: 50px" class="hidden md:table-cell">
          <template #body="{ data }">
            <span class="text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions - Sticky on mobile -->
        <Column header="Actions"  class="w-6" :frozen="true" alignFrozen="right">
          <template #body="{ data }">
            <div class="flex items-center gap-0.5 justify-end">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewOrder(data.id)"
                tooltip="View"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small"
                @click="openStatusDialog(data)"
                tooltip="Update"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-shopping-cart text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">No orders found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Update Status Dialog -->
    <Dialog 
      v-model:visible="statusDialogVisible" 
      header="Update Order Status"
      :style="{ width: windowWidth < 640 ? '95%' : '500px' }"
      modal
      class="p-fluid"
    >
      <div class="space-y-4">
        <div class="field">
          <label class="text-sm font-medium text-gray-700">Order #</label>
          <p class="text-sm font-semibold">{{ selectedOrder?.order_number }}</p>
        </div>

        <div class="field">
          <label class="text-sm font-medium text-gray-700">Current Status</label>
          <Tag :value="formatStatus(selectedOrder?.status)" :severity="getStatusSeverity(selectedOrder?.status)" size="small" />
        </div>

        <div class="field">
          <label class="text-sm font-medium text-gray-700">New Status *</label>
          <Dropdown 
            v-model="statusForm.status" 
            :options="availableStatuses" 
            optionLabel="label"
            optionValue="value"
            placeholder="Select Status"
            class="w-full"
          />
          <small v-if="statusErrors.status" class="text-red-500">{{ statusErrors.status }}</small>
        </div>

        <div v-if="statusForm.status === 'shipped'" class="field">
          <label class="text-sm font-medium text-gray-700">Tracking Number</label>
          <InputText v-model="statusForm.tracking_number" placeholder="Enter tracking number" class="w-full" />
        </div>

        <div v-if="statusForm.status === 'shipped'" class="field">
          <label class="text-sm font-medium text-gray-700">Carrier</label>
          <InputText v-model="statusForm.carrier" placeholder="Enter carrier name" class="w-full" />
        </div>

        <div class="field">
          <label class="text-sm font-medium text-gray-700">Notes (Optional)</label>
          <Textarea v-model="statusForm.notes" rows="2" placeholder="Add notes..." class="w-full" />
        </div>
      </div>

      <template #footer>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
          <Button label="Cancel" icon="pi pi-times" text @click="statusDialogVisible = false" class="w-full sm:w-auto" />
          <Button 
            label="Update" 
            icon="pi pi-save" 
            :loading="updating"
            @click="updateStatus"
            severity="primary"
            class="w-full sm:w-auto"
          />
        </div>
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'
import StatsCard from '@/components/StatsCard.vue'

const router = useRouter()
const toast = useToast()

const windowWidth = ref(window.innerWidth)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchOrders()
  fetchStats()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

// State
const orders = ref<any[]>([])
const stats = ref<any>({})
const loading = ref(false)
const updating = ref(false)
const pagination = ref<any>(null)
const first = ref(0)
const statusDialogVisible = ref(false)
const selectedOrder = ref<any>(null)

const filters = reactive({
  search: '',
  status: '',
  payment_status: '',
  per_page: 15,
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

const statusForm = reactive({
  status: '',
  tracking_number: '',
  carrier: '',
  notes: '',
})

const statusErrors = ref<Record<string, string>>({})

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

const paymentStatusOptions = [
  { label: 'All Payment', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Paid', value: 'paid' },
  { label: 'Failed', value: 'failed' },
  { label: 'Refunded', value: 'refunded' },
  { label: 'Partially Refunded', value: 'partially_refunded' },
]

const availableStatuses = computed(() => {
  const currentStatus = selectedOrder.value?.status
  const map: Record<string, any[]> = {
    pending: [
      { label: 'Processing', value: 'processing' },
      { label: 'Cancelled', value: 'cancelled' },
      { label: 'Failed', value: 'failed' },
    ],
    processing: [
      { label: 'Confirmed', value: 'confirmed' },
      { label: 'Cancelled', value: 'cancelled' },
      { label: 'Failed', value: 'failed' },
    ],
    confirmed: [
      { label: 'Shipped', value: 'shipped' },
      { label: 'Cancelled', value: 'cancelled' },
    ],
    shipped: [
      { label: 'Delivered', value: 'delivered' },
      { label: 'Cancelled', value: 'cancelled' },
    ],
    delivered: [
      { label: 'Completed', value: 'completed' },
      { label: 'Refunded', value: 'refunded' },
    ],
    completed: [
      { label: 'Refunded', value: 'refunded' },
    ],
  }
  return map[currentStatus] || []
})

// Methods
const fetchOrders = async () => {
  loading.value = true
  try {
    const response = await adminApi.getOrdersList(filters)
    orders.value = response.data.data || []
    pagination.value = response.data
    first.value = (filters.page - 1) * filters.per_page
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

const fetchStats = async () => {
  try {
    const response = await adminApi.getOrderStats()
    stats.value = response.data.data || {}
  } catch (error) {
    console.error('Failed to fetch stats:', error)
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
  filters.payment_status = ''
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

const viewOrder = (id: string) => {
  router.push(`/dashboard/admin/orders/${id}`)
}

const openStatusDialog = (order: any) => {
  selectedOrder.value = order
  statusForm.status = ''
  statusForm.tracking_number = ''
  statusForm.carrier = ''
  statusForm.notes = ''
  statusErrors.value = {}
  statusDialogVisible.value = true
}

const updateStatus = async () => {
  statusErrors.value = {}
  
  if (!statusForm.status) {
    statusErrors.value = { status: 'Please select a status' }
    return
  }

  updating.value = true
  try {
    const data: any = { status: statusForm.status }
    if (statusForm.tracking_number) data.tracking_number = statusForm.tracking_number
    if (statusForm.carrier) data.carrier = statusForm.carrier
    if (statusForm.notes) data.notes = statusForm.notes

    await adminApi.updateOrderStatus(selectedOrder.value.id, data)
    
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'Order status updated successfully',
      life: 3000
    })
    
    statusDialogVisible.value = false
    await fetchOrders()
    await fetchStats()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update order status',
      life: 3000
    })
  } finally {
    updating.value = false
  }
}

const exportOrders = async () => {
  try {
    const response = await adminApi.exportOrders(filters)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `orders_export_${new Date().toISOString().split('T')[0]}.csv`)
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

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { 
    style: 'currency', 
    currency: 'USD' 
  }).format(price || 0)
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
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f9fafb;
  font-weight: 600;
  font-size: 0.75rem;
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
  font-size: 0.65rem !important;
  padding: 0.1rem 0.4rem !important;
}

/* ✅ Sticky action column on mobile */
@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child),
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child) {
    position: sticky !important;
    right: 0 !important;
    background: white !important;
    z-index: 3 !important;
  }

  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.6rem !important;
    padding: 0.3rem 0.2rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.6rem !important;
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
    font-size: 0.5rem !important;
    padding: 0.05rem 0.2rem !important;
  }
}

/* Extra small screens */
@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.5rem !important;
    padding: 0.2rem 0.1rem !important;
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

/* Dialog responsive */
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