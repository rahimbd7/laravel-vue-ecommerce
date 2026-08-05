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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Category Details</h1>
            <p class="text-sm text-gray-600">{{ category?.name }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            icon="pi pi-pencil" 
            label="Edit" 
            size="small"
            @click="router.push(`/dashboard/admin/categories/${categoryId}/edit`)"
            severity="primary"
          />
          <Button 
            v-if="category?.is_active"
            icon="pi pi-times" 
            label="Deactivate" 
            size="small"
            severity="warning"
            outlined
            @click="toggleStatus"
          />
          <Button 
            v-else
            icon="pi pi-check" 
            label="Activate" 
            size="small"
            severity="success"
            outlined
            @click="toggleStatus"
          />
          <Button 
            icon="pi pi-trash" 
            label="Delete" 
            size="small"
            severity="danger"
            outlined
            @click="confirmDelete"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
      <i class="pi pi-exclamation-circle text-3xl sm:text-4xl text-red-500 mb-3 block"></i>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <Button 
        label="Try Again" 
        @click="fetchCategory" 
        class="mt-3"
      />
    </div>

    <!-- Category Content -->
    <template v-else-if="category">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Category Info Card -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="text-xl font-bold text-gray-900">{{ category.name }}</h3>
                <p class="text-sm text-gray-500">Slug: {{ category.slug }}</p>
                <p class="text-sm text-gray-500">UUID: {{ category.uuid }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Tag 
                  :value="category.is_active ? 'Active' : 'Inactive'" 
                  :severity="category.is_active ? 'success' : 'danger'" 
                />
                <Tag v-if="category.is_featured" value="Featured" severity="warning" />
              </div>
            </div>

            <Divider />

            <!-- Category Image -->
            <div v-if="category.image" class="mb-4">
              <p class="text-sm text-gray-500 mb-2">Image</p>
              <div class="w-32 h-32 rounded-lg overflow-hidden border border-gray-200">
                <img 
                  :src="category.image" 
                  :alt="category.name" 
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Category ID</p>
                <p class="text-lg font-bold text-gray-900">{{ category.id }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Position</p>
                <p class="text-lg font-bold text-gray-900">{{ category.position || 0 }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Products</p>
                <p class="text-lg font-bold text-[#00685F]">{{ category.product_count || 0 }}</p>
              </div>
              <div v-if="category.icon">
                <p class="text-sm text-gray-500">Icon</p>
                <p class="text-lg">
                  <i :class="category.icon" class="text-2xl"></i>
                  <span class="ml-2 text-sm text-gray-600">{{ category.icon }}</span>
                </p>
              </div>
              <div v-if="parentCategory">
                <p class="text-sm text-gray-500">Parent Category</p>
                <p class="text-lg font-bold text-gray-900">{{ parentCategory.name }}</p>
                <p class="text-xs text-gray-500">ID: {{ parentCategory.id }}</p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="category.description" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Description</h4>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ category.description }}</p>
          </div>

          <!-- SEO Meta -->
          <div v-if="category.meta_title || category.meta_description || category.meta_keywords" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">SEO Meta</h4>
            <div class="space-y-3">
              <div v-if="category.meta_title">
                <p class="text-sm text-gray-500">Meta Title</p>
                <p class="text-sm font-medium text-gray-900">{{ category.meta_title }}</p>
              </div>
              <div v-if="category.meta_description">
                <p class="text-sm text-gray-500">Meta Description</p>
                <p class="text-sm text-gray-700">{{ category.meta_description }}</p>
              </div>
              <div v-if="category.meta_keywords">
                <p class="text-sm text-gray-500">Meta Keywords</p>
                <div class="flex flex-wrap gap-1">
                  <Tag 
                    v-for="keyword in getKeywordsArray(category.meta_keywords)" 
                    :key="keyword"
                    :value="keyword" 
                    severity="info" 
                    size="small"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4">
          <!-- Information -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Information</h4>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Created</p>
                <p class="text-sm text-gray-900">{{ formatDate(category.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Last Updated</p>
                <p class="text-sm text-gray-900">{{ formatDate(category.updated_at) }}</p>
              </div>
              <div v-if="category.deleted_at">
                <p class="text-xs text-gray-500">Deleted</p>
                <p class="text-sm text-red-600">{{ formatDate(category.deleted_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Subcategories -->
          <div v-if="category.children && category.children.length > 0" class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">
              Subcategories 
              <span class="text-sm text-gray-500 font-normal">({{ category.children.length }})</span>
            </h4>
            <div class="space-y-2">
              <div 
                v-for="child in category.children" 
                :key="child.id"
                @click="router.push(`/dashboard/admin/categories/${child.id}`)"
                class="p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-[#00685F] hover:bg-gray-100 transition cursor-pointer"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i v-if="child.icon" :class="child.icon" class="text-[#00685F]"></i>
                    <p class="font-medium text-gray-900 text-sm">{{ child.name }}</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">{{ child.product_count || 0 }} products</span>
                    <Tag 
                      :value="child.is_active ? 'Active' : 'Inactive'" 
                      :severity="child.is_active ? 'success' : 'danger'" 
                      size="small"
                    />
                  </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ child.slug }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Quick Actions</h4>
            <div class="space-y-2">
              <Button 
                label="Edit Category" 
                icon="pi pi-pencil" 
                class="w-full justify-start"
                severity="info"
                text
                @click="router.push(`/dashboard/admin/categories/${categoryId}/edit`)"
              />
              <Button 
                :label="category.is_active ? 'Deactivate' : 'Activate'" 
                :icon="category.is_active ? 'pi pi-times' : 'pi pi-check'"
                class="w-full justify-start"
                :severity="category.is_active ? 'warning' : 'success'"
                text
                @click="toggleStatus"
              />
              <Button 
                label="Delete" 
                icon="pi pi-trash" 
                class="w-full justify-start"
                severity="danger"
                text
                @click="confirmDelete"
              />
            </div>
          </div>
        </div>
      </div>
    </template>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'
import type { Category } from '@/types/models/category.type'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const categoryId = route.params.id as string

// State
const category = ref<Category | null>(null)
const loading = ref(false)
const error = ref('')

// Computed
const parentCategory = computed(() => {
  if (!category.value?.parent_id) return null
  // If parent data is loaded in the category
  return category.value.parent || null
})

// Methods
const fetchCategory = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getCategoriesListByAdmin(categoryId)
    category.value = response.data.data
    console.log('Category data:', category.value)
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load category'
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

const toggleStatus = async () => {
  if (!category.value) return
  
  try {
    const newStatus = !category.value.is_active
    await adminApi.updateCategory(categoryId, { is_active: newStatus })
    category.value.is_active = newStatus
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Category ${newStatus ? 'activated' : 'deactivated'} successfully`,
      life: 3000
    })
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to update category status',
      life: 3000
    })
  }
}

const confirmDelete = () => {
  if (!category.value) return
  
  confirm.require({
    message: `Are you sure you want to delete category "${category.value.name}"? This action cannot be undone.`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.deleteCategory(categoryId)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Category deleted successfully',
          life: 3000
        })
        router.push('/dashboard/admin/categories')
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to delete category',
          life: 3000
        })
      }
    }
  })
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  if (img) {
    const name = category.value?.name || 'Category'
    const initials = name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
    img.src = `data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="50%25" font-size="40" text-anchor="middle" dy=".3em" fill="%239ca3af"%3E${initials}%3C/text%3E%3C/svg%3E`
    img.onerror = null
  }
}

const getKeywordsArray = (keywords: string | string[] | null): string[] => {
  if (!keywords) return []
  if (Array.isArray(keywords)) return keywords
  return keywords.split(',').map(k => k.trim()).filter(k => k)
}

const formatDate = (date: string | undefined): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchCategory()
})
</script>

<style scoped>
/* Responsive fixes */
@media (max-width: 640px) {
  :deep(.p-dialog) {
    margin: 0.5rem !important;
  }
}
</style>