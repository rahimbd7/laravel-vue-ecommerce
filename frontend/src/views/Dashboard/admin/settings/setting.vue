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
            @click="router.push('/dashboard/admin')"
            class="flex-shrink-0"
          />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Settings</h1>
            <p class="text-sm text-gray-600">Manage your platform configuration and preferences</p>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
          <Button
            icon="pi pi-refresh"
            label="Reset"
            severity="secondary"
            outlined
            size="small"
            :loading="loading"
            class="text-sm"
            @click="resetForm"
          />
          <Button
            icon="pi pi-save"
            label="Save Changes"
            size="small"
            :loading="saving"
            class="text-sm bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
            @click="saveSettings"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Settings Form -->
    <div v-else class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="saveSettings" class="space-y-4 sm:space-y-6">
        <!-- Vendor Selection -->
        <div class="rounded-lg border border-[#00685F]/25 bg-[#00685F]/5 p-4 sm:p-5">
          <div class="flex items-start gap-3">
            <div
              class="hidden sm:flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#00685F]/10 text-[#00685F]"
            >
              <i class="pi pi-shop"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-base sm:text-lg font-semibold text-gray-900">Vendor Settings</h2>
              <p class="text-xs sm:text-sm text-gray-600">
                Search and select a vendor to auto-fill their store details, then update their
                commission, payment methods and status.
              </p>

              <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <AutoComplete
                  v-model="selectedVendor"
                  :suggestions="vendorSuggestions"
                  option-label="business_name"
                  :loading="vendorSearching"
                  :min-length="1"
                  force-selection
                  dropdown
                  placeholder="Search vendor by name, email or phone"
                  input-class="w-full text-sm"
                  class="w-full sm:max-w-md"
                  @complete="searchVendors"
                  @item-select="onVendorSelect"
                  @keydown.enter.prevent
                >
                  <template #option="slotProps">
                    <div class="flex flex-col">
                      <span class="text-sm font-medium text-gray-900">
                        {{ slotProps.option.business_name }}
                      </span>
                      <span class="text-xs text-gray-500">
                        {{ slotProps.option.business_email }}
                        <template v-if="slotProps.option.is_verified === false">
                          &middot; Not verified
                        </template>
                      </span>
                    </div>
                  </template>
                </AutoComplete>

                <Button
                  v-if="selectedVendor"
                  icon="pi pi-times"
                  label="Clear"
                  severity="secondary"
                  outlined
                  size="small"
                  class="text-sm w-full sm:w-auto"
                  @click="clearVendorSelection"
                />
              </div>

              <div v-if="selectedVendor" class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span
                  class="inline-flex items-center gap-1 rounded-full bg-[#00685F] px-2.5 py-1 font-medium text-white"
                >
                  <i class="pi pi-check"></i>
                  Vendor #{{ selectedVendor.id }}
                </span>
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 font-medium"
                  :class="
                    selectedVendor.is_verified
                      ? 'bg-green-100 text-green-700'
                      : 'bg-amber-100 text-amber-700'
                  "
                >
                  {{ selectedVendor.is_verified ? 'Verified' : 'Unverified' }}
                </span>
                <span v-if="selectedVendor.user" class="text-gray-600">
                  Owner: {{ selectedVendor.user.name }} ({{ selectedVendor.user.email }})
                </span>
              </div>

              <p v-if="vendorMode" class="mt-3 text-xs text-[#00685F]">
                <i class="pi pi-info-circle"></i>
                Vendor selected - platform-only fields are locked. Saving updates this vendor only.
              </p>
            </div>
          </div>
        </div>

        <!-- General Settings -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4 pb-2 border-b border-gray-200">
            General Settings
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
              <InputText
                v-model="settings.store_name"
                placeholder="Enter store name"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Store Email</label>
              <InputText
                v-model="settings.store_email"
                placeholder="Enter store email"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Store Phone</label>
              <InputText
                v-model="settings.store_phone"
                placeholder="Enter store phone"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Store Currency</label>
              <Dropdown
                v-model="settings.currency"
                :options="currencyOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select currency"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Store Description</label>
            <Textarea
              v-model="settings.store_description"
              placeholder="Enter store description"
              rows="3"
              class="w-full text-sm"
            />
          </div>

          <!-- Vendor-only fields (only shown once a vendor is selected) -->
          <div v-if="vendorMode" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tax / VAT Number</label>
              <InputText
                v-model="vendorMeta.tax_number"
                placeholder="e.g., TAX-123456"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
              <InputText
                v-model="vendorMeta.website"
                placeholder="https://example.com"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Status</label>
              <Dropdown
                v-model="vendorMeta.status"
                :options="vendorStatusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select status"
                class="w-full text-sm"
              />
            </div>

            <div class="flex items-center gap-3 sm:pt-6">
              <Checkbox v-model="vendorMeta.is_verified" input-id="vendorVerified" :binary="true" />
              <label for="vendorVerified" class="text-sm font-medium text-gray-700 cursor-pointer">
                Verified vendor
              </label>
            </div>
          </div>
        </div>

        <!-- Commission Settings -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4 pb-2 border-b border-gray-200">
            Commission Settings
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type</label>
              <Dropdown
                v-model="settings.commission_type"
                :options="commissionOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select commission type"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
              <InputNumber
                v-model="settings.commission_rate"
                placeholder="0.00"
                :min="0"
                :max="100"
                class="w-full"
              />
              <small class="text-gray-500 text-xs">
                {{
                  vendorMode
                    ? 'Applies to the selected vendor only.'
                    : 'Default commission applied to new vendors.'
                }}
              </small>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Payout Delay (Days)</label>
              <InputNumber
                v-model="settings.payout_delay_days"
                placeholder="e.g., 7"
                :min="0"
                :disabled="vendorMode"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <!-- Payment Settings -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4 pb-2 border-b border-gray-200">
            Payment Settings
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
              <InputText
                v-model="settings.currency_symbol"
                placeholder="$"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
              <InputNumber
                v-model="settings.min_order_amount"
                placeholder="0.00"
                mode="currency"
                currency="USD"
                :disabled="vendorMode"
                class="w-full"
              />
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ vendorMode ? 'Vendor Payment Methods' : 'Enabled Payment Methods' }}
              </label>
              <small class="block text-gray-500 text-xs mb-2">
                {{
                  vendorMode
                    ? 'Choose which gateways the selected vendor can accept.'
                    : 'Gateways available platform-wide.'
                }}
              </small>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div
                  v-for="method in paymentMethods"
                  :key="method.value"
                  class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                  <Checkbox
                    :model-value="settings.enabled_payment_methods.includes(method.value)"
                    @change="togglePaymentMethod(method.value)"
                    :binary="true"
                  />
                  <label class="text-sm font-medium text-gray-700 cursor-pointer">
                    {{ method.label }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Email Settings -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4 pb-2 border-b border-gray-200">
            Email Settings
          </h2>
          <p v-if="vendorMode" class="text-xs text-gray-500 -mt-2 mb-4">
            Platform-level settings - clear the vendor selection to edit these.
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mail Driver</label>
              <Dropdown
                v-model="settings.mail_driver"
                :options="mailDriverOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select mail driver"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mail Host</label>
              <InputText
                v-model="settings.mail_host"
                placeholder="smtp.example.com"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mail Port</label>
              <InputNumber
                v-model="settings.mail_port"
                placeholder="587"
                :min="0"
                :disabled="vendorMode"
                class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mail Username</label>
              <InputText
                v-model="settings.mail_username"
                placeholder="Enter mail username"
                :disabled="vendorMode"
                class="w-full text-sm"
              />
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Mail Password</label>
              <Password
                v-model="settings.mail_password"
                placeholder="Enter mail password"
                :toggle-mask="true"
                :disabled="vendorMode"
                :feedback="false"
                class="w-full text-sm"
              />
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button
            type="submit"
            icon="pi pi-save"
            :label="vendorMode ? 'Save Vendor Settings' : 'Save All Settings'"
            :loading="saving"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          />
          <Button
            icon="pi pi-refresh"
            :label="vendorMode ? 'Revert Vendor Changes' : 'Discard Changes'"
            severity="secondary"
            class="w-full sm:w-auto"
            @click="resetForm"
          />
        </div>
      </form>
    </div>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import AutoComplete from 'primevue/autocomplete'
import Toast from 'primevue/toast'
import { adminApi } from '@/api/endpoints/admin/admin.api'

/** Vendor payload returned by GET /admin/vendors */
interface VendorOption {
  id: number
  business_name: string
  business_email: string
  business_phone: string | null
  tax_number: string | null
  website: string | null
  description: string | null
  commission_rate: string | number | null
  is_verified: boolean
  status?: string | null
  payment_methods?: string[] | null
  user?: {
    name?: string
    email?: string
  } | null
}

interface SettingsData {
  store_name: string
  store_description: string
  store_email: string
  store_phone: string
  currency: string
  currency_symbol: string
  commission_type: string
  commission_rate: number | null
  payout_delay_days: number | null
  min_order_amount: number | null
  enabled_payment_methods: string[]
  mail_driver: string
  mail_host: string
  mail_port: number | null
  mail_username: string
  mail_password: string
}

const router = useRouter()
const toast = useToast()

// State
const loading = ref(true)
const saving = ref(false)

// ---- Vendor picker state -------------------------------------------------
// When a vendor is selected the same form drives that vendor's record
// (commission rate, payment methods, store details) instead of the platform
// settings, so an admin can update one vendor without leaving this page.
const selectedVendor = ref<VendorOption | null>(null)
const vendorSuggestions = ref<VendorOption[]>([])
const vendorSearching = ref(false)
/** Snapshot of the selected vendor, used to discard local edits. */
const vendorSnapshot = ref<VendorOption | null>(null)

/** Vendor-only fields that live on the Vendor model, not the settings store. */
const vendorMeta = ref({
  tax_number: '',
  website: '',
  status: 'approved' as string,
  is_verified: false,
})

/** True once a vendor is selected -> platform-only fields are locked. */
const vendorMode = computed(() => selectedVendor.value !== null)

const currencyOptions = [
  { label: 'USD - US Dollar', value: 'usd' },
  { label: 'EUR - Euro', value: 'eur' },
  { label: 'GBP - British Pound', value: 'gbp' },
  { label: 'BDT - Bangladeshi Taka', value: 'bdt' },
  { label: 'CAD - Canadian Dollar', value: 'cad' },
  { label: 'AUD - Australian Dollar', value: 'aud' },
  { label: 'JPY - Japanese Yen', value: 'jpy' },
]

const commissionOptions = [
  { label: 'Percentage', value: 'percentage' },
  { label: 'Fixed Amount', value: 'fixed' },
]

const mailDriverOptions = [
  { label: 'SMTP', value: 'smtp' },
  { label: 'Mailgun', value: 'mailgun' },
  { label: 'Sendmail', value: 'sendmail' },
  { label: 'Log', value: 'log' },
]

const paymentMethods = [
  { label: 'Stripe', value: 'stripe' },
  { label: 'PayPal', value: 'paypal' },
  { label: 'Razorpay', value: 'razorpay' },
  { label: 'Cash on Delivery', value: 'cod' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'SSLCOMMERZ', value: 'sslcommerz' },
]

const vendorStatusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

// Settings form data
const settings = ref<SettingsData>({
  store_name: '',
  store_description: '',
  store_email: '',
  store_phone: '',
  currency: 'usd',
  currency_symbol: '$',
  commission_type: 'percentage',
  commission_rate: 10,
  payout_delay_days: 7,
  min_order_amount: 0,
  enabled_payment_methods: ['stripe', 'paypal', 'cod'],
  mail_driver: 'smtp',
  mail_host: '',
  mail_port: 587,
  mail_username: '',
  mail_password: '',
})

// ---- Vendor search -------------------------------------------------------

/** Coerce the API's decimal string ("10.00") into a number for InputNumber. */
const toNumberOrNull = (value: string | number | null | undefined): number | null => {
  if (value === null || value === undefined || value === '') return null
  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
}

/** Accept both `data: [...]` (paginated) and a bare array. */
const normalizeVendors = (payload: any): VendorOption[] => {
  const data = payload?.data
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  return []
}

// Typing shouldn't fire one request per keystroke
let vendorSearchTimer: ReturnType<typeof setTimeout> | null = null

const searchVendors = (event: { query: string }) => {
  const query = event?.query?.trim() ?? ''

  if (vendorSearchTimer) clearTimeout(vendorSearchTimer)

  if (query.length < 1) {
    vendorSuggestions.value = []
    return
  }

  vendorSearchTimer = setTimeout(async () => {
    vendorSearching.value = true
    try {
      const response = await adminApi.getVendorsList({ search: query, per_page: 10 })
      vendorSuggestions.value = normalizeVendors(response.data)
    } catch (error: any) {
      vendorSuggestions.value = []
      toast.add({
        severity: 'error',
        summary: 'Vendor search failed',
        detail: error.response?.data?.message || 'Could not load vendors',
        life: 3000,
      })
    } finally {
      vendorSearching.value = false
    }
  }, 300)
}

/**
 * Auto-fill the General / Commission / Payment sections from a vendor record,
 * so the admin can review the values and then save commission, payment
 * methods and status for that vendor in one place.
 */
const applyVendor = (vendor: VendorOption) => {
  selectedVendor.value = vendor
  vendorSnapshot.value = JSON.parse(JSON.stringify(vendor))

  settings.value.store_name = vendor.business_name ?? ''
  settings.value.store_email = vendor.business_email ?? ''
  settings.value.store_phone = vendor.business_phone ?? ''
  settings.value.store_description = vendor.description ?? ''
  settings.value.commission_rate = toNumberOrNull(vendor.commission_rate)
  settings.value.enabled_payment_methods = [...(vendor.payment_methods ?? [])]

  vendorMeta.value = {
    tax_number: vendor.tax_number ?? '',
    website: vendor.website ?? '',
    status: vendor.status ?? (vendor.is_verified ? 'approved' : 'pending'),
    is_verified: vendor.is_verified ?? false,
  }
}

const onVendorSelect = async (event: { value: VendorOption }) => {
  const picked = event?.value
  if (!picked?.id) return

  // The suggestion is already complete, but re-fetch the single record so the
  // owning user account (name / email) is always populated in the header.
  try {
    const response = await adminApi.getVendorDetails(picked.id)
    const full = response.data?.data ?? response.data
    applyVendor(full ?? picked)
  } catch {
    applyVendor(picked)
  }
}

const clearVendorSelection = () => {
  selectedVendor.value = null
  vendorSnapshot.value = null
  vendorSuggestions.value = []
  vendorMeta.value = {
    tax_number: '',
    website: '',
    status: 'approved',
    is_verified: false,
  }
  // Re-load platform values that the vendor view had overwritten.
  fetchSettings()
}

// Fetch settings from the server
const fetchSettings = async () => {
  loading.value = true
  try {
    const response = await adminApi.getSettings()
    const data = response.data.data || response.data
    settings.value = {
      store_name: data.store_name ?? '',
      store_description: data.store_description ?? '',
      store_email: data.store_email ?? '',
      store_phone: data.store_phone ?? '',
      currency: data.currency ?? 'usd',
      currency_symbol: data.currency_symbol ?? '$',
      commission_type: data.commission_type ?? 'percentage',
      commission_rate: data.commission_rate ?? null,
      payout_delay_days: data.payout_delay_days ?? null,
      min_order_amount: data.min_order_amount ?? null,
      enabled_payment_methods: data.enabled_payment_methods ?? [],
      mail_driver: data.mail_driver ?? 'smtp',
      mail_host: data.mail_host ?? '',
      mail_port: data.mail_port ?? null,
      mail_username: data.mail_username ?? '',
      mail_password: '',
    }
  } catch {
    console.log('Settings not found, using defaults')
  } finally {
    loading.value = false
  }
}

// Toggle payment method selection
const togglePaymentMethod = (value: string) => {
  const index = settings.value.enabled_payment_methods.indexOf(value)
  if (index === -1) {
    settings.value.enabled_payment_methods.push(value)
  } else {
    settings.value.enabled_payment_methods.splice(index, 1)
  }
}

// Persist the selected vendor's store details, commission and payment methods
const saveVendorSettings = async () => {
  const vendor = selectedVendor.value
  if (!vendor) return

  saving.value = true
  try {
    const response = await adminApi.updateVendorDetails(vendor.id, {
      business_name: settings.value.store_name.trim(),
      business_email: settings.value.store_email.trim(),
      business_phone: settings.value.store_phone.trim() || null,
      description: settings.value.store_description.trim() || null,
      tax_number: vendorMeta.value.tax_number.trim() || null,
      website: vendorMeta.value.website.trim() || null,
      commission_rate: settings.value.commission_rate,
      payment_methods: settings.value.enabled_payment_methods,
      status: vendorMeta.value.status,
      is_verified: vendorMeta.value.is_verified,
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Settings saved for ${settings.value.store_name}`,
      life: 3000,
    })

    // Trust the server's copy so the snapshot reflects exactly what was stored.
    const saved = response.data?.data
    if (saved) {
      applyVendor(saved)
    } else {
      vendorSnapshot.value = JSON.parse(JSON.stringify(vendor))
    }
  } catch (error: any) {
    const errors = error.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0] : null
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail:
        (Array.isArray(firstError) ? firstError[0] : firstError) ||
        error.response?.data?.message ||
        'Failed to save vendor settings',
      life: 4000,
    })
  } finally {
    saving.value = false
  }
}

// Save settings to the server
const saveSettings = async () => {
  if (vendorMode.value) {
    await saveVendorSettings()
    return
  }

  saving.value = true
  try {
    await adminApi.updateSettings(settings.value)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Settings saved successfully',
      life: 3000,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save settings',
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

// Reset the form: revert vendor edits to the last loaded vendor record, or
// reload the platform settings.
const resetForm = () => {
  if (vendorMode.value && vendorSnapshot.value) {
    applyVendor(vendorSnapshot.value)
    toast.add({
      severity: 'info',
      summary: 'Reverted',
      detail: 'Vendor changes discarded',
      life: 2500,
    })
    return
  }
  fetchSettings()
}

// Load settings on mount
onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
/* PrimeVue input styling fixes for small text */
:deep(.p-inputtext) {
  font-size: 0.875rem;
}

:deep(.p-inputnumber-input) {
  font-size: 0.875rem;
}

:deep(.p-dropdown) {
  font-size: 0.875rem;
}

:deep(.p-password-input) {
  font-size: 0.875rem;
}

:deep(.p-checkbox) {
  margin-right: 0.5rem;
}

/* Vendor picker: keep the whole control as one rounded field */
:deep(.p-autocomplete) {
  font-size: 0.875rem;
}

:deep(.p-autocomplete-input) {
  width: 100%;
  font-size: 0.875rem;
}

:deep(.p-autocomplete-panel .p-autocomplete-item) {
  font-size: 0.875rem;
  white-space: normal;
}

/* Mobile responsive fixes */
@media (max-width: 640px) {
  :deep(.p-inputtext) {
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
  }

  :deep(.p-inputnumber-input) {
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
  }

  :deep(.p-dropdown) {
    font-size: 0.8rem;
  }

  :deep(.p-button) {
    font-size: 0.8rem;
  }
}
</style>


