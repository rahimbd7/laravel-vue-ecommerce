<template>
  <nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        
        <!-- Logo - Left Side -->
        <router-link to="/" class="text-xl font-bold text-gray-800 hover:text-[#00685F] transition shrink-0">
          My Shop
        </router-link>

        <!-- Desktop Navigation - Center -->
        <div class="hidden md:flex items-center justify-center flex-1 space-x-8">
          <router-link to="/shop" class="text-gray-700 hover:text-[#00685F] transition font-medium" active-class="text-[#00685F]">
            Shop
          </router-link>
          <router-link to="/new-arrivals" class="text-gray-700 hover:text-[#00685F] transition font-medium" active-class="text-[#00685F]">
            New Arrivals
          </router-link>
          <router-link to="/collections" class="text-gray-700 hover:text-[#00685F] transition font-medium" active-class="text-[#00685F]">
            Collection
          </router-link>
          <router-link to="/sale" class="text-gray-700 hover:text-blue-600 transition font-medium text-red-500" active-class="text-red-700">
            Sale
          </router-link>
        </div>

        <!-- Right Side - Cart & Auth (Desktop) -->
        <div class="hidden md:flex items-center space-x-6 flex-shrink-0">
          <!-- Cart Icon -->
          <router-link to="/cart" class="relative text-gray-700 hover:text-[#00685F] transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6M17 13l1.5 6M9 21h6M12 15v6" />
            </svg>
            <span v-if="cartCount > 0" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
              {{ cartCount }}
            </span>
          </router-link>

          <!-- ✅ Show User Avatar & Dropdown when logged in -->
          <div v-if="authStore.isAuthenticated" class="relative" ref="dropdownRef">
            <button 
              @click="toggleDropdown" 
              class="flex items-center gap-2 hover:opacity-80 transition"
            >
              <!-- Avatar -->
              <div class="w-9 h-9 rounded-full bg-[#00685F] text-white flex items-center justify-center font-semibold text-sm">
                {{ userInitials }}
              </div>
              <span class="text-gray-700 hidden lg:inline">{{ authStore.userName }}</span>
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div 
              v-if="isDropdownOpen"
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
            >
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ authStore.userName }}</p>
                <p class="text-xs text-gray-500 truncate">{{ authStore.userEmail }}</p>
              </div>
              
              <router-link 
                to="/dashboard" 
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                @click="closeDropdown"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
              </router-link>

              <router-link 
                to="dashboard/customer/orders" 
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                @click="closeDropdown"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                My Orders
              </router-link>

              <router-link 
                to="dashboard/customer/profile" 
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                @click="closeDropdown"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Profile
              </router-link>

              <div class="border-t border-gray-100"></div>

              <button 
                @click="handleLogout" 
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition w-full text-left"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
              </button>
            </div>
          </div>

          <!-- ✅ Show Login/Register when logged out -->
          <div v-else class="flex items-center gap-4">
            <router-link to="/login" class="text-gray-700 hover:text-[#00685F] transition font-medium">
              Login
            </router-link>
            <router-link to="/register" class="bg-[#00685F] text-white px-4 py-2 rounded-lg hover:bg-[#004F45] transition">
              Sign Up
            </router-link>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button 
          @click="mobileMenuOpen = !mobileMenuOpen" 
          class="md:hidden text-gray-600 hover:text-[#00685F]"
        >
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile Menu Dropdown -->
      <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-gray-100">
        <div class="flex flex-col space-y-3">
          <router-link to="/shop" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-[#00685F] px-2 py-1">
            Shop
          </router-link>
          <router-link to="/new-arrivals" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-[#00685F] px-2 py-1">
            New Arrivals
          </router-link>
          <router-link to="/collections" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-[#00685F] px-2 py-1">
            Collection
          </router-link>
          <router-link to="/sale" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-red-500 px-2 py-1">
            Sale
          </router-link>
          
          <hr class="my-2">
          
          <router-link to="/cart" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-[#00685F] px-2 py-1 flex items-center gap-2">
            Cart
            <span v-if="cartCount > 0" class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
              {{ cartCount }}
            </span>
          </router-link>
          
          <!-- ✅ Mobile: Show User Info when logged in -->
          <div v-if="authStore.isAuthenticated" class="px-2 py-1">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-full bg-[#00685F] text-white flex items-center justify-center font-semibold text-sm">
                {{ userInitials }}
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ authStore.userName }}</p>
                <p class="text-xs text-gray-500">{{ authStore.userEmail }}</p>
              </div>
            </div>
            
            <router-link to="dashboard" @click="mobileMenuOpen = false" class="flex items-center gap-2 text-gray-600 hover:text-[#00685F] py-1">
              Dashboard
            </router-link>
            <router-link to="dashboard/customer/orders" @click="mobileMenuOpen = false" class="flex items-center gap-2 text-gray-600 hover:text-[#00685F] py-1">
              My Orders
            </router-link>
            <router-link to="dashboard/customer/profile" @click="mobileMenuOpen = false" class="flex items-center gap-2 text-gray-600 hover:text-[#00685F] py-1">
              Profile
            </router-link>
            
            <button @click="handleLogout" class="flex items-center gap-2 text-red-600 hover:text-red-700 py-1 w-full text-left">
              Logout
            </button>
          </div>

          <!-- ✅ Mobile: Show Login/Register when logged out -->
          <div v-else class="flex flex-col space-y-2 px-2 py-1">
            <router-link to="/login" @click="mobileMenuOpen = false" class="text-gray-600 hover:text-[#00685F]">
              Login
            </router-link>
            <router-link to="/register" @click="mobileMenuOpen = false" class="bg-[#00685F] text-white text-center px-2 py-2 rounded-lg hover:bg-[#004F45]">
              Sign Up
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart.store'
import { useAuthStore } from '@/stores/auth.store'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const mobileMenuOpen = ref(false)
const isDropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const cartCount = computed(() => cartStore.itemCount)

// Get user initials for avatar
const userInitials = computed(() => {
  const name = authStore.userName || 'User'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
}

const closeDropdown = () => {
  isDropdownOpen.value = false
}

const handleLogout = async () => {
  closeDropdown()
  await authStore.logout()
  router.push('/login')
}

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isDropdownOpen.value = false
  }
}

onMounted(() => {
  cartStore.fetchCart()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Optional: Add smooth transition for dropdown */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>