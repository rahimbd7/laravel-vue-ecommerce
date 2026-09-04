<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Top Navbar -->
    <Menubar :model="menuItems" class="border-0 rounded-none shadow-sm sticky top-0 z-50">
      <template #start>
        <div class="flex items-center gap-2 sm:gap-4">
          <!-- Menu Toggle Button - visible on tablet and mobile -->
          <Button 
            icon="pi pi-bars" 
            text 
            rounded 
            class="lg:hidden p-button-sm"
            @click="toggleSidebar"
          />
          <router-link :to="dashboardPath" class="text-xl font-bold text-[#00685F]">
            {{ appName }}
          </router-link>
        </div>
      </template>
      <template #end>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Notifications -->
          <div class="relative">
            <Button icon="pi pi-bell" text rounded class="p-button-sm" @click="showToast('info', 'Notifications', 'You have 3 new notifications')" />
            <Badge value="3" severity="danger" class="absolute -top-1 -right-1" />
          </div>
          
          <!-- Desktop: SplitButton with nice avatar -->
          <div class="hidden lg:block">
            <SplitButton 
              :label="userName" 
              :model="userMenuItems" 
              class="p-button-sm" 
              button-class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
            >
              <template #default>
                <div class="flex items-center gap-2">
                  <!-- Nice avatar with gradient background -->
                  <Avatar 
                    :label="userInitials" 
                    size="small" 
                    style="background: linear-gradient(135deg, #00685F, #00A88F); color: white; font-weight: 600;"
                  />
                  <span class="hidden md:inline font-medium text-white">{{ userName }}</span>
                </div>
              </template>
            </SplitButton>
          </div>

          <!-- Mobile/Tablet: Avatar with dropdown (SAME as desktop) -->
          <div class="lg:hidden relative" ref="mobileMenuRef">
            <!-- Avatar button that toggles dropdown -->
            <button 
              @click="toggleMobileMenu"
              class="focus:outline-none focus:ring-2 focus:ring-[#00685F] focus:ring-offset-2 rounded-full transition-all"
              aria-label="User menu"
              :aria-expanded="mobileMenuOpen"
            >
              <!-- Nice avatar with gradient background -->
              <Avatar 
                :label="userInitials" 
                size="small" 
                style="background: linear-gradient(135deg, #00685F, #00A88F); color: white; font-weight: 600; cursor: pointer;"
              />
            </button>

            <!-- Dropdown menu (SAME menu items as desktop) -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 scale-95 -translate-y-2"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-95 -translate-y-2"
            >
              <div 
                v-if="mobileMenuOpen"
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                role="menu"
                aria-label="User menu"
              >
                <!-- User info header with nice avatar -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                  <Avatar 
                    :label="userInitials" 
                    size="large" 
                    style="background: linear-gradient(135deg, #00685F, #00A88F); color: white; font-weight: 600;"
                  />
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ authStore.user?.email }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 capitalize">{{ userRole }}</p>
                  </div>
                </div>

                <!-- Menu items (SAME as desktop userMenuItems) -->
                <div class="py-1">
                  <button
                    v-for="item in userMenuItems"
                    :key="item.label"
                    @click="handleMenuItemClick(item)"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors w-full text-left"
                    role="menuitem"
                  >
                    <i :class="item.icon" class="text-gray-500 w-5 text-center" />
                    <span>{{ item.label }}</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </template>
    </Menubar>

    <!-- Main Content -->
    <div class="flex relative">
      <!-- Sidebar Overlay -->
      <div 
        v-if="sidebarOpen" 
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="closeSidebar"
      ></div>

      <!-- Sidebar -->
      <aside 
        class="fixed lg:relative top-0 left-0 w-64 bg-white border-r border-gray-200 h-screen overflow-y-auto transition-transform duration-300 ease-in-out z-50"
        :class="[
          sidebarOpen ? 'translate-x-0' : '-translate-x-full',
          'lg:translate-x-0 lg:block'
        ]"
      >
        <div class="flex justify-between items-center p-4 border-b border-gray-200 lg:hidden">
          <span class="font-semibold text-gray-900">Menu</span>
          <Button 
            icon="pi pi-times" 
            text 
            rounded 
            class="p-button-sm"
            @click="closeSidebar"
          />
        </div>
        <PanelMenu :model="panelMenuItems" class="border-0 p-2" />
      </aside>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 min-h-screen">
        <router-view />
      </main>
    </div>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useCustomerDashboardStore } from '@/stores/DashboardStore/Customer/dashboard.customer.store'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import Menubar from 'primevue/menubar'
import PanelMenu from 'primevue/panelmenu'
import Button from 'primevue/button'
import SplitButton from 'primevue/splitbutton'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const dashboardStore = useCustomerDashboardStore()
const toast = useToast()
const confirm = useConfirm()

// Sidebar toggle state
const sidebarOpen = ref(false)

// Mobile menu dropdown state
const mobileMenuOpen = ref(false)
const mobileMenuRef = ref<HTMLElement | null>(null)

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
  sidebarOpen.value = false
}

// Mobile menu functions
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

// Close sidebar on route change (mobile/tablet only)
watch(
  () => route.path,
  () => {
    if (window.innerWidth < 1024) {
      closeSidebar()
    }
    closeMobileMenu() // Also close mobile menu on route change
  }
)

// Close mobile menu on Escape key
const handleEscapeKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    if (sidebarOpen.value) {
      closeSidebar()
    }
    if (mobileMenuOpen.value) {
      closeMobileMenu()
    }
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (mobileMenuRef.value && !mobileMenuRef.value.contains(event.target as Node)) {
    closeMobileMenu()
  }
}

// Initialize dashboard data when authenticated
onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey)
  document.addEventListener('click', handleClickOutside)
  
  if (authStore.isAuthenticated) {
    dashboardStore.initialize()
  }
})

// Cleanup event listeners
onUnmounted(() => {
  document.removeEventListener('keydown', handleEscapeKey)
  document.removeEventListener('click', handleClickOutside)
})

// Watch for login state changes
watch(
  () => authStore.isAuthenticated,
  async (isAuthenticated) => {
    if (isAuthenticated && !dashboardStore.isInitialized) {
      await dashboardStore.initialize()
    } else if (!isAuthenticated) {
      dashboardStore.resetAll()
    }
  }
)

const appName = computed(() => {
  const role = authStore.user?.role || 'customer'
  const names: Record<string, string> = {
    admin: 'Admin Panel',
    vendor: 'Vendor Panel',
    customer: 'My Account'
  }
  return names[role] || 'Dashboard'
})

const dashboardPath = computed(() => {
  const role = authStore.user?.role || 'customer'
  const paths: Record<string, string> = {
    admin: '/dashboard/admin',
    vendor: '/dashboard/vendor',
    customer: '/dashboard/customer'
  }
  return paths[role] || '/dashboard/customer'
})

// Get user details from auth store
const userName = computed(() => authStore.userName)
const userRole = computed(() => authStore.user?.role || 'customer')

// Compute user initials from auth store user name
const userInitials = computed(() => {
  const name = authStore.user?.name || 'User'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

// Helper function to get orders path
const getOrdersPath = () => {
  const role = authStore.user?.role || 'customer'
  const paths: Record<string, string> = {
    admin: '/dashboard/admin/orders',
    vendor: '/dashboard/vendor/orders',
    customer: '/dashboard/customer/orders'
  }
  return paths[role] || '/dashboard/customer/orders'
}

// Top Menu Items - Hide on mobile/tablet
const menuItems = ref([
  { label: 'Dashboard', icon: 'pi pi-home', to: dashboardPath.value, class: 'hidden lg:block' },
  { label: 'Shop', icon: 'pi pi-shopping-bag', to: '/shop', class: 'hidden lg:block' },
  { label: 'Orders', icon: 'pi pi-shopping-cart', to: getOrdersPath(), class: 'hidden lg:block' }
])

// Watch for role changes to update menuItems
watch(
  () => authStore.user?.role,
  () => {
    const path = dashboardPath.value
    const ordersPath = getOrdersPath()
    
    menuItems.value = [
      { label: 'Dashboard', icon: 'pi pi-home', to: path, class: 'hidden lg:block' },
      { label: 'Shop', icon: 'pi pi-shopping-bag', to: '/shop', class: 'hidden lg:block' },
      { label: 'Orders', icon: 'pi pi-shopping-cart', to: ordersPath, class: 'hidden lg:block' }
    ]
  },
  { immediate: true }
)

const showToast = (severity: string, summary: string, detail: string) => {
  toast.add({ severity, summary, detail, life: 3000 })
}

// Handle logout
const handleLogout = () => {
  closeMobileMenu() // Close mobile menu first
  
  confirm.require({
    message: 'Are you sure you want to logout?',
    header: 'Confirm Logout',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      await authStore.logout()
      dashboardStore.resetAll()
      router.push('/login')
      showToast('success', 'Logged Out', 'You have been logged out successfully')
    }
  })
}

// Handle menu item click (for mobile dropdown)
const handleMenuItemClick = (item: any) => {
  closeMobileMenu()
  
  if (item.label === 'Logout') {
    handleLogout()
  } else if (item.command) {
    item.command()
  } else if (item.to) {
    router.push(item.to)
  }
}

// User menu items - USED BY BOTH desktop AND mobile
const userMenuItems = computed(() => [
  { 
    label: 'Profile', 
    icon: 'pi pi-user', 
    to: `${dashboardPath.value}/profile`,
    command: () => router.push(`${dashboardPath.value}/profile`)
  },
  { 
    label: 'Dashboard', 
    icon: 'pi pi-home', 
    to: dashboardPath.value,
    command: () => router.push(dashboardPath.value)
  },
  { separator: true },
  { 
    label: 'Logout', 
    icon: 'pi pi-sign-out', 
    command: handleLogout 
  }
])

// Sidebar Menu Items (Role-based)
const panelMenuItems = computed(() => {
  const role = userRole.value
  const items: any[] = []

  // Common items for all roles
  items.push({
    label: 'Dashboard',
    icon: 'pi pi-home',
    items: [
      { 
        label: 'Overview', 
        icon: 'pi pi-chart-line', 
        to: dashboardPath.value, 
        command: () => router.push(dashboardPath.value) 
      }
    ]
  })

  // ===== ADMIN MENU =====
  if (role === 'admin') {
    items.push(
      {
        label: 'User Management',
        icon: 'pi pi-users',
        items: [
          { label: 'User Overview', icon: 'pi pi-list', to: '/dashboard/admin/users', command: () => router.push('/dashboard/admin/users') },
          { label: 'Manage Users', icon: 'pi pi-user', to: '/dashboard/admin/users/user-management', command: () => router.push('/dashboard/admin/users/user-management') },
        ]
      },
      {
        label: 'Category',
        icon: 'pi pi-box',
        items: [
          { label: 'Manage Category', icon: 'pi pi-shopping-cart', to: '/dashboard/admin/category', command: () => router.push('/dashboard/admin/category') },
        ]
      },
      {
        label: 'Products',
        icon: 'pi pi-box',
        items: [
          { label: 'All Products', icon: 'pi pi-list', to: '/dashboard/admin/products', command: () => router.push('/dashboard/admin/products') },
        ]
      },
      {
        label: 'Orders',
        icon: 'pi pi-shopping-cart',
        items: [
          { label: 'All Orders', icon: 'pi pi-list', to: '/dashboard/admin/orders', command: () => router.push('/dashboard/admin/orders') }
        ]
      },
      {
        label: 'Reviews',
        icon: 'pi pi-star',
        items: [
          { label: 'All Reviews', icon: 'pi pi-list', to: '/dashboard/admin/reviews', command: () => router.push('/dashboard/admin/reviews') }
        ]
      },
      {
        label: 'Payments',
        icon: 'pi pi-credit-card',
        items: [
          { label: 'All Payments', icon: 'pi pi-list', to: '/dashboard/admin/payments', command: () => router.push('/dashboard/admin/payments') }
        ]
      },
      {
        label: 'Coupons',
        icon: 'pi pi-tag',
        items: [
          { label: 'Create Coupons', icon: 'pi pi-list', to: '/dashboard/admin/coupons', command: () => router.push('/dashboard/admin/coupons') },
          { label: 'Manage Coupons', icon: 'pi pi-list', to: '/dashboard/admin/coupons/management', command: () => router.push('/dashboard/admin/coupons/management') }
        ]
      },
      {
        label: 'Settings',
        icon: 'pi pi-cog',
        items: [
          { label: 'General', icon: 'pi pi-sliders-h', to: '/dashboard/admin/settings', command: () => router.push('/dashboard/admin/settings') }
        ]
      }
    )
  }

  // ===== VENDOR MENU =====
  if (role === 'vendor') {
    items.push(
      {
        label: 'Profile',
        icon: 'pi pi-user',
        items: [
          { label: 'My Profile', icon: 'pi pi-user-edit', to: '/dashboard/vendor/profile', command: () => router.push('/dashboard/vendor/profile') }
        ]
      },
      {
        label: 'Products',
        icon: 'pi pi-box',
        items: [
          { label: 'All Products', icon: 'pi pi-list', to: '/dashboard/vendor/products', command: () => router.push('/dashboard/vendor/products') },
          { label: 'Add Product', icon: 'pi pi-plus', to: '/dashboard/vendor/products/create', command: () => router.push('/dashboard/vendor/products/create') }
        ]
      },
      {
        label: 'Orders',
        icon: 'pi pi-shopping-cart',
        items: [
          { label: 'My Orders', icon: 'pi pi-list', to: '/dashboard/vendor/orders', command: () => router.push('/dashboard/vendor/orders') }
        ]
      },
      {
        label: 'Payments',
        icon: 'pi pi-credit-card',
        items: [
          { label: 'Payments & Payouts', icon: 'pi pi-list', to: '/dashboard/vendor/payments', command: () => router.push('/dashboard/vendor/payments') }
        ]
      },
      {
        label: 'Shipping',
        icon: 'pi pi-truck',
        items: [
          { label: 'Shipping Settings', icon: 'pi pi-cog', to: '/dashboard/vendor/shipping', command: () => router.push('/dashboard/vendor/shipping') }
        ]
      },
      {
        label: 'Coupons',
        icon: 'pi pi-tag',
        items: [
          { label: 'Create Coupon', icon: 'pi pi-list', to: '/dashboard/vendor/coupons/create', command: () => router.push('/dashboard/vendor/coupons/create') },
          { label: 'Coupons Management', icon: 'pi pi-plus', to: '/dashboard/vendor/coupons/management', command: () => router.push('/dashboard/vendor/coupons/management') }
        ]
      }
    )
  }

  // ===== CUSTOMER MENU =====
  if (role === 'customer') {
    items.push(
      {
        label: 'Profile',
        icon: 'pi pi-user',
        items: [
          { label: 'My Profile', icon: 'pi pi-user-edit', to: '/dashboard/customer/profile', command: () => router.push('/dashboard/customer/profile') },
          { label: 'Address', icon: 'pi pi-map-marker', to: '/dashboard/customer/address', command: () => router.push('/dashboard/customer/address') }
        ]
      },
      {
        label: 'Orders',
        icon: 'pi pi-shopping-cart',
        items: [
          { label: 'My Orders', icon: 'pi pi-list', to: '/dashboard/customer/orders', command: () => router.push('/dashboard/customer/orders') }
        ]
      },
      {
        label: 'Wishlist',
        icon: 'pi pi-heart',
        items: [
          { label: 'My Wishlist', icon: 'pi pi-list', to: '/dashboard/customer/wishlist', command: () => router.push('/dashboard/customer/wishlist') }
        ]
      },
      {
        label: 'Payments',
        icon: 'pi pi-credit-card',
        items: [
          { label: 'Payment History', icon: 'pi pi-list', to: '/dashboard/customer/payments', command: () => router.push('/dashboard/customer/payments') }
        ]
      },
      {
        label: 'Coupons',
        icon: 'pi pi-tag',
        items: [
          { label: 'My Coupons', icon: 'pi pi-list', to: '/dashboard/customer/coupons', command: () => router.push('/dashboard/customer/coupons') }
        ]
      }
    )
  }

  return items
})
</script>

<style scoped>
/* Sidebar transition */
aside {
  transition: transform 0.3s ease-in-out;
}

/* Overlay styles */
.fixed.inset-0 {
  backdrop-filter: blur(2px);
}

/* Hide Menubar mobile toggle button (three dots) on tablet and mobile */
@media (max-width: 1023px) {
  :deep(.p-menubar .p-menubar-button) {
    display: none !important;
  }
}

/* Hide menu items on mobile and tablet (below 1024px) */
@media (max-width: 1023px) {
  :deep(.p-menubar .p-menubar-root-list > .p-menuitem) {
    display: none !important;
  }
}

/* Responsive fixes for PanelMenu on mobile/tablet */
@media (max-width: 1023px) {
  :deep(.p-panelmenu .p-panelmenu-header > a) {
    padding: 0.5rem 0.75rem !important;
  }
  
  :deep(.p-panelmenu .p-menuitem-link) {
    padding: 0.5rem 0.75rem !important;
  }
}

/* Menubar responsive fixes */
:deep(.p-menubar) { 
  background: white; 
  border-bottom: 1px solid #e5e7eb; 
}

:deep(.p-menubar .p-menubar-end) { 
  display: flex; 
  align-items: center; 
}

/* PanelMenu styles */
:deep(.p-panelmenu .p-panelmenu-panel) {
  margin-bottom: 0.25rem;
}

:deep(.p-panelmenu .p-panelmenu-header > a) { 
  background: transparent; 
  border: none; 
  font-weight: 600; 
}

:deep(.p-panelmenu .p-panelmenu-content) { 
  background: transparent; 
  border: none; 
}

:deep(.p-panelmenu .p-menuitem-link) { 
  padding: 0.75rem 1rem; 
  border-radius: 0.5rem; 
  transition: all 0.2s; 
}

:deep(.p-panelmenu .p-menuitem-link:hover) { 
  background: #f3f4f6; 
}

:deep(.p-panelmenu .p-menuitem-link-active) { 
  background: #00685F; 
  color: white; 
}

:deep(.p-panelmenu .p-menuitem-link-active .p-menuitem-text) { 
  color: white; 
}

:deep(.p-panelmenu .p-menuitem-link-active .p-menuitem-icon) { 
  color: white; 
}

/* Dropdown animation */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.lg\\:hidden .absolute {
  animation: slideDown 0.15s ease-out;
}

/* Avatar gradient animation */
.avatar-gradient {
  background: linear-gradient(135deg, #00685F, #00A88F);
  transition: transform 0.2s ease;
}

.avatar-gradient:hover {
  transform: scale(1.05);
}
</style>
