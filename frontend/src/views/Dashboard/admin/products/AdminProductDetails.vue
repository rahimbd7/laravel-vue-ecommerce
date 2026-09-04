<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <Button 
            icon="pi pi-arrow-left" 
            text 
            rounded 
            @click="goBack"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Product Details</h1>
            <p class="text-sm text-gray-600">{{ product?.name }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            icon="pi pi-pencil" 
            label="Edit" 
            size="small"
            @click="editProduct"
            severity="primary"
          />
          <Button 
            v-if="!product?.deleted_at"
            icon="pi pi-trash" 
            label="Delete" 
            size="small"
            severity="danger"
            outlined
            @click="confirmDelete"
          />
          <Button 
            v-else
            icon="pi pi-refresh" 
            label="Restore" 
            size="small"
            severity="success"
            outlined
            @click="confirmRestore"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Product Content -->
    <template v-else-if="product">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Product Images -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Product Images</h4>
            
            <!-- Main Image -->
            <div v-if="getMainImage()" class="relative aspect-square rounded-lg overflow-hidden border border-gray-200 mb-3">
              <img 
                :src="getMainImage()" 
                :alt="product.name"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <div class="absolute top-2 left-2">
                <Tag value="Primary" severity="success" size="small" />
              </div>
            </div>

            <!-- Thumbnail Gallery -->
            <div v-if="getProductImages().length > 0" class="grid grid-cols-3 gap-2">
              <div 
                v-for="(image, index) in getProductImages()" 
                :key="image.id || index"
                class="relative aspect-square rounded-lg overflow-hidden border-2 cursor-pointer transition"
                :class="[
                  selectedImageIndex === index ? 'border-[#00685F]' : 'border-gray-200 hover:border-gray-400'
                ]"
                @click="selectedImageIndex = index"
              >
                <img 
                  :src="getImageUrl(image, 'thumbnail')" 
                  :alt="product.name"
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
                <div v-if="image.is_primary" class="absolute top-0 left-0 right-0 bottom-0 border-2 border-[#00685F] rounded-lg pointer-events-none"></div>
              </div>
            </div>

            <!-- No Images -->
            <div v-if="!getProductImages().length" class="text-center py-8">
              <i class="pi pi-image text-4xl text-gray-300"></i>
              <p class="text-gray-500 mt-2">No images</p>
            </div>
          </div>
        </div>

        <!-- Product Details -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Product Info -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="text-xl font-bold text-gray-900">{{ product.name }}</h3>
                <p class="text-sm text-gray-500">SKU: {{ product.sku || 'N/A' }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Tag 
                  :value="product.deleted_at ? 'Trashed' : product.is_visible ? 'Active' : 'Inactive'" 
                  :severity="product.deleted_at ? 'danger' : product.is_visible ? 'success' : 'secondary'" 
                />
                <Tag 
                  :value="formatStockStatus(product.stock_status)" 
                  :severity="getStockSeverity(product.stock_status)" 
                />
                <Tag v-if="product.is_featured" value="Featured" severity="warning" />
              </div>
            </div>

            <Divider />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Price</p>
                <p class="text-lg font-bold text-[#00685F]">{{ formatPrice(product.price) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Compare Price</p>
                <p class="text-lg font-bold text-gray-900">{{ product.compare_price ? formatPrice(product.compare_price) : 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Stock Quantity</p>
                <p class="text-lg font-bold text-gray-900">{{ product.stock_quantity || 0 }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Category</p>
                <p class="text-lg font-bold text-gray-900">{{ product.category?.name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Vendor</p>
                <p class="text-lg font-bold text-gray-900">{{ product.vendor?.business_name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Has Variations</p>
                <p class="text-lg font-bold text-gray-900">{{ product.has_variations ? 'Yes' : 'No' }}</p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Description</h4>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ product.description || 'No description' }}</p>
          </div>

          <!-- Variations -->
          <div v-if="product.has_variations && product.variations?.length" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Variations</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="variation in product.variations" :key="variation.id">
                    <td class="px-3 py-2 text-sm text-gray-900">{{ variation.name || variation.attributes?.join(', ') }}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">{{ variation.sku || 'N/A' }}</td>
                    <td class="px-3 py-2 text-sm font-medium text-[#00685F] text-right">{{ formatPrice(variation.price) }}</td>
                    <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ variation.stock_quantity || 0 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="product.tags?.length" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Tags</h4>
            <div class="flex flex-wrap gap-2">
              <Tag 
                v-for="tag in product.tags" 
                :key="tag" 
                :value="tag" 
                severity="info" 
                size="small"
              />
            </div>
          </div>

          <!-- Meta -->
          <div v-if="product.meta_title || product.meta_description" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">SEO Meta</h4>
            <div class="space-y-2">
              <div v-if="product.meta_title">
                <p class="text-sm text-gray-500">Meta Title</p>
                <p class="text-sm font-medium text-gray-900">{{ product.meta_title }}</p>
              </div>
              <div v-if="product.meta_description">
                <p class="text-sm text-gray-500">Meta Description</p>
                <p class="text-sm text-gray-700">{{ product.meta_description }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const productId = route.params.id as string

// State
const product = ref<any>(null)
const loading = ref(false)
const selectedImageIndex = ref(0)

const getImageUrl = (image: any, size: 'original' | 'thumbnail' | 'medium' | 'large' = 'original'): string => {
  if (!image) return ''
  
  // Try to get URL from different possible structures
  // 1. Direct URL fields based on size
  if (size === 'thumbnail' && (image.thumbnail_url || image.thumbnail)) {
    return image.thumbnail_url || image.thumbnail
  }
  if (size === 'medium' && (image.medium_url || image.medium)) {
    return image.medium_url || image.medium
  }
  if (size === 'large' && (image.large_url || image.large)) {
    return image.large_url || image.large
  }
  
  // 2. Nested urls object (from ProductImageResource)
  if (image.urls) {
    if (size === 'thumbnail' && image.urls.thumbnail) {
      return image.urls.thumbnail
    }
    if (size === 'medium' && image.urls.medium) {
      return image.urls.medium
    }
    if (size === 'large' && image.urls.large) {
      return image.urls.large
    }
    // Fallback to original
    if (image.urls.original) {
      return image.urls.original
    }
  }
  
  // 3. Top-level fields
  if (image.image_url) return image.image_url
  if (image.secure_url) return image.secure_url
  if (image.url) return image.url
  
  // 4. Check for direct URL fields on the image object
  if (image[`${size}_url`]) return image[`${size}_url`]
  if (image[size]) return image[size]
  
  // 5. Fallback to main product image
  if (product.value?.media?.image) {
    return product.value.media.image
  }
  if (product.value?.media?.thumbnail) {
    return product.value.media.thumbnail
  }
  
  return ''
}

const getProductImages = (): any[] => {
  if (!product.value) return []
  
  // Try to get images from various sources
  // 1. Check if product has images array directly
  if (product.value.images && Array.isArray(product.value.images) && product.value.images.length > 0) {
    return product.value.images
  }
  
  // 2. Check media.images
  if (product.value.media?.images && Array.isArray(product.value.media.images) && product.value.media.images.length > 0) {
    return product.value.media.images
  }
  
  // 3. Check if there's a single image in media
  if (product.value.media?.image) {
    return [{
      id: 'main',
      image_url: product.value.media.image,
      thumbnail_url: product.value.media.thumbnail || product.value.media.image,
      medium_url: product.value.media.image,
      large_url: product.value.media.image,
      is_primary: true,
      urls: {
        original: product.value.media.image,
        thumbnail: product.value.media.thumbnail || product.value.media.image
      }
    }]
  }
  
  return []
}

const getMainImage = (): string => {
  if (!product.value) return ''
  
  const images = getProductImages()
  if (images.length > 0) {
    const selectedIndex = Math.min(selectedImageIndex.value, images.length - 1)
    const image = images[selectedIndex]
    return getImageUrl(image, 'large') || getImageUrl(image, 'original')
  }
  
  // Fallback to media.image
  if (product.value.media?.image) {
    return product.value.media.image
  }
  
  return ''
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  if (img) {
    // Show fallback with product initials
    const productName = product.value?.name || 'Product'
    const initials = productName.split(' ').map((w: string) => w[0]).join('').toUpperCase().slice(0, 2)
    img.src = `data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="50%25" font-size="40" text-anchor="middle" dy=".3em" fill="%239ca3af"%3E${initials}%3C/text%3E%3C/svg%3E`
    img.onerror = null // Prevent infinite loop
  }
}

// Methods
const fetchProduct = async () => {
  loading.value = true
  try {
    const response = await adminApi.getProduct(productId)
    product.value = response.data.data
    // Reset selected image index
    selectedImageIndex.value = 0
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const editProduct = () => {
  router.push(`/dashboard/admin/products/${productId}/edit`)
}

const confirmDelete = () => {
  confirm.require({
    message: `Are you sure you want to delete product "${product.value?.name}"?`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.deleteProduct(productId)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Product moved to trash',
          life: 3000
        })
        await fetchProduct()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete product',
          life: 3000
        })
      }
    }
  })
}

const confirmRestore = () => {
  confirm.require({
    message: `Are you sure you want to restore product "${product.value?.name}"?`,
    header: 'Confirm Restore',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.restoreProduct(productId)
        toast.add({
          severity: 'success',
          summary: 'Restored',
          detail: 'Product restored successfully',
          life: 3000
        })
        await fetchProduct()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to restore product',
          life: 3000
        })
      }
    }
  })
}

const goBack = () => {
  router.push('/dashboard/admin/products')
}

const formatPrice = (price: any): string => {
  if (!price) return '0.00'
  const num = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(num) ? '0.00' : num.toFixed(2)
}

const formatStockStatus = (status: string): string => {
  const map: Record<string, string> = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock'
  }
  return map[status] || status
}

const getStockSeverity = (status: string): string => {
  const map: Record<string, string> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'danger'
  }
  return map[status] || 'info'
}

// Lifecycle
onMounted(() => {
  fetchProduct()
})
</script>

<style scoped>
@media (max-width: 640px) {
  :deep(.p-dialog) {
    margin: 0.5rem !important;
  }
}
</style>
