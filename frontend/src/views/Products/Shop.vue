<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">
          {{ pageTitle }}
        </h1>
        <p class="text-gray-600 mt-2">
          Showing {{ products.length }} of {{ totalProducts }} products
        </p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Left Sidebar - Filters -->
        <aside class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-20">
            <!-- Search Filter -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Search</h3>
              <input 
                v-model="filters.search"
                @input="handleFilterChange"
                type="text" 
                placeholder="Search products..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F] transition"
              />
            </div>

            <!-- Price Range Filter -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Price Range</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm text-gray-600 mb-1">Min Price</label>
                  <input 
                    v-model.number="filters.min_price"
                    @input="handleFilterChange"
                    type="number" 
                    placeholder="0" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]"
                  />
                </div>
                <div>
                  <label class="block text-sm text-gray-600 mb-1">Max Price</label>
                  <input 
                    v-model.number="filters.max_price"
                    @input="handleFilterChange"
                    type="number" 
                    placeholder="10000" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]"
                  />
                </div>
              </div>
            </div>

            <!-- Stock Status Filter -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Stock Status</h3>
              <label class="flex items-center cursor-pointer">
                <input 
                  v-model="filters.in_stock"
                  @change="handleFilterChange"
                  type="checkbox" 
                  class="w-4 h-4 text-[#00685F] border-gray-300 rounded focus:ring-[#00685F]"
                />
                <span class="ml-3 text-gray-700">In Stock Only</span>
              </label>
            </div>

            <!-- Featured Filter -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Featured</h3>
              <label class="flex items-center cursor-pointer">
                <input 
                  v-model="filters.featured"
                  @change="handleFilterChange"
                  type="checkbox" 
                  class="w-4 h-4 text-[#00685F] border-gray-300 rounded focus:ring-[#00685F]"
                />
                <span class="ml-3 text-gray-700">Featured Products</span>
              </label>
            </div>

            <!-- Sort Options -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Sort By</h3>
              <select 
                v-model="filters.sort_by"
                @change="handleFilterChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]"
              >
                <option value="newest">Newest</option>
                <option value="price">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="popularity">Most Popular</option>
                <option value="rating">Highest Rated</option>
                <option value="name">Name: A to Z</option>
              </select>
            </div>

            <!-- Clear Filters Button -->
            <button 
              @click="clearFilters"
              class="w-full bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition font-medium"
            >
              Clear Filters
            </button>
          </div>
        </aside>

        <!-- Right Content - Products Grid -->
        <div class="lg:col-span-3">
          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center items-center py-16">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <p class="text-red-700 font-medium mb-4">{{ error }}</p>
            <button 
              @click="fetchProducts"
              class="bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition"
            >
              Try Again
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="products.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Products Found</h3>
            <p class="text-gray-600 mb-6">Try adjusting your filters or search terms.</p>
            <button 
              @click="clearFilters"
              class="bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition"
            >
              Clear Filters
            </button>
          </div>

          <!-- Products Grid -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <ProductCard 
              v-for="product in products"
              :key="product.id"
              :product="product"
              @add-to-cart="handleAddToCart"
              @toggle-wishlist="handleToggleWishlist"
            />
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
              <!-- Previous Button -->
              <button 
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                ← Previous
              </button>

              <!-- Page Numbers -->
              <div class="flex items-center gap-2">
                <button 
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'px-3 py-2 rounded-lg transition',
                    page === currentPage
                      ? 'bg-[#00685F] text-white'
                      : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
              </div>

              <!-- Next Button -->
              <button 
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                Next →
              </button>
            </div>

            <!-- Page Info -->
            <div class="text-center mt-4 text-gray-600 text-sm">
              Page {{ currentPage }} of {{ totalPages }} 
              <span v-if="totalProducts > 0">
                • Showing {{ startItem }} - {{ endItem }} of {{ totalProducts }} products
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '@/components/common/ProductCard.vue'
import { productApi } from '@/api/productApi'
import { useCartStore } from '@/stores/cart.store'
import type { Product } from '@/types/models/product.types'
import toast from '../../utils/notification/toast'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

// State
const products = ref<Product[]>([])
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalProducts = ref(0)
const perPage = ref(15)

// Filters
const filters = ref({
  search: (route.query.search as string) || '',
  min_price: route.query.min_price ? Number(route.query.min_price) : null,
  max_price: route.query.max_price ? Number(route.query.max_price) : null,
  in_stock: route.query.in_stock === 'true',
  featured: route.query.featured === 'true',
  sort_by: (route.query.sort_by as string) || 'newest',
  sort_order: 'desc',
  category_id: route.query.category_id ? Number(route.query.category_id) : null,
})

// Computed
const pageTitle = computed(() => {
  if (filters.value.category_id) {
    return `Category Products`
  }
  if (route.name === 'new-arrivals') {
    return 'New Arrivals'
  }
  if (route.name === 'sale') {
    return 'Sale Items'
  }
  if (route.name === 'collections') {
    return 'Collections'
  }
  return 'Shop All Products'
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const startItem = computed(() => (currentPage.value - 1) * perPage.value + 1)
const endItem = computed(() => Math.min(currentPage.value * perPage.value, totalProducts.value))

// Methods
const convertSortBy = (sortBy: string | undefined): { sort_by: string; sort_order: string } => {
  const sortMap: Record<string, { sort_by: string; sort_order: string }> = {
    'newest': { sort_by: 'created_at', sort_order: 'desc' },
    'price': { sort_by: 'price', sort_order: 'asc' },
    'price_desc': { sort_by: 'price', sort_order: 'desc' },
    'popularity': { sort_by: 'popularity', sort_order: 'desc' },
    'rating': { sort_by: 'rating', sort_order: 'desc' },
    'name': { sort_by: 'name', sort_order: 'asc' },
  }
  const key = sortBy || 'newest'
  return sortMap[key] !== undefined ? sortMap[key] : sortMap['newest'] as { sort_by: string; sort_order: string; }
}

const fetchProducts = async () => {
  try {
    loading.value = true
    error.value = ''

    const hasFilters = filters.value.search || 
                      filters.value.min_price || 
                      filters.value.max_price || 
                      filters.value.in_stock || 
                      filters.value.featured || 
                      filters.value.category_id 

    let response
    if (!hasFilters) {
      response = await productApi.getAll(currentPage.value, perPage.value)
    } else {
      const sortConfig = convertSortBy(filters.value.sort_by)
      response = await productApi.search({
        search: filters.value.search,
        min_price: filters.value.min_price,
        max_price: filters.value.max_price,
        in_stock: filters.value.in_stock ? 1 : 0,
        featured: filters.value.featured ? 1 : 0,
        category_id: filters.value.category_id,
        sort_by: sortConfig.sort_by,
        sort_order: sortConfig.sort_order,
        page: currentPage.value,
      }, perPage.value)
    }

    products.value = response.data
    totalProducts.value = response.pagination.total
    totalPages.value = response.pagination.total_pages
    currentPage.value = response.pagination.current_page
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred'
    products.value = []
  } finally {
    loading.value = false
  }
}

const handleFilterChange = () => {
  currentPage.value = 1
  updateRouteQuery()
  fetchProducts()
}

const clearFilters = () => {
  filters.value = {
    search: '',
    min_price: null,
    max_price: null,
    in_stock: false,
    featured: false,
    sort_by: 'newest',
    sort_order: 'desc',
    category_id: route.query.category_id ? Number(route.query.category_id) : null,
  }
  currentPage.value = 1
  updateRouteQuery()
  fetchProducts()
}

const updateRouteQuery = () => {
  const query: any = {}

  if (filters.value.search) query.search = filters.value.search
  if (filters.value.min_price) query.min_price = filters.value.min_price
  if (filters.value.max_price) query.max_price = filters.value.max_price
  if (filters.value.in_stock) query.in_stock = 'true'
  if (filters.value.featured) query.featured = 'true'
  if (filters.value.sort_by !== 'newest') query.sort_by = filters.value.sort_by
  if (filters.value.category_id) query.category_id = filters.value.category_id

  router.push({ path: route.path, query })
}

const goToPage = (page: number) => {
  currentPage.value = page
  fetchProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1)
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const handleAddToCart = async (product: Product) => {
  try {
    const result = await cartStore.addItem(product.id, 1, null)
    if (result.success) {
      toast.success('Item added to cart')
    } else {
      alert(`Error: ${result.error || 'Failed to add to cart'}`)
    }
  } catch (err) {
    alert('Failed to add to cart')
  }
}

const handleToggleWishlist = (product: Product) => {
  // Emit to wishlist store or parent component
  console.log('Toggle wishlist:', product)
  // TODO: Connect to wishlist store
}

// Watchers
watch(
  () => route.query,
  () => {
    if (route.query.category_id) {
      filters.value.category_id = Number(route.query.category_id)
    }
    if (route.query.search) {
      filters.value.search = route.query.search as string
    }
    currentPage.value = 1
    fetchProducts()
  }
)

// Lifecycle
onMounted(() => {
  // Set category from route if present
  if (route.query.category_id) {
    filters.value.category_id = Number(route.query.category_id)
  }
  
  fetchProducts()
})
</script>

<style scoped>
/* Custom scrollbar for filter sidebar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
