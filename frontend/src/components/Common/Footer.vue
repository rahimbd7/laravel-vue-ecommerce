<template>
  <footer class="bg-gray-900 text-gray-100 mt-16 border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Main Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <!-- Brand Section -->
        <div class="md:col-span-1">
          <h3 class="text-xl font-bold text-white mb-4">My Shop</h3>
          <p class="text-gray-400 text-sm mb-4">
            Your destination for premium products and exceptional shopping experience.
          </p>
          <!-- Social Links -->
          <div class="flex space-x-4">
            <a href="#" class="text-gray-400 hover:text-[#00685F] transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2s9 5 20 5a9.5 9.5 0 00-9-5.5c4.75 2.25 7-7 7-7" />
              </svg>
            </a>
            <a href="#" class="text-gray-400 hover:text-[#00685F] transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18 2h-3a6 6 0 00-6 6v3H7v4h2v8h4v-8h3l1-4h-4V8a2 2 0 012-2h3z" />
              </svg>
            </a>
            <a href="#" class="text-gray-400 hover:text-[#00685F] transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </a>
            <a href="#" class="text-gray-400 hover:text-[#00685F] transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.015 12.015 0 0024 12c0-6.63-5.37-12-12-12z" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="text-white font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2">
            <li>
              <router-link to="/shop" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Shop All Products
              </router-link>
            </li>
            <li>
              <router-link to="/new-arrivals" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                New Arrivals
              </router-link>
            </li>
            <li>
              <router-link to="/collections" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Collections
              </router-link>
            </li>
            <li>
              <router-link to="/sale" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Sale Items
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Customer Service -->
        <div>
          <h4 class="text-white font-semibold mb-4">Customer Service</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Contact Us
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Shipping & Returns
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                FAQ
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Track Order
              </a>
            </li>
          </ul>
        </div>

        <!-- Information -->
        <div>
          <h4 class="text-white font-semibold mb-4">Information</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                About Us
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Privacy Policy
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Terms of Service
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-400 hover:text-[#00685F] transition text-sm">
                Blog
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Newsletter Signup -->
      <div class="border-t border-gray-800 py-8 mb-8">
        <div class="max-w-md">
          <h4 class="text-white font-semibold mb-3">Subscribe to Our Newsletter</h4>
          <p class="text-gray-400 text-sm mb-4">
            Get the latest updates on new products and special offers!
          </p>
          <div class="flex gap-2">
            <input 
              v-model="email"
              type="email" 
              placeholder="Enter your email" 
              class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-[#00685F] transition"
            />
            <button 
              @click="subscribeNewsletter"
              class="bg-[#00685F] hover:bg-[#004F45] text-white px-6 py-2 rounded-lg transition font-medium"
            >
              Subscribe
            </button>
          </div>
          <p v-if="subscriptionMessage" :class="['text-sm mt-2', subscriptionSuccess ? 'text-green-400' : 'text-red-400']">
            {{ subscriptionMessage }}
          </p>
        </div>
      </div>

      <!-- Bottom Section -->
      <div class="border-t border-gray-800 pt-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <p class="text-gray-400 text-sm mb-4 md:mb-0">
            &copy; 2024 My Shop. All rights reserved.
          </p>
          
          <!-- Payment Methods -->
          <div class="flex items-center space-x-3">
            <span class="text-gray-400 text-xs">We accept:</span>
            <svg class="w-8 h-5 text-gray-400" viewBox="0 0 48 32" fill="currentColor">
              <!-- Visa -->
              <rect x="2" y="2" width="12" height="28" rx="2" />
              <text x="8" y="20" font-size="8" fill="white" text-anchor="middle">VISA</text>
              
              <!-- Mastercard -->
              <rect x="18" y="2" width="12" height="28" rx="2" />
              <circle cx="22" cy="16" r="4" fill="red" opacity="0.7"/>
              <circle cx="28" cy="16" r="4" fill="orange" opacity="0.7"/>
              
              <!-- PayPal -->
              <rect x="34" y="2" width="12" height="28" rx="2" />
              <text x="40" y="20" font-size="6" fill="white" text-anchor="middle">PayPal</text>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const email = ref('')
const subscriptionMessage = ref('')
const subscriptionSuccess = ref(false)

const subscribeNewsletter = () => {
  if (!email.value) {
    subscriptionMessage.value = 'Please enter your email address'
    subscriptionSuccess.value = false
    return
  }

  if (!isValidEmail(email.value)) {
    subscriptionMessage.value = 'Please enter a valid email address'
    subscriptionSuccess.value = false
    return
  }

  // Simulate API call
  subscriptionSuccess.value = true
  subscriptionMessage.value = 'Thank you for subscribing!'
  email.value = ''

  // Clear message after 3 seconds
  setTimeout(() => {
    subscriptionMessage.value = ''
  }, 3000)
}

const isValidEmail = (email: string) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}
</script>
