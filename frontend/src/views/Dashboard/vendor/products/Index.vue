<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">My Products</h1>
          <p class="text-sm sm:text-base text-gray-600">Manage your product inventory</p>
        </div>
        <Button 
          label="Add Product" 
          icon="pi pi-plus" 
          class="w-full sm:w-auto text-sm sm:text-base"
          @click="router.push('/dashboard/vendor/products/create')" 
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <InputText 
          v-model="filters.search" 
          placeholder="Search products..." 
          class="w-full sm:w-64"
          @keyup.enter="fetchProducts"
        />
        <Dropdown 
          v-model="filters.status" 
          :options="statusOptions" 
          placeholder="All Status" 
          class="w-full sm:w-48"
          @change="fetchProducts"
        />
        <Button icon="pi pi-refresh" text @click="resetFilters" class="self-start sm:self-auto" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading.products" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="products.length === 0" class="bg-white rounded-lg shadow-sm p-8 sm:p-12 text-center">
      <i class="pi pi-box text-5xl sm:text-6xl text-gray-300 mb-4 sm:mb-6 block"></i>
      <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2">No Products Yet</h3>
      <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Start adding your first product</p>
      <Button label="Add Product" icon="pi pi-plus" class="text-sm sm:text-base" @click="router.push('/dashboard/vendor/products/create')" />
    </div>

    <!-- Products Table -->
    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable 
        :value="products" 
        class="p-datatable-sm"
        paginator
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        lazy
        @page="onPageChange"
        responsiveLayout="scroll"
        scrollable
        scrollHeight="flex"
      >
        <Column header="Product" style="min-width: 150px; max-width: 250px;">
          <template #body="{ data }">
            <div class="flex items-center gap-2 sm:gap-3">
              <img 
                :src="getProductImage(data)" 
                :alt="data.name"
                class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 object-cover rounded flex-shrink-0"
                @error="handleImageError"
              />
              <div class="min-w-0">
                <p class="font-medium text-gray-900 text-xs sm:text-sm md:text-base truncate">{{ data.name }}</p>
                <p class="text-xs text-gray-500 truncate hidden sm:block">SKU: {{ data.sku || 'N/A' }}</p>
              </div>
            </div>
          </template>
        </Column>

        <Column header="Price" style="min-width: 80px; max-width: 120px;">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm md:text-base font-medium">{{ data.price?.formatted || formatPrice(data.price?.original) }}</span>
          </template>
        </Column>

        <Column header="Stock" style="min-width: 60px; max-width: 100px;">
          <template #body="{ data }">
            <span :class="getStockClass(data.inventory?.quantity)" class="text-xs sm:text-sm md:text-base">
              {{ data.inventory?.quantity ?? 0 }}
            </span>
          </template>
        </Column>

        <Column header="Status" style="min-width: 70px; max-width: 100px;">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-xs sm:text-sm px-1 sm:px-2" />
          </template>
        </Column>

        <!-- Action Column - Always Visible -->
        <Column header="Actions" style="min-width: 70px; max-width: 100px;" class="action-column">
          <template #body="{ data }">
            <div class="flex gap-1 sm:gap-2">
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small" 
                @click="editProduct(data.id)" 
                tooltip="Edit Product"
                class="p-button-sm !w-6 !h-6 sm:!w-8 sm:!h-8"
              />
              <Button 
                icon="pi pi-trash" 
                text 
                rounded 
                severity="danger" 
                size="small" 
                @click="deleteProduct(data.id)" 
                tooltip="Delete Product"
                class="p-button-sm !w-6 !h-6 sm:!w-8 sm:!h-8"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useVendorDashboardStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const dashboardStore = useVendorDashboardStore()

const filters = ref({
  search: '',
  status: null
})

const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Draft', value: 'draft' }
]

const products = computed(() => dashboardStore.products)
const pagination = computed(() => dashboardStore.productPagination)
const loading = computed(() => dashboardStore.loading)

const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'

const getProductImage = (product: any) => {
  if (product.media?.thumbnail) {
    return product.media.thumbnail.startsWith('http') 
      ? product.media.thumbnail 
      : `${baseUrl}/storage/${product.media.thumbnail}`
  }
  if (product.media?.image) {
    return product.media.image.startsWith('http') 
      ? product.media.image 
      : `${baseUrl}/storage/${product.media.image}`
  }
  return null
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="%239CA3AF" stroke-width="2"%3E%3Crect x="3" y="3" width="18" height="18" rx="2"%3E%3C/rect%3E%3Ccircle cx="8.5" cy="8.5" r="1.5"%3E%3C/circle%3E%3Cpath d="M21 15l-5-5L5 21"%3E%3C/path%3E%3C/svg%3E'
}

const fetchProducts = () => {
  dashboardStore.fetchProducts(1, pagination.value.per_page, true)
}

const onPageChange = (event: any) => {
  const page = Math.floor(event.first / event.rows) + 1
  dashboardStore.fetchProducts(page, event.rows)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const resetFilters = () => {
  filters.value = { search: '', status: null }
  fetchProducts()
}

const editProduct = (id: number) => {
  router.push(`/dashboard/vendor/products/${id}/edit`)
}

const deleteProduct = (id: number) => {
  confirm.require({
    message: 'Are you sure you want to delete this product?',
    header: 'Delete Product',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      const result = await dashboardStore.deleteProduct(id)
      if (result.success) {
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Product deleted successfully',
          life: 3000
        })
      }
    }
  })
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price || 0)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getStockClass = (stock: number): string => {
  if (stock === null || stock === undefined) {
    return 'text-gray-400'
  }
  if (stock <= 0) return 'text-red-600 font-semibold'
  if (stock <= 5) return 'text-orange-600 font-semibold'
  return 'text-green-600'
}

const getStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    active: 'success',
    inactive: 'danger',
    draft: 'warning'
  }
  return map[status] || 'info'
}

onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
/* ✅ Action column always visible - sticky at end */
:deep(.p-datatable .p-datatable-tbody > tr > td:last-child),
:deep(.p-datatable .p-datatable-thead > tr > th:last-child) {
  position: sticky !important;
  right: 0 !important;
  background: white !important;
  z-index: 2 !important;
}

/* ✅ Responsive table fixes */

/* Desktop (1024px and above) */
@media (min-width: 1024px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.75rem 1rem !important;
    font-size: 0.875rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 1rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.875rem !important;
  }
}

/* ✅ Tablet (768px to 1023px) - Show Product, Price, Stock, Status, Actions */
@media (min-width: 768px) and (max-width: 1023px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.75rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.75rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.8rem !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 2rem !important;
    height: 2rem !important;
    font-size: 0.75rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 2rem !important;
    height: 2rem !important;
  }
  
  /* ✅ Hide 'Added' column on tablet */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)) {
    display: none !important;
  }
  
  /* ✅ Ensure Product, Price, Stock, Status, Actions visible */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(1)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(1)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(3)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(3)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(4)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)),
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child),
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child) {
    display: table-cell !important;
  }
}

/* ✅ Mobile (below 768px) - Show only Product, Price, Actions */
@media (max-width: 767px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.4rem 0.3rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.4rem 0.3rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.7rem !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.3rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.8rem !important;
    height: 1.8rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.8rem !important;
    height: 1.8rem !important;
    font-size: 0.65rem !important;
  }
  
  /* ✅ Hide Stock, Status, Added on mobile - keep only Product, Price, Actions */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(3)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(3)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(4)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)) {
    display: none !important;
  }
  
  /* ✅ Ensure Product, Price, Actions visible on mobile */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(1)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(1)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)),
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child),
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child) {
    display: table-cell !important;
  }
  
  /* Hide SKU on mobile */
  :deep(.p-datatable .p-datatable-tbody > tr > td:first-child .text-gray-500) {
    display: none !important;
  }
  
  /* Smaller buttons on mobile */
  :deep(.p-datatable .p-button.p-button-sm) {
    padding: 0.2rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.65rem !important;
  }
  
  /* Action column sticky on mobile */
  :deep(.p-datatable .p-datatable-tbody > tr > td:last-child),
  :deep(.p-datatable .p-datatable-thead > tr > th:last-child) {
    position: sticky !important;
    right: 0 !important;
    background: white !important;
    z-index: 3 !important;
  }
}

/* Extra small devices (below 480px) */
@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.25rem 0.15rem !important;
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.25rem 0.15rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.55rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.5rem !important;
  }
  
  :deep(.p-datatable .p-button.p-button-sm) {
    padding: 0.15rem !important;
    min-width: 1.2rem !important;
    height: 1.2rem !important;
  }
}

/* General table styles */
:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  white-space: nowrap;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
}

/* Ensure action column buttons stay visible */
:deep(.p-datatable .p-datatable-tbody > tr > td:last-child .flex) {
  flex-wrap: nowrap;
}
</style>