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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Product</h1>
            <p class="text-sm text-gray-600">Update product information</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            label="Cancel" 
            size="small"
            outlined
            @click="goBack"
          />
          <Button 
            label="Save Changes" 
            icon="pi pi-save" 
            size="small"
            :loading="saving"
            @click="saveProduct"
            severity="primary"
          />
        </div>
      </div>
    </div>

    <!-- Info Banner - Admin Restrictions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <i class="pi pi-info-circle text-blue-500 mt-0.5"></i>
        <div>
          <p class="text-sm text-blue-700 font-medium">Admin Product Editing Restrictions</p>
          <p class="text-xs text-blue-600 mt-1">
            As an admin, you can only update product <strong>status, visibility, featured status, 
            stock status, category, tags, and SEO meta</strong>. 
            Vendor-specific fields like <strong>name, description, price, images, and variations</strong> 
            cannot be modified.
          </p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Form -->
    <template v-else-if="product">
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <!-- Read-only Product Info -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Information (Read-Only)</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 bg-gray-50 rounded-lg p-4">
            <div>
              <p class="text-sm text-gray-500">Product Name</p>
              <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">SKU</p>
              <p class="text-sm font-medium text-gray-900">{{ product.sku || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Price</p>
              <p class="text-sm font-medium text-[#00685F]">{{ formatPrice(product.price) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Vendor</p>
              <p class="text-sm font-medium text-gray-900">{{ product.vendor?.business_name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Created At</p>
              <p class="text-sm font-medium text-gray-900">{{ formatDate(product.created_at) }}</p>
            </div>
            <div v-if="product.deleted_at">
              <p class="text-sm text-red-500">Deleted At</p>
              <p class="text-sm font-medium text-red-600">{{ formatDate(product.deleted_at) }}</p>
            </div>
          </div>
        </div>

        <Divider />

        <!-- Admin Editable Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <h3 class="md:col-span-2 text-lg font-semibold text-gray-900 mb-2">Admin Editable Fields</h3>

          <!-- Category -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700">Category</label>
            <Dropdown 
              v-model="form.category_id" 
              :options="categories" 
              optionLabel="name"
              optionValue="id"
              placeholder="Select Category"
              class="w-full"
              :loading="categoriesLoading"
            />
            <small class="text-gray-500">Change product category</small>
          </div>

          <!-- Stock Status -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700">Stock Status</label>
            <Dropdown 
              v-model="form.stock_status" 
              :options="stockStatusOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select Stock Status"
              class="w-full"
            />
            <small class="text-gray-500">Update stock availability status</small>
          </div>

          <!-- Status (Active/Inactive) -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700">Product Status</label>
            <div class="flex items-center gap-2 mt-2">
              <ToggleButton 
                v-model="form.is_visible" 
                onLabel="Active" 
                offLabel="Inactive"
                onIcon="pi pi-check"
                offIcon="pi pi-times"
                class="w-full"
              />
            </div>
            <small class="text-gray-500">Show or hide product from storefront</small>
          </div>

          <!-- Featured -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700">Featured Product</label>
            <div class="flex items-center gap-2 mt-2">
              <ToggleButton 
                v-model="form.is_featured" 
                onLabel="Featured" 
                offLabel="Not Featured"
                onIcon="pi pi-star"
                offIcon="pi pi-star-o"
                class="w-full"
              />
            </div>
            <small class="text-gray-500">Feature product on homepage and collections</small>
          </div>

          <!-- Tags -->
          <div class="md:col-span-2 space-y-2">
            <label class="text-sm font-medium text-gray-700">Tags</label>
            <Chips v-model="form.tags" class="w-full" />
            <small class="text-gray-500">Press Enter to add a tag</small>
          </div>

          <!-- SEO Meta -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Meta</h3>
            <div class="grid grid-cols-1 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Meta Title</label>
                <InputText v-model="form.meta_title" class="w-full" />
                <small class="text-gray-500">Recommended length: 50-60 characters</small>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Meta Description</label>
                <Textarea 
                  v-model="form.meta_description" 
                  rows="2" 
                  class="w-full"
                  placeholder="Enter meta description"
                />
                <small class="text-gray-500">Recommended length: 150-160 characters</small>
              </div>
            </div>
          </div>

          <!-- Restricted Fields Notice -->
          <div class="md:col-span-2 mt-4">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <i class="pi pi-lock text-yellow-600 mt-0.5"></i>
                <div>
                  <p class="text-sm text-yellow-700 font-medium">Restricted Fields</p>
                  <p class="text-xs text-yellow-600">
                    The following fields cannot be modified by admin: 
                    <strong>Product Name, Description, Price, Compare Price, Cost Price, 
                    Stock Quantity, Images, and Variations</strong>. 
                    These can only be updated by the product vendor.
                  </p>
                </div>
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
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import ToggleButton from 'primevue/togglebutton'
import Chips from 'primevue/chips'
import Divider from 'primevue/divider'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const productId = route.params.id as string

// State
const product = ref<any>(null)
const categories = ref<any[]>([])
const categoriesLoading = ref(false)
const loading = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})

// ✅ Only admin-editable fields
const form = reactive({
  category_id: null as number | null,
  stock_status: '',
  is_visible: true,
  is_featured: false,
  tags: [] as string[],
  meta_title: '',
  meta_description: '',
})

// Options
const stockStatusOptions = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
]

// Methods
const fetchProduct = async () => {
  loading.value = true
  try {
    const response = await adminApi.getProduct(productId)
    product.value = response.data.data
    
    // Populate form with existing values
    form.category_id = product.value.category_id || null
    form.stock_status = product.value.stock_status || 'in_stock'
    form.is_visible = product.value.is_visible ?? true
    form.is_featured = product.value.is_featured ?? false
    form.tags = product.value.tags || []
    form.meta_title = product.value.meta_title || ''
    form.meta_description = product.value.meta_description || ''
    
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  categoriesLoading.value = true
  try {
    const response = await adminApi.getCategoriesList()
    categories.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch categories:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load categories',
      life: 3000
    })
  } finally {
    categoriesLoading.value = false
  }
}

const saveProduct = async () => {
  errors.value = {}
  saving.value = true

  try {
    // ✅ Only send fields admin is allowed to update
    const payload = {
      category_id: form.category_id,
      stock_status: form.stock_status,
      is_visible: form.is_visible,
      is_featured: form.is_featured,
      tags: form.tags,
      meta_title: form.meta_title,
      meta_description: form.meta_description,
    }

    await adminApi.updateProduct(productId, payload)
    
    toast.add({
      severity: 'success',
      summary: 'Saved',
      detail: 'Product updated successfully',
      life: 3000
    })
    
    router.push(`/dashboard/admin/products/${productId}/details`)
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update product',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const goBack = () => {
  router.push(`/dashboard/admin/products/${productId}/details`)
}

const formatPrice = (price: any): string => {
  if (!price) return '0.00'
  const num = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(num) ? '0.00' : num.toFixed(2)
}

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchCategories()
  fetchProduct()
})
</script>

<style scoped>
:deep(.p-chips) {
  width: 100%;
}

:deep(.p-chips .p-chips-multiple-container) {
  min-height: 2.5rem;
  padding: 0.25rem;
}

:deep(.p-chips .p-chips-input-token input) {
  font-size: 0.875rem;
}

@media (max-width: 640px) {
  :deep(.p-dialog) {
    margin: 0.5rem !important;
  }
}
</style>