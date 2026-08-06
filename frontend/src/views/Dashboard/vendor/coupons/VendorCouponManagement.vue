<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Store Coupons</h1>
          <p class="text-sm text-gray-600">Manage coupons for your store</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            label="Create Coupon" 
            icon="pi pi-plus" 
            severity="primary"
            @click="router.push('/dashboard/vendor/coupons/create')"
          />
          <Button 
            icon="pi pi-download" 
            label="Export" 
            severity="secondary"
            outlined
            @click="exportCoupons"
          />
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-600">Total Coupons</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-600">Active</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-600">Expired</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.expired }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-600">Total Uses</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.total_uses }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText 
            v-model="filters.search" 
            placeholder="Search coupons..." 
            class="pl-8 w-full"
            @input="onFilterChange"
          />
        </div>
        <Dropdown 
          v-model="filters.status" 
          :options="statusOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Status"
          class="w-full"
          @change="onFilterChange"
        />
        <Button 
          icon="pi pi-filter-slash" 
          label="Clear" 
          outlined
          @click="clearFilters"
        />
      </div>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        :value="coupons"
        lazy
        paginator
        :rows="filters.per_page"
        :totalRecords="pagination?.total || 0"
        :first="first"
        @page="onPageChange"
        @sort="onSort"
        dataKey="id"
        :loading="loading"
        responsiveLayout="scroll"
      >
        <Column field="id" header="ID" sortable style="min-width: 60px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <span class="text-sm">#{{ data.id }}</span>
          </template>
        </Column>

        <Column field="code" header="Code" sortable style="min-width: 100px">
          <template #body="{ data }">
            <span class="font-mono font-bold text-[#00685F] text-sm">{{ data.code }}</span>
          </template>
        </Column>

        <Column field="name" header="Name" sortable style="min-width: 150px">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-900 text-sm">{{ data.name }}</p>
              <p class="text-xs text-gray-500 truncate max-w-[150px]">{{ data.description || 'No description' }}</p>
            </div>
          </template>
        </Column>

        <Column field="discount_label" header="Discount" sortable style="min-width: 100px">
          <template #body="{ data }">
            <span class="font-bold text-[#00685F] text-sm">{{ data.discount_label || getDiscountLabel(data) }}</span>
          </template>
        </Column>

        <Column field="applies_to" header="Applies To" style="min-width: 100px" class="hidden md:table-cell">
          <template #body="{ data }">
            <span class="text-sm capitalize">{{ data.applies_to?.replace('_', ' ') || 'All' }}</span>
          </template>
        </Column>

        <Column field="status" header="Status" sortable style="min-width: 100px">
          <template #body="{ data }">
            <Tag 
              :value="getStatusLabel(data)" 
              :severity="getStatusSeverity(data)"
              size="small"
            />
          </template>
        </Column>

        <Column field="usage_count" header="Uses" sortable style="min-width: 70px">
          <template #body="{ data }">
            <span class="text-sm">{{ data.usage_count || 0 }}</span>
          </template>
        </Column>

        <Column field="created_at" header="Created" sortable style="min-width: 110px" class="hidden md:table-cell">
          <template #body="{ data }">
            <span class="text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <Column header="Actions" style="min-width: 100px" :frozen="true" alignFrozen="right">
          <template #body="{ data }">
            <div class="flex items-center gap-1 justify-end">
              <Button 
                icon="pi pi-eye" 
                text 
                rounded 
                size="small"
                @click="viewCoupon(data)"
                tooltip="View Details"
                class="!w-7 !h-7"
              />
              <Button 
                icon="pi pi-pencil" 
                text 
                rounded 
                size="small"
                severity="info"
                @click="router.push(`/dashboard/vendor/coupons/${data.id}/edit`)"
                tooltip="Edit"
                class="!w-7 !h-7"
              />
              <Button 
                :icon="data.is_active ? 'pi pi-times' : 'pi pi-check'"
                text 
                rounded 
                size="small"
                :severity="data.is_active ? 'warning' : 'success'"
                @click="toggleStatus(data)"
                :tooltip="data.is_active ? 'Deactivate' : 'Activate'"
                class="!w-7 !h-7"
              />
              <Button 
                icon="pi pi-trash" 
                text 
                rounded 
                size="small"
                severity="danger"
                @click="confirmDelete(data)"
                tooltip="Delete"
                class="!w-7 !h-7"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-tag text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">No coupons found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
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
import { vendorApi } from '@/api/endpoints/vendor/vendor.api'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const coupons = ref<any[]>([])
const allCoupons = ref<any[]>([])
const loading = ref(false)
const first = ref(0)
const pagination = ref<any>(null)

// Computed stats - dynamically calculated from all coupons
const stats = computed(() => {
  const all = allCoupons.value || []
  
  const active = all.filter(c => {
    if (!c.is_active) return false
    if (c.is_expired) return false
    if (c.usage_limit && (c.usage_count || 0) >= c.usage_limit) return false
    return true
  }).length
  
  const expired = all.filter(c => {
    return c.expires_at && new Date(c.expires_at) < new Date()
  }).length
  
  const totalUses = all.reduce((sum, c) => sum + (c.usage_count || 0), 0)
  
  return {
    total: all.length,
    active: active,
    expired: expired,
    total_uses: totalUses,
  }
})

const filters = reactive({
  search: '',
  status: '',
  per_page: 15,
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Expired', value: 'expired' },
  { label: 'Exhausted', value: 'exhausted' },
]

// Methods
const fetchCoupons = async () => {
  loading.value = true
  try {
    const response = await vendorApi.getCoupons(filters)
    const data = response.data?.data || response.data || []
    coupons.value = Array.isArray(data) ? data : [data]
    
    pagination.value = {
      total: response.data?.meta?.total || response.data?.total || 0,
      current_page: response.data?.meta?.current_page || response.data?.current_page || 1,
      last_page: response.data?.meta?.last_page || response.data?.last_page || 1,
      per_page: response.data?.meta?.per_page || response.data?.per_page || filters.per_page,
    }
    first.value = (filters.page - 1) * filters.per_page
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load coupons',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchAllCoupons = async () => {
  try {
    const response = await vendorApi.getCoupons({ 
      per_page: 9999,
      status: '', 
      search: '' 
    })
    const data = response.data?.data || response.data || []
    allCoupons.value = Array.isArray(data) ? data : [data]
  } catch (error) {
    console.error('Failed to fetch all coupons:', error)
    allCoupons.value = []
  }
}

const getDiscountLabel = (coupon: any): string => {
  if (coupon.discount_type === 'percentage') {
    return coupon.discount_value + '%'
  }
  if (coupon.discount_type === 'fixed') {
    return '$' + Number(coupon.discount_value).toFixed(2)
  }
  if (coupon.discount_type === 'free_shipping') {
    return 'Free Shipping'
  }
  if (coupon.discount_type === 'bogo') {
    return 'BOGO'
  }
  return ''
}

const getStatusLabel = (coupon: any): string => {
  if (!coupon.is_active) return 'Inactive'
  if (coupon.is_expired) return 'Expired'
  if (coupon.usage_limit && (coupon.usage_count || 0) >= coupon.usage_limit) return 'Exhausted'
  return 'Active'
}

const getStatusSeverity = (coupon: any): string => {
  const status = getStatusLabel(coupon)
  if (status === 'Active') return 'success'
  if (status === 'Expired' || status === 'Exhausted') return 'danger'
  return 'secondary'
}

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const onFilterChange = () => {
  filters.page = 1
  first.value = 0
  fetchCoupons()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  onFilterChange()
}

const onPageChange = (event: any) => {
  filters.page = Math.floor(event.first / event.rows) + 1
  filters.per_page = event.rows
  first.value = event.first
  fetchCoupons()
}

const onSort = (event: any) => {
  filters.sort_by = event.sortField
  filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc'
  fetchCoupons()
}

const toggleStatus = async (coupon: any) => {
  try {
    await vendorApi.toggleCouponStatus(coupon.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Coupon ${coupon.is_active ? 'deactivated' : 'activated'} successfully`,
      life: 3000
    })
    await Promise.all([fetchCoupons(), fetchAllCoupons()])
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to toggle status',
      life: 3000
    })
  }
}

const confirmDelete = (coupon: any) => {
  confirm.require({
    message: `Are you sure you want to delete coupon "${coupon.name}"?`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await vendorApi.deleteCoupon(coupon.id)
        toast.add({ 
          severity: 'success', 
          summary: 'Deleted', 
          detail: 'Coupon deleted successfully', 
          life: 3000 
        })
        await Promise.all([fetchCoupons(), fetchAllCoupons()])
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete coupon',
          life: 3000
        })
      }
    }
  })
}

const viewCoupon = (coupon: any) => {
  router.push(`/dashboard/vendor/coupons/${coupon.id}/details`)
}

const exportCoupons = async () => {
  try {
    const response = await vendorApi.exportCoupons(filters)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `coupons_export_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({
      severity: 'success',
      summary: 'Export Started',
      detail: 'Coupons exported successfully',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export coupons',
      life: 3000
    })
  }
}

const refreshAll = async () => {
  await Promise.all([fetchCoupons(), fetchAllCoupons()])
}

// Lifecycle
onMounted(() => {
  refreshAll()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f9fafb;
  font-weight: 600;
  font-size: 0.7rem;
  text-transform: uppercase;
  color: #6b7280;
  padding: 0.5rem 0.4rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.4rem 0.3rem;
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f3f4f6;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
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

:deep(.p-datatable .p-tag) {
  font-size: 0.65rem !important;
  padding: 0.1rem 0.4rem !important;
}

/* Mobile responsive */
@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    font-size: 0.55rem !important;
    padding: 0.3rem 0.2rem !important;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    font-size: 0.55rem !important;
    padding: 0.25rem 0.15rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm) {
    min-width: 1.2rem !important;
    height: 1.2rem !important;
    padding: 0.05rem !important;
  }

  :deep(.p-datatable .p-button.p-button-sm .p-button-icon) {
    font-size: 0.5rem !important;
  }

  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.6rem !important;
  }

  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
  }

  :deep(.p-datatable .p-tag) {
    font-size: 0.5rem !important;
    padding: 0.05rem 0.2rem !important;
  }
}
</style>