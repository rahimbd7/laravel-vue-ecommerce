<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">Vendor Profile</h1>
      <p class="text-gray-600">Manage your store profile information</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading.profile" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Profile Form -->
    <div v-else class="bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="saveProfile" class="space-y-6">
        <!-- Store Logo Upload -->
        <div class="flex items-center gap-6 pb-4 border-b border-gray-200">
          <div class="relative">
            <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
              <img 
                v-if="storeLogo" 
                :src="storeLogo" 
                alt="Store Logo" 
                class="w-full h-full object-cover"
              />
              <i v-else class="pi pi-store text-4xl text-gray-400"></i>
            </div>
            <button 
              type="button"
              @click="triggerFileInput"
              class="absolute bottom-0 right-0 bg-[#00685F] text-white p-2 rounded-full hover:bg-[#004F45] transition shadow-lg"
            >
              <i class="pi pi-camera text-sm"></i>
            </button>
            <input 
              ref="fileInput"
              type="file" 
              accept="image/*"
              class="hidden"
              @change="handleLogoUpload"
            />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-700">Store Logo</p>
            <p class="text-xs text-gray-400">Upload a logo for your store (PNG, JPG up to 2MB)</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Store Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Store Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.business_name" 
              placeholder="Enter store name" 
              class="w-full"
              :class="{ 'p-invalid': errors.business_name }"
            />
            <small v-if="errors.business_name" class="text-red-500">{{ errors.business_name }}</small>
          </div>

          <!-- Owner Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Owner Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.name" 
              placeholder="Enter owner name" 
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
          </div>

          <!-- Business Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Email</label>
            <InputText 
              v-model="form.business_email" 
              type="email" 
              placeholder="Enter business email" 
              class="w-full"
              :class="{ 'p-invalid': errors.business_email }"
            />
            <small v-if="errors.business_email" class="text-red-500">{{ errors.business_email }}</small>
          </div>

          <!-- Business Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Phone</label>
            <InputText 
              v-model="form.business_phone" 
              placeholder="Enter business phone" 
              class="w-full"
            />
          </div>

          <!-- Account Email (Read-only) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Email</label>
            <InputText 
              :value="userEmail" 
              type="email" 
              class="w-full"
              disabled
            />
            <small class="text-gray-400">Account email cannot be changed here</small>
          </div>

          <!-- Tax Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Number / GST</label>
            <InputText 
              v-model="form.tax_number" 
              placeholder="Enter tax number" 
              class="w-full"
            />
          </div>

          <!-- Website -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
            <InputText 
              v-model="form.website" 
              placeholder="https://yourstore.com" 
              class="w-full"
              :class="{ 'p-invalid': errors.website }"
            />
            <small v-if="errors.website" class="text-red-500">{{ errors.website }}</small>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Store Description</label>
            <Textarea 
              v-model="form.description" 
              rows="4" 
              placeholder="Describe your store" 
              class="w-full"
            />
          </div>
        </div>

        <!-- Verification Status -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center gap-3">
            <i 
              :class="isVerified ? 'pi pi-check-circle text-green-500' : 'pi pi-clock text-yellow-500'"
              class="text-xl"
            ></i>
            <div>
              <p class="text-sm font-medium text-gray-700">
                {{ isVerified ? 'Verified Vendor' : 'Pending Verification' }}
              </p>
              <p class="text-xs text-gray-500">
                {{ isVerified ? 'Your vendor account is verified' : 'Your vendor account is awaiting admin approval' }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Save Profile" 
            icon="pi pi-save" 
            :loading="saving"
            class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]"
          />
          <Button 
            label="Reset" 
            severity="secondary" 
            @click="resetForm"
          />
        </div>

        <div v-if="successMessage" class="flex items-center gap-2 text-green-600 bg-green-50 p-3 rounded-lg">
          <i class="pi pi-check-circle"></i>
          <span>{{ successMessage }}</span>
        </div>

        <div v-if="error.profile" class="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-lg">
          <i class="pi pi-exclamation-circle"></i>
          <span>{{ error.profile }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth.store'
// ✅ NEW: Import the NEW vendor profile store (NOT the dashboard store)
import { useVendorProfileStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.profile.store'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const toast = useToast()
const authStore = useAuthStore()
const vendorProfileStore = useVendorProfileStore()

const fileInput = ref<HTMLInputElement | null>(null)
const saving = ref(false)
const successMessage = ref('')
const errors = ref<Record<string, string>>({})

const loading = computed(() => vendorProfileStore.loading)
const error = computed(() => vendorProfileStore.error)
const storeLogo = computed(() => vendorProfileStore.storeLogo)
const isVerified = computed(() => vendorProfileStore.isVerified)
const userEmail = computed(() => authStore.userEmail)

const form = ref({
  name: '',
  business_name: '',
  business_email: '',
  business_phone: '',
  tax_number: '',
  website: '',
  description: '',
})

const fetchProfile = async () => {
  try {
    await vendorProfileStore.fetchProfile()
    const data = vendorProfileStore.profile
    
    if (data) {
      form.value = {
        name: data.user?.name || '',
        business_name: data.vendor?.business_name || '',
        business_email: data.vendor?.business_email || '',
        business_phone: data.vendor?.business_phone || '',
        tax_number: data.vendor?.tax_number || '',
        website: data.vendor?.website || '',
        description: data.vendor?.description || '',
      }
    }
  } catch (error) {
    console.log('No vendor profile found, using defaults')
  }
}

const saveProfile = async () => {
  saving.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    await vendorProfileStore.updateProfile({
      name: form.value.name,
      business_name: form.value.business_name,
      business_email: form.value.business_email,
      business_phone: form.value.business_phone,
      tax_number: form.value.tax_number,
      website: form.value.website,
      description: form.value.description,
    })

    successMessage.value = 'Profile updated successfully!'
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Vendor profile updated',
      life: 3000
    })
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save profile',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleLogoUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    
    if (file.size > 2 * 1024 * 1024) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'File size must be less than 2MB',
        life: 3000
      })
      return
    }

    if (!file.type.startsWith('image/')) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'File must be an image',
        life: 3000
      })
      return
    }

    try {
      await vendorProfileStore.updateLogo(file)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Store logo updated successfully',
        life: 3000
      })
    } catch (error: any) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update logo',
        life: 3000
      })
    }
  }
}

const resetForm = () => {
  fetchProfile()
  errors.value = {}
  successMessage.value = ''
}

onMounted(() => {
  fetchProfile()
})
</script>

<style scoped>
.relative button {
  transition: all 0.2s ease;
}

.relative button:hover {
  transform: scale(1.1);
}
</style>