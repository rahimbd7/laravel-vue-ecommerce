<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">My Wishlist</h1>
          <p class="text-gray-600">Products you've saved for later</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-500">{{ wishlistItems.length }} items</span>
          <Button 
            v-if="wishlistItems.length > 0"
            label="Clear All" 
            icon="pi pi-trash" 
            severity="danger"
            size="small"
            outlined
            @click="clearWishlist"
            :loading="clearing"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="wishlistItems.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
      <i class="pi pi-heart text-6xl text-gray-300 mb-6 block"></i>
      <h3 class="text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
      <p class="text-gray-600 mb-6">Start saving your favorite products</p>
      <router-link to="/shop" class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">
        Start Shopping
      </router-link>
    </div>

    <!-- Wishlist Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="item in wishlistItems" :key="item.id" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
        <!-- Product Image -->
        <router-link :to="`/product/${item.product.slug}`" class="block overflow-hidden bg-gray-100">
          <img 
            :src="getProductImage(item.product)" 
            :alt="item.product.name"
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
            @error="handleImageError"
          />
        </router-link>

        <!-- Product Info -->
        <div class="p-4">
          <router-link :to="`/product/${item.product.slug}`">
            <h3 class="font-semibold text-gray-800 hover:text-[#00685F] transition line-clamp-2 min-h-[48px]">
              {{ item.product.name }}
            </h3>
          </router-link>

          <!-- Price -->
          <div class="mt-2 flex items-center gap-2">
            <span class="text-lg font-bold text-[#00685F]">
              ${{ item.product.price }}
            </span>
            <span v-if="item.product.compare_price" class="text-sm text-gray-400 line-through">
              ${{ item.product.compare_price }}
            </span>
          </div>

          <!-- Actions -->
          <div class="mt-4 flex gap-2">
            <Button 
              label="Add to Cart" 
              icon="pi pi-shopping-cart" 
              severity="success"
              size="small"
              class="flex-1 bg-[#00685F]"
              @click="addToCart(item.product)"
              :disabled="item.product.inventory?.status === 'out_of_stock'"
            
            />
            <Button 
              icon="pi pi-heart-fill" 
              severity="danger"
              size="small"
              outlined
              @click="removeFromWishlist(item.product_id)"
              :loading="removingItem === item.product_id"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-8">
      <Paginator 
        v-model:first="first"
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        :pageLinkSize="5"
        @page="onPageChange"
        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
        class="bg-white rounded-lg shadow-sm p-4"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'
import api from '@/api/api'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const loading = ref(false)
const clearing = ref(false)
const removingItem = ref<number | null>(null)
const wishlistItems = ref<any[]>([])
const first = ref(0)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Base URL for images
const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'

// Get product image
const getProductImage = (product: any) => {
  if (product?.image_url) {
    return product.image_url.startsWith('http') 
      ? product.image_url 
      : `${baseUrl}/storage/${product.image}`
  }
  return null
}

// Handle image error
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="%239CA3AF" stroke-width="2"%3E%3Crect x="3" y="3" width="18" height="18" rx="2"%3E%3C/rect%3E%3Ccircle cx="8.5" cy="8.5" r="1.5"%3E%3C/circle%3E%3Cpath d="M21 15l-5-5L5 21"%3E%3C/path%3E%3C/svg%3E'
}

// Fetch wishlist
const fetchWishlist = async () => {
  loading.value = true
  try {
    const response = await api.get(`/wishlist`, {
      params: {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page
      }
    })
    
    const data = response.data.data
    wishlistItems.value = data.data || data
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      per_page: data.per_page || 15,
      total: data.total || 0
    }
    first.value = (pagination.value.current_page - 1) * pagination.value.per_page
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load wishlist',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Add to cart
const addToCart = async (product: any) => {
  if (product.inventory?.status === 'out_of_stock') {
    toast.add({
      severity: 'warn',
      summary: 'Out of Stock',
      detail: 'This product is currently out of stock',
      life: 3000
    })
    return
  }
  
  try {
    await api.post('/cart/add', {
      product_id: product.id,
      quantity: 1
    })
    
    toast.add({
      severity: 'success',
      summary: 'Added to Cart',
      detail: `${product.name} added to your cart`,
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to add to cart',
      life: 3000
    })
  }
}

// Remove from wishlist
const removeFromWishlist = (productId: number) => {
  confirm.require({
    message: 'Are you sure you want to remove this item from your wishlist?',
    header: 'Remove Item',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      removingItem.value = productId
      try {
        await api.delete(`/wishlist/remove/${productId}`)
        
        // Remove from list
        wishlistItems.value = wishlistItems.value.filter(item => item.product_id !== productId)
        pagination.value.total -= 1
        
        toast.add({
          severity: 'success',
          summary: 'Removed',
          detail: 'Item removed from wishlist',
          life: 3000
        })
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to remove item',
          life: 3000
        })
      } finally {
        removingItem.value = null
      }
    }
  })
}

// Clear wishlist
const clearWishlist = () => {
  confirm.require({
    message: 'Are you sure you want to clear your entire wishlist?',
    header: 'Clear Wishlist',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      clearing.value = true
      try {
        await api.delete('/wishlist/clear')
        
        wishlistItems.value = []
        pagination.value.total = 0
        pagination.value.current_page = 1
        pagination.value.last_page = 1
        
        toast.add({
          severity: 'success',
          summary: 'Cleared',
          detail: 'Wishlist cleared successfully',
          life: 3000
        })
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to clear wishlist',
          life: 3000
        })
      } finally {
        clearing.value = false
      }
    }
  })
}

// Pagination
const onPageChange = (event: any) => {
  const page = Math.floor(event.first / event.rows) + 1
  if (page !== pagination.value.current_page) {
    pagination.value.current_page = page
    fetchWishlist()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

onMounted(() => {
  fetchWishlist()
})
</script>

<style scoped>
:deep(.p-paginator) {
  border: none !important;
  background: transparent !important;
  padding: 0.5rem !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: #00685F !important;
  color: white !important;
  border-color: #00685F !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:not(.p-highlight):hover) {
  background: #f3f4f6 !important;
}
</style>