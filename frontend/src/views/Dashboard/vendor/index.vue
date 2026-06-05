<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ userName }}!</h1>
      <p class="text-gray-600">Here's your store performance summary</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Earnings</p>
        <p class="text-2xl font-bold text-[#00685F]">{{ formatPrice(stats.earnings) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Orders</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.orders }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Products</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.products }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Avg Rating</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.avgRating }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Recent Orders</h3>
        <div v-for="order in recentOrders" :key="order.id" class="flex justify-between py-2 border-b border-gray-100">
          <span class="text-sm">{{ order.order_number }}</span>
          <span class="text-sm font-medium">{{ formatPrice(order.grand_total) }}</span>
        </div>
        <router-link to="/dashboard/vendor/orders" class="text-sm text-[#00685F] hover:text-[#004F45] mt-4 inline-block">View All →</router-link>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="space-y-2">
          <Button label="Add New Product" icon="pi pi-plus" class="w-full p-button-outlined" @click="router.push('/dashboard/vendor/products/create')" />
          <Button label="View Orders" icon="pi pi-shopping-cart" class="w-full p-button-outlined" @click="router.push('/dashboard/vendor/orders')" />
          <Button label="Manage Inventory" icon="pi pi-box" class="w-full p-button-outlined" @click="router.push('/dashboard/vendor/products')" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import Button from 'primevue/button'

const router = useRouter()
const authStore = useAuthStore()
const userName = computed(() => authStore.user?.name || 'Vendor')

const stats = ref({ earnings: 0, orders: 0, products: 0, avgRating: '4.8' })
const recentOrders = ref([])

const formatPrice = (price: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)

onMounted(() => {
  stats.value = { earnings: 5400, orders: 45, products: 25, avgRating: '4.8' }
  recentOrders.value = [
    { id: 1, order_number: 'ORD-001', grand_total: 150.00 },
    { id: 2, order_number: 'ORD-002', grand_total: 89.99 }
  ]
})
</script>