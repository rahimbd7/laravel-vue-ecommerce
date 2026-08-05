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
            @click="router.push('/dashboard/admin/coupons')"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Coupon Details</h1>
            <p class="text-sm text-gray-600">{{ coupon?.name }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button 
            icon="pi pi-pencil" 
            label="Edit" 
            size="small"
            @click="router.push(`/dashboard/admin/coupons/${couponId}/edit`)"
            severity="primary"
          />
          <Button 
            v-if="coupon?.is_active"
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
      <Button label="Try Again" @click="fetchCoupon" class="mt-3" />
    </div>

    <!-- Coupon Content -->
    <template v-else-if="coupon">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Coupon Info Card -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="text-xl font-bold text-gray-900">{{ coupon.name }}</h3>
                <p class="text-sm text-gray-500">Code: <span class="font-mono font-semibold text-[#00685F]">{{ coupon.code }}</span></p>
                <p v-if="coupon.description" class="text-sm text-gray-600 mt-1">{{ coupon.description }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Tag 
                  :value="coupon.is_active ? 'Active' : 'Inactive'" 
                  :severity="coupon.is_active ? 'success' : 'danger'" 
                />
                <Tag 
                  v-if="coupon.is_expired" 
                  value="Expired" 
                  severity="danger"
                />
                <Tag 
                  v-if="coupon.is_public" 
                  value="Public" 
                  severity="info"
                />
                <Tag 
                  v-if="coupon.is_first_time_only" 
                  value="First Time Only" 
                  severity="warning"
                />
              </div>
            </div>

            <Divider />

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-500">Discount Type</p>
                <p class="text-lg font-bold text-gray-900 capitalize">{{ coupon.discount_type }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Discount Value</p>
                <p class="text-lg font-bold text-[#00685F]">{{ coupon.discount_label }}</p>
                <p v-if="coupon.max_discount_amount" class="text-xs text-gray-500">
                  Max: ${{ Number(coupon.max_discount_amount).toFixed(2) }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Applies To</p>
                <p class="text-lg font-bold text-gray-900 capitalize">
                  {{ coupon.applies_to.replace('_', ' ') }}
                </p>
                <p v-if="coupon.eligible_items?.length" class="text-xs text-gray-500">
                  {{ coupon.eligible_items.length }} item(s)
                </p>
              </div>
            </div>

            <Divider />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Minimum Order</p>
                <p class="text-lg font-bold text-gray-900">
                  ${{ Number(coupon.minimum_order_amount || 0).toFixed(2) }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Usage Statistics</p>
                <p class="text-lg font-bold text-gray-900">
                  {{ coupon.usage_count || 0 }} / {{ coupon.usage_limit || '∞' }}
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                  <div 
                    class="bg-[#00685F] h-2 rounded-full transition-all"
                    :style="{ width: usagePercentage + '%' }"
                  ></div>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-500">Per Customer Limit</p>
                <p class="text-lg font-bold text-gray-900">{{ coupon.usage_limit_per_customer || 'Unlimited' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Created By</p>
                <p class="text-lg font-bold text-gray-900 capitalize">{{ coupon.created_by || 'Admin' }}</p>
              </div>
            </div>

            <Divider />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Starts At</p>
                <p class="text-lg font-bold text-gray-900">{{ formatDate(coupon.starts_at) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Expires At</p>
                <p class="text-lg font-bold text-gray-900">{{ formatDate(coupon.expires_at) }}</p>
              </div>
            </div>

            <!-- Eligible Items List -->
            <div v-if="coupon.eligible_items?.length && coupon.applies_to !== 'all'" class="mt-4">
              <Divider />
              <p class="text-sm text-gray-500 mb-2">Eligible {{ getEligibleLabel() }}</p>
              <div class="flex flex-wrap gap-1">
                <Tag 
                  v-for="item in coupon.eligible_items" 
                  :key="item"
                  :value="item" 
                  severity="info"
                  size="small"
                />
              </div>
              <p class="text-xs text-gray-400 mt-1">Showing IDs - Configure in edit mode</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4">
          <!-- Quick Stats -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Quick Stats</h4>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Total Uses</span>
                <span class="text-sm font-bold text-gray-900">{{ coupon.usage_count || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Total Discount Given</span>
                <span class="text-sm font-bold text-[#00685F]">
                  ${{ ((coupon.usage_count || 0) * (coupon.discount_value || 0)).toFixed(2) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Usage Rate</span>
                <span class="text-sm font-bold text-gray-900">{{ usageRate }}%</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Status</span>
                <Tag 
                  :value="getStatusLabel()" 
                  :severity="getStatusSeverity()"
                  size="small"
                />
              </div>
            </div>
          </div>

          <!-- Information -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Information</h4>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Created</p>
                <p class="text-sm text-gray-900">{{ formatDate(coupon.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Last Updated</p>
                <p class="text-sm text-gray-900">{{ formatDate(coupon.updated_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">ID</p>
                <p class="text-sm text-gray-900">#{{ coupon.id }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Quick Actions</h4>
            <div class="space-y-2">
              <Button 
                label="Edit Coupon" 
                icon="pi pi-pencil" 
                class="w-full justify-start"
                severity="info"
                text
                @click="router.push(`/dashboard/admin/coupons/${couponId}/edit`)"
              />
              <Button 
                :label="coupon.is_active ? 'Deactivate' : 'Activate'" 
                :icon="coupon.is_active ? 'pi pi-times' : 'pi pi-check'"
                class="w-full justify-start"
                :severity="coupon.is_active ? 'warning' : 'success'"
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
              <Button 
                label="Duplicate" 
                icon="pi pi-copy" 
                class="w-full justify-start"
                severity="secondary"
                text
                @click="duplicateCoupon"
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

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const couponId = route.params.id as string

// State
const coupon = ref<any>(null)
const loading = ref(false)
const error = ref('')

// Computed
const usagePercentage = computed(() => {
  if (!coupon.value) return 0
  if (!coupon.value.usage_limit) return 0
  return Math.min((coupon.value.usage_count || 0) / coupon.value.usage_limit * 100, 100)
})

const usageRate = computed(() => {
  if (!coupon.value) return 0
  if (!coupon.value.usage_limit) return 0
  return Math.min(Math.round(((coupon.value.usage_count || 0) / coupon.value.usage_limit) * 100), 100)
})

// Methods
const fetchCoupon = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await adminApi.getCoupon(couponId)
    coupon.value = response.data.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load coupon'
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

const getStatusLabel = (): string => {
  if (!coupon.value) return 'Unknown'
  if (!coupon.value.is_active) return 'Inactive'
  if (coupon.value.is_expired) return 'Expired'
  if (coupon.value.usage_limit && coupon.value.usage_count >= coupon.value.usage_limit) return 'Exhausted'
  return 'Active'
}

const getStatusSeverity = (): string => {
  const status = getStatusLabel()
  if (status === 'Active') return 'success'
  if (status === 'Expired' || status === 'Exhausted') return 'danger'
  return 'secondary'
}

const getEligibleLabel = (): string => {
  if (!coupon.value) return 'Items'
  const map = {
    specific_products: 'Products',
    specific_categories: 'Categories',
    specific_vendors: 'Vendors',
  }
  return map[coupon.value.applies_to as keyof typeof map] || 'Items'
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const toggleStatus = async () => {
  if (!coupon.value) return
  
  try {
    const newStatus = !coupon.value.is_active
    await adminApi.toggleCouponStatus(couponId)
    coupon.value.is_active = newStatus
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Coupon ${newStatus ? 'activated' : 'deactivated'} successfully`,
      life: 3000
    })
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to toggle status',
      life: 3000
    })
  }
}

const confirmDelete = () => {
  if (!coupon.value) return
  
  confirm.require({
    message: `Are you sure you want to delete coupon "${coupon.value.name}"? This action cannot be undone.`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await adminApi.deleteCoupon(couponId)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Coupon deleted successfully',
          life: 3000
        })
        router.push('/dashboard/admin/coupons')
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to delete coupon',
          life: 3000
        })
      }
    }
  })
}

const duplicateCoupon = async () => {
  if (!coupon.value) return
  
  try {
    const payload = {
      code: `${coupon.value.code}_COPY`,
      name: `${coupon.value.name} (Copy)`,
      description: coupon.value.description,
      discount_type: coupon.value.discount_type,
      discount_value: coupon.value.discount_value,
      max_discount_amount: coupon.value.max_discount_amount,
      applies_to: coupon.value.applies_to,
      eligible_items: coupon.value.eligible_items,
      minimum_order_amount: coupon.value.minimum_order_amount,
      minimum_quantity: coupon.value.minimum_quantity,
      usage_limit: coupon.value.usage_limit,
      usage_limit_per_customer: coupon.value.usage_limit_per_customer,
      starts_at: coupon.value.starts_at,
      expires_at: coupon.value.expires_at,
      is_first_time_only: coupon.value.is_first_time_only,
      eligible_customer_types: coupon.value.eligible_customer_types,
      is_public: coupon.value.is_public,
    }
    
    await adminApi.createCoupon(payload)
    
    toast.add({
      severity: 'success',
      summary: 'Duplicated',
      detail: 'Coupon duplicated successfully',
      life: 3000
    })
    router.push('/dashboard/admin/coupons')
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to duplicate coupon',
      life: 3000
    })
  }
}

// Lifecycle
onMounted(() => {
  fetchCoupon()
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