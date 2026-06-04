<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center space-x-2 text-sm">
          <router-link to="/" class="text-[#00685F] hover:text-[#004F45]">Home</router-link>
          <span class="text-gray-400">/</span>
          <router-link to="/shop" class="text-[#00685F] hover:text-[#004F45]">Shop</router-link>
          <span class="text-gray-400">/</span>
          <span class="text-gray-600">{{ product?.name }}</span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-700 font-medium mb-4">{{ error }}</p>
        <button
          @click="fetchProduct"
          class="bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition"
        >
          Try Again
        </button>
      </div>
    </div>

    <!-- Product Details -->
    <div v-else-if="product" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Product Images -->
        <div class="space-y-4">
          <!-- Main Image -->
          <div class="bg-white rounded-lg overflow-hidden shadow-sm">
            <img
              v-if="product.media?.image"
              :src="product.media.image"
              :alt="product.name"
              class="w-full h-96 object-cover"
              @error="handleImageError"
            />
            <div v-else class="w-full h-96 bg-gray-100 flex items-center justify-center">
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>

          <!-- Thumbnail Gallery -->
          <div v-if="product.images && product.images.length > 0" class="flex gap-3 overflow-x-auto pb-2">
            <button
              v-for="(image, index) in product.images"
              :key="index"
              @click="selectedImageIndex = index"
              :class="[
                'shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition',
                selectedImageIndex === index ? 'border-[#00685F]' : 'border-gray-200'
              ]"
            >
              <img :src="image.thumbnail" :alt="`Product ${index + 1}`" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Product Info -->
        <div class="space-y-6">
          <!-- Title and Price -->
          <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">{{ product.name }}</h1>
            <div class="flex items-center gap-4 mb-4">
              <!-- Price -->
              <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-[#00685F]">{{ product.price.formatted }}</span>
                <span v-if="product.price.compare?.value" class="text-lg text-gray-500 line-through">
                  {{ product.price.compare.formatted }}
                </span>
              </div>
              <!-- Discount Badge -->
              <div v-if="product.price.is_on_sale" class="bg-red-100 text-red-800 px-3 py-1 rounded-lg text-sm font-semibold">
                Save {{ product.price.discount }}%
              </div>
            </div>

            <!-- Rating -->
            <div class="flex items-center gap-2 text-sm">
              <div class="flex text-yellow-400">
                <span v-for="i in 5" :key="i" class="text-lg">
                  {{ i <= Math.round(parseFloat(product.stats.average_rating)) ? '★' : '☆' }}
                </span>
              </div>
              <span class="text-gray-600">({{ product.stats.review_count }} reviews)</span>
              <span class="text-gray-600">• {{ product.stats.sold_count }} sold</span>
            </div>
          </div>

          <!-- Stock Status -->
          <div :class="[
            'p-3 rounded-lg text-sm font-medium',
            product.inventory.is_in_stock
              ? 'bg-green-50 text-green-700'
              : 'bg-red-50 text-red-700'
          ]">
            {{ product.inventory.status_label }}
          </div>

          <!-- Description -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
            <p class="text-gray-600 leading-relaxed">{{ product.description }}</p>
          </div>

          <!-- Product Details -->
          <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg text-sm">
            <div>
              <span class="text-gray-600">SKU:</span>
              <p class="font-semibold text-gray-900">{{ product.sku }}</p>
            </div>
            <div>
              <span class="text-gray-600">Category:</span>
              <p class="font-semibold text-gray-900">{{ product.category?.name }}</p>
            </div>
            <div>
              <span class="text-gray-600">Stock:</span>
              <p class="font-semibold text-gray-900">{{ product.inventory.total_stock }}</p>
            </div>
            <div v-if="product.flags.free_shipping">
              <span class="text-gray-600">Shipping:</span>
              <p class="font-semibold text-green-600">Free Shipping</p>
            </div>
          </div>

          <!-- Variations -->
          <div v-if="product.flags.has_variations && product.variations && product.variations.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Options</h3>
            <select
              v-model="selectedVariation"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]"
            >
              <option value="">Select a variation</option>
              <option v-for="variation in product.variations" :key="variation.id" :value="variation.id">
                {{ variation.name }} - {{ formatPrice(variation.price) }}
              </option>
            </select>
          </div>

          <!-- Quantity Selector -->
          <div class="flex items-center gap-4">
            <label class="text-gray-700 font-medium">Quantity:</label>
            <div class="flex items-center border border-gray-300 rounded-lg">
              <button
                @click="quantity = Math.max(1, quantity - 1)"
                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition"
              >
                −
              </button>
              <input
                v-model.number="quantity"
                type="number"
                min="1"
                class="w-16 text-center border-l border-r border-gray-300 py-2 focus:outline-none"
              />
              <button
                @click="quantity = quantity + 1"
                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition"
              >
                +
              </button>
            </div>
          </div>

          <!-- Add to Cart Button -->
          <button
            @click="handleAddToCart"
            :disabled="!product.inventory.is_in_stock || loading"
            :class="[
              'w-full py-3 rounded-lg font-semibold text-white transition text-lg',
              product.inventory.is_in_stock && !loading
                ? 'bg-[#00685F] hover:bg-[#004F45]'
                : 'bg-gray-400 cursor-not-allowed'
            ]"
          >
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              Adding...
            </span>
            <span v-else>Add to Cart</span>
          </button>

          <!-- Add to Wishlist -->
          <button
            class="w-full py-2 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:border-red-500 hover:text-red-500 transition"
          >
            ♥ Add to Wishlist
          </button>

          <!-- Share -->
          <div class="flex gap-2 pt-4 border-t border-gray-200">
            <span class="text-gray-600 font-medium">Share:</span>
            <button class="text-gray-600 hover:text-[#00685F] transition">Facebook</button>
            <button class="text-gray-600 hover:text-[#00685F] transition">Twitter</button>
            <button class="text-gray-600 hover:text-[#00685F] transition">Copy Link</button>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="relatedProducts.length > 0">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="relatedProduct in relatedProducts"
            :key="relatedProduct.id"
            @click="goToProduct(relatedProduct.slug)"
            class="bg-white rounded-lg shadow-sm hover:shadow-lg transition cursor-pointer overflow-hidden"
          >
            <img
              v-if="relatedProduct.media?.image"
              :src="relatedProduct.media.image"
              :alt="relatedProduct.name"
              class="w-full h-48 object-cover"
            />
            <div class="p-4">
              <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ relatedProduct.name }}</h3>
              <p class="text-[#00685F] font-bold">{{ relatedProduct.price.formatted }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { productApi } from '@/api/productApi'
import { useCartStore } from '@/stores/cart.store'
import type { Product } from '@/types/models/product.types'
import toast from '../../utils/notification/toast'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

// State
const product = ref<Product | null>(null)
const relatedProducts = ref<Product[]>([])
const loading = ref(false)
const error = ref('')
const quantity = ref(1)
const selectedVariation = ref<number | string>('')
const selectedImageIndex = ref(0)

// Methods
const fetchProduct = async () => {
  try {
    loading.value = true
    error.value = ''
    const slug = route.params.slug as string
    product.value = await productApi.getBySlug(slug)
    
    // Reset quantity and variations
    quantity.value = 1
    selectedVariation.value = ''
    
    // Fetch related products
    if (product.value?.id) {
      try {
        relatedProducts.value = await productApi.getRelated(product.value.id, 4)
      } catch (e) {
        console.error('Failed to fetch related products:', e)
      }
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load product'
  } finally {
    loading.value = false
  }
}

const handleAddToCart = async () => {
  if (!product.value) return

  try {
    loading.value = true
    const variationId = selectedVariation.value ? Number(selectedVariation.value) : null
    
    const result = await cartStore.addItem(product.value.id, quantity.value, variationId)
    
    if (result.success) {
      // Show success message (you can replace this with a toast notification)
     toast.success('Item added to cart')
      quantity.value = 1
      selectedVariation.value = ''
    } else {
      alert(`Error: ${result.error || 'Failed to add to cart'}`)
    }
  } catch (err) {
    alert('Failed to add to cart')
  } finally {
    loading.value = false
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

const handleImageError = () => {
  // Handle image loading error
}

const goToProduct = (slug: string) => {
  router.push(`/product/${slug}`)
}

// Lifecycle
onMounted(() => {
  fetchProduct()
})
</script>
