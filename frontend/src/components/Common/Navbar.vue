<script setup lang="ts">
/**
 * Navbar (storefront)
 * ---------------------------------------------------------------------------
 * Issues fixed here:
 *
 * 1. BROKEN LINKS. The mobile menu used relative targets (`to="dashboard"`,
 *    `to="dashboard/customer/orders"`). From `/product/some-slug` those
 *    resolved to `/product/dashboard/...` and 404'd. Now all absolute.
 * 2. WRONG LINKS BY ROLE. Every account link was hard-coded to the *customer*
 *    dashboard, so an admin's "My Orders" sent them to a page their own guard
 *    then bounced them out of. Links are now role-aware.
 * 3. CONFLICTING STYLES. The "Sale" link carried `hover:text-blue-600` AND
 *    `text-red-500` - a blue hover on a red link on a green-branded site.
 * 4. NO GLOBAL SEARCH. Search existed only as a sidebar box inside /shop, so
 *    finding a product from the home page took three navigations. Search is the
 *    #1 task on a storefront; it now lives in the header on every page.
 * 5. ACCESSIBILITY. The whole component had zero ARIA. Added: landmark +
 *    labels, aria-expanded/controls on both toggles, a real role="menu" with
 *    arrow-key support, Escape-to-close, focus return to the trigger, a live
 *    region for the cart count, and body-scroll locking behind the mobile menu
 *    (previously the page scrolled underneath the open panel).
 */
import { computed, onUnmounted, ref, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart.store'
import { useAuthStore } from '@/stores/auth.store'
import { useNotify } from '@/composables/useNotify'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()
const notify = useNotify()

const mobileMenuOpen = ref(false)
const isDropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const dropdownTrigger = ref<HTMLButtonElement | null>(null)
const mobilePanel = ref<HTMLElement | null>(null)
const searchQuery = ref((route.query.search as string) ?? '')

/** `accent` marks the Sale link, which needs the danger tone rather than brand. */
interface NavLink {
  to: string
  label: string
  accent?: boolean
}

const NAV_LINKS: NavLink[] = [
  { to: '/shop', label: 'Shop' },
  { to: '/new-arrivals', label: 'New Arrivals' },
  { to: '/collections', label: 'Collections' },
  { to: '/sale', label: 'Sale', accent: true },
]

const cartCount = computed(() => cartStore.itemCount || 0)
/** A 4-digit badge used to blow the pill out of the header. */
const cartBadge = computed(() => (cartCount.value > 99 ? '99+' : String(cartCount.value)))

const userInitials = computed(() => {
  const name = authStore.userName || 'User'
  return name.trim().split(/\s+/).map((n) => n[0]).join('').toUpperCase().slice(0, 2)
})

/** Role-aware account paths - previously all hard-coded to /customer. */
const accountBase = computed(() => {
  const role = authStore.user?.role
  if (role === 'admin') return '/dashboard/admin'
  if (role === 'vendor') return '/dashboard/vendor'
  return '/dashboard/customer'
})

const accountLinks = computed(() => [
  { to: accountBase.value, label: 'Dashboard', icon: 'pi pi-th-large' },
  { to: `${accountBase.value}/orders`, label: 'My Orders', icon: 'pi pi-box' },
  { to: `${accountBase.value}/profile`, label: 'Profile', icon: 'pi pi-user' },
])

// --- Search ---------------------------------------------------------------
const submitSearch = () => {
  const q = searchQuery.value.trim()
  if (!q) return
  mobileMenuOpen.value = false
  router.push({ path: '/shop', query: { search: q } })
}

// --- Dropdown (role="menu") ----------------------------------------------
const openDropdown = async () => {
  isDropdownOpen.value = true
  await nextTick()
  dropdownRef.value?.querySelector<HTMLElement>('[role="menuitem"]')?.focus()
}

const closeDropdown = (returnFocus = false) => {
  isDropdownOpen.value = false
  if (returnFocus) dropdownTrigger.value?.focus()
}

const toggleDropdown = () => (isDropdownOpen.value ? closeDropdown() : openDropdown())

/** Roving focus so the menu is operable without a mouse (WCAG 2.1.1). */
const onMenuKeydown = (event: KeyboardEvent) => {
  const items = Array.from(dropdownRef.value?.querySelectorAll<HTMLElement>('[role="menuitem"]') ?? [])
  if (!items.length) return
  const index = items.indexOf(document.activeElement as HTMLElement)

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    items[(index + 1) % items.length]?.focus()
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    items[(index - 1 + items.length) % items.length]?.focus()
  } else if (event.key === 'Home') {
    event.preventDefault()
    items[0]?.focus()
  } else if (event.key === 'End') {
    event.preventDefault()
    items[items.length - 1]?.focus()
  }
}

const handleLogout = async () => {
  closeDropdown()
  mobileMenuOpen.value = false
  await authStore.logout()
  notify.success('Signed out', 'You have been logged out successfully.')
  router.push('/login')
}

// --- Global listeners ----------------------------------------------------
const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isDropdownOpen.value = false
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key !== 'Escape') return
  if (isDropdownOpen.value) closeDropdown(true)
  if (mobileMenuOpen.value) mobileMenuOpen.value = false
}

/**
 * Body-scroll lock. Without it the storefront scrolled behind the open mobile
 * panel, so users "lost" the menu and tapped through to whatever slid under it.
 */
watch(mobileMenuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

// Close chrome on navigation so a route change never leaves a panel hanging.
watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false
  isDropdownOpen.value = false
})

document.addEventListener('click', handleClickOutside)
document.addEventListener('keydown', handleKeydown)

/**
 * The old component called cartStore.fetchCart() in onMounted. Because App.vue
 * rendered this navbar on EVERY route - including /login - that fired an
 * authenticated cart request on the login screen, which 401'd and (via the old
 * interceptor) redirected back to /login in a loop. App.vue now hides the
 * storefront chrome on auth/dashboard routes, and the cart is fetched lazily.
 */
if (!cartStore.items.length) {
  cartStore.fetchCart().catch(() => {
    /* A guest with no cart yet is not an error worth showing. */
  })
}

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-ink-200 bg-white/95 backdrop-blur">
    <nav class="page-container" aria-label="Main navigation">
      <div class="flex h-16 items-center gap-3">
        <!-- Logo -->
        <router-link
          to="/"
          class="flex shrink-0 items-center gap-2 text-lg font-bold text-ink-900 transition hover:text-brand-700"
        >
          <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white" aria-hidden="true">
            <i class="pi pi-shopping-bag text-sm" />
          </span>
          <span>My Shop</span>
        </router-link>

        <!-- Primary links (desktop) -->
        <ul class="ml-4 hidden items-center gap-1 lg:flex">
          <li v-for="link in NAV_LINKS" :key="link.to">
            <router-link
              :to="link.to"
              class="rounded-control px-3 py-2 text-sm font-medium transition"
              :class="
                link.accent
                  ? 'text-danger-600 hover:bg-danger-50 hover:text-danger-700'
                  : 'text-ink-700 hover:bg-ink-100 hover:text-brand-700'
              "
              :active-class="link.accent ? 'bg-danger-50 text-danger-700' : 'bg-brand-50 text-brand-700'"
            >
              {{ link.label }}
            </router-link>
          </li>
        </ul>

        <!-- Global search: the storefront had none above the /shop sidebar -->
        <form
          class="ml-auto hidden max-w-xs flex-1 md:block"
          role="search"
          @submit.prevent="submitSearch"
        >
          <label for="site-search" class="sr-only">Search products</label>
          <div class="relative">
            <i
              class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-ink-400"
              aria-hidden="true"
            />
            <input
              id="site-search"
              v-model="searchQuery"
              type="search"
              name="search"
              placeholder="Search products..."
              class="field-control !min-h-10 !py-2 pl-9 text-sm"
              enterkeyhint="search"
            />
          </div>
        </form>

        <div class="ml-auto flex items-center gap-1 md:ml-0">
          <!-- Cart -->
          <router-link
            to="/cart"
            class="relative grid size-11 place-items-center rounded-control text-ink-700 transition hover:bg-ink-100 hover:text-brand-700"
            :aria-label="`Shopping cart, ${cartCount} ${cartCount === 1 ? 'item' : 'items'}`"
          >
            <i class="pi pi-shopping-cart text-lg" aria-hidden="true" />
            <span
              v-if="cartCount > 0"
              class="tabular absolute right-1 top-1 grid min-w-5 place-items-center rounded-full bg-danger-600 px-1 text-[0.625rem] font-bold leading-4 text-white"
              aria-hidden="true"
            >{{ cartBadge }}</span>
          </router-link>
          <!-- Announces count changes without duplicating the visual badge -->
          <span class="sr-only" aria-live="polite">{{ cartCount }} items in cart</span>

          <!-- Account menu -->
          <div v-if="authStore.isAuthenticated" ref="dropdownRef" class="relative">
            <button
              ref="dropdownTrigger"
              type="button"
              class="flex items-center gap-2 rounded-control p-1.5 transition hover:bg-ink-100"
              aria-haspopup="menu"
              :aria-expanded="isDropdownOpen"
              aria-controls="account-menu"
              @click="toggleDropdown"
            >
              <span
                class="grid size-8 place-items-center rounded-full bg-brand-600 text-xs font-semibold text-white"
                aria-hidden="true"
              >{{ userInitials }}</span>
              <span class="hidden max-w-28 truncate text-sm font-medium text-ink-700 xl:inline">
                {{ authStore.userName }}
              </span>
              <i class="pi pi-chevron-down text-xs text-ink-500" aria-hidden="true" />
              <span class="sr-only">Account menu</span>
            </button>

            <Transition name="fade-slide">
              <div
                v-if="isDropdownOpen"
                id="account-menu"
                role="menu"
                aria-label="Account"
                class="absolute right-0 mt-2 w-60 overflow-hidden rounded-card border border-ink-200 bg-white shadow-popover"
                @keydown="onMenuKeydown"
              >
                <div class="border-b border-ink-100 px-4 py-3">
                  <p class="truncate text-sm font-semibold text-ink-900">{{ authStore.userName }}</p>
                  <p class="truncate text-xs text-ink-500">{{ authStore.userEmail }}</p>
                  <!-- Surfacing the role removes the "which panel am I in?" confusion -->
                  <span class="badge badge-brand mt-1.5">{{ authStore.user?.role || 'customer' }}</span>
                </div>

                <router-link
                  v-for="item in accountLinks"
                  :key="item.to"
                  :to="item.to"
                  role="menuitem"
                  class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50 hover:text-brand-700"
                  @click="closeDropdown()"
                >
                  <i :class="item.icon" class="w-4 text-center text-ink-500" aria-hidden="true" />
                  {{ item.label }}
                </router-link>

                <div class="border-t border-ink-100">
                  <button
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-medium text-danger-700 transition hover:bg-danger-50"
                    @click="handleLogout"
                  >
                    <i class="pi pi-sign-out w-4 text-center" aria-hidden="true" />
                    Sign out
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Guest actions -->
          <div v-else class="hidden items-center gap-2 sm:flex">
            <router-link to="/login" class="btn btn-ghost btn-sm">Sign in</router-link>
            <router-link to="/register" class="btn btn-primary btn-sm">Sign up</router-link>
          </div>

          <!-- Mobile toggle -->
          <button
            type="button"
            class="grid size-11 place-items-center rounded-control text-ink-700 transition hover:bg-ink-100 lg:hidden"
            :aria-expanded="mobileMenuOpen"
            aria-controls="mobile-menu"
            :aria-label="mobileMenuOpen ? 'Close menu' : 'Open menu'"
            @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <i :class="mobileMenuOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-lg" aria-hidden="true" />
          </button>
        </div>
      </div>

      <!-- Mobile panel -->
      <Transition name="fade-slide">
        <div
          v-if="mobileMenuOpen"
          id="mobile-menu"
          ref="mobilePanel"
          class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-ink-200 py-4 lg:hidden"
        >
          <!-- Search first: it is the most-used control on a small screen -->
          <form role="search" class="mb-4 md:hidden" @submit.prevent="submitSearch">
            <label for="site-search-mobile" class="sr-only">Search products</label>
            <div class="relative">
              <i
                class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-ink-400"
                aria-hidden="true"
              />
              <input
                id="site-search-mobile"
                v-model="searchQuery"
                type="search"
                placeholder="Search products..."
                class="field-control pl-9"
                enterkeyhint="search"
              />
            </div>
          </form>

          <ul class="space-y-1">
            <li v-for="link in NAV_LINKS" :key="link.to">
              <router-link
                :to="link.to"
                class="block rounded-control px-3 py-3 text-sm font-medium transition"
                :class="link.accent ? 'text-danger-600 hover:bg-danger-50' : 'text-ink-700 hover:bg-ink-100'"
                active-class="bg-brand-50 text-brand-700"
              >
                {{ link.label }}
              </router-link>
            </li>
          </ul>

          <div class="mt-4 border-t border-ink-200 pt-4">
            <template v-if="authStore.isAuthenticated">
              <div class="mb-3 flex items-center gap-3 px-3">
                <span
                  class="grid size-10 place-items-center rounded-full bg-brand-600 text-sm font-semibold text-white"
                  aria-hidden="true"
                >{{ userInitials }}</span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-ink-900">{{ authStore.userName }}</p>
                  <p class="truncate text-xs text-ink-500">{{ authStore.userEmail }}</p>
                </div>
              </div>

              <ul class="space-y-1">
                <li v-for="item in accountLinks" :key="item.to">
                  <router-link
                    :to="item.to"
                    class="flex items-center gap-3 rounded-control px-3 py-3 text-sm text-ink-700 transition hover:bg-ink-100"
                  >
                    <i :class="item.icon" class="w-4 text-center text-ink-500" aria-hidden="true" />
                    {{ item.label }}
                  </router-link>
                </li>
              </ul>

              <button
                type="button"
                class="mt-1 flex w-full items-center gap-3 rounded-control px-3 py-3 text-left text-sm font-medium text-danger-700 transition hover:bg-danger-50"
                @click="handleLogout"
              >
                <i class="pi pi-sign-out w-4 text-center" aria-hidden="true" />
                Sign out
              </button>
            </template>

            <div v-else class="flex flex-col gap-2 px-1">
              <router-link to="/login" class="btn btn-secondary btn-block">Sign in</router-link>
              <router-link to="/register" class="btn btn-primary btn-block">Create account</router-link>
            </div>
          </div>
        </div>
      </Transition>
    </nav>
  </header>
</template>
