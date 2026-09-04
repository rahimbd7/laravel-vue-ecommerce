import { defineStore } from 'pinia'
import api from '@/api/api'
import { useAuthStore } from '@/stores/auth.store'
import { vendorApi } from '@/api/endpoints/vendor/vendor.api'

interface Product {
  id: number
  name: string
  slug: string
  description: string
  price: number
  compare_price: number | null
  status: string
  category_id: number
  vendor_id: number
  created_at: string
  updated_at: string
  images?: any[]
  variations?: any[]
  category?: any
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
  items: any[]
  customer: any
  tracking_number?: string
  carrier?: string
  shipped_at?: string
}

interface Stats {
  total_products: number
  total_orders: number
  total_earnings: number
  pending_orders: number
  average_rating: number
  out_of_stock: number
  low_stock: number
  // Additional vendor stats
  processing_orders: number
  confirmed_orders: number
  shipped_orders: number
  delivered_orders: number
  cancelled_orders: number
  total_revenue: number
  today_orders: number
  today_revenue: number
}

export const useVendorDashboardStore = defineStore('vendorDashboard', {
  state: () => ({
    // Stats
    stats: null as Stats | null,
    
    // Products
    products: [] as Product[],
    productPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },
    
    // Orders
    orders: [] as Order[],
    orderPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },
    
    // Vendor Dashboard Data
    vendorDashboard: null as any,
    topProducts: [] as any[],
    recentOrders: [] as Order[],
    
    // Cache timestamps
    lastFetched: {
      stats: null as number | null,
      products: null as number | null,
      orders: null as number | null,
      dashboard: null as number | null,
    },
    
    // Loading states
    loading: {
      stats: false,
      products: false,
      orders: false,
      dashboard: false,
    },
    
    // Errors
    error: {
      stats: null as string | null,
      products: null as string | null,
      orders: null as string | null,
      dashboard: null as string | null,
    },
    
    // Filters
    filters: {
      status: null as string | null,
      payment_status: null as string | null,
      from_date: null as string | null,
      to_date: null as string | null,
      search: null as string | null,
    },
    
    isInitialized: false
  }),

  getters: {
    isStatsLoaded: (state) => state.stats !== null,
    isProductsLoaded: (state) => state.products.length > 0,
    isOrdersLoaded: (state) => state.orders.length > 0,
    totalProducts: (state) => state.stats?.total_products || 0,
    totalOrders: (state) => state.stats?.total_orders || 0,
    totalEarnings: (state) => state.stats?.total_earnings || 0,
    pendingOrders: (state) => state.stats?.pending_orders || 0,
    averageRating: (state) => state.stats?.average_rating || 0,
    processingOrders: (state) => state.stats?.processing_orders || 0,
    shippedOrders: (state) => state.stats?.shipped_orders || 0,
    deliveredOrders: (state) => state.stats?.delivered_orders || 0,
    cancelledOrders: (state) => state.stats?.cancelled_orders || 0,
    totalRevenue: (state) => state.stats?.total_revenue || 0,
    todayOrders: (state) => state.stats?.today_orders || 0,
    todayRevenue: (state) => state.stats?.today_revenue || 0,
  },

  actions: {
    // ===================== INITIALIZE =====================
    async initialize() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated || authStore.user?.role !== 'vendor') {
        console.log('User is not a vendor, skipping initialization')
        return
      }
      
      if (this.isInitialized) {
        return
      }
      
      try {
        await Promise.all([
          this.fetchDashboard(),
          this.fetchStats(),
          this.fetchProducts(1, 15),
          this.fetchOrders(1, 15),
        ])
        this.isInitialized = true
      } catch (error) {
        console.error('Failed to initialize vendor dashboard:', error)
      }
    },

    // ===================== VENDOR DASHBOARD =====================
    async fetchDashboard(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      if (!force && this.lastFetched.dashboard && 
          Date.now() - this.lastFetched.dashboard < 300000) {
        return this.vendorDashboard
      }

      this.loading.dashboard = true
      this.error.dashboard = null

      try {
        const response = await vendorApi.getDashboard()
        this.vendorDashboard = response.data.data
        this.topProducts = response.data.data.top_products || []
        this.recentOrders = response.data.data.recent_orders || []
        this.lastFetched.dashboard = Date.now()
        return this.vendorDashboard
      } catch (error: any) {
        this.error.dashboard = error.response?.data?.message || 'Failed to load dashboard'
        throw error
      } finally {
        this.loading.dashboard = false
      }
    },

    // ===================== STATS =====================
    async fetchStats(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      if (!force && this.lastFetched.stats && 
          Date.now() - this.lastFetched.stats < 300000) {
        return this.stats
      }

      this.loading.stats = true
      this.error.stats = null

      try {
        // Fetch stats from vendor API
        const response = await vendorApi.getOrderStats()
        const statsData = response.data.data
        
        // Also fetch product stats
        const productStats = await api.get('/v1/products/stats/overview')
        const productData = productStats.data.data
        
        this.stats = {
          total_products: productData.total_products || 0,
          total_orders: statsData.total_orders || 0,
          total_earnings: statsData.total_revenue || 0,
          pending_orders: statsData.pending_orders || 0,
          average_rating: productData.average_rating || 0,
          out_of_stock: productData.out_of_stock || 0,
          low_stock: productData.low_stock || 0,
          processing_orders: statsData.processing_orders || 0,
          confirmed_orders: statsData.confirmed_orders || 0,
          shipped_orders: statsData.shipped_orders || 0,
          delivered_orders: statsData.delivered_orders || 0,
          cancelled_orders: statsData.cancelled_orders || 0,
          total_revenue: statsData.total_revenue || 0,
          today_orders: statsData.today_orders || 0,
          today_revenue: statsData.today_revenue || 0,
        }
        this.lastFetched.stats = Date.now()
        return this.stats
      } catch (error: any) {
        this.error.stats = error.response?.data?.message || 'Failed to load stats'
        throw error
      } finally {
        this.loading.stats = false
      }
    },

    // ===================== PRODUCTS =====================
    async fetchProducts(page = 1, perPage = 15, force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return { products: [], pagination: this.productPagination }

      if (!force && this.lastFetched.products && 
          Date.now() - this.lastFetched.products < 180000) {
        return { products: this.products, pagination: this.productPagination }
      }

      this.loading.products = true
      this.error.products = null

      try {
        const response = await api.get('/v1/products', {
          params: { page, per_page: perPage }
        })
        
        const data = response.data.data
        this.products = data.data || data || []
        this.productPagination = {
          current_page: data.current_page || 1,
          last_page: data.last_page || 1,
          per_page: data.per_page || 15,
          total: data.total || 0
        }
        this.lastFetched.products = Date.now()
        
        return { products: this.products, pagination: this.productPagination }
      } catch (error: any) {
        this.error.products = error.response?.data?.message || 'Failed to load products'
        throw error
      } finally {
        this.loading.products = false
      }
    },

    async createProduct(data: any) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.products = true
      this.error.products = null

      try {
        const response = await api.post('/v1/products', data)
        this.lastFetched.products = null // Invalidate cache
        await this.fetchProducts(1, 15, true)
        return response.data
      } catch (error: any) {
        this.error.products = error.response?.data?.message || 'Failed to create product'
        throw error
      } finally {
        this.loading.products = false
      }
    },

    async updateProduct(productId: number, data: any) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.products = true
      this.error.products = null

      try {
        const response = await api.put(`/v1/products/${productId}`, data)
        this.lastFetched.products = null // Invalidate cache
        await this.fetchProducts(1, 15, true)
        return response.data
      } catch (error: any) {
        this.error.products = error.response?.data?.message || 'Failed to update product'
        throw error
      } finally {
        this.loading.products = false
      }
    },

    async deleteProduct(productId: number) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.products = true
      this.error.products = null

      try {
        await api.delete(`/v1/products/${productId}`)
        this.products = this.products.filter(p => p.id !== productId)
        this.lastFetched.products = null
        return { success: true }
      } catch (error: any) {
        this.error.products = error.response?.data?.message || 'Failed to delete product'
        throw error
      } finally {
        this.loading.products = false
      }
    },

    // ===================== ORDERS =====================
    async fetchOrders(page = 1, perPage = 15, force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return { orders: [], pagination: this.orderPagination }

      if (!force && this.lastFetched.orders && 
          Date.now() - this.lastFetched.orders < 180000) {
        return { orders: this.orders, pagination: this.orderPagination }
      }

      this.loading.orders = true
      this.error.orders = null

      try {
        // ✅ Use vendor API for orders
        const response = await vendorApi.getOrders({
          page,
          per_page: perPage,
          status: this.filters.status,
          payment_status: this.filters.payment_status,
          from_date: this.filters.from_date,
          to_date: this.filters.to_date,
          search: this.filters.search,
        })
        
        const data = response.data.data
        this.orders = data.data || data || []
        this.orderPagination = {
          current_page: data.current_page || 1,
          last_page: data.last_page || 1,
          per_page: data.per_page || 15,
          total: data.total || 0
        }
        this.lastFetched.orders = Date.now()
        
        return { orders: this.orders, pagination: this.orderPagination }
      } catch (error: any) {
        this.error.orders = error.response?.data?.message || 'Failed to load orders'
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    async updateOrderStatus(orderId: number, status: string, trackingNumber?: string, carrier?: string) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.orders = true
      this.error.orders = null

      try {
        // ✅ Use vendor API for status update
        const response = await vendorApi.updateOrderStatus(orderId, {
          status,
          tracking_number: trackingNumber,
          carrier: carrier
        })
        const updatedOrder = response.data.data
        
        // Update in local state
        const index = this.orders.findIndex(o => o.id === orderId)
        if (index !== -1) {
          this.orders[index] = updatedOrder
        }
        
        // Also update recent orders
        const recentIndex = this.recentOrders.findIndex(o => o.id === orderId)
        if (recentIndex !== -1) {
          this.recentOrders[recentIndex] = updatedOrder
        }
        
        this.lastFetched.orders = null // Invalidate cache
        this.lastFetched.dashboard = null // Also invalidate dashboard cache
        
        return response.data
      } catch (error: any) {
        this.error.orders = error.response?.data?.message || 'Failed to update order'
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // ✅ New: Fetch recent orders for dashboard
    async fetchRecentOrders(limit = 10) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return []

      try {
        const response = await vendorApi.getRecentOrders(limit)
        this.recentOrders = response.data.data
        return this.recentOrders
      } catch (error: any) {
        console.error('Failed to fetch recent orders:', error)
        return []
      }
    },

    // ✅ New: Fetch order stats
    async fetchOrderStats(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      if (!force && this.lastFetched.stats && 
          Date.now() - this.lastFetched.stats < 300000) {
        return this.stats
      }

      try {
        const response = await vendorApi.getOrderStats()
        const statsData = response.data.data
        
        // Merge with existing stats
        if (this.stats) {
          this.stats = {
            ...this.stats,
            processing_orders: statsData.processing_orders || 0,
            confirmed_orders: statsData.confirmed_orders || 0,
            shipped_orders: statsData.shipped_orders || 0,
            delivered_orders: statsData.delivered_orders || 0,
            cancelled_orders: statsData.cancelled_orders || 0,
            total_revenue: statsData.total_revenue || 0,
            today_orders: statsData.today_orders || 0,
            today_revenue: statsData.today_revenue || 0,
          }
        }
        this.lastFetched.stats = Date.now()
        return this.stats
      } catch (error: any) {
        console.error('Failed to fetch order stats:', error)
        return null
      }
    },

    // ✅ New: Set filters
    setFilters(filters: any) {
      this.filters = { ...this.filters, ...filters }
    },

    // ✅ New: Reset filters
    resetFilters() {
      this.filters = {
        status: null,
        payment_status: null,
        from_date: null,
        to_date: null,
        search: null,
      }
    },

    // ===================== UTILITY =====================
    resetAll() {
      this.stats = null
      this.products = []
      this.productPagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.orders = []
      this.orderPagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.vendorDashboard = null
      this.topProducts = []
      this.recentOrders = []
      this.lastFetched = {
        stats: null,
        products: null,
        orders: null,
        dashboard: null,
      }
      this.loading = {
        stats: false,
        products: false,
        orders: false,
        dashboard: false,
      }
      this.error = {
        stats: null,
        products: null,
        orders: null,
        dashboard: null,
      }
      this.filters = {
        status: null,
        payment_status: null,
        from_date: null,
        to_date: null,
        search: null,
      }
      this.isInitialized = false
    },

    async refreshAll() {
      await Promise.all([
        this.fetchDashboard(true),
        this.fetchStats(true),
        this.fetchProducts(1, 15, true),
        this.fetchOrders(1, 15, true),
      ])
    }
  }
})
