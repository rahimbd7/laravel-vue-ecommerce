<!-- src/views/Auth/LoginRegister.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-[#00685F] to-[#004F45] flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Flip Card Container -->
      <div class="relative transition-all duration-500 preserve-3d">
        
        <!-- Front Side - Login (Always on top when not flipped) -->
        <div 
          class="backface-hidden w-full bg-white rounded-2xl shadow-2xl p-8"
          :class="{ 'hidden': isRegisterMode }"
        >
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-[#00685F] rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-store text-3xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-gray-500 mt-2">Login to your account</p>
          </div>

          <FormKit type="form" @submit="handleLogin" :actions="false">
            <FormKit
              type="email"
              name="email"
              label="Email Address"
              placeholder="your@email.com"
              validation="required|email"
              :classes="{
                outer: 'mb-4',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <FormKit
              type="password"
              name="password"
              label="Password"
              placeholder="••••••••"
              validation="required"
              :classes="{
                outer: 'mb-6',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-[#00685F] text-white font-semibold py-3 rounded-lg hover:bg-[#004F45] transition disabled:opacity-50"
            >
              {{ loading ? 'Loading...' : 'Login' }}
            </button>
          </FormKit>

          <!-- Register Button (flips the card) -->
          <div class="mt-6 text-center">
            <button 
              @click="flipToRegister"
              class="text-[#00685F] font-semibold hover:text-[#004F45] transition"
            >
              Create New Account
            </button>
          </div>
        </div>

        <!-- Back Side - Register (Hidden by default) -->
        <div 
          v-show="isRegisterMode"
          class="w-full bg-white rounded-2xl shadow-2xl p-8"
        >
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-[#00685F] rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-user-plus text-3xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
            <p class="text-gray-500 mt-2">Join us today</p>
          </div>

          <FormKit type="form" @submit="handleRegister" :actions="false">
            <FormKit
              type="text"
              name="name"
              label="Full Name"
              placeholder="John Doe"
              validation="required|length:2"
              :classes="{
                outer: 'mb-4',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <FormKit
              type="email"
              name="email"
              label="Email Address"
              placeholder="your@email.com"
              validation="required|email"
              :classes="{
                outer: 'mb-4',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <FormKit
              type="password"
              name="password"
              label="Password"
              placeholder="••••••••"
              validation="required|length:8"
              :classes="{
                outer: 'mb-4',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <FormKit
              type="password"
              name="password_confirmation"
              label="Confirm Password"
              placeholder="••••••••"
              validation="required|confirm:password"
              validation-label="Password confirmation"
              :classes="{
                outer: 'mb-6',
                label: 'block text-sm font-medium text-gray-700 mb-1',
                input: 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00685F] focus:border-[#00685F] transition',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <FormKit
              type="checkbox"
              name="terms"
              label="I agree to the Terms of Service and Privacy Policy"
              validation="required|accepted",
              :ignore="true"
              :classes="{
                outer: 'mb-6 flex items-center',
                label: 'ml-2 text-sm text-gray-600',
                input: 'w-4 h-4 text-[#00685F] border-gray-300 rounded focus:ring-[#00685F]',
                message: 'text-red-500 text-sm mt-1'
              }"
            />

            <button
              type="submit"
              :disabled="registering"
              class="w-full bg-[#00685F] text-white font-semibold py-3 rounded-lg hover:bg-[#004F45] transition disabled:opacity-50"
            >
              {{ registering ? 'Creating account...' : 'Create Account' }}
            </button>
          </FormKit>

          <!-- Already Registered Link (flips back to login) -->
          <div class="mt-6 text-center">
            <button 
              @click="flipToLogin"
              class="text-[#00685F] font-semibold hover:text-[#004F45] transition"
            >
              Already have an account? Sign In
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import Swal from 'sweetalert2'

const router = useRouter()
const authStore = useAuthStore()

const isRegisterMode = ref(false)
const loading = ref(false)
const registering = ref(false)

const flipToRegister = () => {
  isRegisterMode.value = true
}

const flipToLogin = () => {
  isRegisterMode.value = false
}

const handleLogin = async (data: any) => {
  loading.value = true
  console.log('Login data:', data) // Debugging log
  try {
    const result = await authStore.login(data.email, data.password)
    if (result.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Welcome Back!',
        text: 'Login successful',
        timer: 1500,
        showConfirmButton: false
      })
      router.push('/')
    } else {
      await Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: result.error || 'Invalid email or password',
        confirmButtonColor: '#00685F'
      })
    }
  } catch (error) {
    await Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Something went wrong',
      confirmButtonColor: '#00685F'
    })
  } finally {
    loading.value = false
  }
}

const handleRegister = async (data: any) => {
  registering.value = true
  try {
    const result = await authStore.register(data.name, data.email, data.password, data.password_confirmation)
    if (result.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Account Created!',
        text: 'Welcome to My Shop!',
        timer: 1500,
        showConfirmButton: false
      })
      router.push('/')
    } else {
      await Swal.fire({
        icon: 'error',
        title: 'Registration Failed',
        text: result.error || 'Unable to create account',
        confirmButtonColor: '#00685F'
      })
    }
  } catch (error) {
    await Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Something went wrong',
      confirmButtonColor: '#00685F'
    })
  } finally {
    registering.value = false
  }
}
</script>