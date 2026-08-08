<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Add New Product</h1>
          <p class="text-sm sm:text-base text-gray-600">Create a new product listing</p>
        </div>
        <Button 
          label="Fill Test Data" 
          icon="pi pi-file" 
          severity="secondary" 
          outlined
          class="text-sm"
          @click="fillTestData"
        />
      </div>
    </div>

    <!-- Product Form -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="submitProduct" class="space-y-4 sm:space-y-6">
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

          <!-- Category with Collapsible Subcategories -->
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
                          <!-- ✅ Show child count for parent categories -->
          <span v-if="item.hasChildren" class="text-xs text-gray-400">
            ({{ item.children.length }})
          </span>
          <!-- ✅ Show product count for leaf categories (optional) -->
          <span v-else-if="item.product_count !== undefined && item.product_count > 0" class="text-xs text-gray-400">
            ({{ item.product_count }} products)
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
                            <span v-if="child.product_count !== undefined" class="text-xs text-gray-400">
                              ({{ child.product_count }})
                            </span>
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
              placeholder="Enter SKU (leave empty to auto-generate)" 
              class="w-full text-sm sm:text-base"
            />
            <small class="text-gray-400 text-xs sm:text-sm">Leave empty to auto-generate</small>
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
                />
                <small class="text-gray-400 text-xs sm:text-sm">{{ (form.meta_title?.length || 0) }}/60 characters</small>
                <small class="text-gray-400 text-xs sm:text-sm block">Leave empty to auto-generate</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <Textarea 
                  v-model="form.meta_description" 
                  rows="2" 
                  placeholder="SEO description (max 160 characters)" 
                  class="w-full text-sm sm:text-base"
                  maxlength="160"
                />
                <small class="text-gray-400 text-xs sm:text-sm">{{ (form.meta_description?.length || 0) }}/160 characters</small>
                <small class="text-gray-400 text-xs sm:text-sm block">Leave empty to auto-generate</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                <InputText 
                  v-model="form.meta_keywords" 
                  placeholder="comma, separated, keywords" 
                  class="w-full text-sm sm:text-base"
                />
              </div>
            </div>
          </div>

          <!-- Images -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Product Images <span class="text-red-500">*</span>
            </label>

            <FileUpload 
              name="images[]" 
              :customUpload="true"
              @select="onFileSelect"
              @remove="onFileRemove"
              multiple
              accept="image/*"
              :maxFileSize="5242880"
              class="w-full"
              :disabled="submitting"
            >
              <template #empty>
                <p class="text-gray-500 text-sm">Drag and drop images here or click to browse</p>
              </template>
            </FileUpload>
            <small class="text-gray-400 text-xs sm:text-sm">Maximum file size: 5MB per image. Images will be uploaded when you save the product.</small>
            
            <div v-if="form.images.length > 0" class="flex flex-wrap gap-2 mt-3 sm:mt-4">
              <div 
                v-for="(file, index) in form.images" 
                :key="'local-' + index" 
                class="relative w-16 h-16 sm:w-20 sm:h-20 border rounded overflow-hidden"
              >
                <img 
                  :src="getImagePreviewUrl(file)" 
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
                <div v-if="index === 0" class="absolute bottom-0 left-0 right-0 bg-[#00685F] text-white text-[8px] text-center py-0.5">
                  Primary
                </div>
              </div>
            </div>
            
            <small v-if="form.images.length === 0" class="text-red-500 text-xs sm:text-sm block mt-1">
              Please select at least one product image
            </small>
          </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <i class="pi pi-spin pi-spinner text-2xl text-[#00685F]"></i>
            <div class="flex-1">
              <p class="text-sm font-medium text-blue-700">Uploading images to Cloudinary...</p>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div 
                  class="bg-[#00685F] h-2 rounded-full transition-all duration-300"
                  :style="{ width: uploadProgress + '%' }"
                ></div>
              </div>
              <p class="text-xs text-blue-600 mt-1">{{ uploadProgress }}% complete</p>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Save Product" 
            icon="pi pi-check" 
            :loading="submitting"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45] text-sm sm:text-base"
          />
          <Button 
            label="Cancel" 
            severity="secondary" 
            class="w-full sm:w-auto text-sm sm:text-base"
            @click="router.back()" 
          />
        </div>
      </form>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
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

interface FormData {
  name: string
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
}

interface CategoryNode {
  id: number
  name: string
  label: string
  level: number
  icon?: string
  hasChildren: boolean
  children: CategoryNode[]
  product_count?: number
}

const router = useRouter()
const toast = useToast()
const dashboardStore = useVendorDashboardStore()

// State
const submitting = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const categories = ref<CategoryNode[]>([])
const errors = ref<Record<string, string[]>>({})

// Dropdown State
const isDropdownOpen = ref(false)
const searchQuery = ref('')
const expandedNodes = ref<number[]>([])
const selectedCategory = ref<{ id: number; label: string } | null>(null)
const dropdownRef = ref<HTMLElement | null>(null)

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
  images: []
})

// Computed
const filteredTree = computed(() => {
  if (!searchQuery.value.trim()) {
    return categories.value
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
    collectIds(filterNodes(categories.value))
    expandedNodes.value = allExpanded
  }
  
  return filterNodes(categories.value)
})

const getImagePreviewUrl = (file: File): string => {
  return URL.createObjectURL(file)
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
      : [],
    product_count: cat.product_count || 0
  }))
}

const fetchCategories = async () => {
  try {
    const response = await api.get('/categories')
    const rawData = response.data.data
    categories.value = buildCategoryTree(rawData)
    console.log('Category tree:', categories.value)
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
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

// ✅ Toggle expand/collapse
const toggleExpand = (id: number) => {
  const index = expandedNodes.value.indexOf(id)
  if (index > -1) {
    expandedNodes.value.splice(index, 1)
  } else {
    expandedNodes.value.push(id)
  }
  // Force reactivity
  expandedNodes.value = [...expandedNodes.value]
  console.log('Expanded nodes:', expandedNodes.value)
}

const isExpanded = (id: number): boolean => {
  return expandedNodes.value.includes(id)
}

const filterCategories = () => {
  // The filtering is handled by the computed property
}

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isDropdownOpen.value = false
  }
}

// Upload methods
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

const onFileSelect = (event: any) => {
  const files = event.files
  if (files && files.length > 0) {
    form.value.images = [...form.value.images, ...files]
    toast.add({
      severity: 'info',
      summary: 'Files Selected',
      detail: `${files.length} image(s) selected. They will be uploaded when you save the product.`,
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

const submitProduct = async () => {
  errors.value = {}
  
  // Validate required fields
  if (!form.value.name) {
    errors.value = { name: ['Product name is required'] }
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Product name is required',
      life: 3000
    })
    return
  }

  if (!form.value.price || form.value.price <= 0) {
    errors.value = { price: ['Price must be greater than 0'] }
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Price must be greater than 0',
      life: 3000
    })
    return
  }

  if (!form.value.category_id) {
    errors.value = { category_id: ['Category is required'] }
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select a category',
      life: 3000
    })
    return
  }

  if (form.value.images.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Images',
      detail: 'Please select at least one product image',
      life: 3000
    })
    return
  }

  submitting.value = true
  uploading.value = true
  uploadProgress.value = 0

  try {
    const uploadedImagesList: UploadedImage[] = []
    const totalFiles = form.value.images.length

    for (let i = 0; i < form.value.images.length; i++) {
      const file = form.value.images[i]
      const result = await uploadToCloudinary(file as File)
      if (result) {
        result.is_primary = i === 0
        uploadedImagesList.push(result)
      }
      uploadProgress.value = Math.round(((i + 1) / totalFiles) * 100)
    }

    uploading.value = false

    const payload = {
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
      images: uploadedImagesList.map((img) => ({
        secure_url: img.secure_url,
        public_id: img.public_id,
        is_primary: img.is_primary,
        thumbnail: img.thumbnail || img.secure_url,
        medium: img.medium || img.secure_url,
        large: img.large || img.secure_url
      }))
    }

    console.log('📤 Submitting product:', payload)

    const response = await api.post('/v1/products', payload)

    if (response.data.status === 'success' || response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product created successfully',
        life: 3000
      })
      
      await dashboardStore.fetchProducts(1, 15, true)
      router.push('/dashboard/vendor/products')
    }
  } catch (error: any) {
    console.error('❌ Product creation error:', error.response?.data)
    
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
        detail: error.response?.data?.message || 'Failed to create product',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
    uploading.value = false
    uploadProgress.value = 0
  }
}

const fillTestData = () => {
  if (categories.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Loading Categories',
      detail: 'Please wait for categories to load',
      life: 2000
    })
    return
  }
  
  const timestamp = Date.now().toString().slice(-6)
  const sku = `SKU-HEAD-${timestamp}`
  
  form.value.name = 'Premium Wireless Headphones'
  form.value.description = 'High-quality wireless headphones with noise cancellation, 40-hour battery life, and premium sound quality.'
  form.value.short_description = 'Premium wireless headphones with noise cancellation and 40-hour battery.'
  form.value.price = 149.99
  form.value.compare_price = 199.99
  
  // Try to find a subcategory
  const firstCategory = categories.value[0]
  const subCategory = firstCategory?.children?.[0]
  form.value.category_id = subCategory?.id || firstCategory?.id || null
  
  // Update selected category display
  if (form.value.category_id) {
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
    const found = findCategory(categories.value, form.value.category_id)
    if (found) {
      selectedCategory.value = { id: found.id, label: found.label }
    }
  }
  
  form.value.status = 'active'
  form.value.stock_quantity = 50
  form.value.stock_status = 'in_stock'
  form.value.sku = sku
  form.value.weight = 0.45
  form.value.dimensions_length = 20
  form.value.dimensions_width = 15
  form.value.dimensions_height = 8
  form.value.meta_title = ''
  form.value.meta_description = ''
  form.value.meta_keywords = ''
  form.value.images = []
  
  toast.add({
    severity: 'info',
    summary: '✅ Test Data Loaded',
    detail: 'Add images and submit!',
    life: 3000
  })
}

// Lifecycle
onMounted(() => {
  fetchCategories()
  document.addEventListener('click', handleClickOutside)
  ;(window as any).fillTestData = fillTestData
  console.log('📝 Type: fillTestData() in console to auto-fill the form')
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