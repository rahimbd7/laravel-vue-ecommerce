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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ isEditing ? 'Edit' : 'Create' }} Coupon</h1>
            <p class="text-sm text-gray-600">{{ isEditing ? 'Update' : 'Create a new' }} coupon for your store</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="saveCoupon" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code</label>
            <div class="flex gap-2">
              <InputText 
                v-model="form.code" 
                placeholder="Auto-generate" 
                class="w-full"
                :class="{ 'p-invalid': errors.code }"
              />
              <Button 
                icon="pi pi-refresh" 
                severity="secondary" 
                outlined
                @click="generateCode"
                tooltip="Generate code"
              />
            </div>
            <small v-if="errors.code" class="text-red-500 text-xs">{{ errors.code }}</small>
            <small v-else class="text-gray-400 text-xs">Leave empty to auto-generate</small>
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

          <!-- Max Discount Amount (for percentage) -->
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
              :options="appliesToOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select scope"
              class="w-full"
              :class="{ 'p-invalid': errors.applies_to }"
            />
          </div>

          <!-- Eligible Items (for specific products/categories/vendors) -->
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
            <small class="text-gray-400 text-xs">Minimum subtotal required to use this coupon</small>
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
            <small class="text-gray-400 text-xs">Max number of times this coupon can be used</small>
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
            <small class="text-gray-400 text-xs">Max times per customer (0 = unlimited)</small>
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
              <span class="text-sm text-gray-600">{{ form.is_public ? 'Visible to all customers' : 'Hidden (only accessible via code)' }}</span>
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
              <span class="text-sm text-gray-600">{{ form.is_first_time_only ? 'Only for new customers' : 'All customers' }}</span>
            </div>
          </div>

          <!-- Customer Types -->
          <div v-if="!form.is_first_time_only" class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Eligible Customer Types</label>
            <MultiSelect 
              v-model="form.eligible_customer_types" 
              :options="customerTypes" 
              optionLabel="label"
              optionValue="value"
              placeholder="All customer types"
              class="w-full sm:w-1/2"
              display="chip"
            />
            <small class="text-gray-400 text-xs">Leave empty for all customer types</small>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            :label="isEditing ? 'Update Coupon' : 'Create Coupon'" 
            icon="pi pi-save" 
            :loading="saving"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          />
          <Button 
            label="Cancel" 
            severity="secondary" 
            class="w-full sm:w-auto"
            @click="router.push('/dashboard/admin/coupons')" 
          />
        </div>
      </form>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted , watch} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import ToggleButton from 'primevue/togglebutton'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEditing = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const eligibleOptions = ref<any[]>([])

const discountTypes = [
  { label: 'Percentage', value: 'percentage' },
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Buy One Get One', value: 'bogo' },
  { label: 'Free Shipping', value: 'free_shipping' },
]

const appliesToOptions = [
  { label: 'All Products', value: 'all' },
  { label: 'Specific Products', value: 'specific_products' },
  { label: 'Specific Categories', value: 'specific_categories' },
  { label: 'Specific Vendors', value: 'specific_vendors' },
]

const customerTypes = [
  { label: 'Customers', value: 'customer' },
  { label: 'Vendors', value: 'vendor' },
  { label: 'Admins', value: 'admin' },
]

const form = ref({
  code: '',
  name: '',
  description: '',
  discount_type: 'percentage',
  discount_value: 0,
  max_discount_amount: null,
  applies_to: 'all',
  eligible_items: [],
  minimum_order_amount: 0,
  minimum_quantity: 0,
  usage_limit: null,
  usage_limit_per_customer: 1,
  starts_at: null as Date | null,
  expires_at: null as Date | null,
  is_first_time_only: false,
  eligible_customer_types: [],
  is_public: true,
  metadata: {},
})

const generateCode = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let code = ''
  for (let i = 0; i < 8; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  form.value.code = code
}

const getEligibleLabel = (): string => {
  const map = {
    specific_products: 'Products',
    specific_categories: 'Categories',
    specific_vendors: 'Vendors',
  }
  return map[form.value.applies_to as keyof typeof map] || 'Items'
}

const fetchEligibleOptions = async () => {
  // Fetch based on applies_to
  if (form.value.applies_to === 'specific_products') {
    const res = await adminApi.getProductsList({ limit: 100 })
    eligibleOptions.value = res.data.data.map((p: any) => ({ id: p.id, name: p.name }))
  } else if (form.value.applies_to === 'specific_categories') {
    const res = await adminApi.getCategoriesList()
    eligibleOptions.value = res.data.data.map((c: any) => ({ id: c.id, name: c.name }))
  } else if (form.value.applies_to === 'specific_vendors') {
    const res = await adminApi.getVendors()
    eligibleOptions.value = res.data.data.map((v: any) => ({ id: v.id, name: v.business_name }))
  }
}

const saveCoupon = async () => {
  errors.value = {}
  saving.value = true

  try {
    const payload = { ...form.value }
    const endpoint = isEditing.value 
      ? adminApi.updateCoupon(route.params.id as string, payload)
      : adminApi.createCoupon(payload)
    
    const response = await endpoint
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Coupon ${isEditing.value ? 'updated' : 'created'} successfully`,
      life: 3000
    })
    
    router.push('/dashboard/admin/coupons')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save coupon',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (route.params.id) {
    isEditing.value = true
    const res = await adminApi.getCoupon(route.params.id as string)
    const data = res.data.data
    form.value = {
      code: data.code,
      name: data.name,
      description: data.description || '',
      discount_type: data.discount_type,
      discount_value: data.discount_value,
      max_discount_amount: data.max_discount_amount,
      applies_to: data.applies_to,
      eligible_items: data.eligible_items || [],
      minimum_order_amount: data.minimum_order_amount || 0,
      minimum_quantity: data.minimum_quantity || 0,
      usage_limit: data.usage_limit,
      usage_limit_per_customer: data.usage_limit_per_customer || 1,
      starts_at: data.starts_at ? new Date(data.starts_at) : null,
      expires_at: data.expires_at ? new Date(data.expires_at) : null,
      is_first_time_only: data.is_first_time_only || false,
      eligible_customer_types: data.eligible_customer_types || [],
      is_public: data.is_public || true,
      metadata: data.metadata || {},
    }
  }
  
  await fetchEligibleOptions()
})

watch(() => form.value.applies_to, fetchEligibleOptions)
</script>