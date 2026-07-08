import { defineStore } from 'pinia'
import api from '@/api/api'
import { useAuthStore } from '@/stores/auth.store'

export const useVendorProfileStore = defineStore('vendorProfile', {
    state: () => ({
        profile: null as any,
        stats: null as any,
        shipping: null as any,
        analytics: null as any,
        loading: {
            profile: false,
            stats: false,
            shipping: false,
            analytics: false,
        },
        error: {
            profile: null as string | null,
            stats: null as string | null,
            shipping: null as string | null,
            analytics: null as string | null,
        },
        isInitialized: false
    }),

    getters: {
        businessName: (state) => state.profile?.vendor?.business_name || '',
        businessEmail: (state) => state.profile?.vendor?.business_email || '',
        businessPhone: (state) => state.profile?.vendor?.business_phone || '',
        isVerified: (state) => state.profile?.vendor?.is_verified || false,
        storeLogo: (state) => state.profile?.vendor?.store_logo || null,
        description: (state) => state.profile?.vendor?.description || '',
        website: (state) => state.profile?.vendor?.website || '',
        taxNumber: (state) => state.profile?.vendor?.tax_number || '',
        commissionRate: (state) => state.profile?.vendor?.commission_rate || 0,
        totalProducts: (state) => state.stats?.total_products || 0,
        totalOrders: (state) => state.stats?.total_orders || 0,
        totalRevenue: (state) => state.stats?.total_revenue || 0,
        pendingOrders: (state) => state.stats?.pending_orders || 0,
        averageRating: (state) => state.stats?.average_rating || 0,
        userName: (state) => state.profile?.user?.name || '',
        userEmail: (state) => state.profile?.user?.email || '',
        userRole: (state) => state.profile?.user?.role || '',
    },

    actions: {
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
                    this.fetchProfile(),
                    this.fetchStats(),
                    this.fetchShipping(),
                ])
                this.isInitialized = true
            } catch (error) {
                console.error('Failed to initialize vendor profile:', error)
            }
        },

        async fetchProfile(force = false) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) return null

            if (!force && this.profile && this.isInitialized) {
                return this.profile
            }

            this.loading.profile = true
            this.error.profile = null

            try {
                const response = await api.get('/vendor/profile')
                this.profile = response.data.data
                return this.profile
            } catch (error: any) {
                this.error.profile = error.response?.data?.message || 'Failed to load profile'
                throw error
            } finally {
                this.loading.profile = false
            }
        },

        async updateProfile(data: any) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) throw new Error('Not authenticated')

            this.loading.profile = true
            this.error.profile = null

            try {
                const response = await api.put('/vendor/profile', data)
                this.profile = response.data.data
                return response.data
            } catch (error: any) {
                this.error.profile = error.response?.data?.message || 'Failed to update profile'
                throw error
            } finally {
                this.loading.profile = false
            }
        },

        async updateLogo(file: File) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) throw new Error('Not authenticated')

            this.loading.profile = true
            this.error.profile = null

            try {
                const formData = new FormData()
                formData.append('logo', file)

                const response = await api.post('/vendor/profile/logo', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })

                if (this.profile?.vendor) {
                    this.profile.vendor.store_logo = response.data.data.store_logo
                }
                return response.data
            } catch (error: any) {
                this.error.profile = error.response?.data?.message || 'Failed to update logo'
                throw error
            } finally {
                this.loading.profile = false
            }
        },

        async fetchStats(force = false) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) return null

            if (!force && this.stats && this.isInitialized) {
                return this.stats
            }

            this.loading.stats = true
            this.error.stats = null

            try {
                const response = await api.get('/vendor/profile/stats')
                this.stats = response.data.data
                return this.stats
            } catch (error: any) {
                this.error.stats = error.response?.data?.message || 'Failed to load stats'
                throw error
            } finally {
                this.loading.stats = false
            }
        },

        async fetchAnalytics(force = false) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) return null

            if (!force && this.analytics && this.isInitialized) {
                return this.analytics
            }

            this.loading.analytics = true
            this.error.analytics = null

            try {
                const response = await api.get('/vendor/profile/analytics')
                this.analytics = response.data.data
                return this.analytics
            } catch (error: any) {
                this.error.analytics = error.response?.data?.message || 'Failed to load analytics'
                throw error
            } finally {
                this.loading.analytics = false
            }
        },

        async fetchShipping(force = false) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) return null

            if (!force && this.shipping && this.isInitialized) {
                return this.shipping
            }

            this.loading.shipping = true
            this.error.shipping = null

            try {
                const response = await api.get('/vendor/shipping')
                this.shipping = response.data.data
                return this.shipping
            } catch (error: any) {
                this.error.shipping = error.response?.data?.message || 'Failed to load shipping settings'
                throw error
            } finally {
                this.loading.shipping = false
            }
        },

        async updateShipping(data: any) {
            const authStore = useAuthStore()
            if (!authStore.isAuthenticated) throw new Error('Not authenticated')

            this.loading.shipping = true
            this.error.shipping = null

            try {
                const response = await api.put('/vendor/shipping', data)
                this.shipping = response.data.data
                return response.data
            } catch (error: any) {
                this.error.shipping = error.response?.data?.message || 'Failed to update shipping settings'
                throw error
            } finally {
                this.loading.shipping = false
            }
        },

        resetAll() {
            this.profile = null
            this.stats = null
            this.shipping = null
            this.analytics = null
            this.isInitialized = false
            this.loading = {
                profile: false,
                stats: false,
                shipping: false,
                analytics: false,
            }
            this.error = {
                profile: null,
                stats: null,
                shipping: null,
                analytics: null,
            }
        },

        async refreshAll() {
            await Promise.all([
                this.fetchProfile(true),
                this.fetchStats(true),
                this.fetchShipping(true),
            ])
        }
    }
})