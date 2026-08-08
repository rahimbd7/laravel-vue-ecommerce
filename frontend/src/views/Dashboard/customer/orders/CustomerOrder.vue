<!-- src/views/Orders/OrderList.vue -->
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center space-x-2 text-sm">
          <router-link to="/" class="text-[#00685F] hover:text-[#004F45]">Home</router-link>
          <span class="text-gray-400">/</span>
          <router-link to="/shop" class="text-[#00685F] hover:text-[#004F45]">Shop</router-link>
          <span class="text-gray-400">/</span>
          <span class="text-gray-600">My Orders</span>
        </div>
      </div>
    </div>
    

    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">My Orders</h1>
        <p class="text-gray-600 mt-2">View and track your order history</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-700 font-medium mb-4">{{ error }}</p>
        <button @click="fetchOrders" class="bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">Try Again</button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="orders.length === 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <i class="pi pi-shopping-bag text-6xl text-gray-300 mb-6 block"></i>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Orders Yet</h3>
        <p class="text-gray-600 mb-6">Start shopping to place your first order</p>
        <router-link to="/shop" class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">Start Shopping</router-link>
      </div>
    </div>

    <!-- Orders List -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Orders Count Info -->
      <div class="mb-4 text-sm text-gray-600">
        Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} - 
        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
        of {{ pagination.total }} orders
      </div>

      <div class="space-y-4">
        <div v-for="order in orders" :key="order.id" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
          <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex flex-wrap justify-between items-center gap-4">
              <div>
                <p class="text-sm text-gray-600">Order #</p>
                <p class="font-semibold text-gray-900">{{ order.order_number || order.id }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Order Date</p>
                <p class="text-gray-900">{{ formatDate(order.created_at) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Amount</p>
                <p class="text-xl font-bold text-[#00685F]">{{ formatPrice(order.grand_total) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Status</p>
                <Tag :value="formatStatus(order.status)" :severity="getStatusSeverity(order.status)" />
              </div>
              <div>
                <p class="text-sm text-gray-600">Payment</p>
                <Tag :value="formatPaymentStatus(order.payment_status)" :severity="getPaymentSeverity(order.payment_status)" />
              </div>
              <router-link :to="{name: 'CustomerOrderDetails', params: {id: order.id}}" class="text-[#00685F] hover:text-[#004F45] font-medium transition">
                View Details →
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- PrimeVue Paginator v4 -->
      <div v-if="orders.length > 0" class="mt-8">
        <Paginator 
          v-model:first="first"
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :pageLinkSize="5"
          @page="onPageChange"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          class="bg-white rounded-lg shadow-sm p-4"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/api'
import Paginator from 'primevue/paginator'
import Tag from 'primevue/tag'

interface Order {
  id: number
  order_number: string
  status: string
  payment_status: string
  grand_total: number
  subtotal: number
  shipping_total: number
  tax_total: number
  created_at: string
  items: any[]
}

const loading = ref(false)
const error = ref('')
const orders = ref<Order[]>([])
const first = ref(0)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 5,
  total: 0
})

const fetchOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/orders', { 
      params: { 
        page: pagination.value.current_page,
        per_page: pagination.value.per_page 
      } 
    })
    
    const data = response.data
    
    if (data && data.data) {
      orders.value = data.data
      
      // Update pagination from API response
      pagination.value = {
        current_page: data.meta?.current_page || data.current_page || 1,
        last_page: data.meta?.last_page || data.last_page || 1,
        per_page: data.meta?.per_page || data.per_page || pagination.value.per_page,
        total: data.meta?.total || data.total || 0
      }
      
      // IMPORTANT: Update first value for Paginator
      first.value = (pagination.value.current_page - 1) * pagination.value.per_page
    } else {
      orders.value = []
    }
  } catch (err: any) {
    console.error('Error:', err)
    error.value = err.response?.data?.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: any) => {
  
  // Calculate the page number from the event
  const page = Math.floor(event.first / event.rows) + 1
  
  if (page !== pagination.value.current_page) {
    pagination.value.current_page = page
    // The first value will be updated when fetchOrders completes
    fetchOrders()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { 
    style: 'currency', 
    currency: 'USD' 
  }).format(Number(price) || 0)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const formatStatus = (status: string): string => {
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
  return statusMap[status] || status
}

const formatPaymentStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    paid: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded',
    partially_refunded: 'Partially Refunded'
  }
  return statusMap[status] || status
}

const getStatusSeverity = (status: string): string => {
  const classes: Record<string, string> = {
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
  return classes[status] || 'info'
}

const getPaymentSeverity = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    failed: 'danger',
    refunded: 'secondary',
    partially_refunded: 'warning'
  }
  return classes[status] || 'info'
}

onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
/* PrimeVue Paginator v4 styles */
:deep(.p-paginator) {
  border: none !important;
  background: transparent !important;
  padding: 0.5rem !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: #00685F !important;
  color: white !important;
  border-color: #00685F !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:not(.p-highlight):hover) {
  background: #f3f4f6 !important;
}

:deep(.p-paginator .p-paginator-element) {
  color: #4B5563 !important;
  border-radius: 0.5rem !important;
  min-width: 2.5rem !important;
  height: 2.5rem !important;
}

:deep(.p-paginator .p-paginator-element.p-highlight) {
  background: #00685F !important;
  color: white !important;
}

:deep(.p-paginator .p-paginator-first),
:deep(.p-paginator .p-paginator-prev),
:deep(.p-paginator .p-paginator-next),
:deep(.p-paginator .p-paginator-last) {
  color: #4B5563 !important;
  border-radius: 0.5rem !important;
  min-width: 2.5rem !important;
  height: 2.5rem !important;
}

:deep(.p-paginator .p-paginator-first:not(.p-disabled):hover),
:deep(.p-paginator .p-paginator-prev:not(.p-disabled):hover),
:deep(.p-paginator .p-paginator-next:not(.p-disabled):hover),
:deep(.p-paginator .p-paginator-last:not(.p-disabled):hover) {
  background: #f3f4f6 !important;
}

:deep(.p-paginator .p-paginator-first.p-disabled),
:deep(.p-paginator .p-paginator-prev.p-disabled),
:deep(.p-paginator .p-paginator-next.p-disabled),
:deep(.p-paginator .p-paginator-last.p-disabled) {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
}
</style>