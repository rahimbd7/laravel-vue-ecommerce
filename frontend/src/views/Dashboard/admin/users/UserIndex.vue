<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User Analytics</h1>
          <p class="text-sm sm:text-base text-gray-600">Comprehensive overview of platform users</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            size="small"
            :loading="loading.users"
            @click="handleRefresh"
            outlined
          />
          <Button 
            icon="pi pi-download" 
            label="Export" 
            size="small"
            @click="handleExport"
            severity="success"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading.users" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Dashboard Content -->
    <template v-else>
      <!-- User Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <UserStatsCard
          title="Total Users"
          :value="totalUsers"
          icon="pi pi-users"
          color="purple"
          :trend="userGrowth"
          :subtext="`+${newUsersToday} today`"
        />
        <UserStatsCard
          title="Active Users"
          :value="activeUsers"
          icon="pi pi-user-check"
          color="green"
          :subtext="`${activePercentage}% of total`"
        />
        <UserStatsCard
          title="New Users (This Week)"
          :value="users?.week || 0"
          icon="pi pi-user-plus"
          color="blue"
          :subtext="`${users?.today || 0} today`"
        />
        <UserStatsCard
          title="Inactive Users"
          :value="users?.inactive || 0"
          icon="pi pi-user-minus"
          color="red"
          :subtext="`${inactivePercentage}% of total`"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- User Growth Chart -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <h3 class="font-semibold text-gray-900 text-sm sm:text-base">User Growth Trend</h3>
            <div class="flex gap-2 mt-2 sm:mt-0">
              <Button 
                v-for="range in dateRanges" 
                :key="range.value"
                :label="range.label" 
                size="small"
                :severity="selectedRange === range.value ? 'primary' : 'secondary'"
                @click="selectedRange = range.value"
                class="text-xs"
              />
            </div>
          </div>
          <UserGrowthChart :data="userGrowthData" :range="selectedRange" />
        </div>

        <!-- User Role Distribution -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">User Role Distribution</h3>
          <UserRoleChart :data="users?.by_role || {}" />
        </div>
      </div>

      <!-- Second Row of Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- User Role Breakdown (Horizontal Bars) -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">User Role Breakdown</h3>
          <UserRoleBreakdown :data="users?.by_role || {}" :total="totalUsers" />
        </div>

        <!-- User Activity Patterns -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
          <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">User Activity Overview</h3>
          <UserActivitySummary 
            :total="totalUsers"
            :active="activeUsers"
            :inactive="users?.inactive || 0"
            :new-users="users?.today || 0"
          />
        </div>
      </div>

      <!-- User Insights -->
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm sm:text-base">User Insights</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Customer/Vendor Ratio</span>
              <i class="pi pi-chart-pie text-purple-500"></i>
            </div>
            <p class="text-lg font-bold text-gray-900 mt-2">
              {{ customerVendorRatio }}
            </p>
            <p class="text-xs text-gray-500">Customers : Vendors</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active Rate</span>
              <i class="pi pi-chart-line text-green-500"></i>
            </div>
            <p class="text-lg font-bold text-gray-900 mt-2">
              {{ activePercentage }}%
            </p>
            <p class="text-xs text-gray-500">{{ activeUsers }} active users</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Growth Rate</span>
              <i class="pi pi-arrow-up text-blue-500"></i>
            </div>
            <p class="text-lg font-bold text-gray-900 mt-2">
              {{ userGrowth > 0 ? '+' : '' }}{{ userGrowth.toFixed(1) }}%
            </p>
            <p class="text-xs text-gray-500">vs last week</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Toast Notifications -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useAdminDashboardStore } from '@/stores/DashboardStore/Admin/admin.dashboard.store'
import UserStatsCard from '@/components/admin/users/UserStatsCard.vue'
import UserGrowthChart from '@/components/admin/users/UserGrowthChart.vue'
import UserRoleChart from '@/components/admin/users/UserRoleChart.vue'
import UserRoleBreakdown from '@/components/admin/users/UserRoleBreakdown.vue'
import UserActivitySummary from '@/components/admin/users/UserActivitySummary.vue'

const toast = useToast()
const dashboardStore = useAdminDashboardStore()

// State
const selectedRange = ref('30')

// Computed
const users = computed(() => dashboardStore.users)
const loading = computed(() => dashboardStore.loading)
const totalUsers = computed(() => dashboardStore.totalUsers)
const activeUsers = computed(() => dashboardStore.activeUsers)

// Calculate growth based on available data (today vs week)
const userGrowth = computed(() => {
  const current = dashboardStore.users?.today || 0
  const previous = dashboardStore.users?.week || 0
  if (previous === 0) return 0
  return ((current - previous) / previous) * 100
})

const newUsersToday = computed(() => dashboardStore.users?.today || 0)

const activePercentage = computed(() => {
  const total = dashboardStore.totalUsers
  const active = dashboardStore.activeUsers
  if (total === 0) return 0
  return ((active / total) * 100).toFixed(1)
})

const inactivePercentage = computed(() => {
  const total = dashboardStore.totalUsers
  const inactive = dashboardStore.users?.inactive || 0
  if (total === 0) return 0
  return ((inactive / total) * 100).toFixed(1)
})

const customerVendorRatio = computed(() => {
  const customers = dashboardStore.users?.by_role?.customer || 0
  const vendors = dashboardStore.users?.by_role?.vendor || 0
  if (vendors === 0) return `${customers}:0`
  return `${customers}:${vendors}`
})

const userGrowthData = computed(() => {
  return dashboardStore.users?.trend || []
})

const dateRanges = [
  { label: '7 Days', value: '7' },
  { label: '30 Days', value: '30' },
  { label: '90 Days', value: '90' },
]

// Methods
const handleRefresh = async () => {
  try {
    await dashboardStore.fetchUsers(true)
    toast.add({
      severity: 'success',
      summary: 'Refreshed',
      detail: 'User data has been updated',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to refresh user data',
      life: 3000
    })
  }
}

const handleExport = () => {
  toast.add({
    severity: 'info',
    summary: 'Exporting',
    detail: 'User data export started',
    life: 3000
  })
}

// Lifecycle
onMounted(async () => {
  await dashboardStore.initialize()
})
</script>

<style scoped>
/* Any custom styles */
</style>