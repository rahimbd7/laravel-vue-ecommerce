<!-- src/views/Checkout/CheckoutPage.vue -->
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center space-x-2 text-sm">
          <router-link to="/" class="text-[#00685F] hover:text-[#004F45]">Home</router-link>
          <span class="text-gray-400">/</span>
          <router-link to="/shop" class="text-[#00685F] hover:text-[#004F45]">Shop</router-link>
          <span class="text-gray-400">/</span>
          <router-link to="/cart" class="text-[#00685F] hover:text-[#004F45]">Cart</router-link>
          <span class="text-gray-400">/</span>
          <span class="text-gray-600">Checkout</span>
        </div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Checkout</h1>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="cartStore.loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Empty Cart -->
    <div v-else-if="cartStore.isEmpty" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6M17 13l1.5 6M9 21h6M12 15v6" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Your Cart is Empty</h3>
        <p class="text-gray-600 mb-6">Add items to your cart to proceed with checkout</p>
        <router-link to="/shop" class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition">
          Continue Shopping
        </router-link>
      </div>
    </div>

    <!-- Checkout Form -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Forms -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Login Warning -->
          <div v-if="!authStore.isAuthenticated" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">
              Already have an account? 
              <router-link to="/login" class="font-semibold underline">Login</router-link> 
              for faster checkout.
            </p>
          </div>

          <!-- Shipping Address -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Shipping Address</h2>
            <div class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                  <input v-model="form.customer_name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="John Doe" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                  <input v-model="form.customer_phone" type="tel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="+1 (555) 000-0000" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <input v-model="form.customer_email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="john@example.com" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                <input v-model="form.shipping_address" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="123 Main Street" />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                  <input v-model="form.shipping_city" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="New York" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
                  <input v-model="form.shipping_state" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="NY" />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code *</label>
                  <input v-model="form.shipping_postal_code" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="10001" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                  <select v-model="form.shipping_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]">
                    <option value="">Select Country</option>
                    <option value="US">United States</option>
                    <option value="CA">Canada</option>
                    <option value="GB">United Kingdom</option>
                    <option value="AU">Australia</option>
                  </select>
                </div>
              </div>

              <label class="flex items-center cursor-pointer mt-4">
                <input v-model="form.use_same_address" type="checkbox" class="w-4 h-4 text-[#00685F] border-gray-300 rounded focus:ring-[#00685F]" />
                <span class="ml-2 text-sm text-gray-700">Billing address same as shipping</span>
              </label>
            </div>
          </div>

          <!-- Billing Address -->
          <div v-if="!form.use_same_address" class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Billing Address</h2>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                <input v-model="form.billing_address" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="123 Main Street" />
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">City *</label><input v-model="form.billing_city" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="New York" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label><input v-model="form.billing_state" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="NY" /></div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code *</label><input v-model="form.billing_postal_code" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="10001" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Country *</label><select v-model="form.billing_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]"><option value="">Select Country</option><option value="US">United States</option><option value="CA">Canada</option><option value="GB">United Kingdom</option><option value="AU">Australia</option></select></div>
              </div>
            </div>
          </div>

          <!-- Shipping Method -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Shipping Method</h2>
            <div class="space-y-3">
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.shipping_method === 'standard' }">
                <input v-model="form.shipping_method" type="radio" value="standard" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <div class="ml-3 flex-1"><span class="block font-medium text-gray-900">Standard Shipping</span><span class="text-sm text-gray-600">5-7 business days</span></div>
                <span class="text-lg font-semibold text-gray-900">{{ subtotal >= 100 ? 'Free' : '$5.99' }}</span>
              </label>
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.shipping_method === 'express' }">
                <input v-model="form.shipping_method" type="radio" value="express" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <div class="ml-3 flex-1"><span class="block font-medium text-gray-900">Express Shipping</span><span class="text-sm text-gray-600">2-3 business days</span></div>
                <span class="text-lg font-semibold text-gray-900">$15.00</span>
              </label>
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.shipping_method === 'overnight' }">
                <input v-model="form.shipping_method" type="radio" value="overnight" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <div class="ml-3 flex-1"><span class="block font-medium text-gray-900">Overnight Shipping</span><span class="text-sm text-gray-600">Next business day</span></div>
                <span class="text-lg font-semibold text-gray-900">$25.00</span>
              </label>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h2>
            <div class="space-y-3">
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.payment_method === 'cod' }">
                <input v-model="form.payment_method" type="radio" value="cod" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <span class="ml-3 font-medium text-gray-900">Cash on Delivery</span>
              </label>
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.payment_method === 'bank_transfer' }">
                <input v-model="form.payment_method" type="radio" value="bank_transfer" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <span class="ml-3 font-medium text-gray-900">Bank Transfer</span>
              </label>
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#00685F] transition" :class="{ 'border-[#00685F] bg-blue-50': form.payment_method === 'credit_card' }">
                <input v-model="form.payment_method" type="radio" value="credit_card" class="w-4 h-4 text-[#00685F] focus:ring-[#00685F]" />
                <span class="ml-3 font-medium text-gray-900">Credit / Debit Card</span>
              </label>
            </div>
          </div>

          <!-- Card Details -->
          <div v-if="form.payment_method === 'credit_card'" class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Card Details</h2>
            <div class="space-y-4">
              <div><label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label><input v-model="form.card_number" type="text" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" /></div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label><input v-model="form.card_expiry" type="text" placeholder="MM/YY" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">CVV</label><input v-model="form.card_cvv" type="text" placeholder="123" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" /></div>
              </div>
              <div><label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label><input v-model="form.card_name" type="text" placeholder="John Doe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" /></div>
            </div>
          </div>

          <!-- Notes -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Notes (Optional)</h2>
            <textarea v-model="form.notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00685F]" placeholder="Special instructions for delivery..."></textarea>
          </div>

          <!-- Terms -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <label class="flex items-start gap-3 cursor-pointer">
              <input v-model="form.terms_agreed" type="checkbox" class="mt-1 w-4 h-4 text-[#00685F] border-gray-300 rounded focus:ring-[#00685F]" />
              <span class="text-sm text-gray-600">I agree to the <a href="#" class="text-[#00685F] hover:text-[#004F45]">Terms & Conditions</a> and <a href="#" class="text-[#00685F] hover:text-[#004F45]">Privacy Policy</a></span>
            </label>
          </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-20 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

            <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
              <div v-for="item in cartStore.activeItems" :key="item.id" class="flex justify-between text-sm">
                <div><p class="text-gray-900 line-clamp-2">{{ item.product_name }}</p><p class="text-gray-600">Qty: {{ item.quantity }}</p></div>
                <p class="font-medium text-gray-900">{{ formatPrice(Number(item.total) || 0) }}</p>
              </div>
            </div>

            <div class="border-t border-gray-200 pt-4 space-y-2">
              <div class="flex justify-between text-sm text-gray-600"><span>Subtotal:</span><span>{{ formatPrice(subtotal) }}</span></div>
              <div class="flex justify-between text-sm text-gray-600"><span>Shipping:</span><span>{{ getShippingCost() }}</span></div>
              <div class="flex justify-between text-sm text-gray-600"><span>Tax (10%):</span><span>{{ formatPrice(subtotal * 0.1) }}</span></div>
              <div v-if="discountTotal > 0" class="flex justify-between text-sm text-red-600"><span>Discount:</span><span>-{{ formatPrice(discountTotal) }}</span></div>
            </div>

            <div class="border-t border-gray-200 pt-4 flex justify-between text-lg font-bold">
              <span>Total:</span>
              <span class="text-[#00685F]">{{ formatPrice(getTotalPrice()) }}</span>
            </div>

            <button @click="placeOrder" :disabled="!form.terms_agreed || loading || !authStore.isAuthenticated" class="w-full bg-[#00685F] text-white font-semibold py-3 rounded-lg hover:bg-[#004F45] disabled:opacity-50 disabled:cursor-not-allowed transition">
              <span v-if="loading" class="flex items-center justify-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>Processing...</span>
              <span v-else>Place Order</span>
            </button>

            <div v-if="!authStore.isAuthenticated" class="text-center text-sm text-red-600">Please login to place order</div>
            <router-link to="/cart" class="block text-center text-[#00685F] hover:text-[#004F45] font-medium py-2 border border-gray-300 rounded-lg transition">Back to Cart</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart.store'
import { useAuthStore } from '@/stores/auth.store'
import api from '@/api/api'
import Swal from 'sweetalert2'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const loading = ref(false)

// ✅ Safe computed values to prevent NaN
const subtotal = computed(() => Number(cartStore.subtotal) || 0)
const discountTotal = computed(() => Number(cartStore.discountTotal) || 0)

const form = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
  shipping_city: '',
  shipping_state: '',
  shipping_postal_code: '',
  shipping_country: '',
  billing_address: '',
  billing_city: '',
  billing_state: '',
  billing_postal_code: '',
  billing_country: '',
  use_same_address: true,
  shipping_method: 'standard',
  payment_method: 'cod',
  notes: '',
  card_number: '',
  card_expiry: '',
  card_cvv: '',
  card_name: '',
  terms_agreed: false
})

// Pre-fill user data
if (authStore.isAuthenticated && authStore.user) {
  form.customer_name = authStore.user.name || ''
  form.customer_email = authStore.user.email || ''
  if (authStore.user.profile) {
    form.customer_phone = authStore.user.profile.phone || ''
    form.shipping_address = authStore.user.profile.address || ''
    form.shipping_city = authStore.user.profile.city || ''
    form.shipping_country = authStore.user.profile.country || ''
  }
}

const copyShippingToBilling = () => {
  if (form.use_same_address) {
    form.billing_address = form.shipping_address
    form.billing_city = form.shipping_city
    form.billing_state = form.shipping_state
    form.billing_postal_code = form.shipping_postal_code
    form.billing_country = form.shipping_country
  }
}

watch(() => form.use_same_address, copyShippingToBilling)
watch([() => form.shipping_address, () => form.shipping_city, () => form.shipping_state, () => form.shipping_postal_code, () => form.shipping_country], () => {
  if (form.use_same_address) copyShippingToBilling()
})

const getShippingCost = (): string => {
  let cost = 5.99
  if (form.shipping_method === 'express') cost = 15.00
  else if (form.shipping_method === 'overnight') cost = 25.00
  else if (form.shipping_method === 'standard' && subtotal.value >= 100) cost = 0
  return formatPrice(cost)
}

const getTotalPrice = (): number => {
  let shippingCost = 5.99
  if (form.shipping_method === 'express') shippingCost = 15.00
  else if (form.shipping_method === 'overnight') shippingCost = 25.00
  else if (form.shipping_method === 'standard' && subtotal.value >= 100) shippingCost = 0
  return (subtotal.value || 0) + (subtotal.value * 0.1) + shippingCost - (discountTotal.value || 0)
}

const formatPrice = (price: number): string => {
  if (isNaN(price) || price === undefined || price === null) return '$0.00'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const placeOrder = async () => {
  if (!form.customer_name) { await Swal.fire({ icon: 'error', title: 'Missing Name', text: 'Please enter your full name', confirmButtonColor: '#00685F' }); return }
  if (!form.customer_email) { await Swal.fire({ icon: 'error', title: 'Missing Email', text: 'Please enter your email address', confirmButtonColor: '#00685F' }); return }
  if (!form.shipping_address || !form.shipping_city || !form.shipping_country) { await Swal.fire({ icon: 'error', title: 'Missing Address', text: 'Please enter complete shipping address', confirmButtonColor: '#00685F' }); return }
  if (!form.terms_agreed) { await Swal.fire({ icon: 'error', title: 'Terms Required', text: 'Please agree to terms and conditions', confirmButtonColor: '#00685F' }); return }
  if (!authStore.isAuthenticated) { await Swal.fire({ icon: 'error', title: 'Login Required', text: 'Please login to place order', confirmButtonColor: '#00685F' }); router.push('/login'); return }

  loading.value = true
  try {
    const response = await api.post('/checkout/process', {
      customer_name: form.customer_name,
      customer_email: form.customer_email,
      customer_phone: form.customer_phone,
      shipping_address: form.shipping_address,
      shipping_city: form.shipping_city,
      shipping_state: form.shipping_state,
      shipping_postal_code: form.shipping_postal_code,
      shipping_country: form.shipping_country,
      billing_address: form.billing_address,
      billing_city: form.billing_city,
      billing_state: form.billing_state,
      billing_postal_code: form.billing_postal_code,
      billing_country: form.billing_country,
      use_same_address: form.use_same_address,
      shipping_method: form.shipping_method,
      payment_method: form.payment_method,
      notes: form.notes
    })
    if (response.data.status === 'success') {
      await Swal.fire({ icon: 'success', title: 'Order Placed!', text: 'Your order has been placed successfully', confirmButtonColor: '#00685F' })
      await cartStore.clear()
      router.push(`/order/${response.data?.data?.order?.id}`)
    } else {
      await Swal.fire({ icon: 'error', title: 'Order Failed', text: response.data.message || 'Failed to place order', confirmButtonColor: '#00685F' })
    }
  } catch (err: any) {
    const errors = err.response?.data?.errors
    if (errors) {
      await Swal.fire({ icon: 'error', title: 'Validation Error', text: Object.values(errors).flat().join('\n'), confirmButtonColor: '#00685F' })
    } else {
      await Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Failed to place order', confirmButtonColor: '#00685F' })
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await cartStore.fetchCart()
})
</script>