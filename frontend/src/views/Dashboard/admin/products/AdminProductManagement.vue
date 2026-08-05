<template>
  <div class="space-y-3 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Product Management</h1>
          <p class="text-xs sm:text-sm text-gray-600">Manage all platform products</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            size="small"
            :loading="loading"
            @click="fetchProducts"
            outlined
            class="text-xs sm:text-sm !px-2 sm:!px-4"
          />
          <Button 
            icon="pi pi-download" 
            label="Export" 
            size="small"
            @click="exportProducts"
            severity="success"
            class="text-xs sm:text-sm !px-2 sm:!px-4"
          />
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-4">
      <StatsCard
        title="Total"
        :value="stats.total || 0"
        icon="pi pi-box"
        color="blue"
      />
      <StatsCard
        title="Active"
        :value="stats.active || 0"
        icon="pi pi-check-circle"
        color="green"
      />
      <StatsCard
        title="Inactive"
        :value="stats.inactive || 0"
        icon="pi pi-times-circle"
        color="red"
      />
      <StatsCard
        title="Trashed"
        :value="stats.trashed || 0"
        icon="pi pi-trash"
        color="red"
      />
      <StatsCard
        title="Out of Stock"
        :value="stats.out_of_stock || 0"
        icon="pi pi-exclamation-circle"
        color="orange"
      />
      <StatsCard
        title="Total Value"
        :value="formatPrice(stats.total_value || 0)"
        icon="pi pi-dollar"
        color="green"
      />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
          <InputText 
            v-model="filters.search" 
            placeholder="Search products..." 
            class="pl-8 w-full text-xs sm:text-sm"
            size="small"
            @input="onFilterChange"
          />
        </div>
        <Dropdown 
          v-model="filters.status" 
          :options="statusOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Status"
          class="w-full text-xs sm:text-sm"
          size="small"
          @change="onFilterChange"
        />
        <Dropdown 
          v-model="filters.stock_status" 
          :options="stockOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="Stock Status"
          class="w-full text-xs sm:text-sm"
          size="small"
          @change="onFilterChange"
        />
        <div class="flex gap-2">
          <Button 
            icon="pi pi-filter-slash" 
            label="Clear" 
            size="small"
            outlined
            @click="clearFilters"
            class="flex-1 text-xs sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        :value="products"
        lazy
        paginator
        :rows="filters.per_page"
        :totalRecords="pagination?.total || 0"
        :first="first"
        @page="onPageChange"
        @sort="onSort"
        v-model:selection="selectedProducts"
        dataKey="id"
        class="p-datatable-sm"
        :loading="loading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        responsiveLayout="scroll"
      >
        <!-- Selection Column -->
        <Column 
          selectionMode="multiple" 
          style="width: 2rem" 
          class="hidden sm:table-cell"
        />
        
        <!-- ID Column -->
        <Column 
          field="id" 
          header="ID" 
          sortable 
          style="min-width: 50px" 
          class="hidden sm:table-cell"
        >
          <template #body="{ data }">
            <span class="text-xs text-center">{{ data.id }}</span>
          </template>
        </Column>

        <!-- Product Column -->
        <Column 
          header="Product" 
          sortable 
          field="name" 
          style="min-width: 120px"
        >
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <img 
                :src="data.primary_image?.url || '/placeholder-product.png'" 
                :alt="data.name"
                class="w-8 h-8 sm:w-10 sm:h-10 object-cover rounded border border-gray-200 shrink-0"
              />
              <div class="min-w-0">
                <!-- Mobile: First word only, Desktop: Full name -->
                <p class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                  <span class="md:hidden">{{ getFirstWord(data.name) }}</span>
                  <span class="hidden md:inline">{{ data.name }}</span>
                </p>
                <p class="hidden md:block text-[10px] sm:text-xs text-gray-500 truncate">SKU: {{ data.sku || 'N/A' }}</p>
              </div>
            </div>
          </template>
        </Column>

        <!-- Vendor Column - Hidden on mobile -->
        <Column 
          field="vendor.business_name" 
          header="Vendor" 
          sortable 
          style="min-width: 100px" 
          class="hidden sm:table-cell"
        >
          <template #body="{ data }">
            <span class="text-xs sm:text-sm truncate block max-w-[100px]">{{ data.vendor?.business_name || 'N/A' }}</span>
          </template>
        </Column>

        <!-- Price Column -->
        <Column 
          field="price" 
          header="Price" 
          sortable 
          style="min-width: 70px"
        >
          <template #body="{ data }">
            <span class="font-bold text-[#00685F] text-xs sm:text-sm">{{ formatPrice(data.price) }}</span>
          </template>
        </Column>

        <!-- Stock Column -->
        <Column 
          field="stock_quantity" 
          header="Stock" 
          sortable 
          style="min-width: 55px"
        >
          <template #body="{ data }">
            <span class="text-xs sm:text-sm font-medium">{{ data.stock_quantity || 0 }}</span>
          </template>
        </Column>

        <!-- Stock Status Column - Hidden on mobile/tablet -->
        <Column 
          field="stock_status" 
          header="Stock Status" 
          class="hidden lg:table-cell"
          sortable 
          style="min-width: 80px"
        >
          <template #body="{ data }">
            <Tag 
              :value="formatStockStatus(data.stock_status)" 
              :severity="getStockSeverity(data.stock_status)" 
              size="small"
              class="text-[10px] sm:text-xs"
            />
          </template>
        </Column>

        <!-- Status Column -->
        <Column 
          field="is_visible" 
          header="Status" 
          sortable 
          style="min-width: 65px"
        >
          <template #body="{ data }">
            <Tag 
              :value="data.deleted_at ? 'Trashed' : data.is_visible ? 'Active' : 'Inactive'" 
              :severity="data.deleted_at ? 'danger' : data.is_visible ? 'success' : 'secondary'" 
              size="small"
              class="text-[10px] sm:text-xs"
            />
          </template>
        </Column>

        <!-- Created At Column - Hidden on mobile/tablet -->
        <Column 
          field="created_at" 
          header="Created" 
          sortable 
          style="min-width: 85px" 
          class="hidden lg:table-cell"
        >
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions Column -->
        <Column 
          header="Actions" 
          style="min-width: 80px" 
          :frozen="true" 
          alignFrozen="right"
        >
          <template #body="{ data }">
            <div class="flex items-center gap-0 md:gap-0.5 justify-end">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewProduct(data.id)"
                tooltip="View"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small"
                @click="openEditDialog(data)"
                tooltip="Edit"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
              <Button 
                v-if="!data.deleted_at"
                icon="pi pi-trash" 
                text 
                rounded 
                size="small"
                severity="danger"
                @click="confirmDelete(data)"
                tooltip="Delete"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
              <Button 
                v-else
                icon="pi pi-refresh" 
                text 
                rounded 
                size="small"
                severity="success"
                @click="confirmRestore(data)"
                tooltip="Restore"
                class="!w-6 !h-6 sm:!w-7 sm:!h-7 !p-0"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-6 sm:py-8">
            <i class="pi pi-box text-3xl sm:text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2 text-sm sm:text-base">No products found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedProducts.length > 0" class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs sm:text-sm text-gray-600">
          {{ selectedProducts.length }} product(s) selected
        </span>
        <div class="flex flex-wrap gap-2">
          <Dropdown 
            v-model="bulkAction" 
            :options="bulkActionOptions" 
            optionLabel="label"
            optionValue="value"
            placeholder="Bulk Action"
            class="w-32 sm:w-40 text-xs sm:text-sm"
            size="small"
          />
          <Button 
            label="Apply" 
            size="small"
            :disabled="!bulkAction"
            @click="applyBulkAction"
            severity="primary"
            class="text-xs sm:text-sm"
          />
          <Button 
            icon="pi pi-times" 
            size="small"
            outlined
            @click="selectedProducts = []"
            class="!w-8 !h-8 sm:!w-9 !h-9"
          />
        </div>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'
import StatsCard from '@/components/StatsCard.vue'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const products = ref<any[]>([])
const selectedProducts = ref<any[]>([])
const stats = ref<any>({})
const loading = ref(false)
const pagination = ref<any>(null)
const first = ref(0)
const bulkAction = ref('')

const filters = reactive({
  search: '',
  status: '',
  stock_status: '',
  per_page: 15,
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

// Options
const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Trashed', value: 'trashed' },
]

const stockOptions = [
  { label: 'All Stock', value: '' },
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
]

const bulkActionOptions = [
  { label: 'Delete Selected', value: 'delete' },
  { label: 'Restore Selected', value: 'restore' },
  { label: 'Activate Selected', value: 'activate' },
  { label: 'Deactivate Selected', value: 'deactivate' },
  { label: 'Feature Selected', value: 'feature' },
  { label: 'Unfeature Selected', value: 'unfeature' },
]

// Methods
const fetchProducts = async () => {
  loading.value = true
  try {
    const response = await adminApi.getProductsList(filters)
    products.value = response.data.data || []
    pagination.value = {
      total: response.data.meta?.total || 0,
      current_page: response.data.meta?.current_page || 1,
      last_page: response.data.meta?.last_page || 1,
      per_page: response.data.meta?.per_page || filters.per_page,
    }
    first.value = (filters.page - 1) * filters.per_page
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await adminApi.getProductStats()
    stats.value = response.data.data || {}
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const onFilterChange = () => {
  filters.page = 1
  first.value = 0
  fetchProducts()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.stock_status = ''
  filters.page = 1
  first.value = 0
  fetchProducts()
}

const onPageChange = (event: any) => {
  const newPage = Math.floor(event.first / event.rows) + 1
  filters.page = newPage
  filters.per_page = event.rows
  first.value = event.first
  fetchProducts()
}

const onSort = (event: any) => {
  filters.sort_by = event.sortField
  filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc'
  filters.page = 1
  first.value = 0
  fetchProducts()
}

const viewProduct = (id: number) => {
  router.push(`/dashboard/admin/products/${id}/details`)
}

const openEditDialog = (product: any) => {
  router.push(`/dashboard/admin/products/${product.id}/edit`)
}

const confirmDelete = (product: any) => {
  confirm.require({
    message: `Are you sure you want to delete product "${product.name}"?`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.deleteProduct(product.id)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Product moved to trash',
          life: 3000
        })
        selectedProducts.value = []
        await fetchProducts()
        await fetchStats()
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

const confirmRestore = (product: any) => {
  confirm.require({
    message: `Are you sure you want to restore product "${product.name}"?`,
    header: 'Confirm Restore',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.restoreProduct(product.id)
        toast.add({
          severity: 'success',
          summary: 'Restored',
          detail: 'Product restored successfully',
          life: 3000
        })
        selectedProducts.value = []
        await fetchProducts()
        await fetchStats()
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

const applyBulkAction = async () => {
  if (!bulkAction.value || selectedProducts.value.length === 0) return

  const productIds = selectedProducts.value.map(p => p.id)
  
  try {
    await adminApi.bulkProductAction({ 
      product_ids: productIds, 
      action: bulkAction.value 
    })
    
    const actionLabels: Record<string, string> = {
      delete: 'moved to trash',
      restore: 'restored',
      activate: 'activated',
      deactivate: 'deactivated',
      feature: 'featured',
      unfeature: 'unfeatured',
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${productIds.length} products ${actionLabels[bulkAction.value]}`,
      life: 3000
    })
    
    selectedProducts.value = []
    bulkAction.value = ''
    await fetchProducts()
    await fetchStats()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to perform bulk action',
      life: 3000
    })
  }
}

const exportProducts = async () => {
  try {
    const response = await adminApi.exportProducts(filters)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `products_export_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({
      severity: 'success',
      summary: 'Export Started',
      detail: 'Products exported successfully',
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export products',
      life: 3000
    })
  }
}

// Helpers
const getFirstWord = (name: string): string => {
  if (!name) return ''
  const words = name.trim().split(' ')
  return words[0] || name
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
    day: 'numeric'
  })
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
  fetchProducts()
  fetchStats()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f9fafb;
  font-weight: 600;
  font-size: 0.65rem;
  text-transform: uppercase;
  color: #6b7280;
  padding: 0.4rem 0.3rem;
  white-space: nowrap;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.35rem 0.25rem;
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f3f4f6;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
  padding: 0.4rem !important;
}

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
  min-width: 1.8rem !important;
  height: 1.8rem !important;
  font-size: 0.7rem !important;
}

:deep(.p-datatable .p-paginator .p-paginator-first),
:deep(.p-datatable .p-paginator .p-paginator-prev),
:deep(.p-datatable .p-paginator .p-paginator-next),
:deep(.p-datatable .p-paginator .p-paginator-last) {
  min-width: 1.8rem !important;
  height: 1.8rem !important;
}

:deep(.p-datatable .p-paginator .p-paginator-element) {
  cursor: pointer !important;
  user-select: none !important;
}

:deep(.p-datatable .p-paginator .p-paginator-element:hover) {
  background: #e5e7eb !important;
  border-radius: 50% !important;
}

:deep(.p-datatable .p-paginator .p-paginator-element.p-disabled) {
  cursor: not-allowed !important;
  opacity: 0.5 !important;
}

:deep(.p-datatable .p-tag) {
  font-size: 0.6rem !important;
  padding: 0.05rem 0.35rem !important;
}

/* Mobile responsive */
@media (max-width: 640px) {
  /* Make last column sticky on mobile */
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child),
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child) {
    position: sticky !important;
    right: 0 !important;
    background: white !important;
    z-index: 3 !important;
    box-shadow: -2px 0 4px rgba(0,0,0,0.05);
  }

  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.5rem !important;
    padding: 0.25rem 0.15rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.5rem !important;
    padding: 0.2rem 0.1rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm) {
    min-width: 1.2rem !important;
    height: 1.2rem !important;
    padding: 0.05rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.45rem !important;
  }

  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.4rem !important;
    height: 1.4rem !important;
    font-size: 0.55rem !important;
  }

  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.4rem !important;
    height: 1.4rem !important;
  }

  :deep(.p-datatable .p-tag) {
    font-size: 0.45rem !important;
    padding: 0.02rem 0.15rem !important;
  }
  
  /* Ensure product name doesn't overflow */
  :deep(.p-datatable .p-datatable-tbody > tr > td:first-child) {
    max-width: 120px;
  }
}

/* Tablet responsive */
@media (min-width: 641px) and (max-width: 1024px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.6rem !important;
    padding: 0.35rem 0.25rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.6rem !important;
    padding: 0.3rem 0.2rem !important;
  }
}

/* Force button sizing */
:deep(.p-button.p-button-icon-only) {
  width: 1.8rem !important;
  height: 1.8rem !important;
}

@media (max-width: 640px) {
  :deep(.p-button.p-button-icon-only) {
    width: 1.5rem !important;
    height: 1.5rem !important;
  }
}
</style>