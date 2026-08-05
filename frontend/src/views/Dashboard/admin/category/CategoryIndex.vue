<template>
  <div class="space-y-3 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Categories</h1>
          <p class="text-xs sm:text-sm text-gray-600">Manage your product categories</p>
        </div>
        <Button 
          label="Add Category" 
          icon="pi pi-plus" 
          severity="primary"
          size="small"
          class="w-full sm:w-auto text-sm"
          @click="router.push('/dashboard/admin/categories/create')"
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Search</label>
          <InputText 
            v-model="filters.search" 
            placeholder="Search categories..." 
            class="w-full text-sm"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Status</label>
          <Dropdown 
            v-model="filters.status" 
            :options="statusOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="All Status" 
            class="w-full text-sm"
            @change="fetchCategories"
          />
        </div>
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Parent Category</label>
          <Dropdown 
            v-model="filters.parent_id" 
            :options="parentOptions" 
            optionLabel="name"
            optionValue="id"
            placeholder="All Categories" 
            class="w-full text-sm"
            @change="fetchCategories"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Categories Table -->
    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <!-- Mobile Card View -->
      <div class="block md:hidden">
        <div v-for="category in paginatedCategories" :key="category.id" class="border-b border-gray-100 p-4 hover:bg-gray-50 transition">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span v-if="category.icon" class="text-lg">
                  <i :class="category.icon"></i>
                </span>
                <p class="text-sm font-semibold text-gray-900 truncate">{{ category.name }}</p>
              </div>
              <p class="text-xs text-gray-500 truncate">{{ category.slug }}</p>
              <div class="flex items-center gap-2 mt-1 flex-wrap">
                <Tag 
                  :value="category.is_active ? 'Active' : 'Inactive'" 
                  :severity="category.is_active ? 'success' : 'danger'"
                  size="small"
                />
                <span class="text-xs text-gray-500">ID: {{ category.id }}</span>
                <span class="text-xs text-gray-500">{{ category.product_count || 0 }} products</span>
              </div>
            </div>
            <div class="flex items-center gap-0 ml-2 shrink-0">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewCategory(category.id)"
                tooltip="View"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small"
                severity="info"
                @click="editCategory(category.id)"
                tooltip="Edit"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
              <Button 
                v-if="category.is_active"
                icon="pi pi-times" 
                text 
                rounded 
                size="small"
                severity="warning"
                @click="toggleStatus(category)"
                tooltip="Deactivate"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
              <Button 
                v-else
                icon="pi pi-check" 
                text 
                rounded 
                size="small"
                severity="success"
                @click="toggleStatus(category)"
                tooltip="Activate"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
              <Button 
                icon="pi pi-trash" 
                text 
                rounded 
                size="small"
                severity="danger"
                @click="confirmDelete(category)"
                tooltip="Delete"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
            </div>
          </div>
        </div>
        <div v-if="filteredCategories.length === 0" class="py-12 text-center text-gray-500">
          <i class="pi pi-inbox text-4xl text-gray-300 block mb-2"></i>
          <p>No categories found</p>
        </div>
      </div>

      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <div class="flex items-center gap-2 cursor-pointer" @click="sortBy('id')">
                  ID
                  <i v-if="sortField === 'id'" :class="sortDirection === 'asc' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-xs"></i>
                </div>
              </th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <div class="flex items-center gap-2 cursor-pointer" @click="sortBy('name')">
                  Name
                  <i v-if="sortField === 'name'" :class="sortDirection === 'asc' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-xs"></i>
                </div>
              </th>
              <th class="hidden lg:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="category in paginatedCategories" :key="category.id" class="hover:bg-gray-50 transition">
              <td class="px-4 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">{{ category.id }}</td>
              <td class="px-4 sm:px-6 py-3 sm:py-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ category.name }}</p>
                  <p class="text-xs text-gray-500">{{ category.slug }}</p>
                  <p v-if="category.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ category.description }}</p>
                </div>
              </td>
              <td class="hidden lg:table-cell px-4 sm:px-6 py-3 sm:py-4">
                <span v-if="category.icon" class="text-xl">
                  <i :class="category.icon"></i>
                </span>
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
              <td class="px-4 sm:px-6 py-3 sm:py-4">
                <Tag 
                  :value="category.is_active ? 'Active' : 'Inactive'" 
                  :severity="category.is_active ? 'success' : 'danger'"
                  size="small"
                />
              </td>
              <td class="px-4 sm:px-6 py-3 sm:py-4 text-sm text-center text-gray-900">{{ category.product_count || 0 }}</td>
              <td class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center gap-0 sm:gap-1 lg:gap-2">
                  <Button 
                    icon="pi pi-eye" 
                    text 
                    rounded 
                    size="small"
                    @click="viewCategory(category.id)"
                    tooltip="View"
                    tooltipOptions="{ position: 'top' }"
                    class="w-7! h-7! sm:w-8! sm:h-8!"
                  />
                  <Button 
                    icon="pi pi-pencil" 
                    text 
                    rounded 
                    size="small"
                    severity="info"
                    @click="editCategory(category.id)"
                    tooltip="Edit"
                    tooltipOptions="{ position: 'top' }"
                    class="w-7! h-7! sm:w-8! sm:h-8!"
                  />
                  <Button 
                    v-if="category.is_active"
                    icon="pi pi-times" 
                    text 
                    rounded 
                    size="small"
                    severity="warning"
                    @click="toggleStatus(category)"
                    tooltip="Deactivate"
                    tooltipOptions="{ position: 'top' }"
                    class="w-7! h-7! sm:w-8! sm:h-8!"
                  />
                  <Button 
                    v-else
                    icon="pi pi-check" 
                    text 
                    rounded 
                    size="small"
                    severity="success"
                    @click="toggleStatus(category)"
                    tooltip="Activate"
                    tooltipOptions="{ position: 'top' }"
                    class="w-7! h-7! sm:w-8! sm:h-8!"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    text 
                    rounded 
                    size="small"
                    severity="danger"
                    @click="confirmDelete(category)"
                    tooltip="Delete"
                    tooltipOptions="{ position: 'top' }"
                    class="w-7! h-7! sm:w-8! sm:h-8!"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="filteredCategories.length === 0">
              <td colspan="7" class="px-4 sm:px-6 py-12 text-center text-gray-500">
                <i class="pi pi-inbox text-4xl text-gray-300 block mb-2"></i>
                <p>No categories found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="border-t border-gray-200 px-3 sm:px-6 py-3 sm:py-4">
        <Paginator 
          v-model:first="first" 
          :rows="itemsPerPage" 
          :totalRecords="filteredCategories.length"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          @page="onPageChange"
          class="border-0 text-sm"
        />
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Paginator from 'primevue/paginator'
import { adminApi } from '@/api/endpoints/admin/admin.api'
import type { Category } from '@/types/models/category.type'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const categories = ref<Category[]>([])
const loading = ref(false)
const first = ref(0)
const itemsPerPage = ref(10)
const sortField = ref<string>('id')
const sortDirection = ref<'asc' | 'desc'>('desc')

// Filters
const filters = ref({
  search: '',
  status: '',
  parent_id: null as number | null
})

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

// Computed
const parentOptions = computed(() => {
  return categories.value
    .filter(c => !c.parent_id || c.is_active)
    .map(c => ({ id: c.id, name: c.name }))
})

const filteredCategories = computed(() => {
  let result = categories.value
  
  // Search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    result = result.filter(c => 
      c.name.toLowerCase().includes(search) || 
      c.slug.toLowerCase().includes(search) ||
      (c.description && c.description.toLowerCase().includes(search))
    )
  }
  
  // Status filter
  if (filters.value.status === 'active') {
    result = result.filter(c => c.is_active)
  } else if (filters.value.status === 'inactive') {
    result = result.filter(c => !c.is_active)
  }
  
  // Parent filter
  if (filters.value.parent_id) {
    result = result.filter(c => c.parent_id === filters.value.parent_id)
  }
  
  // Sort
  result = [...result].sort((a, b) => {
    let aVal: any = a[sortField.value as keyof Category]
    let bVal: any = b[sortField.value as keyof Category]
    
    if (typeof aVal === 'string') {
      aVal = aVal.toLowerCase()
      bVal = bVal.toLowerCase()
    }
    
    if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1
    if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })
  
  return result
})

const paginatedCategories = computed(() => {
  const start = first.value
  const end = start + itemsPerPage.value
  return filteredCategories.value.slice(start, end)
})

// Methods
const fetchCategories = async () => {
  loading.value = true
  try {
    const response = await adminApi.getAllCategoriesByAdmin()
    const data = response.data.data
    console.log('Fetched categories:', data)
    categories.value = Array.isArray(data) ? data : [data]
    first.value = 0
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load categories',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    first.value = 0
  }, 300)
}

const onPageChange = (event: any) => {
  first.value = event.first
  itemsPerPage.value = event.rows
}

const sortBy = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

const viewCategory = (id: number) => {
  router.push(`/dashboard/admin/categories/${id}`)
}

const editCategory = (id: number) => {
  router.push(`/dashboard/admin/categories/${id}/edit`)
}

const toggleStatus = async (category: Category) => {
  try {
    const newStatus = !category.is_active
    await adminApi.updateCategory(category?.id.toString(), { is_active: newStatus })
    
    const index = categories.value.findIndex(c => c.id === category.id)
    if (index !== -1) {
      categories.value[index]!.is_active = newStatus
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Category ${newStatus ? 'activated' : 'deactivated'} successfully`,
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update category status',
      life: 3000
    })
  }
}

const confirmDelete = (category: Category) => {
  confirm.require({
    message: `Are you sure you want to delete category "${category.name}"? This action cannot be undone.`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.deleteCategory(category.id.toString())
        await fetchCategories()
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Category deleted successfully',
          life: 3000
        })
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete category',
          life: 3000
        })
      }
    }
  })
}

// Lifecycle
onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
:deep(.p-paginator) {
  padding: 0.3rem 0;
  background: transparent;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: #00685F;
  color: white;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  min-width: 2rem;
  height: 2rem;
}

:deep(.p-paginator .p-paginator-first),
:deep(.p-paginator .p-paginator-prev),
:deep(.p-paginator .p-paginator-next),
:deep(.p-paginator .p-paginator-last) {
  min-width: 2rem;
  height: 2rem;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Mobile card view spacing */
@media (max-width: 768px) {
  :deep(.p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.8rem;
    height: 1.8rem;
    font-size: 0.8rem;
  }
  
  :deep(.p-paginator .p-paginator-first),
  :deep(.p-paginator .p-paginator-prev),
  :deep(.p-paginator .p-paginator-next),
  :deep(.p-paginator .p-paginator-last) {
    min-width: 1.8rem;
    height: 1.8rem;
  }
}

/* Force button sizing */
:deep(.p-button.p-button-icon-only) {
  width: 2rem !important;
  height: 2rem !important;
}

@media (max-width: 640px) {
  :deep(.p-button.p-button-icon-only) {
    width: 1.8rem !important;
    height: 1.8rem !important;
  }
  
  :deep(.p-button.p-button-icon-only .p-button-icon) {
    font-size: 0.8rem !important;
  }
}
</style>