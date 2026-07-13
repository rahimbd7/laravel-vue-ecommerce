<!-- src/views/Orders/OrderDetail.vue -->
<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
         <router-link 
          to="/dashboard/customer/orders" 
          class="inline-flex items-center gap-2 text-[#00685F] hover:text-[#004F45] font-medium transition"
        >
          <i class="pi pi-arrow-left"></i>
          Back to Orders
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
      </div>

      <!-- Order Details -->
      <div v-else-if="order" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Order Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
              <p class="text-sm text-gray-600">Order #</p>
              <p class="font-semibold text-gray-900">{{ order.order_number }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Order Date</p>
              <p class="text-gray-900">{{ formatDate(order.timestamps?.created_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Status</p>
              <span :class="getStatusClass(order.status?.order)" class="inline-flex px-3 py-1 rounded-full text-sm font-medium">
                {{ formatStatus(order.status?.order) }}
              </span>
            </div>
            <div>
              <p class="text-sm text-gray-600">Payment</p>
              <span :class="getPaymentStatusClass(order.status?.payment)" class="inline-flex px-3 py-1 rounded-full text-sm font-medium">
                {{ formatPaymentStatus(order.status?.payment) }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6">
          <!-- ✅ Show cancellation reason if order is cancelled -->
          <div v-if="order.status?.order === 'cancelled' && order.cancellation_reason" 
               class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <h4 class="text-sm font-semibold text-red-800">Order Cancelled</h4>
                <p class="text-sm text-red-600 mt-1">{{ order.cancellation_reason }}</p>
                <p class="text-xs text-red-500 mt-1">Cancelled on: {{ formatDate(order.cancelled_at || order.timestamps?.updated_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <h3 class="font-semibold text-gray-900 mb-3">Order Items</h3>
          <div class="space-y-3 mb-6">
            <div v-for="item in order.items" :key="item.id" class="flex gap-4 py-3 border-b border-gray-100 last:border-0">
              <div class="flex-1">
                <p class="font-medium text-gray-900">{{ item.product?.name }}</p>
                <p v-if="item.product?.variation" class="text-sm text-gray-600">Variation: {{ item.product.variation }}</p>
                <p class="text-sm text-gray-500">Qty: {{ item.quantity }}</p>
                <p class="text-xs text-gray-400">SKU: {{ item.product?.sku }}</p>
              </div>
              <div class="text-right">
                <p class="font-semibold text-gray-900">{{ formatPrice(item.pricing?.unit_price) }}</p>
                <p class="text-sm text-gray-500">Total: {{ formatPrice(item.pricing?.total) }}</p>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-900 mb-3">Order Summary</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal:</span>
                <span>{{ formatPrice(order.pricing?.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Shipping:</span>
                <span>{{ formatPrice(order.pricing?.shipping_total) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tax:</span>
                <span>{{ formatPrice(order.pricing?.tax_total) }}</span>
              </div>
              <div v-if="order.pricing?.discount_total > 0" class="flex justify-between text-sm text-red-600">
                <span>Discount:</span>
                <span>-{{ formatPrice(order.pricing?.discount_total) }}</span>
              </div>
              <div class="border-t border-gray-200 pt-2 flex justify-between font-semibold">
                <span>Total:</span>
                <span class="text-[#00685F]">{{ formatPrice(order.pricing?.grand_total) }}</span>
              </div>
            </div>
          </div>

          <!-- Shipping & Billing -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Shipping Address</h3>
              <p class="text-sm text-gray-600">{{ order.addresses?.shipping?.address }}</p>
              <p class="text-sm text-gray-600">{{ order.addresses?.shipping?.city }}, {{ order.addresses?.shipping?.state }} {{ order.addresses?.shipping?.postal_code }}</p>
              <p class="text-sm text-gray-600">{{ order.addresses?.shipping?.country }}</p>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Billing Address</h3>
              <p class="text-sm text-gray-600">{{ order.addresses?.billing?.address }}</p>
              <p class="text-sm text-gray-600">{{ order.addresses?.billing?.city }}, {{ order.addresses?.billing?.state }} {{ order.addresses?.billing?.postal_code }}</p>
              <p class="text-sm text-gray-600">{{ order.addresses?.billing?.country }}</p>
            </div>
          </div>

          <!-- Shipping Info -->
          <div v-if="order.shipping" class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="font-semibold text-gray-900 mb-2">Shipping Information</h3>
            <p class="text-sm text-gray-600">Method: {{ order.shipping.method || 'Standard' }}</p>
            <p v-if="order.shipping.tracking_number" class="text-sm text-gray-600">Tracking: {{ order.shipping.tracking_number }}</p>
          </div>

          <!-- Cancel Button - Hide if order is already cancelled -->
          <div v-if="canCancelOrder(order)" class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
            <button @click="confirmCancelOrder" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">Cancel Order</button>
          </div>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else-if="!loading && !order" class="text-center py-20">
        <p class="text-gray-500">Order not found</p>
        <router-link to="/orders" class="text-[#00685F] hover:text-[#004F45] mt-4 inline-block">Back to Orders</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/api'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const order = ref<any>(null)

const fetchOrder = async () => {
  loading.value = true
  try {
    const orderId = route.params.id
    console.log('Order ID from route:', orderId)
    
    if (!orderId) {
      console.error('No order ID found in route params')
      router.push('/orders')
      return
    }
    
    const response = await api.get(`/orders/${orderId}`)
    console.log('Order response:', response.data)
    order.value = response.data.data
  } catch (err: any) {
    console.error('Fetch order error:', err)
    if (err.response?.status === 404) {
      router.push('/orders')
    }
  } finally {
    loading.value = false
  }
}

const canCancelOrder = (order: any): boolean => {
  if (!order?.status?.order) return false
  // ✅ Hide cancel button if order is already cancelled
  if (order.status.order === 'cancelled') return false
  return ['pending', 'processing'].includes(order.status.order) && order.status.payment !== 'paid'
}

const confirmCancelOrder = async () => {
  // ✅ Fixed: Added missing closing quote and proper HTML
  const { value: cancellationReason, isConfirmed } = await Swal.fire({
    title: 'Cancel Order?',
    html: `
      <div class="text-left">
        <p class="text-gray-600 mb-4">Are you sure you want to cancel order <strong>#${order.value.order_number}</strong>?</p>
        <div class="mb-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Reason for Cancellation <span class="text-red-500">*</span>
          </label>
          <textarea 
            id="cancellation-reason" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F] focus:ring-1 focus:ring-[#00685F]" 
            placeholder="Please tell us why you're cancelling this order..."
            rows="4"
            maxlength="500"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">Maximum 500 characters</p>
        </div>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#00685F',
    confirmButtonText: 'Yes, cancel it',
    cancelButtonText: 'No, keep it',
    width: '500px',
    focusConfirm: false,
    preConfirm: () => {
      const reason = (document.getElementById('cancellation-reason') as HTMLTextAreaElement)?.value?.trim()
      if (!reason) {
        Swal.showValidationMessage('Please provide a reason for cancellation')
        return false
      }
      if (reason.length > 500) {
        Swal.showValidationMessage('Reason cannot exceed 500 characters')
        return false
      }
      return reason
    }
  })

  if (!isConfirmed) return
  if (!cancellationReason) return

  try {
    const response = await api.post(`/orders/${order.value.id}/cancel`, { 
      cancellation_reason: cancellationReason 
    })
    
    if (response.data.status === 'success') {
      await Swal.fire({ 
        icon: 'success', 
        title: 'Cancelled', 
        text: 'Order cancelled successfully', 
        confirmButtonColor: '#00685F' 
      })
      fetchOrder()
    }
  } catch (err: any) {
    await Swal.fire({ 
      icon: 'error', 
      title: 'Error', 
      text: err.response?.data?.message || 'Failed to cancel', 
      confirmButtonColor: '#00685F' 
    })
  }
}

const formatPrice = (price: string | number): string => {
  if (!price && price !== 0) return '$0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(numPrice)) return '$0.00'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numPrice)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatStatus = (status: string): string => {
  if (!status) return 'Pending'
  const statusMap: Record<string, string> = {
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
  return statusMap[status.toLowerCase()] || status
}

const formatPaymentStatus = (status: string): string => {
  if (!status) return 'Pending'
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    paid: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded',
    partially_refunded: 'Partially Refunded'
  }
  return statusMap[status.toLowerCase()] || status
}

const getStatusClass = (status: string): string => {
  if (!status) return 'bg-yellow-100 text-yellow-800'
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
    failed: 'bg-red-100 text-red-800'
  }
  return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

const getPaymentStatusClass = (status: string): string => {
  if (!status) return 'bg-yellow-100 text-yellow-800'
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
    partially_refunded: 'bg-orange-100 text-orange-800'
  }
  return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchOrder()
})
</script>