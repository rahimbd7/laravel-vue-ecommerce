<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Apply for Vendor</h1>
      <p class="text-gray-600">Become a vendor and start selling your products on our marketplace</p>
    </div>

    <!-- Checking application status -->
    <template v-if="initialLoading">
      <div class="bg-white rounded-lg shadow-sm p-12 flex flex-col items-center justify-center">
        <i class="pi pi-spin pi-spinner text-4xl text-[#00685F]"></i>
        <p class="mt-4 text-gray-600">Checking your application status...</p>
      </div>
    </template>

    <template v-else>
      <!-- Pending application -->
      <div v-if="isPending" class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
          <i class="pi pi-clock text-amber-600 text-xl"></i>
        </div>
        <div class="min-w-0">
          <h2 class="text-lg font-semibold text-gray-900">Application Pending Review</h2>
          <p class="text-gray-600 mt-1">
            {{ dashboardStore.applicationMessage || 'Your application is currently being reviewed by our team.' }}
          </p>
          <span class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-sm font-medium border border-amber-200">
            <span class="w-2 h-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
            Pending Review
          </span>
        </div>
      </div>

      <div v-if="dashboardStore.vendorApplication" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Name</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_name }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Email</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_email }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Phone</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_phone }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Commission Rate</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.commission_rate }}%</p>
        </div>
      </div>

      <p class="mt-6 text-sm text-gray-500">
        We'll review your application and get back to you. If approved, your account will automatically be
        upgraded to a vendor account and you will gain access to the vendor dashboard.
      </p>
    </div>

    <!-- Verified application -->
    <div v-else-if="isVerified" class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
          <i class="pi pi-check-circle text-green-600 text-xl"></i>
        </div>
        <div class="min-w-0">
          <h2 class="text-lg font-semibold text-gray-900">Vendor Account Verified</h2>
          <p class="text-green-700 mt-1">
            {{ dashboardStore.applicationMessage || 'Congratulations! Your vendor account is verified.' }}
          </p>
          <span class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-green-50 text-green-700 text-sm font-medium border border-green-200">
            <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
            Verified
          </span>
        </div>
      </div>

      <div v-if="dashboardStore.vendorApplication" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Name</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_name }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Email</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_email }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Business Phone</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.business_phone }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-medium">Commission Rate</p>
          <p class="font-medium text-gray-900 mt-1">{{ dashboardStore.vendorApplication.commission_rate }}%</p>
        </div>
      </div>

      <div class="mt-6">
        <Button
          label="Go to Vendor Dashboard"
          icon="pi pi-arrow-right"
          iconPos="right"
          class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          @click="router.push('/dashboard/vendor')"
        />
      </div>
    </div>

    <!-- Rejected application -->
      <div v-else-if="isRejected" class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <i class="pi pi-times-circle text-red-600 text-xl"></i>
          </div>
          <div class="min-w-0">
            <h2 class="text-lg font-semibold text-gray-900">Application Rejected</h2>
            <p class="text-red-600 mt-1">
              {{ dashboardStore.applicationMessage || 'Your vendor application was rejected.' }}
            </p>
            <span class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-700 text-sm font-medium border border-red-200">
              <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>
              Rejected
            </span>
          </div>
        </div>

        <div v-if="dashboardStore.applicationRejectionReason" class="mt-6 bg-red-50 rounded-lg p-4 border border-red-100">
          <p class="text-xs text-red-600 uppercase font-medium">Rejection Reason</p>
          <p class="text-gray-800 mt-1">{{ dashboardStore.applicationRejectionReason }}</p>
        </div>

        <p class="mt-6 text-sm text-gray-500">
          You can update your details below and submit a new application.
        </p>
      </div>

      <!-- Application form (shown when not applied, or to re-apply after rejection) -->
      <div v-if="!dashboardStore.hasAppliedForVendor" class="bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="submitApplication" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Business Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Business Name <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="form.business_name"
              type="text"
              class="w-full"
              :class="{ 'p-invalid': errors.business_name }"
              placeholder="e.g. Zara Fashion House"
            />
            <small v-if="errors.business_name" class="text-red-500">{{ errors.business_name }}</small>
          </div>

          <!-- Business Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Business Email <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="form.business_email"
              type="email"
              class="w-full"
              :class="{ 'p-invalid': errors.business_email }"
              placeholder="e.g. partner@yourstore.com"
            />
            <small v-if="errors.business_email" class="text-red-500">{{ errors.business_email }}</small>
          </div>

          <!-- Business Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Business Phone <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="form.business_phone"
              type="tel"
              class="w-full"
              :class="{ 'p-invalid': errors.business_phone }"
              placeholder="e.g. +8801XXXXXXX"
            />
            <small v-if="errors.business_phone" class="text-red-500">{{ errors.business_phone }}</small>
          </div>

          <!-- Tax Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax / VAT Number</label>
            <InputText
              v-model="form.tax_number"
              type="text"
              class="w-full"
              :class="{ 'p-invalid': errors.tax_number }"
              placeholder="Optional"
            />
            <small v-if="errors.tax_number" class="text-red-500">{{ errors.tax_number }}</small>
          </div>

          <!-- Website -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
            <InputText
              v-model="form.website"
              type="url"
              class="w-full"
              :class="{ 'p-invalid': errors.website }"
              placeholder="e.g. https://yourstore.com"
            />
            <small v-if="errors.website" class="text-red-500">{{ errors.website }}</small>
          </div>

          <!-- Commission Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Commission Rate</label>
            <div class="flex items-center gap-2">
              <InputNumber
                v-model="form.commission_rate"
                :min="0"
                :max="100"
                :step="0.5"
                :maxFractionDigits="2"
                class="w-full"
                :class="{ 'p-invalid': errors.commission_rate }"
              />
              <span class="text-gray-500 font-medium">%</span>
            </div>
            <small class="text-gray-400">Leave empty for the default rate (10%)</small>
            <small v-if="errors.commission_rate" class="text-red-500 block">{{ errors.commission_rate }}</small>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
            <Textarea
              v-model="form.description"
              rows="4"
              class="w-full"
              :class="{ 'p-invalid': errors.description }"
              placeholder="Tell us a little about your business and what you plan to sell..."
            />
            <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
          <Button
            type="submit"
            label="Submit Application"
            icon="pi pi-send"
            :loading="submitting"
            class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          />
          <Button
            type="button"
            label="Clear"
            icon="pi pi-refresh"
            severity="secondary"
            :disabled="submitting"
            @click="resetForm"
          />
          <div v-if="successMessage" class="flex items-center gap-2 text-green-600">
            <i class="pi pi-check-circle"></i>
            <span>{{ successMessage }}</span>
          </div>
        </div>
      </form>
    </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth.store'
import { useCustomerDashboardStore } from '@/stores/DashboardStore/Customer/dashboard.customer.store'

import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const dashboardStore = useCustomerDashboardStore()

const submitting = ref(false)
const successMessage = ref('')
const errors = ref<Record<string, string>>({})

// Pre-fill business email / phone from the signed-in profile when available
const form = ref({
  business_name: '',
  business_email: authStore.userEmail || '',
  business_phone: dashboardStore.userPhone || '',
  tax_number: '',
  website: '',
  description: '',
  commission_rate: 10 as number | null
})

const isPending = computed(() => dashboardStore.vendorApplicationStatus === 'pending')
const isVerified = computed(() => dashboardStore.vendorApplicationStatus === 'verified')
const isRejected = computed(() => dashboardStore.vendorApplicationStatus === 'rejected')
// True only while the first status fetch is in-flight (so a re-submission
// doesn't replace the form with the page-level spinner).
const initialLoading = computed(() => dashboardStore.loading.application && dashboardStore.vendorApplicationStatus === null)

// Laravel returns `errors` as { field: [messages] } -> flatten to strings
const parseErrors = (serverErrors: Record<string, any>) => {
  const normalized: Record<string, string> = {}
  Object.entries(serverErrors || {}).forEach(([key, messages]) => {
    normalized[key] = Array.isArray(messages) ? messages[0] : String(messages)
  })
  return normalized
}

const submitApplication = async () => {
  errors.value = {}
  successMessage.value = ''
  submitting.value = true

  try {
    const response = await dashboardStore.applyForVendor({
      business_name: form.value.business_name.trim(),
      business_email: form.value.business_email.trim(),
      business_phone: form.value.business_phone.trim(),
      tax_number: form.value.tax_number.trim() || null,
      website: form.value.website.trim() || null,
      description: form.value.description.trim() || null,
      commission_rate: form.value.commission_rate ?? null
    })

    successMessage.value = response.message || 'Vendor application submitted successfully!'
    toast.add({
      severity: 'success',
      summary: 'Application Sent',
      detail: successMessage.value,
      life: 5000
    })
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = parseErrors(error.response.data.errors)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to submit your application',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

const resetForm = () => {
  errors.value = {}
  successMessage.value = ''
  form.value = {
    business_name: '',
    business_email: authStore.userEmail || '',
    business_phone: dashboardStore.userPhone || '',
    tax_number: '',
    website: '',
    description: '',
    commission_rate: 10
  }
}

onMounted(async () => {
  try {
    // `force = true` skips the 5-minute TTL cache so an admin decision
    // (approved / rejected) is reflected the next time this page is opened.
    await dashboardStore.fetchVendorApplicationStatus(true)
  } catch (error) {
    // The form still renders; the status is re-fetched the next time the page is opened.
    console.warn('Failed to load vendor application status', error)
  }
})
</script>

<style scoped>
:deep(.p-inputtext.p-invalid),
:deep(.p-inputnumber.p-invalid .p-inputnumber-input) {
  border-color: #ef4444;
}
</style>