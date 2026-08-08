<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center space-x-2 text-sm">
          <router-link to="/" class="text-[#00685F] hover:text-[#004F45]">Home</router-link>
          <span class="text-gray-400">/</span>
          <span class="text-gray-600">Shopping Cart</span>
        </div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Shopping Cart</h1>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Empty Cart -->
    <div v-else-if="cartStore.isEmpty" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Your Cart is Empty</h3>
        <p class="text-gray-600 mb-6">Start shopping to add items to your cart</p>
        <router-link to="/shop"
          class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">
          Continue Shopping
        </router-link>
      </div>
    </div>

    <!-- Cart Contents -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Table -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                  <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Product</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Price</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Quantity</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Total</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="item in cartStore.activeItems" :key="item.id" class="hover:bg-gray-50 transition">
                    <!-- Product Info -->
                    <td class="px-6 py-4">
                      <div class="flex gap-4 items-start">
                        <div class="shrink-0">
                          <img 
                            v-if="getProductImage(item)" 
                            :src="getProductImage(item)" 
                            :alt="item.product_name" 
                            class="w-16 h-16 rounded object-cover"
                            @error="(e) => handleImageError(e, item)"
                          />
                          <div v-else class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                          </div>
                        </div>
                        <div>
                          <router-link v-if="item.product?.slug" :to="`/product/${item.product.slug}`"
                            class="font-semibold text-gray-900 hover:text-[#00685F] transition">
                            {{ item.product_name }}
                          </router-link>
                          <p v-else class="font-semibold text-gray-900">{{ item.product_name }}</p>
                          <p v-if="item.product_variation_name" class="text-sm text-gray-600">
                            {{ item.product_variation_name }}
                          </p>
                          <p class="text-xs text-gray-500 mt-1">SKU: {{ item.product_sku }}</p>
                        </div>
                      </div>
                    </td>
                    <!-- Price -->
                    <td class="px-6 py-4 text-gray-900">{{ formatPrice(item.unit_price) }}</td>
                    <!-- Quantity -->
                    <td class="px-6 py-4">
                      <div class="flex items-center border border-gray-300 rounded-lg w-fit">
                        <button @click="updateQuantity(item.id, item.quantity - 1)"
                          :disabled="item.quantity <= 1 || updatingId === item.id"
                          class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                          −
                        </button>
                        <span class="px-4 py-1 border-l border-r border-gray-300 text-center min-w-10">
                          {{ item.quantity }}
                        </span>
                        <button @click="updateQuantity(item.id, item.quantity + 1)" :disabled="updatingId === item.id"
                          class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                          +
                        </button>
                      </div>
                    </td>
                    <!-- Total -->
                    <td class="px-6 py-4 font-semibold text-gray-900">{{ formatPrice(item.total) }}</td>
                    <!-- Action -->
                    <td class="px-6 py-4">
                      <button @click="removeFromCart(item.id)" :disabled="deletingId === item.id"
                        class="text-red-600 hover:text-red-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <span v-if="deletingId === item.id">Removing...</span>
                        <span v-else>Remove</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-4 p-4">
              <div v-for="item in cartStore.activeItems" :key="item.id"
                class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex gap-3">
                  <img 
                    v-if="getProductImage(item)" 
                    :src="getProductImage(item)" 
                    :alt="item.product_name" 
                    class="w-16 h-16 rounded object-cover"
                    @error="(e) => handleImageError(e, item)"
                  />
                  <div v-else class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <router-link v-if="item.product?.slug" :to="`/product/${item.product.slug}`"
                      class="font-semibold text-gray-900 hover:text-[#00685F] transition">
                      {{ item.product_name }}
                    </router-link>
                    <p v-else class="font-semibold text-gray-900">{{ item.product_name }}</p>
                    <p v-if="item.product_variation_name" class="text-sm text-gray-600">
                      {{ item.product_variation_name }}
                    </p>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Price:</span>
                  <span class="font-semibold text-gray-900">{{ formatPrice(item.unit_price) }}</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Quantity:</span>
                  <div class="flex items-center border border-gray-300 rounded-lg">
                    <button @click="updateQuantity(item.id, item.quantity - 1)"
                      :disabled="item.quantity <= 1 || updatingId === item.id"
                      class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                      −
                    </button>
                    <span class="px-3 py-1 border-l border-r border-gray-300 text-center min-w-10">
                      {{ item.quantity }}
                    </span>
                    <button @click="updateQuantity(item.id, item.quantity + 1)" :disabled="updatingId === item.id"
                      class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                      +
                    </button>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Total:</span>
                  <span class="font-semibold text-gray-900">{{ formatPrice(item.total) }}</span>
                </div>

                <button @click="removeFromCart(item.id)" :disabled="deletingId === item.id"
                  class="w-full text-red-600 hover:text-red-700 font-medium py-2 border-t border-gray-200 mt-3 disabled:opacity-50 disabled:cursor-not-allowed transition">
                  <span v-if="deletingId === item.id">Removing...</span>
                  <span v-else>Remove</span>
                </button>
              </div>
            </div>

            <!-- Continue Shopping Button -->
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
              <router-link to="/shop" class="text-[#00685F] hover:text-[#004F45] font-medium transition">
                ← Continue Shopping
              </router-link>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-20 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>

            <div class="space-y-2 border-b border-gray-200 pb-4">
              <div class="flex justify-between text-gray-600">
                <span>Subtotal ({{ cartStore.totalItems }} items):</span>
                <span>{{ formatPrice(cartStore.subtotal) }}</span>
              </div>
              <div v-if="cartStore.discountTotal > 0" class="flex justify-between text-red-600">
                <span>Discount:</span>
                <span>-{{ formatPrice(cartStore.discountTotal) }}</span>
              </div>
              <div v-if="cartStore.taxTotal > 0" class="flex justify-between text-gray-600">
                <span>Tax:</span>
                <span>{{ formatPrice(cartStore.taxTotal) }}</span>
              </div>
              <div v-if="cartStore.shippingTotal > 0" class="flex justify-between text-gray-600">
                <span>Shipping:</span>
                <span>{{ formatPrice(cartStore.shippingTotal) }}</span>
              </div>
            </div>

            <div class="flex justify-between text-lg font-bold text-gray-900">
              <span>Total:</span>
              <span class="text-[#00685F]">{{ formatPrice(cartStore.totalPrice) }}</span>
            </div>

            <!-- Checkout Button -->
            <router-link to="/checkout"
              class="block w-full bg-[#00685F] text-white font-semibold py-3 rounded-lg text-center hover:bg-[#004F45] transition">
              Proceed to Checkout
            </router-link>

            <!-- Clear Cart Button -->
            <button @click="clearCart" :disabled="cartStore.loading"
              class="w-full border-2 border-red-300 text-red-600 font-semibold py-2 rounded-lg hover:bg-red-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
              Clear Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCartStore } from '@/stores/cart.store'
import { showConfirmDialog, showErrorAlert, showSuccessToast } from '@/utils/notification/sweetalert'

const cartStore = useCartStore()

// State
const loading = ref(false)
const updatingId = ref<number | null>(null)
const deletingId = ref<number | null>(null)

// ✅ Helper function to get product image from various formats
const getProductImage = (item: any): string => {
  if (!item?.product) return ''
  
  const product = item.product
  const media = product.media
  
  // Try to get image from various sources
  // 1. Check if media has thumbnail
  if (media?.thumbnail) {
    return media.thumbnail
  }
  
  // 2. Check if media has image
  if (media?.image) {
    return media.image
  }
  
  // 3. Check if product has direct image_url
  if (product.image_url) {
    return product.image_url
  }
  
  // 4. Check if product has images array
  if (product.images && Array.isArray(product.images) && product.images.length > 0) {
    const firstImage = product.images[0]
    // Try to get thumbnail from the image object
    if (firstImage.thumbnail || firstImage.thumbnail_url) {
      return firstImage.thumbnail_url || firstImage.thumbnail
    }
    // Try to get URL from various fields
    if (firstImage.image_url || firstImage.secure_url || firstImage.url) {
      return firstImage.image_url || firstImage.secure_url || firstImage.url
    }
    // Try nested urls object
    if (firstImage.urls?.thumbnail) {
      return firstImage.urls.thumbnail
    }
    if (firstImage.urls?.original) {
      return firstImage.urls.original
    }
  }
  
  // 5. Check for product thumbnail from media
  if (product.media?.thumbnail) {
    return product.media.thumbnail
  }
  
  return ''
}

// ✅ Handle image loading errors
const handleImageError = (event: Event, item: any) => {
  const img = event.target as HTMLImageElement
  if (img) {
    // Show fallback with product initials
    const productName = item?.product_name || 'Product'
    const initials = productName.split(' ').map((w: string) => w[0]).join('').toUpperCase().slice(0, 2)
    img.src = `data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="50%25" font-size="40" text-anchor="middle" dy=".3em" fill="%239ca3af"%3E${initials}%3C/text%3E%3C/svg%3E`
    img.onerror = null // Prevent infinite loop
  }
}

// Methods
const fetchCart = async () => {
  try {
    loading.value = true
    await cartStore.fetchCart()
  } catch (err) {
    console.error('Failed to fetch cart:', err)
  } finally {
    loading.value = false
  }
}

const updateQuantity = async (cartItemId: number, newQuantity: number) => {
  if (newQuantity < 1) return

  try {
    updatingId.value = cartItemId
    const result = await cartStore.updateItem(cartItemId, newQuantity)
    if (!result.success) {
      await showErrorAlert(result.error || 'Failed to update quantity')
    }
  } catch (err) {
    await showErrorAlert('Failed to update quantity')
  } finally {
    updatingId.value = null
  }
}

const removeFromCart = async (cartItemId: number) => {
  const confirmed = await showConfirmDialog({
    title: 'Remove Item?',
    text: 'Are you sure you want to remove this item from your cart?',
    icon: 'question',
    confirmText: 'Yes, remove it'
  })
  
  if (!confirmed) return

  try {
    deletingId.value = cartItemId
    const result = await cartStore.removeItem(cartItemId)
    if (!result.success) {
      await showErrorAlert(result.error || 'Failed to remove item')
    }
  } catch (err) {
    await showErrorAlert('Failed to remove item')
  } finally {
    deletingId.value = null
  }
}

const clearCart = async () => {
  const confirmed = await showConfirmDialog({
    title: 'Clear Entire Cart?',
    text: 'This action cannot be undone!',
    icon: 'warning',
    confirmText: 'Yes, clear it'
  })
  if (!confirmed) return

  try {
    const result = await cartStore.clear()
    if (result.success) {
      await showSuccessToast('Cart cleared successfully')
    } else {
      await showErrorAlert(result.error || 'Failed to clear cart')
    }
  } catch (err) {
    await showErrorAlert('Failed to clear cart')
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

// Lifecycle
onMounted(() => {
  fetchCart()
})
</script>