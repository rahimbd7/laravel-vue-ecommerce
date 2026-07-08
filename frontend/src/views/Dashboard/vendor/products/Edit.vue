<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Product</h1>
          <p class="text-sm sm:text-base text-gray-600">Update your product information</p>
        </div>
        <Button 
          label="Back to Products" 
          icon="pi pi-arrow-left" 
          severity="secondary"
          outlined
          class="w-full sm:w-auto text-sm sm:text-base"
          @click="router.push('/dashboard/vendor/products')"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6 text-center">
      <i class="pi pi-exclamation-circle text-3xl sm:text-4xl text-red-500 mb-3 sm:mb-4 block"></i>
      <p class="text-red-700 font-medium text-sm sm:text-base">{{ error }}</p>
      <div class="flex flex-col sm:flex-row justify-center gap-2 mt-4">
        <Button label="Try Again" @click="fetchProduct" class="text-sm sm:text-base" />
        <Button label="Back to Products" severity="secondary" outlined @click="router.push('/dashboard/vendor/products')" class="text-sm sm:text-base" />
      </div>
    </div>

    <!-- Product Form -->
    <div v-else-if="product" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="updateProduct" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <!-- Product Name -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Product Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.name" 
              placeholder="Enter product name" 
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="text-red-500 text-xs sm:text-sm">{{ errors.name }}</small>
          </div>

          <!-- Slug (Auto-generated, read-only) -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <InputText 
              v-model="form.slug" 
              placeholder="Auto-generated slug" 
              class="w-full bg-gray-50 text-sm sm:text-base"
              disabled
            />
            <small class="text-gray-400 text-xs sm:text-sm">Auto-generated from product name</small>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description <span class="text-red-500">*</span>
            </label>
            <Textarea 
              v-model="form.description" 
              rows="6" 
              placeholder="Enter product description" 
              class="w-full text-sm sm:text-base"
              :class="{ 'p-invalid': errors.description }"
            />
            <small v-if="errors.description" class="text-red-500 text-xs sm:text-sm">{{ errors.description }}</small>
          </div>

          <!-- Short Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
            <Textarea 
              v-model="form.short_description" 
              rows="3" 
              placeholder="Enter short description" 
              class="w-full text-sm sm:text-base"
            />
          </div>

          <!-- Price -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Price <span class="text-red-500">*</span>
            </label>
            <InputNumber 
              v-model="form.price" 
              mode="currency" 
              currency="USD" 
              placeholder="0.00" 
              class="w-full"
              :class="{ 'p-invalid': errors.price }"
            />
            <small v-if="errors.price" class="text-red-500 text-xs sm:text-sm">{{ errors.price }}</small>
          </div>

          <!-- Compare Price -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Compare Price</label>
            <InputNumber 
              v-model="form.compare_price" 
              mode="currency" 
              currency="USD" 
              placeholder="0.00" 
              class="w-full"
              :class="{ 'p-invalid': errors.compare_price }"
            />
            <small v-if="errors.compare_price" class="text-red-500 text-xs sm:text-sm">{{ errors.compare_price }}</small>
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Category <span class="text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.category_id" 
              :options="categories" 
              optionLabel="name"
              optionValue="id"
              placeholder="Select category" 
              class="w-full"
              :class="{ 'p-invalid': errors.category_id }"
              filter
            />
            <small v-if="errors.category_id" class="text-red-500 text-xs sm:text-sm">{{ errors.category_id }}</small>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <Dropdown 
              v-model="form.status" 
              :options="statusOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select status" 
              class="w-full"
            />
          </div>

          <!-- Stock Quantity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Stock Quantity <span class="text-red-500">*</span>
            </label>
            <InputNumber 
              v-model="form.stock_quantity" 
              placeholder="0" 
              class="w-full"
              :class="{ 'p-invalid': errors.stock_quantity }"
              @update:modelValue="updateStockStatus"
            />
            <small v-if="errors.stock_quantity" class="text-red-500 text-xs sm:text-sm">{{ errors.stock_quantity }}</small>
          </div>

          <!-- Stock Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Stock Status <span class="text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.stock_status" 
              :options="stockStatusOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select stock status" 
              class="w-full"
              :class="{ 'p-invalid': errors.stock_status }"
            />
            <small v-if="errors.stock_status" class="text-red-500 text-xs sm:text-sm">{{ errors.stock_status }}</small>
          </div>

          <!-- SKU -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
            <InputText 
              v-model="form.sku" 
              placeholder="Leave empty to auto-generate" 
              class="w-full text-sm sm:text-base"
              :class="{ 'p-invalid': errors.sku }"
            />
            <small v-if="errors.sku" class="text-red-500 text-xs sm:text-sm">{{ errors.sku }}</small>
            <small v-else class="text-gray-400 text-xs sm:text-sm">Leave empty to auto-generate</small>
          </div>

          <!-- Weight -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
            <InputNumber 
              v-model="form.weight" 
              placeholder="0.00" 
              class="w-full"
            />
          </div>

          <!-- Dimensions -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dimensions (L x W x H in cm)</label>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
              <InputNumber v-model="form.dimensions_length" placeholder="L" class="w-full" />
              <InputNumber v-model="form.dimensions_width" placeholder="W" class="w-full" />
              <InputNumber v-model="form.dimensions_height" placeholder="H" class="w-full" />
            </div>
          </div>

          <!-- SEO & Meta Information -->
          <div class="md:col-span-2 border-t border-gray-200 pt-4 sm:pt-6 mt-4 sm:mt-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">SEO & Meta Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                <InputText 
                  v-model="form.meta_title" 
                  placeholder="SEO title (max 60 characters)" 
                  class="w-full text-sm sm:text-base"
                  maxlength="60"
                  :class="{ 'p-invalid': errors.meta_title }"
                />
                <small v-if="errors.meta_title" class="text-red-500 text-xs sm:text-sm">{{ errors.meta_title }}</small>
                <small v-else class="text-gray-400 text-xs sm:text-sm">{{ (form.meta_title?.length || 0) }}/60 characters</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <Textarea 
                  v-model="form.meta_description" 
                  rows="2" 
                  placeholder="SEO description (max 160 characters)" 
                  class="w-full text-sm sm:text-base"
                  maxlength="160"
                  :class="{ 'p-invalid': errors.meta_description }"
                />
                <small v-if="errors.meta_description" class="text-red-500 text-xs sm:text-sm">{{ errors.meta_description }}</small>
                <small v-else class="text-gray-400 text-xs sm:text-sm">{{ (form.meta_description?.length || 0) }}/160 characters</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                <InputText 
                  v-model="form.meta_keywords" 
                  placeholder="comma, separated, keywords" 
                  class="w-full text-sm sm:text-base"
                  :class="{ 'p-invalid': errors.meta_keywords }"
                />
                <small v-if="errors.meta_keywords" class="text-red-500 text-xs sm:text-sm">{{ errors.meta_keywords }}</small>
              </div>
            </div>
          </div>

          <!-- Images -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Images</label>
            <FileUpload 
              name="images[]" 
              :customUpload="true"
              @select="onFileSelect"
              @remove="onFileRemove"
              multiple
              accept="image/*"
              :maxFileSize="2097152"
              class="w-full"
            >
              <template #empty>
                <p class="text-gray-500 text-sm">Drag and drop images here or click to browse</p>
              </template>
            </FileUpload>
            <small class="text-gray-400 text-xs sm:text-sm">Maximum file size: 2MB per image</small>
            
            <!-- Existing Images -->
            <div v-if="form.existing_images && form.existing_images.length > 0" class="flex flex-wrap gap-2 mt-3 sm:mt-4">
              <div v-for="(image, index) in form.existing_images" :key="image.id" class="relative w-16 h-16 sm:w-20 sm:h-20 border rounded overflow-hidden">
                <img 
                  :src="getExistingImageUrl(image)" 
                  class="w-full h-full object-cover" 
                  alt="Existing product image" 
                />
                <button 
                  type="button"
                  @click="removeExistingImage(index)" 
                  class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-xs hover:bg-red-600"
                >
                  ×
                </button>
              </div>
            </div>

            <!-- New Image Preview -->
            <div v-if="form.images && form.images.length > 0" class="flex flex-wrap gap-2 mt-3 sm:mt-4">
              <div v-for="(image, index) in form.images" :key="index" class="relative w-16 h-16 sm:w-20 sm:h-20 border rounded overflow-hidden">
                <img 
                  :src="getImagePreviewUrl(image)" 
                  class="w-full h-full object-cover" 
                  alt="Product image" 
                />
                <button 
                  type="button"
                  @click="removeImage(index)" 
                  class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-xs hover:bg-red-600"
                >
                  ×
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Update Product" 
            icon="pi pi-save" 
            :loading="submitting"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45] text-sm sm:text-base"
          />
          <Button 
            label="Cancel" 
            severity="secondary" 
            class="w-full sm:w-auto text-sm sm:text-base"
            @click="router.push('/dashboard/vendor/products')" 
          />
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import Button from 'primevue/button'
import api from '@/api/api'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'

interface FormData {
  name: string
  slug: string
  description: string
  short_description: string
  price: number | null
  compare_price: number | null
  category_id: number | null
  status: string
  stock_quantity: number | null
  stock_status: string
  sku: string
  weight: number | null
  dimensions_length: number | null
  dimensions_width: number | null
  dimensions_height: number | null
  meta_title: string
  meta_description: string
  meta_keywords: string
  images: File[]
  existing_images: any[]
}

interface Category {
  id: number
  name: string
}

const router = useRouter()
const route = useRoute()
const toast = useToast()
const dashboardStore = useVendorDashboardStore()

const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const product = ref<any>(null)
const categories = ref<Category[]>([])
const errors = ref<Record<string, string[]>>({})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Draft', value: 'draft' }
]

const stockStatusOptions = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Backorder', value: 'backorder' }
]

const form = ref<FormData>({
  name: '',
  slug: '',
  description: '',
  short_description: '',
  price: null,
  compare_price: null,
  category_id: null,
  status: 'active',
  stock_quantity: null,
  stock_status: 'out_of_stock',
  sku: '',
  weight: null,
  dimensions_length: null,
  dimensions_width: null,
  dimensions_height: null,
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  images: [],
  existing_images: []
})

const getImagePreviewUrl = (file: File): string => {
  return URL.createObjectURL(file)
}

const getExistingImageUrl = (image: any): string => {
  const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'
  if (image.image_url) {
    return image.image_url.startsWith('http') ? image.image_url : `${baseUrl}/storage/${image.image_url}`
  }
  if (image.path) {
    return image.path.startsWith('http') ? image.path : `${baseUrl}/storage/${image.path}`
  }
  return ''
}

const updateStockStatus = () => {
  const stock = form.value.stock_quantity ?? 0
  if (stock <= 0) {
    form.value.stock_status = 'out_of_stock'
  } else if (stock <= 10) {
    form.value.stock_status = 'low_stock'
  } else {
    form.value.stock_status = 'in_stock'
  }
}

const fetchCategories = async () => {
  try {
    const response = await api.get('/categories')
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

const fetchProduct = async () => {
  const productId = route.params.id
  
  if (!productId) {
    error.value = 'Product ID not found'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Product ID not found in URL',
      life: 3000
    })
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await api.get(`/v1/products/${productId}`)
    
    if (!response.data || !response.data.data) {
      throw new Error('Product not found')
    }
    
    product.value = response.data.data
    const data = product.value
    
    // ✅ Helper function to safely parse numbers
    const parseNumber = (value: any): number | null => {
      if (value === null || value === undefined || value === '') {
        return null
      }
      const parsed = parseFloat(value)
      return isNaN(parsed) ? null : parsed
    }
    
    // ✅ Populate form with properly parsed values from API structure
    form.value = {
      name: data.name || '',
      slug: data.slug || '',
      description: data.description || '',
      short_description: data.short_description || '',
      price: parseNumber(data.price?.original),
      compare_price: parseNumber(data.price?.compare?.value),
      category_id: data.category?.id || null,
      status: data.status || 'active',
      stock_quantity: data.inventory?.quantity ?? null,
      stock_status: data.inventory?.status || 'out_of_stock',
      sku: data.sku || '',
      weight: parseNumber(data.shipping?.weight),
      dimensions_length: data.shipping?.dimensions_array?.length || null,
      dimensions_width: data.shipping?.dimensions_array?.width || null,
      dimensions_height: data.shipping?.dimensions_array?.height || null,
      meta_title: data.seo?.meta_title || '',
      meta_description: data.seo?.meta_description || '',
      meta_keywords: data.seo?.meta_keywords || '',
      images: [],
      existing_images: data.images || []
    }
    
    // ✅ Update stock status based on quantity
    updateStockStatus()
    
  } catch (err: any) {
    console.error('❌ Fetch product error:', err)
    error.value = err.response?.data?.message || 'Failed to load product'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.value,
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onFileSelect = (event: any) => {
  const files = event.files
  if (files && files.length > 0) {
    form.value.images = [...form.value.images, ...files]
  }
}

const onFileRemove = (event: any) => {
  form.value.images = form.value.images.filter(f => f !== event.file)
}

const removeImage = (index: number) => {
  form.value.images.splice(index, 1)
}

const removeExistingImage = (index: number) => {
  form.value.existing_images.splice(index, 1)
}

const updateProduct = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    
    // ✅ Basic fields
    formData.append('name', form.value.name || '')
    formData.append('description', form.value.description || '')
    formData.append('short_description', form.value.short_description || '')
    formData.append('price', String(form.value.price ?? 0))
    formData.append('category_id', String(form.value.category_id ?? ''))
    formData.append('status', form.value.status || 'active')
    formData.append('stock_quantity', String(form.value.stock_quantity ?? 0))
    formData.append('stock_status', form.value.stock_status || 'out_of_stock')
    formData.append('sku', form.value.sku || '')
    
    if (form.value.weight !== null) {
      formData.append('weight', String(form.value.weight))
    }
    
    if (form.value.compare_price !== null && form.value.compare_price !== undefined) {
      formData.append('compare_price', String(form.value.compare_price))
    }
    
    // ✅ Dimensions
    const dimensions = [
      form.value.dimensions_length,
      form.value.dimensions_width,
      form.value.dimensions_height
    ]
    if (dimensions.every(d => d !== null && d !== undefined)) {
      formData.append('dimensions', dimensions.join(' x '))
    }

    // ✅ Meta fields
    formData.append('meta_title', form.value.meta_title || '')
    formData.append('meta_description', form.value.meta_description || '')
    formData.append('meta_keywords', form.value.meta_keywords || '')

    // ✅ New images
    form.value.images.forEach((file) => {
      formData.append('images[]', file)
    })

    // ✅ Method spoofing for PUT
    formData.append('_method', 'PUT')

    const response = await api.post(`/v1/products/${product.value.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.status === 'success' || response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product updated successfully',
        life: 3000
      })
      
      await dashboardStore.fetchProducts(1, 15, true)
      router.push('/dashboard/vendor/products')
    }
  } catch (error: any) {
    console.error('❌ Product update error:', error.response?.data)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
      const firstError = Object.values(errors.value)[0]?.[0]
      if (firstError) {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: firstError,
          life: 5000
        })
      }
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update product',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchCategories()
  fetchProduct()
})
</script>

<style scoped>
:deep(.p-fileupload .p-fileupload-content) {
  padding: 1rem;
  border: 2px dashed #e5e7eb;
  border-radius: 0.5rem;
}

:deep(.p-fileupload .p-fileupload-content:hover) {
  border-color: #00685F;
}

:deep(.p-inputtext.p-invalid) {
  border-color: #ef4444;
}

:deep(.p-inputtext:disabled) {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

/* ✅ Responsive fixes */
@media (max-width: 640px) {
  :deep(.p-inputtext) {
    font-size: 0.875rem !important;
  }
  
  :deep(.p-dropdown .p-dropdown-label) {
    font-size: 0.875rem !important;
  }
  
  :deep(.p-textarea) {
    font-size: 0.875rem !important;
  }
  
  :deep(.p-fileupload .p-fileupload-content) {
    padding: 0.5rem !important;
  }
}

@media (max-width: 480px) {
  .grid-cols-3 {
    grid-template-columns: 1fr !important;
  }
}
</style>