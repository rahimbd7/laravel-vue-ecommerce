<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
      <h4 class="font-semibold text-gray-900">Order History</h4>
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText 
            v-model="searchQuery" 
            placeholder="Search orders..." 
            class="pl-9 w-full sm:w-40 text-sm"
            size="small"
          />
        </div>
        <Dropdown 
          v-model="statusFilter" 
          :options="statusOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="Status"
          class="w-full sm:w-32"
          size="small"
        />
        <Button 
          icon="pi pi-refresh" 
          size="small"
          text
          rounded
          :loading="loading"
          @click="fetchOrders"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Orders List -->
    <template v-else>
      <div v-if="filteredOrders.length === 0" class="text-center py-8">
        <i class="pi pi-shopping-cart text-4xl text-gray-300"></i>
        <p class="text-gray-500 mt-2">No orders found</p>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="order in filteredOrders" 
          :key="order.id"
          class="border border-gray-100 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-[#00685F]/10 flex items-center justify-center flex-shrink-0">
                <i class="pi pi-shopping-bag text-[#00685F]"></i>
              </div>
              <div>
                <p class="font-medium text-gray-900 text-sm">
                  Order #{{ order.order_number }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ formatDate(order.created_at) }}
                </p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <Tag 
                :value="order.status" 
                :severity="getStatusSeverity(order.status)" 
                size="small"
              />
              <Tag 
                :value="order.payment_status" 
                :severity="getPaymentSeverity(order.payment_status)" 
                size="small"
              />
            </div>
          </div>

          <Divider class="my-2" />

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex flex-wrap gap-3 text-sm">
              <div>
                <span class="text-gray-500">Items:</span>
                <span class="font-medium text-gray-900 ml-1">{{ order.items_count || 0 }}</span>
              </div>
              <div>
                <span class="text-gray-500">Vendor:</span>
                <span class="font-medium text-gray-900 ml-1">{{ order.vendor_name || 'N/A' }}</span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-lg font-bold text-[#00685F]">
                ${{ order.grand_total?.toFixed(2) || '0.00' }}
              </span>
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewOrder(order.id)"
                tooltip="View Order Details"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center mt-4">
        <Paginator 
          :rows="perPage" 
          :totalRecords="totalOrders" 
          :first="(currentPage - 1) * perPage"
          @page="onPageChange"
          template="PrevPageLink PageLinks NextPageLink"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Paginator from 'primevue/paginator'
import Button from 'primevue/button'
import api from '@/api/api'

const props = defineProps<{
  userId: string
}>()

const router = useRouter()
const toast = useToast()

// State
const orders = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const perPage = ref(5)
const totalOrders = ref(0)

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Refunded', value: 'refunded' },
]

const totalPages = computed(() => Math.ceil(totalOrders.value / perPage.value))

const filteredOrders = computed(() => {
  let filtered = orders.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(order => 
      order.order_number?.toLowerCase().includes(query) ||
      order.vendor_name?.toLowerCase().includes(query)
    )
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(order => order.status === statusFilter.value)
  }
  
  return filtered
})

// Methods
const fetchOrders = async () => {
  loading.value = true
  try {
    const response = await api.get(`/admin/users/${props.userId}/orders`, {
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        search: searchQuery.value,
        status: statusFilter.value,
      }
    })
    orders.value = response.data.data || []
    totalOrders.value = response.data.meta?.total || 0
  } catch (error) {
    console.error('Failed to fetch user orders:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load order history',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const viewOrder = (orderId: number) => {
  router.push(`/dashboard/admin/orders/${orderId}`)
}

const onPageChange = (event: any) => {
  currentPage.value = Math.floor(event.first / event.rows) + 1
  fetchOrders()
}

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
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
    failed: 'danger',
  }
  return map[status] || 'info'
}

const getPaymentSeverity = (status: string): string => {
  const map: Record<string, string> = {
    paid: 'success',
    pending: 'warning',
    failed: 'danger',
    refunded: 'secondary',
  }
  return map[status] || 'info'
}

// Watch for filter changes
watch([searchQuery, statusFilter], () => {
  currentPage.value = 1
  fetchOrders()
})

// Lifecycle
onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
/* Mobile optimizations */
@media (max-width: 640px) {
  :deep(.p-paginator) {
    padding: 0.5rem;
  }
}
</style>
