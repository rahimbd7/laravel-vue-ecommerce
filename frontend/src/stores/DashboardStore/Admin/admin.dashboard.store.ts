import { defineStore } from 'pinia'
import api from '@/api/api'
import { useAuthStore } from '@/stores/auth.store'

// ===================== INTERFACES =====================

interface RevenueStats {
  total: number
  today: number
  week: number
  month: number
  previous_month: number
  growth: number
  trend: Array<{ date: string; revenue: number }>
}

interface UserStats {
  total: number
  today: number
  week: number
  active: number
  inactive: number
  by_role: {
    customer: number
    vendor: number
    admin: number
  }
  trend: Array<{ date: string; new_users: number }>
}

interface VendorStats {
  total: number
  pending: number
  verified: number
  rejected: number
  suspended: number
  by_status: {
    active: number
    pending: number
    suspended: number
    rejected: number
  }
  pending_list: Array<{
    id: number
    business_name: string
    business_email: string
    owner_name: string
    owner_email: string
    created_at: string
  }>
  trend: Array<{ date: string; new_vendors: number }>
}

interface OrderStats {
  total: number
  today: number
  week: number
  month: number
  by_status: {
    pending: number
    processing: number
    confirmed: number
    shipped: number
    delivered: number
    completed: number
    cancelled: number
    refunded: number
  }
  avg_order_value: number
  fulfillment_rate: number
  recent: Array<{
    id: number
    order_number: string
    customer_name: string
    vendor_name: string
    grand_total: number
    status: string
    payment_status: string
    created_at: string
  }>
  trend: Array<{ date: string; orders: number }>
}

interface CommissionStats {
  total: number
  pending: number
  paid: number
  month: number
  previous_month: number
  growth: number
  by_vendor: Array<{
    vendor_name: string
    commission: number
  }>
  trend: Array<{ date: string; commission: number }>
}

interface Activity {
  id: number
  type: string
  user_id: number | null
  user_name: string
  user_role: string
  reference_type: string | null
  reference_id: number | null
  amount: number | null
  status: string | null
  description: string
  icon: string
  color: string
  created_at: string
  time_ago: string
}

interface PendingVendor {
  id: number
  business_name: string
  business_email: string
  owner_name: string
  owner_email: string
  created_at: string
}

interface TopVendor {
  id: number
  business_name: string
  total_orders: number
  total_sales: number
  total_products: number
}

interface PayoutSummary {
  total_pending: number
  total_processing: number
  total_paid: number
  total_commission: number
  total_platform_fee: number
  pending_count: number
  processing_count: number
  paid_count: number
}

interface PendingPayout {
  id: number
  vendor_id: number
  order_id: number
  amount: number
  commission: number
  platform_fee: number
  net_amount: number
  status: string
  created_at: string
  vendor: {
    business_name: string
  }
  order: {
    order_number: string
  }
}

// ===================== STORE =====================

export const useAdminDashboardStore = defineStore('adminDashboard', {
  state: () => ({
    // Dashboard Data
    revenue: null as RevenueStats | null,
    users: null as UserStats | null,
    vendors: null as VendorStats | null,
    orders: null as OrderStats | null,
    commission: null as CommissionStats | null,
    activities: [] as Activity[],
    topVendors: [] as TopVendor[],
    pendingVendors: [] as PendingVendor[],
    pendingPayouts: [] as PendingPayout[],
    payoutSummary: null as PayoutSummary | null,

    // Loading States
    loading: {
      dashboard: false,
      revenue: false,
      users: false,
      vendors: false,
      orders: false,
      commission: false,
      activities: false,
      topVendors: false,
      pendingVendors: false,
      pendingPayouts: false,
      payoutSummary: false,
    },

    // Errors
    error: {
      dashboard: null as string | null,
      revenue: null as string | null,
      users: null as string | null,
      vendors: null as string | null,
      orders: null as string | null,
      commission: null as string | null,
      activities: null as string | null,
      topVendors: null as string | null,
      pendingVendors: null as string | null,
      pendingPayouts: null as string | null,
      payoutSummary: null as string | null,
    },

    // Initialization
    isInitialized: false,
    lastFetched: {
      dashboard: null as number | null,
    },
  }),

  getters: {
    // Revenue
    totalRevenue: (state) => state.revenue?.total || 0,
    todayRevenue: (state) => state.revenue?.today || 0,
    weekRevenue: (state) => state.revenue?.week || 0,
    monthRevenue: (state) => state.revenue?.month || 0,
    revenueGrowth: (state) => state.revenue?.growth || 0,

    // Users
    totalUsers: (state) => state.users?.total || 0,
    todayUsers: (state) => state.users?.today || 0,
    weekUsers: (state) => state.users?.week || 0,
    activeUsers: (state) => state.users?.active || 0,
    inactiveUsers: (state) => state.users?.inactive || 0,
    customerCount: (state) => state.users?.by_role?.customer || 0,
    vendorCount: (state) => state.users?.by_role?.vendor || 0,
    adminCount: (state) => state.users?.by_role?.admin || 0,

    // Vendors
    totalVendors: (state) => state.vendors?.total || 0,
    pendingVendorsCount: (state) => state.vendors?.pending || 0,
    verifiedVendors: (state) => state.vendors?.verified || 0,
    rejectedVendors: (state) => state.vendors?.rejected || 0,
    suspendedVendors: (state) => state.vendors?.suspended || 0,

    // Orders
    totalOrders: (state) => state.orders?.total || 0,
    todayOrders: (state) => state.orders?.today || 0,
    weekOrders: (state) => state.orders?.week || 0,
    monthOrders: (state) => state.orders?.month || 0,
    pendingOrders: (state) => state.orders?.by_status?.pending || 0,
    processingOrders: (state) => state.orders?.by_status?.processing || 0,
    completedOrders: (state) => state.orders?.by_status?.completed || 0,
    cancelledOrders: (state) => state.orders?.by_status?.cancelled || 0,
    fulfillmentRate: (state) => state.orders?.fulfillment_rate || 0,
    avgOrderValue: (state) => state.orders?.avg_order_value || 0,
    recentOrders: (state) => state.orders?.recent || [],

    // Commission
    totalCommission: (state) => state.commission?.total || 0,
    pendingCommission: (state) => state.commission?.pending || 0,
    paidCommission: (state) => state.commission?.paid || 0,
    monthCommission: (state) => state.commission?.month || 0,
    commissionGrowth: (state) => state.commission?.growth || 0,
    commissionByVendor: (state) => state.commission?.by_vendor || [],

    // Activities
    recentActivities: (state) => state.activities.slice(0, 10),
    activityCount: (state) => state.activities.length,

    // Payouts
    totalPendingPayouts: (state) => state.payoutSummary?.total_pending || 0,
    totalProcessingPayouts: (state) => state.payoutSummary?.total_processing || 0,
    totalPaidPayouts: (state) => state.payoutSummary?.total_paid || 0,

    // Loaded status
    isDataLoaded: (state) => state.revenue !== null && state.users !== null && state.vendors !== null,
  },

  actions: {
    // ===================== INITIALIZE =====================
    async initialize(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
        console.log('User is not an admin, skipping initialization')
        return
      }

      if (this.isInitialized && !force) {
        return
      }

      this.loading.dashboard = true
      this.error.dashboard = null

      try {
        await this.fetchAllDashboardData(force)
        this.isInitialized = true
      } catch (error: any) {
        console.error('Failed to initialize admin dashboard:', error)
        this.error.dashboard = error.message || 'Failed to load dashboard data'
        throw error
      } finally {
        this.loading.dashboard = false
      }
    },

    // ===================== FETCH ALL DATA =====================
    async fetchAllDashboardData(force = false) {
      if (!force && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return
      }

      this.loading.dashboard = true
      this.error.dashboard = null

      try {
        await Promise.all([
          this.fetchRevenue(force),
          this.fetchUsers(force),
          this.fetchVendors(force),
          this.fetchOrders(force),
          this.fetchCommission(force),
          this.fetchActivities(force),
          this.fetchTopVendors(force),
        ])
        this.lastFetched.dashboard = Date.now()
      } catch (error: any) {
        console.error('Failed to fetch dashboard data:', error)
        this.error.dashboard = error.message || 'Failed to load dashboard data'
        throw error
      } finally {
        this.loading.dashboard = false
      }
    },

    // ===================== REVENUE =====================
    async fetchRevenue(force = false) {
      if (!force && this.revenue && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.revenue
      }

      this.loading.revenue = true
      this.error.revenue = null

      try {
        const response = await api.get('/admin/dashboard/revenue')
        this.revenue = response.data.data
        return this.revenue
      } catch (error: any) {
        this.error.revenue = error.response?.data?.message || 'Failed to load revenue data'
        throw error
      } finally {
        this.loading.revenue = false
      }
    },

    // ===================== USERS =====================
    async fetchUsers(force = false) {
      if (!force && this.users && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.users
      }

      this.loading.users = true
      this.error.users = null

      try {
        const response = await api.get('/admin/dashboard/users')
        this.users = response.data.data
        return this.users
      } catch (error: any) {
        this.error.users = error.response?.data?.message || 'Failed to load user data'
        throw error
      } finally {
        this.loading.users = false
      }
    },

    // ===================== VENDORS =====================
    async fetchVendors(force = false) {
      if (!force && this.vendors && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.vendors
      }

      this.loading.vendors = true
      this.error.vendors = null

      try {
        const response = await api.get('/admin/dashboard/vendors')
        this.vendors = response.data.data
        return this.vendors
      } catch (error: any) {
        this.error.vendors = error.response?.data?.message || 'Failed to load vendor data'
        throw error
      } finally {
        this.loading.vendors = false
      }
    },

    // ===================== ORDERS =====================
    async fetchOrders(force = false) {
      if (!force && this.orders && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.orders
      }

      this.loading.orders = true
      this.error.orders = null

      try {
        const response = await api.get('/admin/dashboard/orders')
        this.orders = response.data.data
        return this.orders
      } catch (error: any) {
        this.error.orders = error.response?.data?.message || 'Failed to load order data'
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // ===================== COMMISSION =====================
    async fetchCommission(force = false) {
      if (!force && this.commission && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.commission
      }

      this.loading.commission = true
      this.error.commission = null

      try {
        const response = await api.get('/admin/dashboard/commissions')
        this.commission = response.data.data
        return this.commission
      } catch (error: any) {
        this.error.commission = error.response?.data?.message || 'Failed to load commission data'
        throw error
      } finally {
        this.loading.commission = false
      }
    },

    // ===================== ACTIVITIES =====================
    async fetchActivities(force = false, limit = 20) {
      if (!force && this.activities.length > 0 && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 120000) {
        return this.activities
      }

      this.loading.activities = true
      this.error.activities = null

      try {
        const response = await api.get('/admin/dashboard/activities', {
          params: { limit }
        })
        this.activities = response.data.data?.data || response.data.data || []
        return this.activities
      } catch (error: any) {
        this.error.activities = error.response?.data?.message || 'Failed to load activities'
        throw error
      } finally {
        this.loading.activities = false
      }
    },

    // ===================== TOP VENDORS =====================
    async fetchTopVendors(force = false, limit = 10) {
      if (!force && this.topVendors.length > 0 && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.topVendors
      }

      this.loading.topVendors = true
      this.error.topVendors = null

      try {
        const response = await api.get('/admin/dashboard/top-vendors', {
          params: { limit }
        })
        this.topVendors = response.data.data || []
        return this.topVendors
      } catch (error: any) {
        this.error.topVendors = error.response?.data?.message || 'Failed to load top vendors'
        throw error
      } finally {
        this.loading.topVendors = false
      }
    },

    // ===================== PENDING VENDORS =====================
    async fetchPendingVendors(force = false) {
      if (!force && this.pendingVendors.length > 0 && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.pendingVendors
      }

      this.loading.pendingVendors = true
      this.error.pendingVendors = null

      try {
        const response = await api.get('/admin/vendor/pending')
        this.pendingVendors = response.data.data || []
        return this.pendingVendors
      } catch (error: any) {
        this.error.pendingVendors = error.response?.data?.message || 'Failed to load pending vendors'
        throw error
      } finally {
        this.loading.pendingVendors = false
      }
    },

    // ===================== APPROVE VENDOR =====================
    async approveVendor(vendorId: number): Promise<any> {
      try {
        const response = await api.post(`/admin/vendor/${vendorId}/approve`)
        // Refresh pending vendors list
        await this.fetchPendingVendors(true)
        await this.fetchVendors(true)
        return response.data
      } catch (error) {
        throw error
      }
    },

    // ===================== REJECT VENDOR =====================
    async rejectVendor(vendorId: number, rejectionReason?: string): Promise<any> {
      try {
        const response = await api.post(`/admin/vendor/${vendorId}/reject`, {
          rejection_reason: rejectionReason
        })
        // Refresh pending vendors list
        await this.fetchPendingVendors(true)
        await this.fetchVendors(true)
        return response.data
      } catch (error) {
        throw error
      }
    },

    // ===================== PAYOUT SUMMARY =====================
    async fetchPayoutSummary(force = false) {
      if (!force && this.payoutSummary && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.payoutSummary
      }

      this.loading.payoutSummary = true
      this.error.payoutSummary = null

      try {
        const response = await api.get('/admin/payouts/summary')
        this.payoutSummary = response.data.data
        return this.payoutSummary
      } catch (error: any) {
        this.error.payoutSummary = error.response?.data?.message || 'Failed to load payout summary'
        throw error
      } finally {
        this.loading.payoutSummary = false
      }
    },

    // ===================== PENDING PAYOUTS =====================
    async fetchPendingPayouts(perPage = 15) {
      this.loading.pendingPayouts = true
      this.error.pendingPayouts = null

      try {
        const response = await api.get('/admin/payouts/pending', {
          params: { per_page: perPage }
        })
        this.pendingPayouts = response.data.data?.payouts?.data || response.data.data?.payouts || []
        return response.data.data
      } catch (error: any) {
        this.error.pendingPayouts = error.response?.data?.message || 'Failed to load pending payouts'
        throw error
      } finally {
        this.loading.pendingPayouts = false
      }
    },

    // ===================== PROCESS PAYOUTS =====================
    async processPayouts(payoutIds: number[], reference?: string): Promise<any> {
      try {
        const response = await api.post('/admin/payouts/process', {
          payout_ids: payoutIds,
          reference: reference
        })
        // Refresh payouts
        await this.fetchPendingPayouts()
        await this.fetchPayoutSummary(true)
        return response.data
      } catch (error) {
        throw error
      }
    },

    // ===================== REFRESH =====================
    async refreshAll() {
      this.lastFetched.dashboard = null
      await this.fetchAllDashboardData(true)
    },

    // ===================== RESET =====================
    resetAll() {
      this.revenue = null
      this.users = null
      this.vendors = null
      this.orders = null
      this.commission = null
      this.activities = []
      this.topVendors = []
      this.pendingVendors = []
      this.pendingPayouts = []
      this.payoutSummary = null
      this.isInitialized = false
      this.lastFetched.dashboard = null
      this.loading = {
        dashboard: false,
        revenue: false,
        users: false,
        vendors: false,
        orders: false,
        commission: false,
        activities: false,
        topVendors: false,
        pendingVendors: false,
        pendingPayouts: false,
        payoutSummary: false,
      }
      this.error = {
        dashboard: null,
        revenue: null,
        users: null,
        vendors: null,
        orders: null,
        commission: null,
        activities: null,
        topVendors: null,
        pendingVendors: null,
        pendingPayouts: null,
        payoutSummary: null,
      }
    },
  },
})
