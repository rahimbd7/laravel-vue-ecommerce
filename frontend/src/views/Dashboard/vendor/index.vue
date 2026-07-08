<template>
  <div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ userName }}!</h1>
      <p class="text-gray-600">Here's your store performance summary</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Earnings</p>
            <p class="text-2xl font-bold text-[#00685F]">{{ formatPrice(stats?.total_earnings || 0) }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-lg">
            <i class="pi pi-dollar text-green-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Orders</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats?.total_orders || 0 }}</p>
          </div>
          <div class="p-3 bg-blue-100 rounded-lg">
            <i class="pi pi-shopping-cart text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Products</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats?.total_products || 0 }}</p>
          </div>
          <div class="p-3 bg-purple-100 rounded-lg">
            <i class="pi pi-box text-purple-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Average Rating</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats?.average_rating || 0 }} ★</p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-lg">
            <i class="pi pi-star text-yellow-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-600">Pending Orders</p>
        <p class="text-xl font-semibold text-yellow-600">{{ stats?.pending_orders || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <p class="text-sm text-gray-600">Processing Orders</p>
        <p class="text-xl font-semibold text-blue-600">{{ stats?.processing_orders || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <p class="text-sm text-gray-600">Shipped Orders</p>
        <p class="text-xl font-semibold text-purple-600">{{ stats?.shipped_orders || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-600">Out of Stock</p>
        <p class="text-xl font-semibold text-red-600">{{ stats?.out_of_stock || 0 }}</p>
      </div>
    </div>

    <!-- Today Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <p class="text-sm text-gray-600">Today's Orders</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats?.today_orders || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <p class="text-sm text-gray-600">Today's Revenue</p>
        <p class="text-2xl font-bold text-[#00685F]">{{ formatPrice(stats?.today_revenue || 0) }}</p>
      </div>
    </div>

    <!-- Top Products & Recent Orders -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Top Products</h3>
        <div v-if="topProducts.length === 0" class="text-center py-4 text-gray-500">
          No products sold yet
        </div>
        <div v-for="product in topProducts" :key="product.id" class="flex justify-between py-2 border-b border-gray-100">
          <span class="text-sm text-gray-600">{{ product.name }}</span>
          <span class="text-sm font-medium">{{ product.total_sold }} sold</span>
          <span class="text-sm text-[#00685F]">{{ formatPrice(product.total_revenue) }}</span>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-gray-900">Recent Orders</h3>
          <router-link to="/dashboard/vendor/orders" class="text-sm text-[#00685F] hover:text-[#004F45]">
            View All →
          </router-link>
        </div>
        <div v-if="recentOrders.length === 0" class="text-center py-4 text-gray-500">
          No recent orders
        </div>
        <div v-for="order in recentOrders" :key="order.id" class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-sm text-gray-600">#{{ order.order_number }}</span>
          <span class="text-sm font-medium">{{ formatPrice(order.grand_total) }}</span>
          <Tag :value="order.status" :severity="getStatusSeverity(order.status)" size="small" />
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Button 
          label="Add Product" 
          icon="pi pi-plus" 
          class="w-full justify-center" 
          outlined
          @click="router.push('/dashboard/vendor/products/create')" 
        />
        <Button 
          label="View Orders" 
          icon="pi pi-shopping-cart" 
          class="w-full justify-center" 
          outlined
          @click="router.push('/dashboard/vendor/orders')" 
        />
        <Button 
          label="Manage Products" 
          icon="pi pi-box" 
          class="w-full justify-center" 
          outlined
          @click="router.push('/dashboard/vendor/products')" 
        />
        <Button 
          label="Shipping Settings" 
          icon="pi pi-truck" 
          class="w-full justify-center" 
          outlined
          @click="router.push('/dashboard/vendor/shipping')" 
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading.dashboard || loading.stats" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const router = useRouter()
const authStore = useAuthStore()
const dashboardStore = useVendorDashboardStore()

const userName = computed(() => authStore.userName)
const stats = computed(() => dashboardStore.stats)
const loading = computed(() => dashboardStore.loading)
const recentOrders = computed(() => dashboardStore.recentOrders)
const topProducts = computed(() => dashboardStore.topProducts)

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { 
    style: 'currency', 
    currency: 'USD' 
  }).format(price || 0)
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

onMounted(async () => {
  if (!dashboardStore.isInitialized) {
    await dashboardStore.initialize()
  } else {
    await Promise.all([
      dashboardStore.fetchDashboard(true),
      dashboardStore.fetchStats(true),
      dashboardStore.fetchRecentOrders(5),
    ])
  }
})
</script>