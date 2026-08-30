<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Admin Dashboard</h1>
      <p class="text-sm sm:text-base text-gray-600">Overview of your platform performance</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading.dashboard" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Dashboard Content -->
    <template v-else>
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatsCard
          title="Total Revenue"
          :value="formatPrice(revenue?.total || 0)"
          icon="pi pi-dollar"
          color="green"
          :trend="revenue?.growth"
        />
        <StatsCard
          title="Total Orders"
          :value="orders?.total || 0"
          icon="pi pi-shopping-cart"
          color="blue"
          :subtext="`${orders?.by_status?.pending || 0} pending`"
        />
        <StatsCard
          title="Total Users"
          :value="users?.total || 0"
          icon="pi pi-users"
          color="purple"
          :subtext="`${users?.active || 0} active`"
        />
        <StatsCard
          title="Total Vendors"
          :value="vendors?.total || 0"
          icon="pi pi-store"
          color="orange"
          :subtext="`${vendors?.pending || 0} pending approval`"
        />
      </div>

      <!-- Revenue & Order Chart -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">Revenue Trend</h3>
          <RevenueChart :data="revenueTrendData" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">Order Status Distribution</h3>
          <OrderStatusChart :data="orders?.by_status || {}" />
        </div>
      </div>

      <!-- Commission & User Growth -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">Commission Trends</h3>
          <CommissionChart :data="commissionTrendData" />
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">User Growth</h3>
          <UserGrowthChart :data="userGrowthData" />
        </div>
      </div>

      <!-- Recent Orders & Pending Vendors -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Recent Orders</h3>
            <router-link to="/dashboard/admin/orders" class="text-xs sm:text-sm text-[#00685F] hover:text-[#004F45]">
              View All →
            </router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50">
                  <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium text-gray-900">
                    {{ order.order_number }}
                  </td>
                  <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-600">
                    {{ order.customer_name }}
                  </td>
                  <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium text-[#00685F]">
                    {{ formatPrice(order.grand_total) }}
                  </td>
                  <td class="px-3 sm:px-4 py-2 sm:py-3">
                    <Tag :value="order.status" :severity="getStatusSeverity(order.status)" size="small" />
                  </td>
                </tr>
                <tr v-if="recentOrders.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-sm">No recent orders</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Pending Vendor Approvals</h3>
            <router-link to="/dashboard/admin/vendors" class="text-xs sm:text-sm text-[#00685F] hover:text-[#004F45]">
              View All →
            </router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                  <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="vendor in pendingVendors" :key="vendor.id" class="hover:bg-gray-50">
                  <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium text-gray-900">
                    {{ vendor.business_name }}
                  </td>
                  <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-600">
                    {{ vendor.owner_name }}
                  </td>
                  <td class="px-3 sm:px-4 py-2 sm:py-3">
                    <div class="flex gap-2">
                      <Button icon="pi pi-check" size="small" severity="success" text rounded @click="approveVendor(vendor.id)" />
                      <Button icon="pi pi-times" size="small" severity="danger" text rounded @click="rejectVendor(vendor.id)" />
                    </div>
                  </td>
                </tr>
                <tr v-if="pendingVendors.length === 0">
                  <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-sm">No pending approvals</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Recent Activities</h3>
        </div>
        <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
          <div v-for="activity in activities" :key="activity.id" class="px-4 sm:px-6 py-3 sm:py-4 flex items-start gap-3 hover:bg-gray-50">
            <div class="flex-shrink-0 mt-1">
              <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="getActivityColorClass(activity.color)">
                <i :class="activity.icon" class="text-sm text-white"></i>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900">
                {{ activity.description }}
                <span class="text-xs text-gray-400 ml-2">{{ activity.time_ago }}</span>
              </p>
              <p class="text-xs text-gray-500">{{ activity.user_name }} · {{ activity.user_role }}</p>
            </div>
          </div>
          <div v-if="activities.length === 0" class="px-4 py-8 text-center text-gray-500 text-sm">
            No recent activities
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import api from '@/api/api'
import { useAdminDashboardStore } from '@/stores/DashboardStore/Admin/admin.dashboard.store'
import { useAuthStore } from '@/stores/auth.store'
import StatsCard from '../../../components/StatsCard.vue'
import RevenueChart from '../../../components/RevenueChart.vue'
import OrderStatusChart from '../../../components/OrderStatusChart.vue'
import CommissionChart from '../../../components/CommissionChart.vue'
import UserGrowthChart from '../../../components/UserGrowthChart.vue'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const dashboardStore = useAdminDashboardStore()

// Computed data from store
const revenue = computed(() => dashboardStore.revenue)
const users = computed(() => dashboardStore.users)
const vendors = computed(() => dashboardStore.vendors)
const orders = computed(() => dashboardStore.orders)
const commission = computed(() => dashboardStore.commission)
const activities = computed(() => dashboardStore.activities)
const loading = computed(() => dashboardStore.loading)
const recentOrders = computed(() => dashboardStore.orders?.recent || [])
const pendingVendors = computed(() => dashboardStore.vendors?.pending_list || [])

const revenueTrendData = computed(() => {
  if (Array.isArray(revenue.value?.trend)) {
    return revenue.value.trend
  }
  return []
})

const commissionTrendData = computed(() => {
  if (Array.isArray(commission.value?.trend)) {
    return commission.value.trend
  }
  return []
})

const userGrowthData = computed(() => {
  if (Array.isArray(users.value?.trend)) {
    return users.value.trend
  }
  return []
})

// Format price
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price || 0)
}

// Get status severity
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

// Get activity color class
const getActivityColorClass = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    purple: 'bg-purple-500',
    indigo: 'bg-indigo-500',
    cyan: 'bg-cyan-500',
    teal: 'bg-teal-500',
    yellow: 'bg-yellow-500',
    gray: 'bg-gray-500',
  }
  return map[color] || 'bg-gray-500'
}

// Approve vendor
const approveVendor = async (vendorId: number) => {
  confirm.require({
    message: 'Are you sure you want to approve this vendor?',
    header: 'Approve Vendor',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await api.post(`/admin/vendor/${vendorId}/approve`)
        toast.add({
          severity: 'success',
          summary: 'Approved',
          detail: 'Vendor approved successfully',
          life: 3000
        })
        await dashboardStore.fetchVendors(true)
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to approve vendor',
          life: 3000
        })
      }
    }
  })
}

// Reject vendor
const rejectVendor = async (vendorId: number) => {
  confirm.require({
    message: 'Are you sure you want to reject this vendor?',
    header: 'Reject Vendor',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await api.post(`/admin/vendor/${vendorId}/reject`)
        toast.add({
          severity: 'success',
          summary: 'Rejected',
          detail: 'Vendor rejected successfully',
          life: 3000
        })
        await dashboardStore.fetchVendors(true)
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to reject vendor',
          life: 3000
        })
      }
    }
  })
}

let refreshInterval: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  if (authStore.isAuthenticated && authStore.user?.role === 'admin') {
    await dashboardStore.initialize()
    
    refreshInterval = setInterval(() => {
      dashboardStore.refreshAll()
    }, 300000)
  }
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
})
</script>

<style scoped>
/* Table scroll fix for mobile */
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}

/* Activity feed scroll */
.max-h-80 {
  max-height: 20rem;
}
</style>