 <template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ userName }} hello!</h1>
      <p class="text-gray-600">Here's a summary of your account activity</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Orders</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.totalOrders }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Spent</p>
        <p class="text-2xl font-bold text-[#00685F]">{{ formatPrice(stats.totalSpent) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Wishlist Items</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.wishlistItems }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Recent Orders</h3>
        <div v-for="order in recentOrders" :key="order.id" class="flex justify-between py-2 border-b border-gray-100">
          <span class="text-sm">{{ order.order_number }}</span>
          <span class="text-sm font-medium">{{ formatPrice(order.grand_total) }}</span>
        </div>
        <router-link to="/dashboard/customer/orders" class="text-sm text-[#00685F] hover:text-[#004F45] mt-4 inline-block">View All →</router-link>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="space-y-2">
          <Button label="Shop Now" icon="pi pi-shopping-bag" class="w-full p-button-outlined" @click="router.push('/shop')" />
          <Button label="View Wishlist" icon="pi pi-heart" class="w-full p-button-outlined" @click="router.push('/dashboard/customer/wishlist')" />
          <Button label="My Profile" icon="pi pi-user" class="w-full p-button-outlined" @click="router.push('/dashboard/customer/profile')" />
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
const userName = computed(() => authStore.user?.name || 'Customer')

const stats = ref({ totalOrders: 12, totalSpent: 450.75, wishlistItems: 5 })

interface RecentOrder {
  id: number
  order_number: string
  grand_total: number
}

const recentOrders = ref<RecentOrder[]>([])

const formatPrice = (price: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)

onMounted(() => {
  recentOrders.value = [
    { id: 1, order_number: 'ORD-001', grand_total: 150.00 },
    { id: 2, order_number: 'ORD-002', grand_total: 89.99 }
  ]
})
</script> -->

