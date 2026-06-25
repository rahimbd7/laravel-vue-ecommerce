<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
      <p class="text-gray-600">Manage your personal information</p>
    </div>

    <!-- Avatar Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center gap-6">
        <div class="relative">
          <div class="w-24 h-24 rounded-full bg-[#00685F] text-white flex items-center justify-center text-3xl font-semibold">
            {{ userInitials }}
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
            @change="handleAvatarUpload"
          />
        </div>
        <div>
          <p class="text-sm text-gray-600">Upload a new avatar</p>
          <p class="text-xs text-gray-400">PNG, JPG up to 2MB</p>
          <p v-if="avatarUrl" class="text-xs text-green-600 mt-1">Avatar uploaded successfully</p>
        </div>
      </div>
    </div>

    <!-- Profile Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="updateProfile" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Full Name <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.name" 
              type="text"
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
              placeholder="Enter your full name"
            />
            <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email Address <span class="text-red-500">*</span>
            </label>
            <InputText 
              v-model="form.email" 
              type="email"
              class="w-full"
              placeholder="Enter your email"
              disabled
            />
            <small class="text-gray-400">Email cannot be changed</small>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <InputText 
              v-model="form.phone" 
              type="tel"
              class="w-full"
              :class="{ 'p-invalid': errors.phone }"
              placeholder="Enter your phone number"
            />
            <small v-if="errors.phone" class="text-red-500">{{ errors.phone }}</small>
          </div>

          <!-- Date of Birth -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
            <Calendar 
              v-model="form.date_of_birth" 
              dateFormat="yy-mm-dd"
              class="w-full"
              placeholder="Select your date of birth"
              :showIcon="true"
            />
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Save Changes" 
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

    <!-- Password Change Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
      <form @submit.prevent="changePassword" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
            <Password 
              v-model="passwordForm.current_password" 
              toggleMask
              class="w-full"
              placeholder="Enter current password"
              :feedback="false"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <Password 
              v-model="passwordForm.new_password" 
              toggleMask
              class="w-full"
              placeholder="Enter new password"
              :feedback="true"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <Password 
              v-model="passwordForm.new_password_confirmation" 
              toggleMask
              class="w-full"
              placeholder="Confirm new password"
              :feedback="false"
            />
          </div>
        </div>
        <div>
          <Button 
            type="submit" 
            label="Change Password" 
            icon="pi pi-key"
            :loading="passwordLoading"
            severity="warning"
          />
        </div>
        <div v-if="passwordSuccess" class="text-green-600 text-sm">{{ passwordSuccess }}</div>
        <div v-if="passwordError" class="text-red-500 text-sm">{{ passwordError }}</div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Password from 'primevue/password'
import Button from 'primevue/button'
import api from '@/api/api'

const authStore = useAuthStore()
const toast = useToast()
const fileInput = ref<HTMLInputElement | null>(null)

const loading = ref(false)
const passwordLoading = ref(false)
const successMessage = ref('')
const passwordSuccess = ref('')
const passwordError = ref('')
const avatarUrl = ref('')
const errors = ref<Record<string, string>>({})

// Form data
const form = ref({
  name: '',
  email: '',
  phone: '',
  date_of_birth: null as Date | null
})

// Password form
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// Computed user initials
const userInitials = computed(() => {
  const name = form.value.name || authStore.user?.name || 'User'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

// Format date for API
const formatDate = (date: Date | null): string | null => {
  if (!date) return null
  const d = new Date(date)
  if (isNaN(d.getTime())) return null
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Fetch profile data
const fetchProfile = async () => {
  try {
    const response = await api.get('/me')
    const data = response.data.data
    
    form.value = {
      name: data.name || '',
      email: data.email || '',
      phone: data.profile?.phone || '',
      date_of_birth: data.profile?.date_of_birth ? new Date(data.profile.date_of_birth) : null
    }
    
    if (data.profile?.avatar) {
      avatarUrl.value = data.profile.avatar
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load profile',
      life: 3000
    })
  }
}

// Trigger file input
const triggerFileInput = () => {
  fileInput.value?.click()
}

// Handle avatar upload
const handleAvatarUpload = async (event: Event) => {
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

    try {
      const formData = new FormData()
      formData.append('avatar', file)

      const response = await api.put('/profile/avatar', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.status === 'success') {
        avatarUrl.value = response.data.data?.avatar || ''
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Avatar updated successfully',
          life: 3000
        })
        await authStore.fetchProfile()
      }
    } catch (error: any) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to upload avatar',
        life: 3000
      })
    }
  }
}

// Update profile
const updateProfile = async () => {
  loading.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    const response = await api.put('/profile', {
      name: form.value.name,
      phone: form.value.phone,
      date_of_birth: formatDate(form.value.date_of_birth)
    })

    if (response.data.status === 'success') {
      await authStore.fetchProfile()
      successMessage.value = 'Profile updated successfully!'
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Profile updated successfully',
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
        detail: error.response?.data?.message || 'Failed to update profile',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

// Change password
const changePassword = async () => {
  passwordLoading.value = true
  passwordSuccess.value = ''
  passwordError.value = ''

  try {
    await api.put('/profile/change-password', {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    })

    passwordSuccess.value = 'Password changed successfully!'
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Password changed successfully',
      life: 3000
    })
  } catch (error: any) {
    passwordError.value = error.response?.data?.message || 'Failed to change password'
  } finally {
    passwordLoading.value = false
  }
}

// Reset form
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
:deep(.p-inputtext.p-invalid) {
  border-color: #ef4444;
}

:deep(.p-inputtext:disabled) {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

:deep(.p-password input) {
  width: 100%;
}

.relative button {
  transition: all 0.2s ease;
}

.relative button:hover {
  transform: scale(1.1);
}
</style>