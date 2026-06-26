<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Top Navbar -->
    <Menubar :model="menuItems" class="border-0 rounded-none shadow-sm sticky top-0 z-50">
      <template #start>
        <router-link :to="dashboardPath" class="text-xl font-bold text-[#00685F]">
          {{ appName }}
        </router-link>
      </template>
      <template #end>
        <div class="flex items-center gap-3">
          <div class="relative">
            <Button icon="pi pi-bell" text rounded class="p-button-sm" @click="showToast('info', 'Notifications', 'You have 3 new notifications')" />
            <Badge value="3" severity="danger" class="absolute -top-1 -right-1" />
          </div>
          <SplitButton :label="userName" :model="userMenuItems" class="p-button-sm" button-class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]">
            <template #default>
              <div class="flex items-center gap-2">
                <Avatar :label="userInitials" size="small" style="background-color: #00685F; color: white;" />
                <span class="hidden md:inline">{{ userName }}</span>
              </div>
            </template>
          </SplitButton>
        </div>
      </template>
    </Menubar>

    <!-- Main Content -->
    <div class="flex">
      <!-- Sidebar -->
      <div class="w-64 bg-white border-r border-gray-200 min-h-screen sticky top-0">
        <PanelMenu :model="panelMenuItems" class="border-0 p-2" />
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-6">
        <router-view />
      </main>
    </div>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
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

// ✅ Initialize dashboard data when authenticated
onMounted(async () => {
  if (authStore.isAuthenticated) {
    await dashboardStore.initialize()
  }
})

// ✅ Watch for login state changes
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

// ✅ Get user details from auth store
const userName = computed(() => authStore.userName)
const userRole = computed(() => authStore.user?.role || 'customer')

// ✅ Compute user initials from auth store user name
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

// Top Menu Items
const menuItems = ref([
  { label: 'Dashboard', icon: 'pi pi-home', to: dashboardPath.value },
  { label: 'Shop', icon: 'pi pi-shopping-bag', to: '/shop' },
  { label: 'Orders', icon: 'pi pi-shopping-cart', to: getOrdersPath() }
])

// Watch for role changes to update menuItems
watch(
  () => authStore.user?.role,
  () => {
    const path = dashboardPath.value
    const ordersPath = getOrdersPath()
    
    menuItems.value = [
      { label: 'Dashboard', icon: 'pi pi-home', to: path },
      { label: 'Shop', icon: 'pi pi-shopping-bag', to: '/shop' },
      { label: 'Orders', icon: 'pi pi-shopping-cart', to: ordersPath }
    ]
  },
  { immediate: true }
)

const showToast = (severity: string, summary: string, detail: string) => {
  toast.add({ severity, summary, detail, life: 3000 })
}

// Define handleLogout BEFORE using it in userMenuItems
const handleLogout = () => {
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

// User menu items
const userMenuItems = ref([
  { label: 'Profile', icon: 'pi pi-user', command: () => router.push(`${dashboardPath.value}/profile`) },
  { label: 'Dashboard', icon: 'pi pi-home', command: () => router.push(dashboardPath.value) },
  { separator: true },
  { label: 'Logout', icon: 'pi pi-sign-out', command: handleLogout }
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
          { label: 'All Users', icon: 'pi pi-list', to: '/dashboard/admin/users', command: () => router.push('/dashboard/admin/users') },
          { label: 'Customers', icon: 'pi pi-user', to: '/dashboard/admin/users/customers', command: () => router.push('/dashboard/admin/users/customers') },
          { label: 'Vendors', icon: 'pi pi-store', to: '/dashboard/admin/users/vendors', command: () => router.push('/dashboard/admin/users/vendors') }
        ]
      },
      {
        label: 'Products',
        icon: 'pi pi-box',
        items: [
          { label: 'All Products', icon: 'pi pi-list', to: '/dashboard/admin/products', command: () => router.push('/dashboard/admin/products') },
          { label: 'Add Product', icon: 'pi pi-plus', to: '/dashboard/admin/products/create', command: () => router.push('/dashboard/admin/products/create') }
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
          { label: 'My Products', icon: 'pi pi-list', to: '/dashboard/vendor/products', command: () => router.push('/dashboard/vendor/products') },
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
      }
    )
  }

  return items
})
</script>

<style scoped>
:deep(.p-menubar) { background: white; border-bottom: 1px solid #e5e7eb; }
:deep(.p-menubar .p-menubar-end) { display: flex; align-items: center; }
:deep(.p-panelmenu .p-panelmenu-header > a) { background: transparent; border: none; font-weight: 600; }
:deep(.p-panelmenu .p-panelmenu-content) { background: transparent; border: none; }
:deep(.p-panelmenu .p-menuitem-link) { padding: 0.75rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
:deep(.p-panelmenu .p-menuitem-link:hover) { background: #f3f4f6; }
:deep(.p-panelmenu .p-menuitem-link-active) { background: #00685F; color: white; }
:deep(.p-panelmenu .p-menuitem-link-active .p-menuitem-text) { color: white; }
:deep(.p-panelmenu .p-menuitem-link-active .p-menuitem-icon) { color: white; }
</style>