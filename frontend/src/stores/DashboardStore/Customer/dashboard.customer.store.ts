import { defineStore } from 'pinia'
import api from '@/api/api'
import { useAuthStore } from '@/stores/auth.store'

interface Profile {
  id: number
  uuid: string
  name: string
  email: string
  role: string
  profile: {
    phone: string | null
    avatar: string | null
    address: string | null
    city: string | null
    state: string | null
    postal_code: string | null
    country: string | null
    date_of_birth: string | null
  }
  created_at: string
  updated_at: string
}

interface WishlistItem {
  id: number
  product_id: number
  product: {
    id: number
    name: string
    slug: string
    price: number
    compare_price: number | null
    image: string | null
    inventory?: {
      status: string
    }
  }
  created_at: string
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
}

export interface Payment {
  id: number
  order_id: number
  user_id: number
  amount: number | string
  payment_method: string
  status: string
  transaction_id: string | null
  gateway_response: any
  paid_at: string | null
  metadata: Record<string, any> | null
  created_at: string
  updated_at: string
  order?: {
    id: number
    order_number: string
    status: string
    grand_total: number
  } | null
}

interface Address {
  address: string
  city: string
  state: string | null
  postal_code: string | null
  country: string
  full_address: string
}

interface Vendor {
  id: number
  business_name: string
  business_email: string
  business_phone: string
  tax_number: string | null
  website: string | null
  description: string | null
  commission_rate: number
  is_verified: boolean
  user?: {
    uuid: string
    name: string
    email: string
  }
  created_at: string
  updated_at: string
}

type VendorApplicationStatus = 'not_applied' | 'pending' | 'verified' | 'rejected'

export const useCustomerDashboardStore = defineStore('customerDashboard', {
  state: () => ({
    // Profile
    profile: null as Profile | null,
    
    // Wishlist
    wishlist: [] as WishlistItem[],
    wishlistCount: 0,
    
    // Orders
    orders: [] as Order[],
    orderPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },

    // Payments
    payments: [] as Payment[],
    paymentPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },
    
    // Address
    address: null as Address | null,

    // Vendor application
    vendorApplicationStatus: null as VendorApplicationStatus | null,
    vendorApplication: null as Vendor | null,
    applicationMessage: null as string | null,
    applicationRejectionReason: null as string | null,
    
    // Cache timestamps (for TTL-based invalidation)
    lastFetched: {
      profile: null as number | null,
      wishlist: null as number | null,
      orders: null as number | null,
      payments: null as number | null,
      address: null as number | null,
      application: null as number | null,
    },
    
    // Loading states
    loading: {
      profile: false,
      wishlist: false,
      orders: false,
      payments: false,
      address: false,
      application: false,
    },
    
    // Errors
    error: {
      profile: null as string | null,
      wishlist: null as string | null,
      orders: null as string | null,
      payments: null as string | null,
      address: null as string | null,
      application: null as string | null,
    },
    
    // Initialization flag
    isInitialized: false
  }),

  getters: {
    // Profile
    isProfileLoaded: (state) => state.profile !== null,
    userFullName: (state) => state.profile?.name || 'User',
    userEmail: (state) => state.profile?.email || '',
    userInitials: (state) => {
      const name = state.profile?.name || 'User'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    },
    userAvatar: (state) => state.profile?.profile?.avatar || null,
    userPhone: (state) => state.profile?.profile?.phone || '',
    userDateOfBirth: (state) => state.profile?.profile?.date_of_birth || null,

    // Wishlist
    isWishlistLoaded: (state) => state.wishlist.length > 0,
    wishlistItems: (state) => state.wishlist,
    wishlistTotal: (state) => state.wishlistCount,
    isInWishlist: (state) => (productId: number) => {
      return state.wishlist.some(item => item.product_id === productId)
    },

    // Orders
    isOrdersLoaded: (state) => state.orders.length > 0,
    orderCount: (state) => state.orderPagination.total,
    recentOrders: (state) => state.orders.slice(0, 5),

    // Payments
    paymentCount: (state) => state.paymentPagination.total,
    successfulPayments: (state) => state.payments.filter(p => p.status === 'success'),
    totalPaid: (state) => state.payments
      .filter(p => p.status === 'success')
      .reduce((sum, p) => sum + (Number(p.amount) || 0), 0),
    pendingPaymentAmount: (state) => state.payments
      .filter(p => p.status === 'pending' || p.status === 'processing')
      .reduce((sum, p) => sum + (Number(p.amount) || 0), 0),

    // Address
    isAddressLoaded: (state) => state.address !== null,
    fullAddress: (state) => state.address?.full_address || '',

    // Vendor application
    hasAppliedForVendor: (state) =>
      state.vendorApplicationStatus === 'pending' || state.vendorApplicationStatus === 'verified',
    isVendorVerified: (state) => state.vendorApplicationStatus === 'verified',
    isVendorRejected: (state) => state.vendorApplicationStatus === 'rejected',
  },

  actions: {
    // ===================== INITIALIZE =====================
    async initialize() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) {
        console.log('User not authenticated, skipping dashboard initialization')
        return
      }
      
      if (this.isInitialized) {
        return
      }
      
      try {
        await Promise.all([
          this.fetchProfile(),
          this.fetchWishlist(),
        ])
        this.isInitialized = true
      } catch (error) {
        console.error('Failed to initialize dashboard:', error)
      }
    },

    // ===================== PROFILE =====================
    async fetchProfile(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      // Check cache TTL (5 minutes)
      if (!force && this.lastFetched.profile && 
          Date.now() - this.lastFetched.profile < 300000) {
        return this.profile
      }

      this.loading.profile = true
      this.error.profile = null

      try {
        const response = await api.get('/me')
        this.profile = response.data.data
        this.lastFetched.profile = Date.now()
        return this.profile
      } catch (error: any) {
        this.error.profile = error.response?.data?.message || 'Failed to load profile'
        throw error
      } finally {
        this.loading.profile = false
      }
    },

    async updateProfile(data: {
      name?: string
      phone?: string
      date_of_birth?: string
    }) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.profile = true
      this.error.profile = null

      try {
        const response = await api.put('/profile', data)
        this.profile = response.data.data
        this.lastFetched.profile = Date.now()
        return response.data
      } catch (error: any) {
        this.error.profile = error.response?.data?.message || 'Failed to update profile'
        throw error
      } finally {
        this.loading.profile = false
      }
    },

    async updateAvatar(file: File) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.profile = true
      this.error.profile = null

      try {
        const formData = new FormData()
        formData.append('avatar', file)

        const response = await api.put('/profile/avatar', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (this.profile && this.profile.profile) {
          this.profile.profile.avatar = response.data.data.avatar
        }
        this.lastFetched.profile = Date.now()
        return response.data
      } catch (error: any) {
        this.error.profile = error.response?.data?.message || 'Failed to update avatar'
        throw error
      } finally {
        this.loading.profile = false
      }
    },

    async changePassword(data: {
      current_password: string
      new_password: string
      new_password_confirmation: string
    }) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.profile = true
      this.error.profile = null

      try {
        const response = await api.put('/profile/change-password', data)
        return response.data
      } catch (error: any) {
        this.error.profile = error.response?.data?.message || 'Failed to change password'
        throw error
      } finally {
        this.loading.profile = false
      }
    },

    // ===================== WISHLIST =====================
    async fetchWishlist(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return []

      // Check cache TTL (5 minutes)
      if (!force && this.lastFetched.wishlist && 
          Date.now() - this.lastFetched.wishlist < 300000) {
        return this.wishlist
      }

      this.loading.wishlist = true
      this.error.wishlist = null

      try {
        const response = await api.get('/wishlist')
        const data = response.data.data
        this.wishlist = data.data || data || []
        this.wishlistCount = this.wishlist.length
        this.lastFetched.wishlist = Date.now()
        return this.wishlist
      } catch (error: any) {
        this.error.wishlist = error.response?.data?.message || 'Failed to load wishlist'
        throw error
      } finally {
        this.loading.wishlist = false
      }
    },

    async toggleWishlist(productId: number) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Please login to manage wishlist')

      this.loading.wishlist = true
      this.error.wishlist = null

      try {
        const response = await api.post('/wishlist/toggle', { product_id: productId })
        const { status, message } = response.data.data

        if (status === 'added') {
          await this.fetchWishlist(true)
        } else {
          this.wishlist = this.wishlist.filter(item => item.product_id !== productId)
          this.wishlistCount = this.wishlist.length
          this.lastFetched.wishlist = Date.now()
        }

        return { status, message }
      } catch (error: any) {
        this.error.wishlist = error.response?.data?.message || 'Failed to update wishlist'
        throw error
      } finally {
        this.loading.wishlist = false
      }
    },

    async removeFromWishlist(productId: number) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.wishlist = true
      this.error.wishlist = null

      try {
        await api.delete(`/wishlist/remove/${productId}`)
        
        this.wishlist = this.wishlist.filter(item => item.product_id !== productId)
        this.wishlistCount = this.wishlist.length
        this.lastFetched.wishlist = Date.now()
        
        return { success: true }
      } catch (error: any) {
        this.error.wishlist = error.response?.data?.message || 'Failed to remove from wishlist'
        throw error
      } finally {
        this.loading.wishlist = false
      }
    },

    async clearWishlist() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.wishlist = true
      this.error.wishlist = null

      try {
        await api.delete('/wishlist/clear')
        
        this.wishlist = []
        this.wishlistCount = 0
        this.lastFetched.wishlist = Date.now()
        
        return { success: true }
      } catch (error: any) {
        this.error.wishlist = error.response?.data?.message || 'Failed to clear wishlist'
        throw error
      } finally {
        this.loading.wishlist = false
      }
    },

    // ===================== ORDERS =====================
    async fetchOrders(page = 1, perPage = 15, force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return { orders: [], pagination: this.orderPagination }

      // Check cache TTL (3 minutes)
      if (!force && this.lastFetched.orders && 
          Date.now() - this.lastFetched.orders < 180000) {
        return { orders: this.orders, pagination: this.orderPagination }
      }

      this.loading.orders = true
      this.error.orders = null

      try {
        const response = await api.get('/orders', {
          params: { page, per_page: perPage }
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

    async getOrderDetails(orderId: number) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      // Check if order is already in cache
      const cachedOrder = this.orders.find(order => order.id === orderId)
      if (cachedOrder) {
        return cachedOrder
      }

      try {
        const response = await api.get(`/orders/${orderId}`)
        const order = response.data.data
        // Add to cache
        const existingIndex = this.orders.findIndex(o => o.id === orderId)
        if (existingIndex !== -1) {
          this.orders[existingIndex] = order
        } else {
          this.orders.unshift(order)
        }
        return order
      } catch (error: any) {
        throw error
      }
    },

    // ===================== PAYMENTS =====================
    async fetchPayments(force = false, page = 1, perPage = 15) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return { payments: [], pagination: this.paymentPagination }

      // Check cache TTL (5 minutes)
      if (!force && this.lastFetched.payments &&
          Date.now() - this.lastFetched.payments < 300000) {
        return { payments: this.payments, pagination: this.paymentPagination }
      }

      this.loading.payments = true
      this.error.payments = null

      try {
        const response = await api.get('/payments/my', {
          params: { page, per_page: perPage }
        })

        // `/payments/my` returns paginationResponse -> items in `data`,
        // pagination metadata in `meta`.
        const data = response.data.data || []
        const meta = response.data.meta || {}

        this.payments = data
        this.paymentPagination = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || perPage,
          total: meta.total || 0
        }
        this.lastFetched.payments = Date.now()

        return { payments: this.payments, pagination: this.paymentPagination }
      } catch (error: any) {
        this.error.payments = error.response?.data?.message || 'Failed to load payment history'
        throw error
      } finally {
        this.loading.payments = false
      }
    },

    // ===================== ADDRESS =====================
    async fetchAddress(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      // Check cache TTL (5 minutes)
      if (!force && this.lastFetched.address && 
          Date.now() - this.lastFetched.address < 300000) {
        return this.address
      }

      this.loading.address = true
      this.error.address = null

      try {
        const response = await api.get('/profile/address')
        this.address = response.data.data
        this.lastFetched.address = Date.now()
        return this.address
      } catch (error: any) {
        this.error.address = error.response?.data?.message || 'Failed to load address'
        throw error
      } finally {
        this.loading.address = false
      }
    },

    async updateAddress(data: {
      address: string
      city: string
      state: string | null
      postal_code: string | null
      country: string
    }) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.address = true
      this.error.address = null

      try {
        const response = await api.put('/profile/address', data)
        this.address = response.data.data
        this.lastFetched.address = Date.now()
        return response.data
      } catch (error: any) {
        this.error.address = error.response?.data?.message || 'Failed to update address'
        throw error
      } finally {
        this.loading.address = false
      }
    },

    // ===================== VENDOR APPLICATION =====================
    async fetchVendorApplicationStatus(force = false) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return null

      // Check cache TTL (5 minutes)
      if (!force && this.lastFetched.application &&
          Date.now() - this.lastFetched.application < 300000) {
        return { status: this.vendorApplicationStatus, vendor: this.vendorApplication, message: this.applicationMessage }
      }

      this.loading.application = true
      this.error.application = null

      try {
        const response = await api.get('/vendor/application-status')
        const data = response.data.data

        this.vendorApplicationStatus = data.status || 'not_applied'
        this.vendorApplication = data.vendor || null
        this.applicationMessage = data.message || null
        this.applicationRejectionReason = data.rejection_reason || null
        this.lastFetched.application = Date.now()

        // If the admin approved the application the backend promotes the user to
        // "vendor". Refresh the auth profile so the sidebar/role-based routes
        // reflect the new role without requiring a re-login.
        if (this.vendorApplicationStatus === 'verified') {
          authStore.fetchProfile().catch(() => {})
        }

        return { status: this.vendorApplicationStatus, vendor: this.vendorApplication, message: this.applicationMessage }
      } catch (error: any) {
        this.error.application = error.response?.data?.message || 'Failed to load vendor application status'
        throw error
      } finally {
        this.loading.application = false
      }
    },

    async applyForVendor(data: {
      business_name: string
      business_email: string
      business_phone: string
      tax_number?: string | null
      website?: string | null
      description?: string | null
      commission_rate?: number | null
    }) {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) throw new Error('Not authenticated')

      this.loading.application = true
      this.error.application = null

      try {
        const response = await api.post('/vendor/apply', data)
        const vendor = response.data.data

        this.vendorApplication = vendor
        this.vendorApplicationStatus = 'pending'
        this.applicationMessage = response.data.message || 'Vendor application submitted successfully'
        this.applicationRejectionReason = null
        this.lastFetched.application = Date.now()

        return response.data
      } catch (error: any) {
        this.error.application = error.response?.data?.message || 'Failed to submit vendor application'
        throw error
      } finally {
        this.loading.application = false
      }
    },

    // ===================== UTILITY =====================
    resetAll() {
      this.profile = null
      this.wishlist = []
      this.wishlistCount = 0
      this.orders = []
      this.orderPagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.payments = []
      this.paymentPagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.address = null
      this.vendorApplicationStatus = null
      this.vendorApplication = null
      this.applicationMessage = null
      this.applicationRejectionReason = null
      this.lastFetched = {
        profile: null,
        wishlist: null,
        orders: null,
        payments: null,
        address: null,
        application: null,
      }
      this.loading = {
        profile: false,
        wishlist: false,
        orders: false,
        payments: false,
        address: false,
        application: false,
      }
      this.error = {
        profile: null,
        wishlist: null,
        orders: null,
        payments: null,
        address: null,
        application: null,
      }
      this.isInitialized = false
    },

    async refreshAll() {
      await Promise.all([
        this.fetchProfile(true),
        this.fetchWishlist(true),
        this.fetchOrders(1, 15, true),
        this.fetchAddress(true),
        this.fetchVendorApplicationStatus(true),
        this.fetchPayments(true),
      ])
    }
  }
})
