<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const SUGGESTIONS = [
  { to: '/shop', label: 'Browse all products', icon: 'pi pi-shopping-bag' },
  { to: '/new-arrivals', label: 'See new arrivals', icon: 'pi pi-star' },
  { to: '/cart', label: 'View your cart', icon: 'pi pi-shopping-cart' },
]
</script>

<template>
  <div class="page-container grid min-h-[70vh] place-items-center py-16">
    <div class="w-full max-w-lg text-center">
      <p class="text-6xl font-black text-brand-600 sm:text-7xl" aria-hidden="true">404</p>

      <h1 class="mt-4 text-2xl font-bold text-ink-900 sm:text-3xl">
        We could not find that page
      </h1>
      <p class="mt-3 text-ink-600">
        The link may be broken, or the page may have been moved or removed.
        Nothing is wrong with your connection.
      </p>

      <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
        <!-- Back first: the most likely intent after a mistyped URL -->
        <button type="button" class="btn btn-secondary" @click="router.back()">
          <i class="pi pi-arrow-left" aria-hidden="true" />
          Go back
        </button>
        <router-link to="/" class="btn btn-primary">
          <i class="pi pi-home" aria-hidden="true" />
          Back to home
        </router-link>
      </div>

      <nav class="mt-10 text-left" aria-label="Suggested pages">
        <h2 class="mb-3 text-sm font-semibold text-ink-700">Or try one of these</h2>
        <ul class="card divide-y divide-ink-100 overflow-hidden">
          <li v-for="item in SUGGESTIONS" :key="item.to">
            <router-link
              :to="item.to"
              class="flex items-center gap-3 px-4 py-3.5 text-sm font-medium text-ink-700 transition hover:bg-brand-50 hover:text-brand-700"
            >
              <i :class="item.icon" class="w-4 text-center text-ink-500" aria-hidden="true" />
              <span class="flex-1">{{ item.label }}</span>
              <i class="pi pi-chevron-right text-xs text-ink-400" aria-hidden="true" />
            </router-link>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>
