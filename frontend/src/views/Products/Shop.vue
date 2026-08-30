<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '@/components/common/ProductCard.vue'
import { productApi } from '@/api/productApi'
import { useCartStore } from '@/stores/cart.store'
import { useDebounceFn } from '@/composables/useDebounce'
import { useNotify } from '@/composables/useNotify'
import type { Product } from '@/types/models/product.types'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const notify = useNotify()

const products = ref<Product[]>([])
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalProducts = ref(0)
const perPage = ref(15)
const filtersOpen = ref(false)

const SORT_OPTIONS = [
  { value: 'newest', label: 'Newest first' },
  { value: 'price', label: 'Price: low to high' },
  { value: 'price_desc', label: 'Price: high to low' },
  { value: 'popularity', label: 'Most popular' },
  { value: 'rating', label: 'Highest rated' },
  { value: 'name', label: 'Name: A to Z' },
] as const

const filters = ref({
  search: (route.query.search as string) || '',
  min_price: route.query.min_price ? Number(route.query.min_price) : null as number | null,
  max_price: route.query.max_price ? Number(route.query.max_price) : null as number | null,
  in_stock: route.query.in_stock === 'true',
  featured: route.query.featured === 'true',
  sort_by: (route.query.sort_by as string) || 'newest',
  category_id: route.query.category_id ? Number(route.query.category_id) : null as number | null,
})

const pageTitle = computed(() => {
  if (route.name === 'new-arrivals') return 'New Arrivals'
  if (route.name === 'sale') return 'Sale Items'
  if (route.name === 'collections') return 'Collections'
  if (filters.value.category_id) return 'Category Products'
  return 'Shop All Products'
})

/** Validate the range before we even ask the server (WCAG 3.3.1/3.3.3). */
const priceError = computed(() => {
  const { min_price: min, max_price: max } = filters.value
  if (min !== null && min < 0) return 'Minimum price cannot be negative.'
  if (min !== null && max !== null && min > max) {
    return 'Minimum price is higher than the maximum, so nothing can match.'
  }
  return ''
})

/** Removable summary chips - the user can finally see what is filtering. */
const activeChips = computed(() => {
  const chips: { key: string; label: string }[] = []
  const f = filters.value
  if (f.search) chips.push({ key: 'search', label: `"${f.search}"` })
  if (f.min_price !== null) chips.push({ key: 'min_price', label: `Min $${f.min_price}` })
  if (f.max_price !== null) chips.push({ key: 'max_price', label: `Max $${f.max_price}` })
  if (f.in_stock) chips.push({ key: 'in_stock', label: 'In stock only' })
  if (f.featured) chips.push({ key: 'featured', label: 'Featured' })
  return chips
})
const activeCount = computed(() => activeChips.value.length)

const visiblePages = computed(() => {
  const pages: (number | 'gap')[] = []
  const total = totalPages.value
  const current = currentPage.value
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
    return pages
  }
  // First, last and a window around the current page - the old fixed 5-button
  // window hid page 1 entirely once you were past page 4.
  pages.push(1)
  if (current > 3) pages.push('gap')
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i)
  if (current < total - 2) pages.push('gap')
  pages.push(total)
  return pages
})

const startItem = computed(() => (totalProducts.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1))
const endItem = computed(() => Math.min(currentPage.value * perPage.value, totalProducts.value))

const SORT_MAP: Record<string, { sort_by: string; sort_order: string }> = {
  newest: { sort_by: 'created_at', sort_order: 'desc' },
  price: { sort_by: 'price', sort_order: 'asc' },
  price_desc: { sort_by: 'price', sort_order: 'desc' },
  popularity: { sort_by: 'popularity', sort_order: 'desc' },
  rating: { sort_by: 'rating', sort_order: 'desc' },
  name: { sort_by: 'name', sort_order: 'asc' },
}

const fetchProducts = async () => {
  if (priceError.value) return

  loading.value = true
  error.value = ''

  try {
    const f = filters.value
    const hasFilters = Boolean(
      f.search || f.min_price || f.max_price || f.in_stock || f.featured || f.category_id,
    )

    const response = hasFilters
      ? await productApi.search(
          {
            search: f.search,
            min_price: f.min_price,
            max_price: f.max_price,
            in_stock: f.in_stock ? 1 : 0,
            featured: f.featured ? 1 : 0,
            category_id: f.category_id,
            ...(SORT_MAP[f.sort_by] ?? SORT_MAP.newest),
            page: currentPage.value,
          },
          perPage.value,
        )
      : await productApi.getAll(currentPage.value, perPage.value)

    products.value = response.data
    totalProducts.value = response.pagination.total
    totalPages.value = response.pagination.total_pages
    currentPage.value = response.pagination.current_page
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'We could not load products right now.'
    products.value = []
  } finally {
    loading.value = false
  }
}

/**
 * `syncing` guards the route watcher so our own router.push (from changing a
 * filter) does not trigger a second fetch. This was the double-request bug.
 */
let syncing = false
const applyFilters = async () => {
  currentPage.value = 1
  const query: Record<string, string | number> = {}
  const f = filters.value
  if (f.search) query.search = f.search
  if (f.min_price !== null) query.min_price = f.min_price
  if (f.max_price !== null) query.max_price = f.max_price
  if (f.in_stock) query.in_stock = 'true'
  if (f.featured) query.featured = 'true'
  if (f.sort_by !== 'newest') query.sort_by = f.sort_by
  if (f.category_id) query.category_id = f.category_id

  syncing = true
  await router.replace({ path: route.path, query })
  syncing = false
  await fetchProducts()
}

/** 400ms: long enough to finish a word, short enough to feel live. */
const debouncedApply = useDebounceFn(applyFilters, 400)

const removeChip = (key: string) => {
  const f = filters.value as Record<string, unknown>
  f[key] = key === 'in_stock' || key === 'featured' ? false : key === 'search' ? '' : null
  applyFilters()
}

const clearFilters = () => {
  filters.value = {
    search: '',
    min_price: null,
    max_price: null,
    in_stock: false,
    featured: false,
    sort_by: 'newest',
    category_id: route.query.category_id ? Number(route.query.category_id) : null,
  }
  applyFilters()
}

const goToPage = (page: number) => {
  if (page === currentPage.value || page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleAddToCart = async (product: Product) => {
  const result = await cartStore.addItem(product.id, 1, null)
  if (result.success) {
    notify.success('Added to cart', product.name)
  } else {
    // Was a native blocking alert() - an OS dialog on a shopping page.
    notify.error('Could not add to cart', result.error || 'Please try again.')
  }
}

// Only react to navigation that did NOT originate from applyFilters().
watch(
  () => route.query,
  () => {
    if (syncing) return
    filters.value.category_id = route.query.category_id ? Number(route.query.category_id) : null
    filters.value.search = (route.query.search as string) || ''
    currentPage.value = 1
    fetchProducts()
  },
)

onMounted(fetchProducts)
</script>

<template>
  <div class="min-h-screen bg-ink-50">
    <!-- Page header -->
    <div class="border-b border-ink-200 bg-white">
      <div class="page-container py-6">
        <nav aria-label="Breadcrumb" class="mb-2 text-sm">
          <ol class="flex items-center gap-2 text-ink-500">
            <li><router-link to="/" class="link">Home</router-link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-ink-700">{{ pageTitle }}</li>
          </ol>
        </nav>
        <h1 class="text-2xl font-bold text-ink-900 sm:text-3xl">{{ pageTitle }}</h1>
        <p class="mt-1 text-sm text-ink-600">
          <span v-if="loading">Loading products...</span>
          <span v-else-if="totalProducts">
            Showing {{ startItem }}-{{ endItem }} of {{ totalProducts }} products
          </span>
          <span v-else>No products to show</span>
        </p>
      </div>
    </div>

    <div class="page-container py-6 lg:py-8">
      <!-- Mobile toolbar: filters are behind a button instead of pushing the
           product grid 600px down the page -->
      <div class="mb-4 flex items-center gap-2 lg:hidden">
        <button
          type="button"
          class="btn btn-secondary btn-sm flex-1"
          aria-controls="filter-panel"
          :aria-expanded="filtersOpen"
          @click="filtersOpen = true"
        >
          <i class="pi pi-filter" aria-hidden="true" />
          Filters
          <span v-if="activeCount" class="badge badge-brand ml-1">{{ activeCount }}</span>
        </button>

        <div class="flex-1">
          <label for="sort-mobile" class="sr-only">Sort products by</label>
          <select
            id="sort-mobile"
            v-model="filters.sort_by"
            class="field-control !min-h-9 !py-1.5 text-sm"
            @change="applyFilters"
          >
            <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Active filter chips -->
      <div v-if="activeCount" class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Filters</span>
        <button
          v-for="chip in activeChips"
          :key="chip.key"
          type="button"
          class="badge badge-brand gap-1.5 py-1 pl-2.5 pr-1.5 transition hover:bg-brand-100"
          @click="removeChip(chip.key)"
        >
          {{ chip.label }}
          <i class="pi pi-times text-[0.6rem]" aria-hidden="true" />
          <span class="sr-only">Remove filter</span>
        </button>
        <button type="button" class="text-xs font-semibold text-danger-700 hover:underline" @click="clearFilters">
          Clear all
        </button>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:gap-8">
        <!-- Filter panel: static sidebar on desktop, bottom sheet on mobile -->
        <aside
          id="filter-panel"
          class="lg:col-span-1"
          :class="
            filtersOpen
              ? 'fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] overflow-y-auto rounded-t-2xl bg-white p-5 shadow-popover lg:static lg:z-auto lg:max-h-none lg:rounded-none lg:p-0 lg:shadow-none'
              : 'hidden lg:block'
          "
          :aria-label="'Product filters'"
        >
          <div class="mb-4 flex items-center justify-between lg:hidden">
            <h2 class="text-base font-semibold text-ink-900">Filters</h2>
            <button
              type="button"
              class="btn btn-ghost btn-icon btn-sm"
              aria-label="Close filters"
              @click="filtersOpen = false"
            >
              <i class="pi pi-times" aria-hidden="true" />
            </button>
          </div>

          <div class="card space-y-5 p-5 lg:sticky lg:top-20">
            <!-- Search -->
            <div>
              <label for="filter-search" class="field-label">Search</label>
              <div class="relative">
                <i
                  class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-ink-400"
                  aria-hidden="true"
                />
                <input
                  id="filter-search"
                  v-model="filters.search"
                  type="search"
                  placeholder="Search products..."
                  class="field-control pl-9"
                  @input="debouncedApply"
                />
              </div>
            </div>

            <!-- Price -->
            <fieldset class="border-t border-ink-200 pt-5">
              <legend class="field-label">Price range</legend>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label for="filter-min" class="field-hint mb-1 block">Min</label>
                  <input
                    id="filter-min"
                    v-model.number="filters.min_price"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    placeholder="0"
                    class="field-control"
                    :aria-invalid="priceError ? 'true' : undefined"
                    aria-describedby="price-error"
                    @change="debouncedApply"
                  />
                </div>
                <div>
                  <label for="filter-max" class="field-hint mb-1 block">Max</label>
                  <input
                    id="filter-max"
                    v-model.number="filters.max_price"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    placeholder="10000"
                    class="field-control"
                    :aria-invalid="priceError ? 'true' : undefined"
                    aria-describedby="price-error"
                    @change="debouncedApply"
                  />
                </div>
              </div>
              <!-- Previously an invalid range just returned an empty grid -->
              <p v-if="priceError" id="price-error" class="field-error" role="alert">
                <i class="pi pi-exclamation-circle mt-px shrink-0 text-[0.9em]" aria-hidden="true" />
                <span>{{ priceError }}</span>
              </p>
            </fieldset>

            <!-- Availability -->
            <fieldset class="space-y-3 border-t border-ink-200 pt-5">
              <legend class="field-label">Availability</legend>
              <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                <input
                  v-model="filters.in_stock"
                  type="checkbox"
                  class="size-4 rounded border-ink-300 text-brand-600"
                  @change="applyFilters"
                />
                In stock only
              </label>
              <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                <input
                  v-model="filters.featured"
                  type="checkbox"
                  class="size-4 rounded border-ink-300 text-brand-600"
                  @change="applyFilters"
                />
                Featured products
              </label>
            </fieldset>

            <!-- Sort (desktop) -->
            <div class="hidden border-t border-ink-200 pt-5 lg:block">
              <label for="filter-sort" class="field-label">Sort by</label>
              <select id="filter-sort" v-model="filters.sort_by" class="field-control" @change="applyFilters">
                <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <button type="button" class="btn btn-secondary btn-block" @click="clearFilters">
              Clear filters
            </button>
          </div>

          <button
            type="button"
            class="btn btn-primary btn-block mt-4 lg:hidden"
            @click="filtersOpen = false"
          >
            Show {{ totalProducts }} results
          </button>
        </aside>

        <!-- Scrim for the mobile sheet -->
        <div
          v-if="filtersOpen"
          class="fixed inset-0 z-40 bg-ink-900/40 lg:hidden"
          @click="filtersOpen = false"
        />

        <!-- Results -->
        <div class="lg:col-span-3">
          <!-- Announces result changes; the grid updated silently before -->
          <p class="sr-only" role="status" aria-live="polite">
            {{ loading ? 'Loading products' : `${totalProducts} products found` }}
          </p>

          <!-- Skeleton grid keeps the page height stable instead of collapsing
               to a lone spinner on every filter change -->
          <div v-if="loading" class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3" aria-hidden="true">
            <div v-for="n in 6" :key="n" class="card overflow-hidden">
              <div class="skeleton aspect-[4/5] rounded-none"></div>
              <div class="space-y-2 p-4">
                <div class="skeleton h-4 w-full"></div>
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton h-3 w-24"></div>
                <div class="skeleton h-6 w-20"></div>
                <div class="skeleton h-9 w-full"></div>
              </div>
            </div>
          </div>

          <!-- Error state: says what happened and offers the one useful action -->
          <div v-else-if="error" class="card p-10 text-center">
            <div class="mx-auto mb-4 grid size-12 place-items-center rounded-full bg-danger-50">
              <i class="pi pi-exclamation-triangle text-xl text-danger-600" aria-hidden="true" />
            </div>
            <h2 class="text-lg font-semibold text-ink-900">We could not load products</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-600">{{ error }}</p>
            <button type="button" class="btn btn-primary mt-6" @click="fetchProducts">
              <i class="pi pi-refresh" aria-hidden="true" />
              Try again
            </button>
          </div>

          <!-- Empty state that distinguishes "no matches" from "no catalogue" -->
          <div v-else-if="products.length === 0" class="card p-10 text-center">
            <div class="mx-auto mb-4 grid size-12 place-items-center rounded-full bg-ink-100">
              <i class="pi pi-search text-xl text-ink-500" aria-hidden="true" />
            </div>
            <h2 class="text-lg font-semibold text-ink-900">No products match your filters</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-600">
              <template v-if="activeCount">
                Try removing a filter or widening your price range.
              </template>
              <template v-else>
                There are no products in this collection yet. Check back soon.
              </template>
            </p>
            <button v-if="activeCount" type="button" class="btn btn-primary mt-6" @click="clearFilters">
              Clear all filters
            </button>
            <router-link v-else to="/shop" class="btn btn-primary mt-6">Browse all products</router-link>
          </div>

          <!-- 2 columns on phones (was 1: a single card per screen forced
               endless scrolling), 2 on tablet, 3 on desktop -->
          <div v-else class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
            <ProductCard
              v-for="product in products"
              :key="product.id"
              :product="product"
              @add-to-cart="handleAddToCart"
            />
          </div>

          <!-- Pagination -->
          <nav v-if="totalPages > 1 && !loading" class="card mt-6 p-4" aria-label="Product pages">
            <div class="flex items-center justify-between gap-2">
              <button
                type="button"
                class="btn btn-secondary btn-sm"
                :disabled="currentPage === 1"
                @click="goToPage(currentPage - 1)"
              >
                <i class="pi pi-chevron-left text-xs" aria-hidden="true" />
                <span class="hidden sm:inline">Previous</span>
                <span class="sr-only sm:hidden">Previous page</span>
              </button>

              <ul class="flex items-center gap-1">
                <li v-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                  <span v-if="page === 'gap'" class="px-1.5 text-ink-400" aria-hidden="true">...</span>
                  <button
                    v-else
                    type="button"
                    class="btn btn-sm min-w-9"
                    :class="page === currentPage ? 'btn-primary' : 'btn-ghost'"
                    :aria-current="page === currentPage ? 'page' : undefined"
                    :aria-label="`Page ${page}`"
                    @click="goToPage(page as number)"
                  >
                    {{ page }}
                  </button>
                </li>
              </ul>

              <button
                type="button"
                class="btn btn-secondary btn-sm"
                :disabled="currentPage === totalPages"
                @click="goToPage(currentPage + 1)"
              >
                <span class="hidden sm:inline">Next</span>
                <span class="sr-only sm:hidden">Next page</span>
                <i class="pi pi-chevron-right text-xs" aria-hidden="true" />
              </button>
            </div>

            <p class="mt-3 text-center text-xs text-ink-500">
              Page {{ currentPage }} of {{ totalPages }}
            </p>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>
