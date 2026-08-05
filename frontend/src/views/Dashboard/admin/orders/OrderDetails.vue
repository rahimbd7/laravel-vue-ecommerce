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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Order Details</h1>
            <p class="text-sm text-gray-600">Order #{{ order?.order_number }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            icon="pi pi-pencil" 
            label="Update Status" 
            size="small"
            @click="openStatusDialog"
            severity="primary"
          />
          <Button 
            v-if="canCancel"
            icon="pi pi-times" 
            label="Cancel" 
            size="small"
            severity="danger"
            outlined
            @click="confirmCancel"
          />
          <Button 
            v-if="canRefund"
            icon="pi pi-undo" 
            label="Refund" 
            size="small"
            severity="warning"
            outlined
            @click="confirmRefund"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Order Content -->
    <template v-else-if="order">
      <!-- Status Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
          <p class="text-xs text-gray-500">Order Status</p>
          <Tag :value="formatStatus(order.status)" :severity="getStatusSeverity(order.status)" class="mt-1" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
          <p class="text-xs text-gray-500">Payment</p>
          <Tag :value="formatPaymentStatus(order.payment_status)" :severity="getPaymentSeverity(order.payment_status)" class="mt-1" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
          <p class="text-xs text-gray-500">Total</p>
          <p class="text-lg font-bold text-[#00685F] mt-1">{{ formatPrice(order.grand_total) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 text-center">
          <p class="text-xs text-gray-500">Date</p>
          <p class="text-sm font-medium text-gray-900 mt-1">{{ formatDate(order.created_at) }}</p>
        </div>
      </div>

      <!-- Order Details Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <i class="pi pi-user text-[#00685F]"></i> Customer Information
          </h4>
          <div class="space-y-2">
            <p class="text-sm"><span class="text-gray-500">Name:</span> {{ order.customer_name }}</p>
            <p class="text-sm"><span class="text-gray-500">Email:</span> {{ order.customer_email }}</p>
            <p class="text-sm"><span class="text-gray-500">Phone:</span> {{ order.customer_phone || 'N/A' }}</p>
            <Button 
              v-if="order.user_id"
              icon="pi pi-user" 
              label="View Customer" 
              size="small"
              text
              @click="viewCustomer(order.user_id)"
              class="mt-2"
            />
          </div>
        </div>

        <!-- Shipping Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <i class="pi pi-truck text-[#00685F]"></i> Shipping Information
          </h4>
          <div class="space-y-2">
            <p class="text-sm"><span class="text-gray-500">Method:</span> {{ order.shipping_method || 'N/A' }}</p>
            <p class="text-sm"><span class="text-gray-500">Tracking:</span> 
              <span v-if="order.tracking_number" class="text-[#00685F] font-medium">
                {{ order.tracking_number }}
              </span>
              <span v-else class="text-gray-400">Not available</span>
            </p>
            <p class="text-sm"><span class="text-gray-500">Carrier:</span> {{ order.carrier || 'N/A' }}</p>
            <p class="text-sm"><span class="text-gray-500">Address:</span> {{ order.shipping_address }}</p>
            <p class="text-sm">{{ order.shipping_city }}, {{ order.shipping_state }} {{ order.shipping_postal_code }}</p>
            <p class="text-sm">{{ order.shipping_country }}</p>
          </div>
        </div>

        <!-- Billing Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <i class="pi pi-credit-card text-[#00685F]"></i> Billing Information
          </h4>
          <div class="space-y-2">
            <p class="text-sm"><span class="text-gray-500">Method:</span> {{ order.payment_method || 'N/A' }}</p>
            <p class="text-sm"><span class="text-gray-500">Transaction:</span> {{ order.payment_transaction_id || 'N/A' }}</p>
            <p class="text-sm"><span class="text-gray-500">Paid At:</span> {{ order.paid_at ? formatDate(order.paid_at) : 'N/A' }}</p>
            <p class="text-sm"><span class="text-gray-500">Address:</span> {{ order.billing_address }}</p>
            <p class="text-sm">{{ order.billing_city }}, {{ order.billing_state }} {{ order.billing_postal_code }}</p>
            <p class="text-sm">{{ order.billing_country }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
          <h4 class="font-semibold text-gray-900">Order Items</h4>
          <span class="text-sm text-gray-500">{{ order.items?.length || 0 }} items</span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-900">
                  {{ item.product_name }}
                  <span v-if="item.product_variation_name" class="text-xs text-gray-500 block">
                    {{ item.product_variation_name }}
                  </span>
                </td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600">{{ item.product_sku || 'N/A' }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">{{ formatPrice(item.unit_price) }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">{{ item.quantity }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm font-medium text-[#00685F] text-right">{{ formatPrice(item.total) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="4" class="px-3 sm:px-4 py-2 sm:py-3 text-sm font-medium text-gray-900 text-right">Subtotal</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-900 text-right">{{ formatPrice(order.subtotal) }}</td>
              </tr>
              <tr v-if="order.discount_total > 0">
                <td colspan="4" class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">Discount</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-red-600 text-right">-{{ formatPrice(order.discount_total) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">Shipping</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">{{ formatPrice(order.shipping_total) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">Tax</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 text-right">{{ formatPrice(order.tax_total) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-3 sm:px-4 py-3 text-sm font-bold text-gray-900 text-right">Grand Total</td>
                <td class="px-3 sm:px-4 py-3 text-sm font-bold text-[#00685F] text-right">{{ formatPrice(order.grand_total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Order Notes -->
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6" v-if="order.notes || order.customer_notes">
        <h4 class="font-semibold text-gray-900 mb-3">Order Notes</h4>
        <div v-if="order.notes" class="bg-gray-50 rounded-lg p-3 mb-2">
          <p class="text-sm text-gray-700"><span class="font-medium">Admin Note:</span> {{ order.notes }}</p>
        </div>
        <div v-if="order.customer_notes" class="bg-gray-50 rounded-lg p-3">
          <p class="text-sm text-gray-700"><span class="font-medium">Customer Note:</span> {{ order.customer_notes }}</p>
        </div>
      </div>

      <!-- Order Timeline -->
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <i class="pi pi-clock text-[#00685F]"></i> Order Timeline
        </h4>
        <div v-if="timeline.length === 0" class="text-center py-4">
          <p class="text-gray-500">No activities found</p>
        </div>
        <div v-else class="space-y-3 max-h-80 overflow-y-auto">
          <div v-for="activity in timeline" :key="activity.id" class="flex gap-3 border-l-2 border-gray-200 pl-4 pb-3">
            <div class="flex-shrink-0 mt-1">
              <div class="w-6 h-6 rounded-full flex items-center justify-center bg-gray-200">
                <i class="pi pi-clock text-xs text-gray-600"></i>
              </div>
            </div>
            <div>
              <p class="text-sm text-gray-900">{{ activity.description }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(activity.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>

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
          <p class="text-sm font-semibold">{{ order?.order_number }}</p>
        </div>

        <div class="field">
          <label class="text-sm font-medium text-gray-700">Current Status</label>
          <Tag :value="formatStatus(order?.status)" :severity="getStatusSeverity(order?.status)" size="small" />
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
import { ref, reactive, onMounted, computed, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const orderId = route.params.id as string
const windowWidth = ref(window.innerWidth)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchOrderDetails()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

// State
const order = ref<any>(null)
const timeline = ref<any[]>([])
const loading = ref(false)
const updating = ref(false)
const statusDialogVisible = ref(false)

const statusForm = reactive({
  status: '',
  tracking_number: '',
  carrier: '',
  notes: '',
})

const statusErrors = ref<Record<string, string>>({})

// Computed
const canCancel = computed(() => {
  if (!order.value) return false
  return ['pending', 'processing'].includes(order.value.status)
})

const canRefund = computed(() => {
  if (!order.value) return false
  return ['delivered', 'completed'].includes(order.value.status) && 
         order.value.payment_status === 'paid'
})

const availableStatuses = computed(() => {
  const currentStatus = order.value?.status
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
const fetchOrderDetails = async () => {
  loading.value = true
  try {
    const [orderResponse, timelineResponse] = await Promise.all([
      adminApi.getOrder(orderId),
      adminApi.getOrderTimeline(orderId)
    ])
    order.value = orderResponse.data.data
    timeline.value = timelineResponse.data.data || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load order details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const openStatusDialog = () => {
  statusForm.status = ''
  statusForm.tracking_number = ''
  statusForm.carrier = ''
  statusForm.notes = ''
  statusErrors.value = {}
  statusDialogVisible.value = true
}

const updateStatus = async () => {
  statusErrors.value = {}
  console.log('Updating status with form:', statusForm)
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

    await adminApi.updateOrderStatus(orderId, data)
    
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'Order status updated successfully',
      life: 3000
    })
    
    statusDialogVisible.value = false
    await fetchOrderDetails()
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

const confirmCancel = () => {
  confirm.require({
    message: `Are you sure you want to cancel order #${order.value?.order_number}?`,
    header: 'Confirm Cancel',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.cancelOrder(orderId, { reason: 'Cancelled by admin' })
        toast.add({
          severity: 'success',
          summary: 'Cancelled',
          detail: 'Order cancelled successfully',
          life: 3000
        })
        await fetchOrderDetails()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to cancel order',
          life: 3000
        })
      }
    }
  })
}

const confirmRefund = () => {
  confirm.require({
    message: `Are you sure you want to refund order #${order.value?.order_number}?`,
    header: 'Confirm Refund',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        // You need to implement refund endpoint
        // await adminApi.refundOrder(orderId, { reason: 'Refunded by admin' })
        // toast.add({
        //   severity: 'success',
        //   summary: 'Refunded',
        //   detail: 'Order refunded successfully',
        //   life: 3000
        // })
        await fetchOrderDetails()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to refund order',
          life: 3000
        })
      }
    }
  })
}

const viewCustomer = (userId: string) => {
  router.push(`/dashboard/admin/users/${userId}`)
}

const goBack = () => {
  router.push('/dashboard/admin/orders')
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
:deep(.p-confirm-dialog .p-dialog-content) {
  padding: 1.5rem;
}

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

.max-h-80 {
  max-height: 20rem;
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