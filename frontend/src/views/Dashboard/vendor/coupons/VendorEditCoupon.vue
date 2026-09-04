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
            @click="router.push('/dashboard/vendor/coupons/management')"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Store Coupon</h1>
            <p class="text-sm text-gray-600">Update coupon information</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Tag 
            :value="coupon?.is_active ? 'Active' : 'Inactive'" 
            :severity="coupon?.is_active ? 'success' : 'danger'"
          />
          <Tag 
            v-if="coupon?.is_expired" 
            value="Expired" 
            severity="danger"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Form -->
    <div v-else class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="updateCoupon" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Coupon Code <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.code" 
              placeholder="Enter coupon code"
              class="w-full"
              :class="{ 'p-invalid': errors.code }"
            />
            <small v-if="errors.code" class="text-red-500 text-xs">{{ errors.code }}</small>
            <small v-else class="text-gray-400 text-xs">Must be unique</small>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Coupon Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.name" 
              placeholder="e.g., Summer Sale 20%"
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="text-red-500 text-xs">{{ errors.name }}</small>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <Textarea 
              v-model="form.description" 
              rows="2" 
              placeholder="Describe your coupon"
              class="w-full"
            />
          </div>

          <!-- Discount Type & Value -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Discount Type <span class="text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.discount_type" 
              :options="discountTypes" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select type"
              class="w-full"
              :class="{ 'p-invalid': errors.discount_type }"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Discount Value <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
              <InputNumber 
                v-model="form.discount_value" 
                :min="0" 
                :step="form.discount_type === 'percentage' ? 1 : 0.01"
                class="w-full"
                :class="{ 'p-invalid': errors.discount_value }"
              />
              <span class="text-sm font-medium text-gray-600 min-w-8">
                {{ form.discount_type === 'percentage' ? '%' : '$' }}
              </span>
            </div>
            <small v-if="errors.discount_value" class="text-red-500 text-xs">{{ errors.discount_value }}</small>
          </div>

          <!-- Max Discount Amount -->
          <div v-if="form.discount_type === 'percentage'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Discount Amount</label>
            <InputNumber 
              v-model="form.max_discount_amount" 
              :min="0" 
              prefix="$"
              placeholder="No limit"
              class="w-full"
            />
            <small class="text-gray-400 text-xs">Maximum discount amount for this coupon</small>
          </div>

          <!-- Applies To -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Applies To <span class="text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.applies_to" 
              :options="vendorAppliesToOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select scope"
              class="w-full"
              :class="{ 'p-invalid': errors.applies_to }"
            />
            <small class="text-gray-400 text-xs">Only applies to your store's products</small>
          </div>

          <!-- Eligible Items -->
          <div v-if="form.applies_to !== 'all'">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ getEligibleLabel() }} <span class="text-red-500">*</span>
            </label>
            <MultiSelect 
              v-model="form.eligible_items" 
              :options="eligibleOptions" 
              optionLabel="name"
              optionValue="id"
              :placeholder="`Select ${getEligibleLabel().toLowerCase()}`"
              class="w-full"
              filter
              display="chip"
              :class="{ 'p-invalid': errors.eligible_items }"
            />
            <small v-if="errors.eligible_items" class="text-red-500 text-xs">{{ errors.eligible_items }}</small>
          </div>

          <!-- Minimum Order Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
            <InputNumber 
              v-model="form.minimum_order_amount" 
              :min="0" 
              prefix="$"
              placeholder="0"
              class="w-full"
            />
          </div>

          <!-- Usage Limit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Total Usage Limit</label>
            <InputNumber 
              v-model="form.usage_limit" 
              :min="1" 
              placeholder="Unlimited"
              class="w-full"
            />
            <div class="mt-1 text-xs text-gray-500">
              Current uses: {{ coupon?.usage_count || 0 }}
            </div>
          </div>

          <!-- Per Customer Limit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Per Customer Limit</label>
            <InputNumber 
              v-model="form.usage_limit_per_customer" 
              :min="0" 
              :max="10"
              placeholder="1"
              class="w-full"
            />
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Starts At</label>
            <Calendar 
              v-model="form.starts_at" 
              dateFormat="yy-mm-dd"
              showTime
              hourFormat="24"
              placeholder="Select start date"
              class="w-full"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
            <Calendar 
              v-model="form.expires_at" 
              dateFormat="yy-mm-dd"
              showTime
              hourFormat="24"
              placeholder="Select expiry date"
              class="w-full"
              :class="{ 'p-invalid': errors.expires_at }"
            />
            <small v-if="errors.expires_at" class="text-red-500 text-xs">{{ errors.expires_at }}</small>
          </div>
        </div>

        <!-- Settings -->
        <div class="border-t border-gray-200 pt-4">
          <h3 class="text-base font-semibold text-gray-900 mb-3">Additional Settings</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex items-center gap-3">
              <ToggleButton 
                v-model="form.is_public" 
                onLabel="Public" 
                offLabel="Private" 
                onIcon="pi pi-globe" 
                offIcon="pi pi-lock"
                class="w-32"
              />
              <span class="text-sm text-gray-600">{{ form.is_public ? 'Visible to all' : 'Hidden (code only)' }}</span>
            </div>

            <div class="flex items-center gap-3">
              <ToggleButton 
                v-model="form.is_first_time_only" 
                onLabel="First Time" 
                offLabel="All Users" 
                onIcon="pi pi-user-plus" 
                offIcon="pi pi-users"
                class="w-32"
              />
              <span class="text-sm text-gray-600">{{ form.is_first_time_only ? 'New customers only' : 'All customers' }}</span>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Update Coupon" 
            icon="pi pi-save" 
            :loading="saving"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"

          />
          <Button 
            label="Cancel" 
            severity="secondary" 
            class="w-full sm:w-auto"
            @click="router.push('/dashboard/vendor/coupons/management')" 
          />
        </div>
      </form>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import ToggleButton from 'primevue/togglebutton'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import {useVendorDashboardStore} from '@/stores/DashboardStore/Vendor/dashboard.vendor.store'
import api from '@/api/api'
import { vendorApi } from '@/api/endpoints/vendor/vendor.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const dashboardStore = useVendorDashboardStore()

const loading = ref(false)
const saving = ref(false)
const coupon = ref<any>(null)
const errors = ref<Record<string, string>>({})
const eligibleOptions = ref<any[]>([])

const discountTypes = [
  { label: 'Percentage', value: 'percentage' },
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Buy One Get One', value: 'bogo' },
  { label: 'Free Shipping', value: 'free_shipping' },
]

const vendorAppliesToOptions = [
  { label: 'All My Products', value: 'all' },
  { label: 'Specific Products', value: 'specific_products' },
  { label: 'Specific Categories', value: 'specific_categories' },
]

const form = ref({
  code: '',
  name: '',
  description: '',
  discount_type: 'percentage',
  discount_value: 0,
  max_discount_amount: null as number | null,
  applies_to: 'all',
  eligible_items: [] as number[],
  minimum_order_amount: 0,
  minimum_quantity: 0,
  usage_limit: null as number | null,
  usage_limit_per_customer: 1,
  starts_at: null as Date | null,
  expires_at: null as Date | null,
  is_first_time_only: false,
  is_public: true,
  is_active: true,
  metadata: {} as Record<string, any>,
})

const getEligibleLabel = (): string => {
  const map = {
    specific_products: 'Products',
    specific_categories: 'Categories',
  }
  return map[form.value.applies_to as keyof typeof map] || 'Items'
}

const fetchEligibleOptions = async () => {
   try {
    if (form.value.applies_to === 'specific_products') {
      const res = await dashboardStore.fetchProducts()
      eligibleOptions.value = res.products.map((p: any) => ({ id: p.id, name: p.name }))
    } else if (form.value.applies_to === 'specific_categories') {
      const res = await api.get('/categories')
      console.log('Fetched categories:', res.data.data)
      eligibleOptions.value = res.data.data.map((c: any) => ({ id: c.id, name: c.name }))
    }
  } catch (error) {
    console.error('Failed to fetch eligible options:', error)
  }
}

const fetchCoupon = async () => {
  loading.value = true
  try {
    const response = await vendorApi.getCoupon(route.params.id as string)
    coupon.value = response.data.data
    
    form.value = {
      code: coupon.value.code || '',
      name: coupon.value.name || '',
      description: coupon.value.description || '',
      discount_type: coupon.value.discount_type || 'percentage',
      discount_value: coupon.value.discount_value || 0,
      max_discount_amount: coupon.value.max_discount_amount ?? null,
      applies_to: coupon.value.applies_to || 'all',
      eligible_items: coupon.value.eligible_items || [],
      minimum_order_amount: coupon.value.minimum_order_amount || 0,
      minimum_quantity: coupon.value.minimum_quantity || 0,
      usage_limit: coupon.value.usage_limit ?? null,
      usage_limit_per_customer: coupon.value.usage_limit_per_customer || 1,
      starts_at: coupon.value.starts_at ? new Date(coupon.value.starts_at) : null,
      expires_at: coupon.value.expires_at ? new Date(coupon.value.expires_at) : null,
      is_first_time_only: coupon.value.is_first_time_only || false,
      is_public: coupon.value.is_public ?? true,
      is_active: coupon.value.is_active ?? true,
      metadata: coupon.value.metadata || {},
    }
    
    await fetchEligibleOptions()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load coupon',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const updateCoupon = async () => {
  errors.value = {}
  saving.value = true

  try {
    const payloadData: any = { ...form.value }
    const payload: any = { ...payloadData }
    
    if (payload.starts_at) {
      payload.starts_at = payload.starts_at instanceof Date 
        ? payload.starts_at.toISOString() 
        : payload.starts_at
    }
    if (payload.expires_at) {
      payload.expires_at = payload.expires_at instanceof Date 
        ? payload.expires_at.toISOString() 
        : payload.expires_at
    }

    const response = await vendorApi.updateCoupon(route.params.id as string, payload)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Coupon updated successfully',
      life: 3000
    })
    
    router.push('/dashboard/vendor/coupons/management')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update coupon',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchCoupon()
})

watch(() => form.value.applies_to, fetchEligibleOptions)
</script>
