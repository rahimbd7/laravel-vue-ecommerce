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
            @click="router.push('/dashboard/admin/category')"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Category</h1>
            <p class="text-sm text-gray-600">Update category information</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Category Form -->
    <div v-else class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="updateCategory" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <!-- Name -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.name" 
              placeholder="Enter category name" 
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
              placeholder="Auto-generated from name" 
              class="w-full"
              :class="{ 'p-invalid': errors.slug }"
            />
            <small v-if="errors.slug" class="text-red-500 text-xs sm:text-sm">{{ errors.slug }}</small>
            <small v-else class="text-gray-400 text-xs sm:text-sm">Leave empty to auto-generate from name</small>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <Textarea 
              v-model="form.description" 
              rows="4" 
              placeholder="Enter category description" 
              class="w-full text-sm sm:text-base"
            />
          </div>

          <!-- Image Upload - No Automatic Upload -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>

            <div class="flex items-center gap-4">
              <!-- Image Preview -->
              <div v-if="getImagePreview()" class="w-20 h-20 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0">
                <img 
                  :src="getImagePreview()" 
                  alt="Category image" 
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
              </div>
              <div v-else class="w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center flex-shrink-0">
                <i class="pi pi-image text-3xl text-gray-400"></i>
              </div>
              
              <div class="flex-1">
                <FileUpload 
                  mode="basic" 
                  accept="image/*" 
                  :maxFileSize="2048000"
                  @select="onFileSelect"
                  chooseLabel="Choose Image"
                  class="w-full"
                  :disabled="submitting"
                />
                <small class="text-gray-400 text-xs sm:text-sm">Max file size: 2MB. Image will be uploaded when you save.</small>
              </div>
              
              <Button 
                v-if="getImagePreview() && !isNewImage"
                icon="pi pi-times" 
                text 
                rounded 
                severity="danger"
                @click="removeImage"
                tooltip="Remove image"
              />
              <Button 
                v-if="isNewImage"
                icon="pi pi-times" 
                text 
                rounded 
                severity="danger"
                @click="cancelNewImage"
                tooltip="Cancel upload"
              />
            </div>
            
            <!-- Upload Progress -->
            <div v-if="uploading" class="mt-3">
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
          </div>

          <!-- Icon -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Icon Class</label>
            <InputText 
              v-model="form.icon" 
              placeholder="e.g., fa-solid fa-house" 
              class="w-full text-sm sm:text-base"
            />
            <div v-if="form.icon" class="mt-2 flex items-center gap-2">
              <span class="text-sm text-gray-600">Preview:</span>
              <i :class="form.icon" class="text-xl"></i>
            </div>
          </div>

          <!-- Parent Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
            <Dropdown 
              v-model="form.parent_id" 
              :options="parentOptions" 
              optionLabel="name"
              optionValue="id"
              placeholder="Select parent category" 
              class="w-full"
              filter
            />
            <small class="text-gray-400 text-xs sm:text-sm">Leave empty for top-level category</small>
          </div>

          <!-- Position -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <InputNumber 
              v-model="form.position" 
              placeholder="0" 
              class="w-full"
              :min="0"
              :step="1"
            />
            <small class="text-gray-400 text-xs sm:text-sm">Order in which categories are displayed</small>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <div class="flex items-center gap-3 mt-1">
              <ToggleButton 
                v-model="form.is_active" 
                onLabel="Active" 
                offLabel="Inactive" 
                onIcon="pi pi-check" 
                offIcon="pi pi-times"
                class="w-24"
              />
            </div>
          </div>

          <!-- Featured -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Featured</label>
            <div class="flex items-center gap-3 mt-1">
              <ToggleButton 
                v-model="form.is_featured" 
                onLabel="Featured" 
                offLabel="Not Featured" 
                onIcon="pi pi-star" 
                offIcon="pi pi-star"
                class="w-32"
              />
            </div>
          </div>

          <!-- SEO Meta -->
          <div class="md:col-span-2 border-t border-gray-200 pt-4 sm:pt-6 mt-4">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3">SEO Meta</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                <InputText 
                  v-model="form.meta_title" 
                  placeholder="SEO title (max 60 characters)" 
                  class="w-full text-sm sm:text-base"
                  maxlength="60"
                />
                <small class="text-gray-400 text-xs sm:text-sm">{{ (form.meta_title?.length || 0) }}/60 characters</small>
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
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                <InputText 
                  v-model="metaKeywordsString" 
                  placeholder="Enter keywords separated by commas" 
                  class="w-full text-sm sm:text-base"
                />
                <small class="text-gray-400 text-xs sm:text-sm">Separate keywords with commas</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Update Category" 
            icon="pi pi-save" 
            :loading="submitting"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45] text-sm sm:text-base"
          />
          <Button 
            label="Cancel" 
            severity="secondary" 
            class="w-full sm:w-auto text-sm sm:text-base"
            @click="router.push('/dashboard/admin/category')" 
          />
        </div>
      </form>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import ToggleButton from 'primevue/togglebutton'
import FileUpload from 'primevue/fileupload'
import { adminApi } from '@/api/endpoints/admin/admin.api'
import type { Category, CategoryFormData } from '@/types/models/category.type'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const categoryId = route.params.id as string

// Cloudinary configuration
const CLOUDINARY_CLOUD_NAME = import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || 'your-cloud-name'
const CLOUDINARY_UPLOAD_PRESET = import.meta.env.VITE_CLOUDINARY_PRODUCT_PRESET || 'product_preset'

// State
const loading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const allCategories = ref<Category[]>([])
const imagePreview = ref<string>('')
const imageFile = ref<File | null>(null) // ✅ Store selected file
const isNewImage = ref(false) // ✅ Track if image is new
const errors = ref<Record<string, string[]>>({})

// Form
const form = ref<CategoryFormData>({
  name: '',
  slug: '',
  description: null,
  image: null,
  icon: null,
  parent_id: null,
  parent_uuid: null,
  position: 0,
  is_active: true,
  is_featured: false,
  meta_title: null,
  meta_description: null,
  meta_keywords: null
})

// Computed
const metaKeywordsString = computed({
  get: () => {
    if (!form.value.meta_keywords) return ''
    return Array.isArray(form.value.meta_keywords) 
      ? form.value.meta_keywords.join(', ') 
      : form.value.meta_keywords
  },
  set: (value: string) => {
    form.value.meta_keywords = value 
      ? value.split(',').map(k => k.trim()).filter(k => k)
      : null
  }
})

const parentOptions = computed(() => {
  const currentId = Number(categoryId)
  
  return allCategories.value
    .filter(c => {
      if (c.id === currentId) return false
      return c.is_active === true
    })
    .map(c => ({
      id: c.id,
      name: c.name,
      uuid: c.uuid
    }))
})

// ✅ Helper function to find parent by UUID
const findParentIdByUuid = (uuid: string | null): number | null => {
  if (!uuid) return null
  const parent = allCategories.value.find(c => c.uuid === uuid)
  return parent ? parent.id : null
}

// ✅ Get image preview URL
const getImagePreview = (): string => {
  // Show new image preview if selected
  if (imageFile.value) {
    return URL.createObjectURL(imageFile.value) as string
  }
  // Show existing image
  if (form.value.image && typeof form.value.image === 'string') {
    if (form.value.image.startsWith('http://') || form.value.image.startsWith('https://')) {
      return form.value.image
    }
    if (form.value.image.startsWith('categories/')) {
      return `/storage/${form.value.image}`
    }
    return form.value.image
  }
  return ''
}

// ✅ Fetch all data in correct order
const fetchData = async () => {
  loading.value = true
  try {
    // 1. First, load all categories
    const categoriesResponse = await adminApi.getAllCategoriesByAdmin()
    const data = categoriesResponse.data.data
    allCategories.value = Array.isArray(data) ? data : [data]
    
    // 2. Then, fetch the specific category
    const categoryResponse = await adminApi.getCategoriesListByAdmin(categoryId)
    const category = categoryResponse.data.data
  
    // 3. Find the parent ID
    const parentNumericId = findParentIdByUuid(category.parent_id)
    
    // 4. Set form values
    form.value = {
      name: category.name || '',
      slug: category.slug || '',
      description: category.description || null,
      image: category.image || null,
      icon: category.icon || null,
      parent_id: parentNumericId,
      parent_uuid: category.parent_id || null,
      position: category.position || 0,
      is_active: category.is_active !== undefined ? category.is_active : true,
      is_featured: category.is_featured || false,
      meta_title: category.meta_title || null,
      meta_description: category.meta_description || null,
      meta_keywords: category.meta_keywords || null
    }   
    
    // Set image preview
    if (category.image) {
      imagePreview.value = category.image
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load category',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// ✅ Upload file to Cloudinary (called only on submit)
const uploadToCloudinary = async (file: File): Promise<string | null> => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('upload_preset', CLOUDINARY_UPLOAD_PRESET)
  formData.append('folder', 'categories')

  try {
    const response = await fetch(
      `https://api.cloudinary.com/v1_1/${CLOUDINARY_CLOUD_NAME}/image/upload`,
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
    return result.secure_url
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

// ✅ Handle file selection - ONLY store the file, don't upload
const onFileSelect = (event: any) => {
  const file = event.files[0]
  if (!file) return
  
  // Store the file
  imageFile.value = file
  isNewImage.value = true
  
  // Show preview using object URL
  imagePreview.value = URL.createObjectURL(file)
  
  toast.add({
    severity: 'info',
    summary: 'Image Selected',
    detail: 'Image will be uploaded when you save the category',
    life: 3000
  })
}

// ✅ Cancel new image selection
const cancelNewImage = () => {
  imageFile.value = null
  isNewImage.value = false
  imagePreview.value = form.value.image as string || ''
}

const removeImage = () => {
  imageFile.value = null
  isNewImage.value = false
  imagePreview.value = ''
  form.value.image = null
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  if (img) {
    const name = form.value.name || 'Category'
    const initial = name.charAt(0).toUpperCase()
    img.src = `data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="50%25" font-size="40" text-anchor="middle" dy=".3em" fill="%239ca3af"%3E${initial}%3C/text%3E%3C/svg%3E`
    img.onerror = null
  }
}

const updateCategory = async () => {
  errors.value = {}
  submitting.value = true

  try {
    // ✅ Upload image to Cloudinary only during submit if there's a new image
    let finalImageUrl = form.value.image
    
    if (imageFile.value) {
      uploading.value = true
      uploadProgress.value = 0
      
      const uploadedUrl = await uploadToCloudinary(imageFile.value)
      if (uploadedUrl) {
        finalImageUrl = uploadedUrl
      }
      uploading.value = false
      uploadProgress.value = 0
    }
    
    const payload = {
      name: form.value.name || '',
      slug: form.value.slug || '',
      description: form.value.description || null,
      image: finalImageUrl, // ✅ Use uploaded or existing image
      icon: form.value.icon || null,
      parent_id: form.value.parent_id || null,
      position: form.value.position || 0,
      is_active: form.value.is_active !== undefined ? form.value.is_active : true,
      is_featured: form.value.is_featured || false,
      meta_title: form.value.meta_title || null,
      meta_description: form.value.meta_description || null,
      meta_keywords: form.value.meta_keywords || null
    }

    console.log('📤 Updating category with payload:', payload)

    const response = await adminApi.updateCategory(categoryId, payload)
    
    if (response.data.status === 'success') {
      // ✅ Clear the image file after successful upload
      imageFile.value = null
      isNewImage.value = false
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category updated successfully',
        life: 3000
      })
      router.push('/dashboard/admin/category')
    }
  } catch (error: any) {
    console.error('❌ Update error:', error)
    
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
        detail: error.response?.data?.message || 'Failed to update category',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
    uploading.value = false
    uploadProgress.value = 0
  }
}

// ✅ Lifecycle - load all data
onMounted(() => {
  fetchData()
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
}
</style>