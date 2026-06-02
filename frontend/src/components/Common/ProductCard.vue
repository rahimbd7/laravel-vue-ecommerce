<!-- components/products/ProductCard.vue -->
<template>
  <div class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
    <!-- Sale Badge -->
    <div v-if="product.price.is_on_sale" class="absolute top-3 left-3 z-10">
      <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-md">
        -{{ product.price.discount }}%
      </span>
    </div>

    <!-- Low Stock Badge -->
    <div v-else-if="product.inventory?.status === 'low_stock'" class="absolute top-3 left-3 z-10">
      <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-md">
        Low Stock
      </span>
    </div>

    <!-- Out of Stock Badge -->
    <div v-if="product.inventory?.status === 'out_of_stock'" class="absolute inset-0 bg-black/50 z-10 flex items-center justify-center">
      <span class="bg-gray-800 text-white text-sm font-semibold px-3 py-2 rounded-lg">Out of Stock</span>
    </div>

    <!-- Wishlist Button -->
    <button 
      @click="$emit('toggle-wishlist', product)"
      class="absolute top-3 right-3 z-10 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition group/wishlist"
    >
      <svg 
        class="w-4 h-4 text-gray-500 group-hover/wishlist:text-red-500 transition" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
    </button>

    <!-- Product Image -->
    <router-link :to="`/product/${product.slug}`" class="block overflow-hidden bg-gray-100">
      <img 
        v-if="productImage"
        :src="productImage" 
        :alt="product.name"
        class="w-full h-56 object-cover group-hover:scale-105 transition-transform duration-500"
        @error="handleImageError"
      />
      <div v-else class="w-full h-56 bg-gray-100 flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </router-link>

    <!-- Product Info -->
    <div class="p-4">
      <router-link :to="`/product/${product.slug}`">
        <h3 class="font-semibold text-gray-800 hover:text-blue-600 transition line-clamp-2 min-h-[56px]">
          {{ product.name }}
        </h3>
      </router-link>

      <!-- Rating - Whole stars only -->
      <div class="flex items-center gap-1 mt-2">
        <div class="flex items-center">
          <span v-for="i in 5" :key="i" class="text-sm">
            <span v-if="i <= Math.floor(Number(product.stats.average_rating))" class="text-yellow-400">★</span>
            <span v-else class="text-gray-300">★</span>
          </span>
        </div>
        <span class="text-xs text-gray-500">({{ product.stats.review_count }})</span>
      </div>

      <!-- Price -->
      <div class="mt-3 flex items-center gap-2">
        <span class="text-xl font-bold text-gray-900">
          {{ product.price.formatted }}
        </span>
        <span v-if="product.price.compare?.formatted" class="text-sm text-gray-400 line-through">
          {{ product.price.compare.formatted }}
        </span>
      </div>

      <!-- Add to Cart Button -->
      <button 
        @click="$emit('add-to-cart', product)"
        :disabled="product.inventory?.status === 'out_of_stock'"
        class="mt-4 w-full py-2 rounded-lg font-medium transition"
        :class="product.inventory?.status !== 'out_of_stock'
          ? 'bg-blue-600 text-white hover:bg-blue-700 active:scale-95' 
          : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
      >
        {{ product.inventory?.status === 'out_of_stock' ? 'Out of Stock' : 'Add to Cart' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Product } from '@/types/models/product.types'

const props = defineProps<{
  product: Product
}>()

defineEmits<{
  (e: 'add-to-cart', product: Product): void
  (e: 'toggle-wishlist', product: Product): void
}>()

// Image error handling
const imageError = ref(false)

// Base URL for storage
const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'

// Product image with proper URL
const productImage = computed(() => {
  if (imageError.value) return null
  
  const imagePath = props.product.media?.image || props.product.media?.thumbnail
  
  if (!imagePath) return null
  
  // If it's already a full URL
  if (imagePath.startsWith('http')) {
    return imagePath
  }
  
  // Build full URL
  return `${baseUrl}/storage/${imagePath}`
})

const handleImageError = () => {
  imageError.value = true
}

</script>