<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Order Details</h1>
          <p class="text-sm sm:text-base text-gray-600">View and manage order #{{ order?.order_number }}</p>
        </div>
        <Button 
          label="Back to Orders" 
          icon="pi pi-arrow-left" 
          severity="secondary"
          outlined
          class="w-full sm:w-auto text-sm sm:text-base"
          @click="router.push('/dashboard/vendor/orders')"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6 text-center">
      <i class="pi pi-exclamation-circle text-3xl sm:text-4xl text-red-500 mb-3 sm:mb-4 block"></i>
      <p class="text-red-700 font-medium text-sm sm:text-base">{{ error }}</p>
      <Button label="Try Again" @click="fetchOrder" class="mt-4 text-sm sm:text-base" />
    </div>

    <!-- Order Details -->
    <div v-else-if="order" class="space-y-4 sm:space-y-6">
      <!-- Order Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 border border-gray-200">
          <p class="text-xs sm:text-sm text-gray-600">Order Status</p>
          <Tag :value="formatStatus(order.status)" :severity="getStatusSeverity(order.status)" class="mt-1 text-xs sm:text-sm" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 border border-gray-200">
          <p class="text-xs sm:text-sm text-gray-600">Payment Status</p>
          <Tag :value="formatPaymentStatus(order.payment_status)" :severity="getPaymentSeverity(order.payment_status)" class="mt-1 text-xs sm:text-sm" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 border border-gray-200">
          <p class="text-xs sm:text-sm text-gray-600">Total Amount</p>
          <p class="text-lg sm:text-xl font-bold text-[#00685F] mt-1">{{ formatPrice(order.grand_total) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 border border-gray-200">
          <p class="text-xs sm:text-sm text-gray-600">Order Date</p>
          <p class="text-sm sm:text-base text-gray-900 mt-1">{{ formatDate(order.created_at) }}</p>
        </div>
      </div>

      <!-- Customer & Shipping Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
          <h3 class="font-semibold text-gray-900 mb-3 sm:mb-4 text-sm sm:text-base">Customer Information</h3>
          <div class="space-y-2 text-sm">
            <p><span class="text-gray-600">Name:</span> {{ order.customer_name }}</p>
            <p><span class="text-gray-600">Email:</span> {{ order.customer_email }}</p>
            <p v-if="order.customer_phone"><span class="text-gray-600">Phone:</span> {{ order.customer_phone }}</p>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
          <h3 class="font-semibold text-gray-900 mb-3 sm:mb-4 text-sm sm:text-base">Shipping Information</h3>
          <div class="space-y-2 text-sm">
            <p><span class="text-gray-600">Address:</span> {{ order.shipping_address }}</p>
            <p><span class="text-gray-600">City:</span> {{ order.shipping_city }}</p>
            <p v-if="order.shipping_state"><span class="text-gray-600">State:</span> {{ order.shipping_state }}</p>
            <p v-if="order.shipping_postal_code"><span class="text-gray-600">Postal Code:</span> {{ order.shipping_postal_code }}</p>
            <p><span class="text-gray-600">Country:</span> {{ order.shipping_country }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Order Items</h3>
        </div>
        <DataTable :value="order.items" class="p-datatable-sm">
          <Column field="product_name" header="Product" style="min-width: 150px; max-width: 250px;">
            <template #body="{ data }">
              <div>
                <p class="font-medium text-gray-900 text-xs sm:text-sm">{{ data.product_name }}</p>
                <p v-if="data.product_variation_name" class="text-xs text-gray-500">{{ data.product_variation_name }}</p>
                <p class="text-xs text-gray-400 hidden sm:block">SKU: {{ data.product_sku }}</p>
              </div>
            </template>
          </Column>
          <Column field="quantity" header="Qty" style="min-width: 50px; max-width: 80px;">
            <template #body="{ data }">
              <span class="text-xs sm:text-sm">{{ data.quantity }}</span>
            </template>
          </Column>
          <Column field="unit_price" header="Unit Price" style="min-width: 80px; max-width: 120px;">
            <template #body="{ data }">
              <span class="text-xs sm:text-sm">{{ formatPrice(data.unit_price) }}</span>
            </template>
          </Column>
          <Column field="total" header="Total" style="min-width: 80px; max-width: 120px;">
            <template #body="{ data }">
              <span class="text-xs sm:text-sm font-bold text-[#00685F]">{{ formatPrice(data.total) }}</span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Order Totals -->
      <div class="flex justify-end">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 w-full sm:w-80">
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal</span>
              <span>{{ formatPrice(order.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Tax</span>
              <span>{{ formatPrice(order.tax_total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Shipping</span>
              <span>{{ formatPrice(order.shipping_total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Discount</span>
              <span>{{ formatPrice(order.discount_total) }}</span>
            </div>
            <div class="border-t border-gray-200 pt-2 mt-2">
              <div class="flex justify-between font-bold text-base sm:text-lg">
                <span>Total</span>
                <span class="text-[#00685F]">{{ formatPrice(order.grand_total) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tracking & Notes -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <div v-if="order.tracking_number" class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
          <h3 class="font-semibold text-gray-900 mb-2 text-sm sm:text-base">Tracking Information</h3>
          <p class="text-sm"><span class="text-gray-600">Tracking #:</span> {{ order.tracking_number }}</p>
          <p v-if="order.carrier" class="text-sm"><span class="text-gray-600">Carrier:</span> {{ order.carrier }}</p>
          <p v-if="order.shipped_at" class="text-sm"><span class="text-gray-600">Shipped:</span> {{ formatDate(order.shipped_at) }}</p>
        </div>

        <div v-if="order.cancellation_reason" class="bg-red-50 rounded-lg shadow-sm p-4 sm:p-6 border border-red-200">
          <h3 class="font-semibold text-red-700 mb-2 text-sm sm:text-base">Cancellation Reason</h3>
          <p class="text-sm text-red-600">{{ order.cancellation_reason }}</p>
          <p v-if="order.cancelled_at" class="text-sm text-red-500 mt-2">Cancelled on: {{ formatDate(order.cancelled_at) }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
        <h3 class="font-semibold text-gray-900 mb-3 sm:mb-4 text-sm sm:text-base">Order Actions</h3>
        <div class="flex flex-wrap gap-2 sm:gap-3">
          <!-- Status Update -->
          <div v-if="canUpdateStatus(order.status)" class="flex gap-2 items-center">
            <Dropdown 
              v-model="selectedStatus"
              :options="getAvailableStatuses(order.status)"
              optionLabel="label"
              optionValue="value"
              placeholder="Update Status"
              class="w-40 sm:w-48"
              @change="updateOrderStatus(order.id, selectedStatus)"
            />
          </div>

          <!-- Tracking Info Button -->
          <Button 
            v-if="order.status === 'shipped' && order.tracking_number"
            label="View Tracking" 
            icon="pi pi-external-link" 
            severity="info"
            outlined
            class="text-sm"
            @click="openTracking(order.tracking_number)"
          />

          <!-- Invoice -->
          <Button 
            label="Download Invoice" 
            icon="pi pi-file-pdf" 
            severity="secondary"
            outlined
            class="text-sm"
            @click="downloadInvoice(order.id)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'
import { vendorApi } from '@/api/endpoints/vendor/vendor.api'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const dashboardStore = useVendorDashboardStore()

const order = ref<any>(null)
const loading = ref(false)
const error = ref('')
const selectedStatus = ref('')

// ✅ Get available statuses based on current status
const getAvailableStatuses = (currentStatus: string) => {
  const statusMap: Record<string, any[]> = {
    pending: [{ label: 'Processing', value: 'processing' }],
    processing: [{ label: 'Confirmed', value: 'confirmed' }],
    confirmed: [{ label: 'Shipped', value: 'shipped' }]
  }
  return statusMap[currentStatus] || []
}

const canUpdateStatus = (status: string): boolean => {
  return ['pending', 'processing', 'confirmed'].includes(status)
}

const fetchOrder = async () => {
  const orderId = route.params.id
  if (!orderId) {
    error.value = 'Order ID not found'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await vendorApi.getOrder(Number(orderId))
    order.value = response.data.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load order details'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.value,
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

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
        selectedStatus.value = ''
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
    
    // Refresh order data
    await fetchOrder()
    selectedStatus.value = ''
    
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to update order status',
      life: 3000
    })
  }
}

const openTracking = (trackingNumber: string) => {
  window.open(`https://www.17track.net/en/track?nums=${trackingNumber}`, '_blank')
}

const downloadInvoice = (orderId: number) => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Invoice download will be available soon',
    life: 3000
  })
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
  fetchOrder()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

/* ✅ Responsive fixes */
@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.4rem 0.3rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.4rem 0.3rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.7rem !important;
  }
  
  /* ✅ Hide SKU on mobile */
  :deep(.p-datatable .p-datatable-tbody > tr > td:first-child .text-gray-400) {
    display: none !important;
  }
}

@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.3rem 0.2rem !important;
    font-size: 0.55rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.3rem 0.2rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.6rem !important;
  }
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  white-space: nowrap;
}
</style>
