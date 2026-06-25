<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">My Address</h1>
      <p class="text-gray-600">Manage your shipping address</p>
    </div>

    <!-- Address Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="updateAddress" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Address -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <Textarea 
              v-model="form.address" 
              rows="2"
              class="w-full"
              :class="{ 'p-invalid': errors.address }"
              placeholder="Enter your address"
            />
            <small v-if="errors.address" class="text-red-500">{{ errors.address }}</small>
          </div>

          <!-- City -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <InputText 
              v-model="form.city" 
              type="text"
              class="w-full"
              placeholder="Enter your city"
            />
          </div>

          <!-- State -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
            <InputText 
              v-model="form.state" 
              type="text"
              class="w-full"
              placeholder="Enter your state"
            />
          </div>

          <!-- Postal Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
            <InputText 
              v-model="form.postal_code" 
              type="text"
              class="w-full"
              placeholder="Enter your postal code"
            />
          </div>

          <!-- Country -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
            <Dropdown 
              v-model="form.country" 
              :options="countries" 
              optionLabel="name"
              optionValue="code"
              placeholder="Select your country"
              class="w-full"
              filter
            />
          </div>
        </div>

        <!-- Full Address Preview -->
        <div v-if="fullAddress" class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm font-medium text-gray-700 mb-1">Full Address Preview:</p>
          <p class="text-gray-600">{{ fullAddress }}</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Save Address" 
            icon="pi pi-save"
            :loading="loading"
            class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          />
          <Button 
            type="button" 
            label="Cancel" 
            severity="secondary"
            @click="resetForm"
          />
          
          <div v-if="successMessage" class="flex items-center gap-2 text-green-600">
            <i class="pi pi-check-circle"></i>
            <span>{{ successMessage }}</span>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import api from '@/api/api'

const toast = useToast()
const loading = ref(false)
const successMessage = ref('')
const errors = ref<Record<string, string>>({})

// Countries list
const countries = [
  { name: 'United States', code: 'US' },
  { name: 'United Kingdom', code: 'GB' },
  { name: 'Canada', code: 'CA' },
  { name: 'Australia', code: 'AU' },
  { name: 'Germany', code: 'DE' },
  { name: 'France', code: 'FR' },
  { name: 'Bangladesh', code: 'BD' },
  { name: 'India', code: 'IN' },
  { name: 'Pakistan', code: 'PK' },
  { name: 'Other', code: 'OTHER' }
]

// Form data
const form = ref({
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: ''
})

// Computed full address
const fullAddress = computed(() => {
  const parts = [
    form.value.address,
    form.value.city,
    form.value.state,
    form.value.postal_code,
    countries.find(c => c.code === form.value.country)?.name
  ].filter(Boolean)
  return parts.join(', ')
})

// Fetch address
const fetchAddress = async () => {
  try {
    const response = await api.get('/profile/address')
    const data = response.data.data
    
    if (data) {
      form.value = {
        address: data.address || '',
        city: data.city || '',
        state: data.state || '',
        postal_code: data.postal_code || '',
        country: data.country || 'US'
      }
    }
  } catch (error: any) {
    // If no address exists, keep form empty
    console.log('No address found')
  }
}

// Update address
const updateAddress = async () => {
  loading.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    const response = await api.put('/profile/address', {
      address: form.value.address,
      city: form.value.city,
      state: form.value.state,
      postal_code: form.value.postal_code,
      country: form.value.country
    })

    if (response.data.status === 'success') {
      successMessage.value = 'Address updated successfully!'
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Address updated successfully',
        life: 3000
      })
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update address',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

// Reset form
const resetForm = () => {
  fetchAddress()
  errors.value = {}
  successMessage.value = ''
}

onMounted(() => {
  fetchAddress()
})
</script>

<style scoped>
:deep(.p-inputtext.p-invalid) {
  border-color: #ef4444;
}
</style>