<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ userName }}!</h1>
      <p class="text-gray-600">Here's what's happening with your store today.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Revenue</p>
        <p class="text-2xl font-bold text-[#00685F]">{{ formatPrice(stats.revenue) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Orders</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.orders }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Customers</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.customers }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-600">Total Products</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.products }}</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="font-semibold text-gray-900">Recent Orders</h3>
        <router-link to="/dashboard/admin/orders" class="text-sm text-[#00685F] hover:text-[#004F45]">View All →</router-link>
      </div>
      <DataTable :value="recentOrders" class="p-datatable-sm">
        <Column field="order_number" header="Order #" />
        <Column field="customer_name" header="Customer" />
        <Column field="grand_total" header="Total">
          <template #body="slotProps">{{ formatPrice(slotProps.data.grand_total) }}</template>
        </Column>
        <Column field="status" header="Status">
          <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getSeverity(slotProps.data.status)" />
          </template>
        </Column>
        <Column header="Action">
          <template #body="slotProps">
            <Button icon="pi pi-eye" text rounded @click="viewOrder(slotProps.data.id)" />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const router = useRouter()
const authStore = useAuthStore()

const userName = computed(() => authStore.user?.name || 'Admin')

const stats = ref({ revenue: 0, orders: 0, customers: 0, products: 0 })

interface RecentOrder {
  id: number
  order_number: string
  customer_name: string
  grand_total: number
  status: string
}

const recentOrders = ref<RecentOrder[]>([])

const fetchData = async () => {
  // Simulate API call - replace with actual API
  stats.value = { revenue: 25000, orders: 150, customers: 300, products: 45 }
  recentOrders.value = [
    { id: 1, order_number: 'ORD-001', customer_name: 'John Doe', grand_total: 150.00, status: 'paid' },
    { id: 2, order_number: 'ORD-002', customer_name: 'Jane Smith', grand_total: 89.99, status: 'pending' },
    { id: 3, order_number: 'ORD-003', customer_name: 'Bob Johnson', grand_total: 210.50, status: 'shipped' }
  ]
}

const viewOrder = (id: number) => router.push(`/order/${id}`)
const formatPrice = (price: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
const getSeverity = (status: string) => {
  const map: Record<string, string> = { 
    paid: 'success', 
    pending: 'warning', 
    cancelled: 'danger',
    shipped: 'info'
  }
  return map[status] || 'info'
}

onMounted(fetchData)
</script>