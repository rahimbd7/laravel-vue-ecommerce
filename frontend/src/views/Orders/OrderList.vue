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
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Orders Yet</h3>
        <p class="text-gray-600 mb-6">Start shopping to place your first order</p>
        <router-link to="/shop" class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">Start Shopping</router-link>
      </div>
    </div>

    <!-- Orders List -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="space-y-4">
        <div v-for="order in orders" :key="order.id" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
          <!-- Order Header -->
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
                <span :class="getStatusClass(order.status)" class="inline-flex px-3 py-1 rounded-full text-sm font-medium">
                  {{ formatStatus(order.status) }}
                </span>
              </div>
              <div>
                <p class="text-sm text-gray-600">Payment</p>
                <span :class="getPaymentStatusClass(order.payment_status)" class="inline-flex px-3 py-1 rounded-full text-sm font-medium">
                  {{ formatPaymentStatus(order.payment_status) }}
                </span>
              </div>
              <router-link :to="`/order/${order.id}`" class="text-[#00685F] hover:text-[#004F45] font-medium transition">
                View Details
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
        <div class="flex items-center gap-2">
          <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">Previous</button>
          <span class="px-4 py-2 text-gray-700">Page {{ currentPage }} of {{ pagination.last_page }}</span>
          <button @click="goToPage(currentPage + 1)" :disabled="currentPage === pagination.last_page" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/api'

interface OrderItem {
  id: number
  product_name: string
  product_variation_name: string | null
  quantity: number
  unit_price: number
  total: number
}

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
  items: OrderItem[]
}

const loading = ref(false)
const error = ref('')
const orders = ref<Order[]>([])
const currentPage = ref(1)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const fetchOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/orders', { params: { page: currentPage.value } })
    const data = response.data.data
    
    if (Array.isArray(data)) {
      orders.value = data
    } else if (data && data.data) {
      orders.value = data.data
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        per_page: data.per_page || 15,
        total: data.total || 0
      }
    } else {
      orders.value = []
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

const goToPage = (page: number) => {
  if (page < 1 || page > pagination.value.last_page) return
  currentPage.value = page
  fetchOrders()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(price) || 0)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
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

const getStatusClass = (status: string): string => {
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
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPaymentStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
    partially_refunded: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchOrders()
})
</script>
