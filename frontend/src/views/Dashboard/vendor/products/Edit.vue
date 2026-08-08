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

          <!-- Slug -->
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
          </div>

          <!-- Category with Subcategories -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Category <span class="text-red-500">*</span>
            </label>
            
            <!-- Custom Category Dropdown -->
            <div class="relative" ref="dropdownRef">
              <!-- Dropdown Trigger -->
              <div 
                @click="toggleDropdown"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white cursor-pointer flex items-center justify-between hover:border-[#00685F] transition"
                :class="{ 'border-[#00685F]': isDropdownOpen, 'p-invalid': errors.category_id }"
              >
                <span class="text-sm" :class="{ 'text-gray-500': !selectedCategory }">
                  {{ selectedCategory ? selectedCategory.label : 'Select category' }}
                </span>
                <i class="pi" :class="isDropdownOpen ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
              </div>
              
              <small v-if="errors.category_id" class="text-red-500 text-xs sm:text-sm">{{ errors.category_id }}</small>
              <small v-else class="text-gray-400 text-xs sm:text-sm">Select a category or subcategory</small>

              <!-- Dropdown Menu -->
              <div 
                v-if="isDropdownOpen"
                class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-80 overflow-y-auto"
              >
                <div class="p-2">
                  <!-- Search Input -->
                  <div class="relative mb-2">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <InputText 
                      v-model="searchQuery"
                      placeholder="Search categories..." 
                      class="w-full pl-8 text-sm"
                      @input="filterCategories"
                    />
                  </div>

                  <!-- Category Tree -->
                  <div v-if="filteredTree.length > 0" class="space-y-0.5">
                    <div 
                      v-for="item in filteredTree" 
                      :key="item.id"
                      class="category-item"
                    >
                      <!-- Category Item -->
                      <div class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 transition group">
                        <!-- Expand/Collapse Button -->
                        <button 
                          v-if="item.hasChildren"
                          type="button"
                          @click.stop="toggleExpand(item.id)"
                          class="p-0.5 hover:bg-gray-200 rounded transition mr-1"
                        >
                          <i 
                            class="pi text-xs text-gray-500"
                            :class="isExpanded(item.id) ? 'pi-chevron-down' : 'pi-chevron-right'"
                          ></i>
                        </button>
                        <span v-else class="w-4 mr-1"></span>
                        
                        <!-- Category Name -->
                        <div 
                          @click="selectCategory(item)"
                          class="flex items-center gap-2 flex-1 cursor-pointer"
                        >
                          <span 
                            v-for="i in item.level" 
                            :key="i" 
                            class="inline-block w-4"
                          ></span>
                          <i v-if="item.icon" :class="item.icon" class="text-[#00685F] text-sm"></i>
                          <span class="text-sm" :class="{ 'font-semibold text-[#00685F]': selectedCategory?.id === item.id }">
                            {{ item.label }}
                          </span>
                          <span v-if="item.hasChildren" class="text-xs text-gray-400">
                            ({{ item.children.length }})
                          </span>
                        </div>
                        
                        <!-- Select Indicator -->
                        <i 
                          v-if="selectedCategory?.id === item.id"
                          class="pi pi-check text-[#00685F] text-xs ml-auto"
                        ></i>
                      </div>

                      <!-- Children (Subcategories) - Expandable -->
                      <div 
                        v-if="item.hasChildren && isExpanded(item.id)"
                        class="ml-6 border-l-2 border-gray-100 pl-2 space-y-0.5"
                      >
                        <div 
                          v-for="child in item.children" 
                          :key="child.id"
                          @click="selectCategory(child)"
                          class="flex items-center px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50 transition ml-4"
                          :class="{ 'bg-[#00685F]/10 text-[#00685F]': selectedCategory?.id === child.id }"
                        >
                          <div class="flex items-center gap-2 flex-1">
                            <span class="inline-block w-4"></span>
                            <i v-if="child.icon" :class="child.icon" class="text-[#00685F] text-sm"></i>
                            <span class="text-sm">{{ child.label }}</span>
                          </div>
                          <i 
                            v-if="selectedCategory?.id === child.id"
                            class="pi pi-check text-[#00685F] text-xs"
                          ></i>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- No Results -->
                  <div v-else class="text-center py-4 text-gray-500 text-sm">
                    <i class="pi pi-inbox text-2xl block mb-2 text-gray-300"></i>
                    No categories found
                  </div>
                </div>
              </div>
            </div>
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

          <!-- Images with Cloudinary Management -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Images</label>
            
            <!-- Upload Progress -->
            <div v-if="uploading" class="mb-3">
              <div class="flex items-center gap-3">
                <i class="pi pi-spin pi-spinner text-2xl text-[#00685F]"></i>
                <span class="text-gray-600">Uploading... {{ uploadProgress }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div 
                  class="bg-[#00685F] h-2 rounded-full transition-all duration-300"
                  :style="{ width: uploadProgress + '%' }"
                ></div>
              </div>
            </div>

            <FileUpload 
              name="images[]" 
              :customUpload="true"
              @select="onFileSelect"
              @remove="onFileRemove"
              multiple
              accept="image/*"
              :maxFileSize="5242880"
              class="w-full"
              :disabled="uploading"
            >
              <template #empty>
                <p class="text-gray-500 text-sm">Drag and drop images here or click to browse</p>
              </template>
            </FileUpload>
            <small class="text-gray-400 text-xs sm:text-sm">Maximum file size: 5MB per image.</small>

            <!-- Existing Images -->
            <div v-if="form.existing_images && form.existing_images.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">Existing Images ({{ form.existing_images.length }})</p>
              <div class="flex flex-wrap gap-2">
                <div 
                  v-for="(image, index) in form.existing_images" 
                  :key="image.id || index" 
                  class="relative w-20 h-20 sm:w-24 sm:h-24 border-2 rounded-lg overflow-hidden group"
                  :class="{ 'border-[#00685F]': image.is_primary }"
                >
                  <img 
                    :src="getExistingImageUrl(image)" 
                    class="w-full h-full object-cover" 
                    :alt="'Product image ' + (index + 1)"
                    @error="handleImageError(index)"
                  />
                  <!-- Primary Badge -->
                  <div v-if="image.is_primary" class="absolute top-1 left-1">
                    <span class="bg-[#00685F] text-white text-[10px] px-1.5 py-0.5 rounded-full">Primary</span>
                  </div>
                  <!-- Set Primary Button -->
                  <button 
                    v-if="!image.is_primary"
                    type="button"
                    @click="setPrimaryImage(index)"
                    class="absolute bottom-1 left-1 opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <span class="bg-black/70 text-white text-[10px] px-1.5 py-0.5 rounded hover:bg-black/90">
                      Set Primary
                    </span>
                  </button>
                  <!-- Delete Button -->
                  <button 
                    type="button"
                    @click="removeExistingImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 shadow-md z-10"
                  >
                    <i class="pi pi-times text-xs"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- No Images Message -->
            <div v-else-if="!uploading" class="mt-3 text-center py-4 bg-gray-50 rounded-lg">
              <i class="pi pi-image text-3xl text-gray-300"></i>
              <p class="text-sm text-gray-500 mt-1">No images uploaded yet</p>
              <p class="text-xs text-gray-400">Upload images using the file selector above</p>
            </div>

            <!-- New Image Preview -->
            <div v-if="form.images && form.images.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">New Images ({{ form.images.length }})</p>
              <div class="flex flex-wrap gap-2">
                <div 
                  v-for="(file, index) in form.images" 
                  :key="'new-' + index" 
                  class="relative w-20 h-20 sm:w-24 sm:h-24 border-2 border-dashed border-blue-400 rounded-lg overflow-hidden bg-blue-50"
                >
                  <img 
                    :src="getImagePreviewUrl(file)" 
                    class="w-full h-full object-cover" 
                    alt="New product image" 
                  />
                  <button 
                    type="button"
                    @click="removeImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center hover:bg-red-600 shadow-md z-10"
                  >
                    <i class="pi pi-times text-xs"></i>
                  </button>
                  <div class="absolute bottom-0 left-0 right-0 bg-blue-500/80 text-white text-[10px] text-center py-0.5">
                    New
                  </div>
                </div>
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

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import api from '@/api/api'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'

interface UploadedImage {
  secure_url: string
  public_id: string
  is_primary: boolean
  thumbnail?: string
  medium?: string
  large?: string
}

interface ExistingImage {
  id?: number | string
  image_url?: string
  secure_url?: string
  url?: string
  thumbnail_url?: string
  medium_url?: string
  large_url?: string
  thumbnail?: string
  medium?: string
  large?: string
  is_primary?: boolean
  cloudinary_public_id?: string
  public_id?: string
} 

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
  existing_images: ExistingImage[]
  images_to_delete: (number | string)[]
}

interface CategoryNode {
  id: number
  name: string
  label: string
  level: number
  icon?: string
  hasChildren: boolean
  children: CategoryNode[]
}

const router = useRouter()
const route = useRoute()
const toast = useToast()
const dashboardStore = useVendorDashboardStore()

const loading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const product = ref<any>(null)
const errors = ref<Record<string, string[]>>({})

// Dropdown State
const isDropdownOpen = ref(false)
const searchQuery = ref('')
const expandedNodes = ref<number[]>([])
const selectedCategory = ref<{ id: number; label: string } | null>(null)
const dropdownRef = ref<HTMLElement | null>(null)
const categoryTree = ref<CategoryNode[]>([])

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
  existing_images: [],
  images_to_delete: []
})

// Computed
const filteredTree = computed(() => {
  if (!searchQuery.value.trim()) {
    return categoryTree.value
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  const filterNodes = (nodes: CategoryNode[]): CategoryNode[] => {
    return nodes
      .map(node => {
        const matches = node.label.toLowerCase().includes(query)
        const filteredChildren = filterNodes(node.children || [])
        
        if (matches || filteredChildren.length > 0) {
          return {
            ...node,
            children: filteredChildren,
          }
        }
        return null
      })
      .filter(Boolean) as CategoryNode[]
  }
  
  // Auto-expand when searching
  if (searchQuery.value.trim()) {
    const allExpanded: number[] = []
    const collectIds = (nodes: CategoryNode[]) => {
      for (const node of nodes) {
        if (node.hasChildren) {
          allExpanded.push(node.id)
          collectIds(node.children)
        }
      }
    }
    collectIds(filterNodes(categoryTree.value))
    expandedNodes.value = allExpanded
  }
  
  return filterNodes(categoryTree.value)
})

const getImagePreviewUrl = (file: File): string => {
  return URL.createObjectURL(file)
}

const getExistingImageUrl = (image: ExistingImage | any): string => {
  if (!image) return ''
  return image.image_url || 
         image.secure_url || 
         image.url || 
         image.thumbnail_url || 
         image.thumbnail || 
         image.medium_url ||
         image.large_url ||
         ''
}

const handleImageError = (index: number) => {
  console.warn('Failed to load image at index:', index)
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

// Category Helpers
const buildCategoryTree = (cats: any[], level: number = 0): CategoryNode[] => {
  return cats.map(cat => ({
    id: cat.id,
    name: cat.name,
    label: cat.name,
    level: level,
    icon: cat.icon || undefined,
    hasChildren: cat.children && cat.children.length > 0,
    children: cat.children && cat.children.length > 0 
      ? buildCategoryTree(cat.children, level + 1)
      : []
  }))
}

// Dropdown Methods
const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
  if (isDropdownOpen.value) {
    searchQuery.value = ''
  }
}

const selectCategory = (category: CategoryNode) => {
  selectedCategory.value = {
    id: category.id,
    label: category.label
  }
  form.value.category_id = category.id
  isDropdownOpen.value = false
  searchQuery.value = ''
}

const toggleExpand = (id: number) => {
  const index = expandedNodes.value.indexOf(id)
  if (index > -1) {
    expandedNodes.value.splice(index, 1)
  } else {
    expandedNodes.value.push(id)
  }
  expandedNodes.value = [...expandedNodes.value]
}

const isExpanded = (id: number): boolean => {
  return expandedNodes.value.includes(id)
}

const filterCategories = () => {
  // Filtering is handled by computed property
}

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isDropdownOpen.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await api.get('/categories')
    const rawData = response.data.data
    categoryTree.value = buildCategoryTree(rawData)
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
    
    const parseNumber = (value: any): number | null => {
      if (value === null || value === undefined || value === '') {
        return null
      }
      const parsed = parseFloat(value)
      return isNaN(parsed) ? null : parsed
    }
    
    // Extract images from the API response
    let existingImages: ExistingImage[] = []
    
    if (data.media?.images && Array.isArray(data.media.images) && data.media.images.length > 0) {
      existingImages = data.media.images.map((img: any) => {
        let imageUrl = img.image_url || 
                       img.secure_url || 
                       img.url || 
                       img.urls?.original || 
                       img.urls?.image ||
                       data.media.image ||
                       ''
        
        if (imageUrl === data.media.image && img.urls?.original) {
          imageUrl = img.urls.original
        }
        
        return {
          id: img.id || `img-${Date.now()}-${Math.random()}`,
          image_url: imageUrl,
          secure_url: img.secure_url || img.urls?.original || imageUrl,
          url: img.url || img.urls?.original || imageUrl,
          thumbnail_url: img.thumbnail_url || img.urls?.thumbnail || img.thumbnail || imageUrl,
          medium_url: img.medium_url || img.urls?.medium || img.medium || imageUrl,
          large_url: img.large_url || img.urls?.large || img.large || imageUrl,
          thumbnail: img.thumbnail || img.thumbnail_url || img.urls?.thumbnail || imageUrl,
          medium: img.medium || img.medium_url || img.urls?.medium || imageUrl,
          large: img.large || img.large_url || img.urls?.large || imageUrl,
          is_primary: img.is_primary || false,
          cloudinary_public_id: img.cloudinary_public_id || img.public_id || null,
          public_id: img.public_id || img.cloudinary_public_id || null,
        }
      })
      
      if (existingImages.length > 0 && !existingImages.some((img) => img.is_primary)) {
  const firstImage = existingImages[0]
  if (firstImage) {
    firstImage.is_primary = true
  }
}
    } 
    else if (data.images && Array.isArray(data.images) && data.images.length > 0) {
      existingImages = data.images.map((img: any) => ({
        id: img.id || `img-${Date.now()}-${Math.random()}`,
        image_url: img.image_url || img.secure_url || img.url || '',
        secure_url: img.secure_url || img.image_url || '',
        url: img.url || img.image_url || '',
        thumbnail_url: img.thumbnail_url || img.thumbnail || img.image_url || '',
        medium_url: img.medium_url || img.medium || img.image_url || '',
        large_url: img.large_url || img.large || img.image_url || '',
        thumbnail: img.thumbnail || img.thumbnail_url || img.image_url || '',
        medium: img.medium || img.medium_url || img.image_url || '',
        large: img.large || img.large_url || img.image_url || '',
        is_primary: img.is_primary || false,
        cloudinary_public_id: img.cloudinary_public_id || img.public_id || null,
        public_id: img.public_id || img.cloudinary_public_id || null,
      }))
      
      if (existingImages.length > 0 && !existingImages.some((img) => img.is_primary)) {
        const firstImage = existingImages[0]
        if (firstImage) {
          firstImage.is_primary = true
        }
      }
    }
    else if (data.media?.image) {
      existingImages = [{
        id: 'single-image',
        image_url: data.media.image,
        secure_url: data.media.image,
        url: data.media.image,
        thumbnail_url: data.media.thumbnail || data.media.image,
        medium_url: data.media.image,
        large_url: data.media.image,
        thumbnail: data.media.thumbnail || data.media.image,
        medium: data.media.image,
        large: data.media.image,
        is_primary: true,
        cloudinary_public_id: '',
        public_id: '',
      }]
    }
    
    const uniqueImages: ExistingImage[] = []
    const seenIds = new Set()
    
    existingImages.forEach((img) => {
      const id = img.id || img.image_url
      if (!seenIds.has(id)) {
        seenIds.add(id)
        uniqueImages.push(img)
      }
    })
    
    // Set selected category
    const categoryId = data.category?.id
    if (categoryId) {
      form.value.category_id = categoryId
      // Find category name for display
      const findCategory = (nodes: CategoryNode[], id: number): CategoryNode | null => {
        for (const node of nodes) {
          if (node.id === id) return node
          if (node.children) {
            const found = findCategory(node.children, id)
            if (found) return found
          }
        }
        return null
      }
      const foundCategory = findCategory(categoryTree.value, categoryId)
      if (foundCategory) {
        selectedCategory.value = { id: foundCategory.id, label: foundCategory.label }
      }
    }
    
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
      existing_images: uniqueImages,
      images_to_delete: []
    }
    
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

// Upload file to Cloudinary
const uploadToCloudinary = async (file: File): Promise<UploadedImage | null> => {
  const cloudName = import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || 'your-cloud-name'
  const uploadPreset = import.meta.env.VITE_CLOUDINARY_PRODUCT_PRESET || 'product_preset'
  
  const formData = new FormData()
  formData.append('file', file)
  formData.append('upload_preset', uploadPreset)
  formData.append('folder', 'products')

  try {
    const response = await fetch(
      `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
      {
        method: 'POST',
        body: formData,
      }
    )

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error?.message || 'Upload failed')
    }

    const result = await response.json()
    
    return {
      secure_url: result.secure_url,
      public_id: result.public_id,
      is_primary: false,
      thumbnail: result.secure_url,
      medium: result.secure_url,
      large: result.secure_url,
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Upload Failed',
      detail: error.message || 'Failed to upload image to Cloudinary',
      life: 5000
    })
    return null
  }
}

// Set primary image
const setPrimaryImage = (index: number) => {
  form.value.existing_images = form.value.existing_images.map((img, i) => ({
    ...img,
    is_primary: i === index
  }))
  toast.add({
    severity: 'success',
    summary: 'Primary Image Updated',
    detail: 'Primary image has been changed',
    life: 3000
  })
}

const onFileSelect = (event: any) => {
  const files = event.files
  if (files && files.length > 0) {
    form.value.images = [...form.value.images, ...files]
    toast.add({
      severity: 'info',
      summary: 'Files Selected',
      detail: `${files.length} image(s) selected. They will be uploaded when you save.`,
      life: 3000
    })
  }
}

const onFileRemove = (event: any) => {
  form.value.images = form.value.images.filter(f => f !== event.file)
}

const removeImage = (index: number) => {
  form.value.images.splice(index, 1)
}

// Remove existing image with confirmation
const removeExistingImage = (index: number) => {
  const image = form.value.existing_images[index]
  
  if (!image) return
  
  if (image.is_primary && form.value.existing_images.length > 1) {
    toast.add({
      severity: 'warn',
      summary: 'Cannot Remove Primary',
      detail: 'Set another image as primary first, or add a new image.',
      life: 3000
    })
    return
  }
  
  if (!confirm('Are you sure you want to remove this image?')) {
    return
  }
  
  if (image.id && typeof image.id === 'number') {
    form.value.images_to_delete.push(image.id)
  }
  
  form.value.existing_images.splice(index, 1)
  
 if (form.value.existing_images.length > 0 && !form.value.existing_images.some((img: any) => img.is_primary)) {
  const firstImage = form.value.existing_images.find((_, index) => index === 0)
  if (firstImage) {
    firstImage.is_primary = true
  }
}
  
  toast.add({
    severity: 'success',
    summary: 'Image Removed',
    detail: 'Image has been removed from the product',
    life: 3000
  })
}

const updateProduct = async () => {
  errors.value = {}
  submitting.value = true

  try {
    let finalImages: UploadedImage[] = [] 
     
    if (form.value.existing_images && form.value.existing_images.length > 0) {
      form.value.existing_images.forEach((img: ExistingImage) => {
        const imageUrl = img.image_url || img.secure_url || img.url || ''
        const publicId = img.cloudinary_public_id || img.public_id || ''
        
        finalImages.push({
          secure_url: imageUrl,
          public_id: publicId,
          is_primary: img.is_primary || false,
          thumbnail: img.thumbnail_url || img.thumbnail || imageUrl,
          medium: img.medium_url || img.medium || imageUrl,
          large: img.large_url || img.large || imageUrl,
        })
      })
    }

    if (form.value.images && form.value.images.length > 0) {
      uploading.value = true
      let uploadedCount = 0
      const totalFiles = form.value.images.length
      
      for (const file of form.value.images) {
        const result = await uploadToCloudinary(file)
        if (result) {
          result.is_primary = finalImages.length === 0 && uploadedCount === 0
          finalImages.push(result)
        }
        uploadedCount++
        uploadProgress.value = Math.round((uploadedCount / totalFiles) * 100)
      }
      uploading.value = false
      uploadProgress.value = 0
    }

    if (finalImages.length > 0 && !finalImages.some(img => img.is_primary)) {
      finalImages[0]!.is_primary = true
    }

    const payload: any = {
      name: form.value.name || '',
      description: form.value.description || '',
      short_description: form.value.short_description || '',
      price: form.value.price ?? 0,
      compare_price: form.value.compare_price ?? 0,
      category_id: form.value.category_id ?? null,
      status: form.value.status || 'active',
      stock_quantity: form.value.stock_quantity ?? 0,
      stock_status: form.value.stock_status || 'out_of_stock',
      sku: form.value.sku || '',
      weight: form.value.weight ?? 0,
      dimensions: [
        form.value.dimensions_length,
        form.value.dimensions_width,
        form.value.dimensions_height
      ].filter(d => d !== null && d !== undefined).join(' x ') || null,
      meta_title: form.value.meta_title || '',
      meta_description: form.value.meta_description || '',
      meta_keywords: form.value.meta_keywords || '',
      images: finalImages.map((img) => ({
        secure_url: img.secure_url,
        public_id: img.public_id,
        is_primary: img.is_primary,
        thumbnail: img.thumbnail || img.secure_url,
        medium: img.medium || img.secure_url,
        large: img.large || img.secure_url
      }))
    }

    if (form.value.images_to_delete && form.value.images_to_delete.length > 0) {
      payload.images_to_delete = form.value.images_to_delete
    }

    const response = await api.put(`/v1/products/${product.value.id}`, payload)

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
    uploading.value = false
    uploadProgress.value = 0
  }
}

onMounted(() => {
  fetchCategories()
  fetchProduct()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
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

.category-item {
  transition: all 0.2s ease;
}

/* Custom scrollbar for dropdown */
.max-h-80::-webkit-scrollbar {
  width: 6px;
}

.max-h-80::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.max-h-80::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.max-h-80::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Responsive fixes */
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