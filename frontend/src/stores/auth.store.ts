// src/stores/auth.ts
import { defineStore } from 'pinia'
import api from '@/api/api'

interface User {
  id: string
  name: string
  email: string
  role: string
  profile?: {
    phone?: string
    address?: string
    city?: string
    country?: string
  }
}

interface AuthState {
  token: string | null
  user: User | null
  loading: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    token: localStorage.getItem('token'),
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    loading: false
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isVendor: (state) => state.user?.role === 'vendor',
    isCustomer: (state) => state.user?.role === 'customer',
    userName: (state) => state.user?.name || 'User',
    userEmail: (state) => state.user?.email || ''
  },
  
  actions: {
    setToken(token: string) {
      this.token = token
      localStorage.setItem('token', token)
    },
    
    setUser(user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    
    cleanState() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
    
    async login(email: string, password: string) {
      this.loading = true
      try {
        const result = await api.post('/login', { email, password })
        const data = await result.data
        
        if (data.status === 'success') {
          this.setToken(data.data.token)
          this.setUser(data.data.user)
          return { success: true, data }
        }
        
        return { success: false, error: data.message }
      } catch (error: any) {
        return { success: false, error: error.message }
      } finally {
        this.loading = false
      }
    },
    
    async register(name: string, email: string, password: string, password_confirmation: string): Promise<{ success: boolean; error?: string }> {
      this.loading = true
      try {
        const response = await api.post('/register', { name, email, password, password_confirmation })
        const data =await response.data
        
        if (data.status === 'success') {
          this.setToken(data.data.token)
          this.setUser(data.data.user)
          return { success: true }
        }
        
        return { success: false, error: data.message || 'Registration failed' }
      } catch (error: any) {
        return { 
          success: false, 
          error: error.response?.data?.message || error.message || 'Registration failed'
        }
      } finally {
        this.loading = false
      }
    },
    
    async logout(): Promise<void> {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.cleanState()
      }
    },
    
    async fetchProfile(): Promise<User | null> {
      if (!this.token) return null
      
      try {
        const response = await api.get('/me')
        const data = response.data
        
        if (data.status === 'success') {
          this.setUser(data.data)
          return data.data
        }
        
        return null
      } catch (error) {
        console.error('Fetch profile error:', error)
        return null
      }
    }
  }
})