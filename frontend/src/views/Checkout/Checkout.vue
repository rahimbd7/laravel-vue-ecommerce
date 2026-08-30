<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart.store'
import { useAuthStore } from '@/stores/auth.store'
import { useNotify } from '@/composables/useNotify'
import { formatPrice } from '@/composables/useCurrency'
import {
  useFormValidation, required, email as emailRule, phone, postalCode, accepted,
} from '@/composables/useFormValidation'
import api from '@/api/api'
import AppInput from '@/components/ui/AppInput.vue'
import ProgressSteps from '@/components/ui/ProgressSteps.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const notify = useNotify()

const submitting = ref(false)
const loadingProfile = ref(true)
const profileError = ref('')
const cardProcessing = ref(false)

// ---------------------------------------------------------------------------
// Steps
// ---------------------------------------------------------------------------
const STEPS = [
  { key: 'address', label: 'Address' },
  { key: 'delivery', label: 'Delivery & Payment' },
  { key: 'review', label: 'Review' },
]
const step = ref(0)
const furthest = ref(0)

// ---------------------------------------------------------------------------
// Pricing — ONE source of truth
// ---------------------------------------------------------------------------
const FREE_SHIPPING_THRESHOLD = 100
const TAX_RATE = 0.1

const SHIPPING_METHODS = [
  { value: 'standard', label: 'Standard Shipping', eta: '5-7 business days', cost: 5.99, freeOver: FREE_SHIPPING_THRESHOLD },
  { value: 'express', label: 'Express Shipping', eta: '2-3 business days', cost: 15 },
  { value: 'overnight', label: 'Overnight Shipping', eta: 'Next business day', cost: 25 },
] as const

const PAYMENT_METHODS = [
  { value: 'cod', label: 'Cash on Delivery', hint: 'Pay the courier when your order arrives.', icon: 'pi pi-money-bill' },
  { value: 'bank_transfer', label: 'Bank Transfer', hint: 'We will email transfer instructions.', icon: 'pi pi-building-columns' },
  { 
    value: 'credit_card', 
    label: 'Credit/Debit Card (BDT & International)', 
    hint: 'Secure payment with Visa, Mastercard, or bKash card.', 
    icon: 'pi pi-credit-card' 
  },
] as const

// Added country list with Bangladesh at top for local users
const COUNTRIES = [
  { code: 'BD', name: 'Bangladesh' },
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'AU', name: 'Australia' },
  { code: 'IN', name: 'India' },
  { code: 'DE', name: 'Germany' },
  { code: 'FR', name: 'France' },
]

// Added BDT-specific formatting
const CURRENCY = {
  code: 'BDT',
  symbol: '৳',
  locale: 'bn-BD'
}

const MAX_COUPONS = 3
const couponCode = ref('')
const applyingCoupon = ref(false)
const appliedCoupons = ref<{ code: string; discount: number }[]>([])

const form = reactive({
  customer_name: '', customer_email: '', customer_phone: '',
  shipping_address: '', shipping_city: '', shipping_state: '',
  shipping_postal_code: '', shipping_country: 'BD', // Default to Bangladesh
  billing_address: '', billing_city: '', billing_state: '',
  billing_postal_code: '', billing_country: 'BD',
  use_same_address: true,
  shipping_method: 'standard' as string,
  payment_method: 'cod' as string,
  notes: '',
  terms_agreed: false,
  save_address: false,
})

// New: Track if user is paying by card
const isCardPayment = computed(() => form.payment_method === 'credit_card')

const subtotal = computed(() => Number(cartStore.subtotal) || 0)
const totalDiscount = computed(() =>
  appliedCoupons.value.reduce((sum, c) => sum + (Number(c.discount) || 0), 0),
)

const shippingCost = computed(() => {
  const method = SHIPPING_METHODS.find((m) => m.value === form.shipping_method) ?? SHIPPING_METHODS[0]
  if ('freeOver' in method && method.freeOver && subtotal.value >= method.freeOver) return 0
  return method.cost
})

const totals = computed(() => {
  const discounted = Math.max(0, subtotal.value - totalDiscount.value)
  const tax = discounted * TAX_RATE
  return {
    subtotal: subtotal.value,
    discount: totalDiscount.value,
    shipping: shippingCost.value,
    tax,
    grand: discounted + tax + shippingCost.value,
  }
})

const amountToFreeShipping = computed(() => Math.max(0, FREE_SHIPPING_THRESHOLD - subtotal.value))
const couponLimitReached = computed(() => appliedCoupons.value.length >= MAX_COUPONS)

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------
const billingRules = computed(() =>
  form.use_same_address
    ? {}
    : {
        billing_address: [required('Billing street address')],
        billing_city: [required('Billing city')],
        billing_country: [required('Billing country')],
        billing_postal_code: [postalCode()],
      },
)

const {
  errors: fieldErrors,
  submitted,
  errorCount,
  validateField,
  validateAll,
  handleBlur,
  handleInput,
  focusFirstError,
  applyServerErrors,
} = useFormValidation(form, {
  customer_name: [required('Full name')],
  customer_email: [required('Email address'), emailRule()],
  customer_phone: [required('Phone number'), phone()],
  shipping_address: [required('Street address')],
  shipping_city: [required('City')],
  shipping_country: [required('Country')],
  shipping_postal_code: [required('ZIP / postal code'), postalCode()],
  terms_agreed: [accepted('The terms and conditions')],
  ...billingRules.value,
})

const STEP_FIELDS: Record<number, (keyof typeof form)[]> = {
  0: ['customer_name', 'customer_email', 'customer_phone', 'shipping_address',
      'shipping_city', 'shipping_country', 'shipping_postal_code'],
  1: [],
  2: ['terms_agreed'],
}

const stepIsValid = (index: number) =>
  STEP_FIELDS[index]?.every((f) => !fieldErrors[f]) ?? true

const goToStep = (index: number) => {
  step.value = index
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const nextStep = async () => {
  const fields = STEP_FIELDS[step.value] ?? []
  let ok = true
  for (const field of fields) if (!validateField(field)) ok = false

  if (!ok) {
    await focusFirstError()
    return
  }

  furthest.value = Math.max(furthest.value, step.value + 1)
  goToStep(Math.min(step.value + 1, STEPS.length - 1))
}

// ---------------------------------------------------------------------------
// Billing mirror
// ---------------------------------------------------------------------------
const copyShippingToBilling = () => {
  if (!form.use_same_address) return
  form.billing_address = form.shipping_address
  form.billing_city = form.shipping_city
  form.billing_state = form.shipping_state
  form.billing_postal_code = form.shipping_postal_code
  form.billing_country = form.shipping_country
}
watch(() => form.use_same_address, copyShippingToBilling)
watch(
  () => [form.shipping_address, form.shipping_city, form.shipping_state, form.shipping_postal_code, form.shipping_country],
  copyShippingToBilling,
)

// ---------------------------------------------------------------------------
// Data loading
// ---------------------------------------------------------------------------
const loadProfile = async () => {
  loadingProfile.value = true
  profileError.value = ''
  try {
    const { data } = await api.get('/me')
    const user = data.data
    form.customer_name = user.name || ''
    form.customer_email = user.email || ''

    const p = user.profile
    if (p) {
      form.customer_phone = p.phone || ''
      form.shipping_address = p.address || ''
      form.shipping_city = p.city || ''
      form.shipping_state = p.state || ''
      form.shipping_postal_code = p.postal_code || ''
      form.shipping_country = p.country || 'BD'
    }
  } catch {
    profileError.value = 'We could not load your saved details. Please enter them below.'
  } finally {
    loadingProfile.value = false
  }
}

const fetchAppliedCoupons = async () => {
  try {
    const { data } = await api.get('/coupons/applied')
    if (data.status === 'success') appliedCoupons.value = data.data || []
  } catch {
    appliedCoupons.value = cartStore.couponCode
      ? [{ code: cartStore.couponCode, discount: cartStore.couponDiscount || 0 }]
      : []
  }
}

// ---------------------------------------------------------------------------
// Coupons
// ---------------------------------------------------------------------------
const applyCoupon = async () => {
  const code = couponCode.value.trim()
  if (!code) {
    notify.warn('Enter a code', 'Type a coupon code before applying.')
    return
  }
  if (couponLimitReached.value) {
    notify.warn('Coupon limit reached', `You can use up to ${MAX_COUPONS} coupons per order.`)
    return
  }

  applyingCoupon.value = true
  try {
    const { data } = await api.post('/coupons/apply', { code })
    if (data.status !== 'success') throw new Error(data.message)

    appliedCoupons.value = data.data.applied_coupons || []
    cartStore.discountTotal = totalDiscount.value
    couponCode.value = ''
    notify.success('Coupon applied', `You saved ${formatPrice(totalDiscount.value)} so far.`)
  } catch (error) {
    notify.apiError(error, 'That coupon code is not valid or has expired.')
  } finally {
    applyingCoupon.value = false
  }
}

const removeCoupon = async (code: string) => {
  try {
    await api.delete('/coupons/remove', { data: { code } })
    await fetchAppliedCoupons()
    cartStore.discountTotal = totalDiscount.value
    notify.info('Coupon removed', code)
  } catch (error) {
    notify.apiError(error, 'We could not remove that coupon.')
  }
}

// ---------------------------------------------------------------------------
// Submit - UPDATED to handle card payments properly
// ---------------------------------------------------------------------------
const blockingReason = computed(() => {
  if (!authStore.isAuthenticated) return 'Please sign in to place your order.'
  if (!form.terms_agreed) return 'Please accept the terms and conditions to continue.'
  return ''
})

const placeOrder = async () => {
  if (!authStore.isAuthenticated) {
    notify.warn('Sign in required', 'Please sign in to complete your order.')
    router.push({ path: '/login', query: { redirect: '/checkout' } })
    return
  }

  if (!validateAll()) {
    const firstBad = Object.keys(fieldErrors)[0] as keyof typeof form
    const owningStep = Number(
      Object.entries(STEP_FIELDS).find(([, fields]) => fields.includes(firstBad))?.[0] ?? 2,
    )
    if (owningStep !== step.value) goToStep(owningStep)
    await focusFirstError()
    notify.error('Please check the highlighted fields', `${errorCount} field(s) need attention.`)
    return
  }

  submitting.value = true
  try {
    const orderData = {
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
      save_address: form.save_address,
      notes: form.notes,
      coupon_codes: appliedCoupons.value.map((c) => c.code),
      currency: 'BDT',
    }

    const { data } = await api.post('/checkout/process', orderData)

    if (data.status !== 'success') throw new Error(data.message)

    // FIXED: If paying by card, redirect to payment provider BEFORE clearing cart
    if (isCardPayment.value && data.data?.payment_url) {
      // Redirect to payment provider with the order ID
      const paymentRedirectUrl = data.data.payment_url || `/payment/${data.data.order.id}`
      // Store order ID in session for return
      sessionStorage.setItem('pending_order_id', data.data.order.id)
      // Redirect to payment page
      window.location.href = paymentRedirectUrl
      return
    }

    // For non-card payments, clear cart and show success
    appliedCoupons.value = []
    cartStore.discountTotal = 0
    await cartStore.clear()

    notify.success('Order placed', 'Thank you! A confirmation email is on its way.')
    router.push(`/dashboard/customer/order/${data?.data?.order?.id}`)
  } catch (error: any) {
    applyServerErrors(error?.response?.data?.errors)
    notify.apiError(error, 'We could not place your order. Nothing has been charged.')
  } finally {
    submitting.value = false
  }
}

// New: Handle returning from payment gateway
const handlePaymentReturn = () => {
  const params = new URLSearchParams(window.location.search)
  const status = params.get('payment_status')
  const orderId = params.get('order_id')
  
  if (status === 'success' && orderId) {
    sessionStorage.removeItem('pending_order_id')
    cartStore.clear()
    notify.success('Payment successful!', 'Your order is confirmed.')
    router.push(`/dashboard/customer/order/${orderId}`)
  } else if (status === 'failed') {
    notify.error('Payment failed', 'Please try again or choose another payment method.')
    router.push('/checkout')
  }
}

onMounted(async () => {
  await Promise.all([cartStore.fetchCart(), loadProfile(), fetchAppliedCoupons()])
  
  // Check if returning from payment
  if (window.location.search.includes('payment_status')) {
    handlePaymentReturn()
  }
})
</script>

<template>
  <div class="min-h-screen bg-ink-50 pb-28 lg:pb-0">
    <!-- Header -->
    <div class="border-b border-ink-200 bg-white">
      <div class="page-container py-4">
        <nav aria-label="Breadcrumb" class="text-sm">
          <ol class="flex flex-wrap items-center gap-2 text-ink-500">
            <li><router-link to="/" class="link">Home</router-link></li>
            <li aria-hidden="true">/</li>
            <li><router-link to="/cart" class="link">Cart</router-link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-ink-700">Checkout</li>
          </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-ink-900 sm:text-3xl">Checkout</h1>
        <p class="mt-1 text-sm text-ink-500">All amounts in Bangladeshi Taka (BDT) ৳</p>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="cartStore.loading && !cartStore.items.length" class="page-container py-8">
      <div class="grid gap-6 lg:grid-cols-3 lg:gap-8">
        <div class="space-y-4 lg:col-span-2">
          <div v-for="n in 3" :key="n" class="card space-y-3 p-5">
            <div class="skeleton h-5 w-40"></div>
            <div class="skeleton h-11 w-full"></div>
            <div class="skeleton h-11 w-full"></div>
          </div>
        </div>
        <div class="card space-y-3 p-5">
          <div class="skeleton h-5 w-32"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-2/3"></div>
          <div class="skeleton h-12 w-full"></div>
        </div>
      </div>
    </div>

    <!-- Empty cart -->
    <div v-else-if="cartStore.isEmpty" class="page-container py-16">
      <div class="card mx-auto max-w-lg">
        <EmptyState
          icon="pi pi-shopping-cart"
          title="Your cart is empty"
          description="Add something you like and it will show up here, ready to check out."
        >
          <template #action>
            <router-link to="/shop" class="btn btn-primary">Start shopping</router-link>
          </template>
        </EmptyState>
      </div>
    </div>

    <!-- Checkout -->
    <div v-else class="page-container py-6 lg:py-8">
      <!-- Progress -->
      <div class="card mb-6 p-5">
        <ProgressSteps :steps="STEPS" :current="step" :furthest="furthest" @go="goToStep" />
      </div>

      <div class="grid gap-6 lg:grid-cols-3 lg:gap-8">
        <form class="space-y-5 lg:col-span-2" novalidate @submit.prevent="placeOrder">
          <!-- Guest / profile notices -->
          <div
            v-if="!authStore.isAuthenticated"
            class="flex items-start gap-3 rounded-card border border-warning-100 bg-warning-50 p-4"
          >
            <i class="pi pi-info-circle mt-0.5 text-warning-700" aria-hidden="true" />
            <p class="text-sm text-warning-700">
              <router-link :to="{ path: '/login', query: { redirect: '/checkout' } }" class="font-semibold underline">
                Sign in
              </router-link>
              to use your saved addresses and track this order.
            </p>
          </div>
          <div
            v-else-if="profileError"
            class="flex items-start gap-3 rounded-card border border-warning-100 bg-warning-50 p-4"
            role="alert"
          >
            <i class="pi pi-exclamation-triangle mt-0.5 text-warning-700" aria-hidden="true" />
            <p class="text-sm text-warning-700">{{ profileError }}</p>
          </div>

          <!-- Error summary -->
          <div
            v-if="submitted && errorCount > 0"
            class="rounded-card border border-danger-100 bg-danger-50 p-4"
            role="alert"
            tabindex="-1"
          >
            <p class="flex items-center gap-2 text-sm font-semibold text-danger-700">
              <i class="pi pi-exclamation-circle" aria-hidden="true" />
              Please fix {{ errorCount }} field{{ errorCount === 1 ? '' : 's' }} before continuing
            </p>
            <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs text-danger-700">
              <li v-for="(message, field) in fieldErrors" :key="field">{{ message }}</li>
            </ul>
          </div>

          <!-- ============ STEP 1 · ADDRESS ============ -->
          <section v-show="step === 0" class="space-y-5" aria-label="Shipping address">
            <div class="card p-5">
              <h2 class="mb-4 text-base font-semibold text-ink-900">Contact details</h2>
              <div v-if="loadingProfile" class="space-y-3">
                <div class="skeleton h-11 w-full"></div>
                <div class="skeleton h-11 w-full"></div>
              </div>
              <div v-else class="grid gap-4 sm:grid-cols-2">
                <AppInput
                  v-model="form.customer_name" name="customer_name" label="Full name" required
                  autocomplete="name" placeholder="Jane Doe" :error="fieldErrors.customer_name"
                  @blur="handleBlur('customer_name')" />
                <AppInput
                  v-model="form.customer_phone" name="customer_phone" label="Phone number" required
                  type="tel" inputmode="tel" autocomplete="tel" placeholder="+880 1XXX XXXXXXX"
                  hint="Couriers use this for delivery updates."
                  :error="fieldErrors.customer_phone" @blur="handleBlur('customer_phone')" />
                <div class="sm:col-span-2">
                  <AppInput
                    v-model="form.customer_email" name="customer_email" label="Email address" required
                    type="email" inputmode="email" autocomplete="email" placeholder="jane@example.com"
                    hint="Your receipt and tracking link go here."
                    :error="fieldErrors.customer_email" @blur="handleBlur('customer_email')" />
                </div>
              </div>
            </div>

            <div class="card p-5">
              <h2 class="mb-4 text-base font-semibold text-ink-900">Shipping address</h2>
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <AppInput
                    v-model="form.shipping_address" name="shipping_address" label="Street address" required
                    autocomplete="shipping street-address" placeholder="123 Main Street, Apt 4B"
                    :error="fieldErrors.shipping_address" @blur="handleBlur('shipping_address')" />
                </div>
                <AppInput
                  v-model="form.shipping_city" name="shipping_city" label="City" required
                  autocomplete="shipping address-level2" placeholder="Dhaka"
                  :error="fieldErrors.shipping_city" @blur="handleBlur('shipping_city')" />
                <AppInput
                  v-model="form.shipping_state" name="shipping_state" label="State / Division"
                  autocomplete="shipping address-level1" placeholder="Dhaka" />
                <AppInput
                  v-model="form.shipping_postal_code" name="shipping_postal_code" label="ZIP / Postal code" required
                  autocomplete="shipping postal-code" inputmode="text" placeholder="1000"
                  :error="fieldErrors.shipping_postal_code" @blur="handleBlur('shipping_postal_code')" />
                <div data-field="shipping_country">
                  <label for="shipping_country" class="field-label">
                    Country <span aria-hidden="true" class="text-danger-600">*</span>
                    <span class="sr-only">(required)</span>
                  </label>
                  <select
                    id="shipping_country" v-model="form.shipping_country" name="shipping_country"
                    class="field-control" autocomplete="shipping country"
                    :aria-invalid="fieldErrors.shipping_country ? 'true' : undefined"
                    aria-describedby="shipping_country-error"
                    @blur="handleBlur('shipping_country')"
                  >
                    <option value="">Select a country</option>
                    <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">{{ c.name }}</option>
                  </select>
                  <p v-if="fieldErrors.shipping_country" id="shipping_country-error" class="field-error" role="alert">
                    <i class="pi pi-exclamation-circle mt-px shrink-0 text-[0.9em]" aria-hidden="true" />
                    <span>{{ fieldErrors.shipping_country }}</span>
                  </p>
                </div>
              </div>

              <div class="mt-5 space-y-3 border-t border-ink-100 pt-4">
                <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                  <input v-model="form.use_same_address" type="checkbox" class="size-4 rounded border-ink-300 text-brand-600" />
                  Billing address is the same as shipping
                </label>
                <label v-if="authStore.isAuthenticated" class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                  <input v-model="form.save_address" type="checkbox" class="size-4 rounded border-ink-300 text-brand-600" />
                  Save this address for next time
                </label>
              </div>
            </div>

            <div v-if="!form.use_same_address" class="card p-5">
              <h2 class="mb-4 text-base font-semibold text-ink-900">Billing address</h2>
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <AppInput v-model="form.billing_address" name="billing_address" label="Street address" required
                    autocomplete="billing street-address" :error="fieldErrors.billing_address"
                    @blur="handleBlur('billing_address')" />
                </div>
                <AppInput v-model="form.billing_city" name="billing_city" label="City" required
                  autocomplete="billing address-level2" :error="fieldErrors.billing_city"
                  @blur="handleBlur('billing_city')" />
                <AppInput v-model="form.billing_state" name="billing_state" label="State / Division"
                  autocomplete="billing address-level1" />
                <AppInput v-model="form.billing_postal_code" name="billing_postal_code" label="ZIP / Postal code"
                  autocomplete="billing postal-code" :error="fieldErrors.billing_postal_code"
                  @blur="handleBlur('billing_postal_code')" />
                <div>
                  <label for="billing_country" class="field-label">Country</label>
                  <select id="billing_country" v-model="form.billing_country" class="field-control" autocomplete="billing country">
                    <option value="">Select a country</option>
                    <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">{{ c.name }}</option>
                  </select>
                </div>
              </div>
            </div>
          </section>

          <!-- ============ STEP 2 · DELIVERY & PAYMENT ============ -->
          <section v-show="step === 1" class="space-y-5" aria-label="Delivery and payment">
            <fieldset class="card p-5">
              <legend class="mb-4 text-base font-semibold text-ink-900">Delivery speed</legend>
              <div class="space-y-3">
                <label
                  v-for="method in SHIPPING_METHODS" :key="method.value"
                  class="choice-tile" :data-selected="form.shipping_method === method.value"
                >
                  <input
                    v-model="form.shipping_method" type="radio" name="shipping_method"
                    :value="method.value" class="size-4 shrink-0 text-brand-600"
                  />
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-ink-900">{{ method.label }}</span>
                    <span class="block text-xs text-ink-500">{{ method.eta }}</span>
                  </span>
                  <span class="tabular shrink-0 text-sm font-semibold">
                    <template v-if="'freeOver' in method && method.freeOver && subtotal >= method.freeOver">
                      <span class="text-success-700">Free</span>
                    </template>
                    <template v-else>{{ formatPrice(method.cost) }}</template>
                  </span>
                </label>
              </div>

              <div v-if="amountToFreeShipping > 0" class="mt-4 rounded-control bg-brand-50 p-3">
                <p class="text-xs font-medium text-brand-800">
                  Add {{ formatPrice(amountToFreeShipping) }} more for free standard shipping.
                </p>
                <div
                  class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-100"
                  role="progressbar"
                  :aria-valuenow="Math.round((subtotal / FREE_SHIPPING_THRESHOLD) * 100)"
                  aria-valuemin="0" aria-valuemax="100"
                  aria-label="Progress towards free shipping"
                >
                  <div class="h-full bg-brand-600 transition-all" :style="{ width: `${Math.min(100, (subtotal / FREE_SHIPPING_THRESHOLD) * 100)}%` }" />
                </div>
              </div>
            </fieldset>

            <fieldset class="card p-5">
              <legend class="mb-4 text-base font-semibold text-ink-900">Payment method</legend>
              <div class="space-y-3">
                <label
                  v-for="method in PAYMENT_METHODS" :key="method.value"
                  class="choice-tile" :data-selected="form.payment_method === method.value"
                >
                  <input
                    v-model="form.payment_method" type="radio" name="payment_method"
                    :value="method.value" class="size-4 shrink-0 text-brand-600"
                  />
                  <i :class="method.icon" class="shrink-0 text-ink-500" aria-hidden="true" />
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-ink-900">{{ method.label }}</span>
                    <span class="block text-xs text-ink-500">{{ method.hint }}</span>
                  </span>
                </label>
              </div>

              <!-- UPDATED: Better card payment info with BDT support -->
              <div
                v-if="isCardPayment"
                class="mt-4 space-y-3 rounded-control border border-info-100 bg-info-50 p-4"
              >
                <div class="flex items-start gap-3">
                  <i class="pi pi-lock mt-0.5 text-info-700" aria-hidden="true" />
                  <div class="flex-1">
                    <p class="text-sm font-medium text-info-800">Secure card payment</p>
                    <p class="text-xs text-info-700">
                      We accept Visa, Mastercard, and bKash cards. Your card will be charged in BDT ({{ CURRENCY.symbol }})
                      after you confirm the order.
                    </p>
                    <p class="mt-2 text-xs text-info-700">
                      🔒 All transactions are secured with 3D Secure authentication.
                    </p>
                    <!-- Added international card support info -->
                    <p class="mt-1 text-xs text-info-600">
                      🌍 International cards accepted. Your bank may charge a foreign transaction fee.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Mobile banking hint for Bangladesh users -->
              <div
                v-if="form.payment_method === 'bank_transfer'"
                class="mt-4 flex items-start gap-3 rounded-control border border-info-100 bg-info-50 p-3"
              >
                <i class="pi pi-info-circle mt-0.5 text-info-700" aria-hidden="true" />
                <p class="text-xs text-info-700">
                  After placing your order, we'll send you our bank account details. 
                  Available banks: bKash, Nagad, Rocket, Dutch-Bangla, and all commercial banks.
                </p>
              </div>
            </fieldset>

            <!-- Coupons -->
            <div class="card p-5">
              <h2 class="mb-1 text-base font-semibold text-ink-900">Discount codes</h2>
              <p class="mb-4 text-xs text-ink-500">
                You can combine up to {{ MAX_COUPONS }} codes on one order.
              </p>

              <ul v-if="appliedCoupons.length" class="mb-4 space-y-2">
                <li
                  v-for="coupon in appliedCoupons" :key="coupon.code"
                  class="flex items-center gap-3 rounded-control border border-success-100 bg-success-50 p-3"
                >
                  <i class="pi pi-check-circle shrink-0 text-success-600" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-success-700">{{ coupon.code }}</p>
                    <p class="tabular text-xs text-success-700">
                      Saves {{ formatPrice(coupon.discount) }}
                    </p>
                  </div>
                  <button
                    type="button" class="btn btn-ghost btn-xs text-danger-700"
                    :aria-label="`Remove coupon ${coupon.code}`"
                    @click="removeCoupon(coupon.code)"
                  >
                    <i class="pi pi-times text-[0.65rem]" aria-hidden="true" />
                    Remove
                  </button>
                </li>
              </ul>

              <div class="flex flex-col gap-2 sm:flex-row">
                <div class="flex-1">
                  <label for="coupon" class="sr-only">Coupon code</label>
                  <input
                    id="coupon" v-model="couponCode" type="text" class="field-control uppercase"
                    placeholder="Enter code" :disabled="couponLimitReached"
                    autocapitalize="characters" autocomplete="off"
                    @keyup.enter.prevent="applyCoupon"
                  />
                </div>
                <button
                  type="button" class="btn btn-outline"
                  :disabled="applyingCoupon || couponLimitReached" :aria-busy="applyingCoupon"
                  @click="applyCoupon"
                >
                  <i v-if="applyingCoupon" class="pi pi-spinner animate-spin" aria-hidden="true" />
                  {{ applyingCoupon ? 'Checking...' : 'Apply' }}
                </button>
              </div>
              <p v-if="couponLimitReached" class="field-hint">
                Remove a code to add a different one.
              </p>
            </div>

            <div class="card p-5">
              <label for="notes" class="field-label">Delivery notes (optional)</label>
              <textarea
                id="notes" v-model="form.notes" rows="3" maxlength="500"
                class="field-control resize-y"
                placeholder="Gate code, safe place to leave the parcel, etc."
              />
            </div>
          </section>

          <!-- ============ STEP 3 · REVIEW ============ -->
          <section v-show="step === 2" class="space-y-5" aria-label="Review your order">
            <div class="card divide-y divide-ink-100">
              <div class="flex items-start justify-between gap-4 p-5">
                <div class="min-w-0">
                  <h2 class="text-sm font-semibold text-ink-900">Delivering to</h2>
                  <p class="mt-1 text-sm text-ink-600">{{ form.customer_name }}</p>
                  <p class="text-sm text-ink-600">
                    {{ form.shipping_address }}, {{ form.shipping_city }}
                    {{ form.shipping_state }} {{ form.shipping_postal_code }}
                  </p>
                  <p class="text-sm text-ink-600">{{ form.customer_phone }} · {{ form.customer_email }}</p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm shrink-0" @click="goToStep(0)">Edit</button>
              </div>

              <div class="flex items-start justify-between gap-4 p-5">
                <div class="min-w-0">
                  <h2 class="text-sm font-semibold text-ink-900">Delivery &amp; payment</h2>
                  <p class="mt-1 text-sm text-ink-600">
                    {{ SHIPPING_METHODS.find((m) => m.value === form.shipping_method)?.label }}
                    ({{ SHIPPING_METHODS.find((m) => m.value === form.shipping_method)?.eta }})
                  </p>
                  <p class="text-sm text-ink-600">
                    {{ PAYMENT_METHODS.find((m) => m.value === form.payment_method)?.label }}
                    {{ isCardPayment ? '🔒' : '' }}
                  </p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm shrink-0" @click="goToStep(1)">Edit</button>
              </div>

              <div class="p-5">
                <h2 class="mb-3 text-sm font-semibold text-ink-900">
                  Items ({{ cartStore.activeItems.length }})
                </h2>
                <ul class="space-y-2.5">
                  <li
                    v-for="item in cartStore.activeItems" :key="item.id"
                    class="flex items-start justify-between gap-3 text-sm"
                  >
                    <span class="min-w-0 text-ink-700">
                      <span class="line-clamp-2">{{ item.product_name }}</span>
                      <span class="text-xs text-ink-500">Qty {{ item.quantity }}</span>
                    </span>
                    <span class="tabular shrink-0 font-medium text-ink-900">
                      {{ formatPrice(item.total) }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>

            <div class="card p-5" data-field="terms_agreed">
              <label class="flex cursor-pointer items-start gap-3">
                <input
                  v-model="form.terms_agreed" name="terms_agreed" type="checkbox"
                  class="mt-0.5 size-4 shrink-0 rounded border-ink-300 text-brand-600"
                  :aria-invalid="fieldErrors.terms_agreed ? 'true' : undefined"
                  aria-describedby="terms-error"
                  @change="handleInput('terms_agreed')"
                />
                <span class="text-sm text-ink-600">
                  I agree to the
                  <a href="#" class="link">Terms &amp; Conditions</a> and
                  <a href="#" class="link">Privacy Policy</a>.
                </span>
              </label>
              <p v-if="fieldErrors.terms_agreed" id="terms-error" class="field-error" role="alert">
                <i class="pi pi-exclamation-circle mt-px shrink-0 text-[0.9em]" aria-hidden="true" />
                <span>{{ fieldErrors.terms_agreed }}</span>
              </p>
            </div>
          </section>

          <!-- Step navigation -->
          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <router-link v-if="step === 0" to="/cart" class="btn btn-secondary">
              <i class="pi pi-arrow-left" aria-hidden="true" />
              Back to cart
            </router-link>
            <button v-else type="button" class="btn btn-secondary" @click="goToStep(step - 1)">
              <i class="pi pi-arrow-left" aria-hidden="true" />
              Back
            </button>

            <button v-if="step < STEPS.length - 1" type="button" class="btn btn-primary" @click="nextStep">
              Continue
              <i class="pi pi-arrow-right" aria-hidden="true" />
            </button>
            <button
              v-else type="submit" class="btn btn-primary btn-lg"
              :disabled="submitting" :aria-busy="submitting"
            >
              <i v-if="submitting" class="pi pi-spinner animate-spin" aria-hidden="true" />
              <i v-else-if="isCardPayment" class="pi pi-credit-card" aria-hidden="true" />
              <i v-else class="pi pi-lock" aria-hidden="true" />
              {{ submitting ? 'Processing...' : isCardPayment ? `Pay ${formatPrice(totals.grand)} with Card` : `Pay ${formatPrice(totals.grand)}` }}
            </button>
          </div>
        </form>

        <!-- Order summary -->
        <aside class="lg:col-span-1" aria-label="Order summary">
          <div class="card p-5 lg:sticky lg:top-20">
            <h2 class="mb-4 text-base font-semibold text-ink-900">Order summary</h2>

            <ul class="mb-4 max-h-56 space-y-2.5 overflow-y-auto pr-1">
              <li
                v-for="item in cartStore.activeItems" :key="item.id"
                class="flex items-start justify-between gap-2 text-sm"
              >
                <span class="min-w-0 text-ink-700">
                  <span class="line-clamp-2">{{ item.product_name }}</span>
                  <span class="text-xs text-ink-500">Qty {{ item.quantity }}</span>
                </span>
                <span class="tabular shrink-0 font-medium text-ink-900">{{ formatPrice(item.total) }}</span>
              </li>
            </ul>

            <dl class="space-y-2 border-t border-ink-200 pt-4 text-sm" aria-live="polite">
              <div class="flex justify-between text-ink-600">
                <dt>Subtotal</dt>
                <dd class="tabular">{{ formatPrice(totals.subtotal) }}</dd>
              </div>
              <div v-if="totals.discount > 0" class="flex justify-between font-medium text-success-700">
                <dt>Discounts ({{ appliedCoupons.length }})</dt>
                <dd class="tabular">-{{ formatPrice(totals.discount) }}</dd>
              </div>
              <div class="flex justify-between text-ink-600">
                <dt>Shipping</dt>
                <dd class="tabular">
                  <span v-if="totals.shipping === 0" class="font-medium text-success-700">Free</span>
                  <span v-else>{{ formatPrice(totals.shipping) }}</span>
                </dd>
              </div>
              <div class="flex justify-between text-ink-600">
                <dt>Estimated tax</dt>
                <dd class="tabular">{{ formatPrice(totals.tax) }}</dd>
              </div>
            </dl>

            <div class="mt-4 flex items-baseline justify-between border-t border-ink-200 pt-4">
              <span class="text-base font-bold text-ink-900">Total</span>
              <span class="tabular text-xl font-bold text-brand-700">{{ formatPrice(totals.grand) }}</span>
            </div>

            <!-- Trust signals -->
            <ul class="mt-5 space-y-2 border-t border-ink-100 pt-4 text-xs text-ink-500">
              <li class="flex items-center gap-2">
                <i class="pi pi-lock text-success-600" aria-hidden="true" />
                Encrypted, secure checkout
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-undo text-success-600" aria-hidden="true" />
                30-day returns on eligible items
              </li>
              <li v-if="isCardPayment" class="flex items-center gap-2 text-success-600">
                <i class="pi pi-shield" aria-hidden="true" />
                3D Secure authenticated
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </div>

    <!-- Sticky mobile bar -->
    <div
      v-if="!cartStore.isEmpty"
      class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200 bg-white/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden"
    >
      <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1">
          <p class="text-xs text-ink-500">Total</p>
          <p class="tabular truncate text-lg font-bold text-brand-700">{{ formatPrice(totals.grand) }}</p>
        </div>
        <button v-if="step < STEPS.length - 1" type="button" class="btn btn-primary" @click="nextStep">
          Continue
        </button>
        <button
          v-else type="button" class="btn btn-primary"
          :disabled="submitting" :aria-busy="submitting" @click="placeOrder"
        >
          <i v-if="submitting" class="pi pi-spinner animate-spin" aria-hidden="true" />
          {{ submitting ? 'Processing...' : 'Place order' }}
        </button>
      </div>
      <p v-if="step === STEPS.length - 1 && blockingReason" class="mt-1.5 text-center text-xs text-danger-700">
        {{ blockingReason }}
      </p>
    </div>
  </div>
</template>