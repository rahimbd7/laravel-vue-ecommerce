<template>
  <section class="py-12 px-4 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="text-center mb-10">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Featured Products</h2>
        <p class="text-gray-500 max-w-2xl mx-auto">Discover our hand-picked selection of top-rated products</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Products Grid -->
      <div v-else-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard 
          v-for="product in products" 
          :key="product.id"
          :product="product"
          @add-to-cart="handleAddToCart"
          @toggle-wishlist="handleToggleWishlist"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <p class="text-gray-500">No featured products available.</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useProductStore } from '@/stores/product.store'
import { useCartStore } from '@/stores/cart.store'
import ProductCard from './ProductCard.vue'
import toast from '../../utils/notification/toast'

const productStore = useProductStore()
const cartStore = useCartStore()

const products = computed(() => productStore.featuredList)
const loading = computed(() => productStore.isLoading)

const handleAddToCart = async (product: any) => {
  try {
    await cartStore.addItem(product.id, 1, null)
   toast.success('Item added to cart')
  } catch (error) {
    console.error('Failed to add to cart:', error)
  }
}

const handleToggleWishlist = (product: any) => {
  console.log('Toggle wishlist:', product.id)
}

onMounted(() => {
  if (products.value.length === 0) {
    productStore.fetchFeaturedProducts(8)
  }
})
</script>