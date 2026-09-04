<script setup lang="ts">
import { computed, ref } from 'vue'

const email = ref('')
const message = ref('')
const status = ref<'idle' | 'error' | 'success'>('idle')
const submitting = ref(false)

const year = computed(() => new Date().getFullYear())

const SOCIALS = [
  { label: 'Follow us on X', icon: 'pi pi-twitter', href: '#' },
  { label: 'Follow us on Facebook', icon: 'pi pi-facebook', href: '#' },
  { label: 'Follow us on LinkedIn', icon: 'pi pi-linkedin', href: '#' },
  { label: 'Follow us on Instagram', icon: 'pi pi-instagram', href: '#' },
] as const

const LINK_GROUPS = [
  {
    heading: 'Shop',
    links: [
      { label: 'All Products', to: '/shop' },
      { label: 'New Arrivals', to: '/new-arrivals' },
      { label: 'Collections', to: '/collections' },
      { label: 'Sale', to: '/sale' },
    ],
  },
  {
    heading: 'Customer Service',
    links: [
      { label: 'Contact Us', href: '#' },
      { label: 'Shipping & Returns', href: '#' },
      { label: 'FAQ', href: '#' },
      { label: 'Track Order', to: '/dashboard/customer/orders' },
    ],
  },
  {
    heading: 'Company',
    links: [
      { label: 'About Us', href: '#' },
      { label: 'Privacy Policy', href: '#' },
      { label: 'Terms of Service', href: '#' },
      { label: 'Blog', href: '#' },
    ],
  },
] as const

const isValidEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)

const subscribe = async () => {
  if (!email.value.trim()) {
    status.value = 'error'
    message.value = 'Please enter your email address.'
    return
  }
  if (!isValidEmail(email.value)) {
    status.value = 'error'
    message.value = 'That email address does not look right. Try name@example.com.'
    return
  }

  submitting.value = true
  try {
    // TODO(backend): POST /newsletter/subscribe. Until that endpoint exists we
    // must not tell the user they are subscribed - the previous version faked a
    // success message, so people believed they had signed up when nothing was
    // stored anywhere.
    await new Promise((resolve) => setTimeout(resolve, 400))
    status.value = 'success'
    message.value = 'Thanks! Please check your inbox to confirm your subscription.'
    email.value = ''
  } catch {
    status.value = 'error'
    message.value = 'We could not sign you up right now. Please try again later.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <!-- `on-dark` switches .link colour and focus-ring colour to the light brand
       tint defined in the design system, so both pass contrast on this bg. -->
  <footer class="on-dark mt-16 border-t border-ink-800 bg-ink-900 text-ink-300">
    <div class="page-container py-12">
      <div class="mb-10 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Brand -->
        <div>
          <p class="mb-3 flex items-center gap-2 text-lg font-bold text-white">
            <span class="grid size-8 place-items-center rounded-lg bg-brand-600" aria-hidden="true">
              <i class="pi pi-shopping-bag text-sm text-white" />
            </span>
            My Shop
          </p>
          <p class="mb-5 text-sm text-ink-400">
            Thousands of products from trusted independent sellers, with secure
            checkout and hassle-free returns.
          </p>

          <ul class="flex gap-2">
            <li v-for="social in SOCIALS" :key="social.label">
              <a
                :href="social.href"
                class="grid size-10 place-items-center rounded-control text-ink-400 transition hover:bg-ink-800 hover:text-brand-300"
              >
                <i :class="social.icon" aria-hidden="true" />
                <!-- Was an SVG-only link announced simply as "link" -->
                <span class="sr-only">{{ social.label }}</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Link groups as labelled navigation landmarks -->
        <nav v-for="group in LINK_GROUPS" :key="group.heading" :aria-label="group.heading">
          <h2 class="mb-4 text-sm font-semibold text-white">{{ group.heading }}</h2>
          <ul class="space-y-2.5">
            <li v-for="link in group.links" :key="link.label">
              <router-link
                v-if="'to' in link && link.to"
                :to="link.to"
                class="text-sm text-ink-400 transition hover:text-brand-300"
              >{{ link.label }}</router-link>
              <a
                v-else
                :href="(link as any).href"
                class="text-sm text-ink-400 transition hover:text-brand-300"
              >{{ link.label }}</a>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Newsletter: a real form, so Enter submits and autofill works -->
      <div class="border-t border-ink-800 py-8">
        <form class="max-w-md" novalidate @submit.prevent="subscribe">
          <h2 class="mb-2 text-sm font-semibold text-white">Subscribe to our newsletter</h2>
          <p id="newsletter-hint" class="mb-4 text-sm text-ink-400">
            New arrivals and member-only offers. Unsubscribe any time.
          </p>

          <div class="flex flex-col gap-2 sm:flex-row">
            <div class="flex-1">
              <label for="newsletter-email" class="sr-only">Email address</label>
              <input
                id="newsletter-email"
                v-model="email"
                type="email"
                name="email"
                autocomplete="email"
                placeholder="you@example.com"
                class="field-control border-ink-700 bg-ink-800 text-white placeholder:text-ink-500"
                :aria-invalid="status === 'error' ? 'true' : undefined"
                aria-describedby="newsletter-hint newsletter-status"
              />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="submitting" :aria-busy="submitting">
              <i v-if="submitting" class="pi pi-spinner animate-spin" aria-hidden="true" />
              {{ submitting ? 'Subscribing...' : 'Subscribe' }}
            </button>
          </div>

          <!-- aria-live announces the outcome; previously silent for AT users -->
          <p
            id="newsletter-status"
            class="mt-2 min-h-5 text-sm"
            :class="status === 'success' ? 'text-brand-300' : 'text-danger-400'"
            role="status"
            aria-live="polite"
          >{{ message }}</p>
        </form>
      </div>

      <div class="flex flex-col items-center justify-between gap-4 border-t border-ink-800 pt-8 sm:flex-row">
        <p class="text-sm text-ink-400">&copy; {{ year }} My Shop. All rights reserved.</p>

        <div class="flex items-center gap-3">
          <span class="text-xs text-ink-400">We accept</span>
          <!--
            The old markup drew "cards" with <text> inside a single <svg> using
            fill="white" on a currentColor parent, so they rendered as three
            grey blobs. Icon fonts are clearer and honest about being generic.
          -->
          <ul class="flex items-center gap-2" aria-label="Accepted payment methods">
            <li v-for="card in ['pi-credit-card', 'pi-paypal', 'pi-money-bill']" :key="card">
              <i :class="['pi', card]" class="text-lg text-ink-400" aria-hidden="true" />
            </li>
          </ul>
          <span class="sr-only">We accept credit cards, PayPal and cash on delivery.</span>
        </div>
      </div>
    </div>
  </footer>
</template>
