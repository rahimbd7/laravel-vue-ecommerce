<script lang="ts">
import api from '@/api/api'

const wishlistIds = new Set<number>()
let wishlistPromise: Promise<void> | null = null

export const primeWishlist = (isAuthenticated: boolean): Promise<void> => {
  if (!isAuthenticated) return Promise.resolve()
  if (wishlistPromise) return wishlistPromise

  wishlistPromise = api
    .get('/wishlist')
    .then(({ data }) => {
      const items = data?.data ?? data ?? []
      for (const item of items) {
        const id = Number(item?.product_id ?? item?.product?.id)
        if (Number.isFinite(id)) wishlistIds.add(id)
      }
    })
    .catch(() => {
      // A missing/empty wishlist is not an error the shopper needs to see.
    })

  return wishlistPromise
}

export const wishlistCache = wishlistIds
</script>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useNotify } from '@/composables/useNotify'
import type { Product } from '@/types/models/product.types'

const props = defineProps<{ product: Product }>()

const emit = defineEmits<{
  (e: 'add-to-cart', product: Product): void
  (e: 'wishlist-updated'): void
}>()

const notify = useNotify()
const authStore = useAuthStore()

const imageError = ref(false)
const wishlistLoading = ref(false)
const addingToCart = ref(false)
const isInWishlist = ref(false)

const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'

const stockStatus = computed(() => props.product.inventory?.status)
const isOutOfStock = computed(() => stockStatus.value === 'out_of_stock')
const isLowStock = computed(() => stockStatus.value === 'low_stock')

const rating = computed(() => Number(props.product.stats?.average_rating ?? 0))
const reviewCount = computed(() => Number(props.product.stats?.review_count ?? 0))

const productImage = computed(() => {
  if (imageError.value) return null
  const path = props.product.media?.image || props.product.media?.thumbnail
  if (!path) return null
  return path.startsWith('http') ? path : `${baseUrl}/storage/${path}`
})

/**
 * Cloudinary can resize on the fly, so phones fetch a ~400px file instead of
 * the full-resolution original. Non-Cloudinary URLs pass straight through.
 */
const srcset = computed(() => {
  const url = productImage.value
  if (!url || !url.includes('/upload/')) return undefined
  const [head, tail] = url.split('/upload/')
  return [320, 480, 640]
    .map((w) => `${head}/upload/f_auto,q_auto,c_fill,w_${w}/${tail} ${w}w`)
    .join(', ')
})

const toggleWishlist = async () => {
  if (wishlistLoading.value) return

  // Explain the requirement instead of silently 401-ing (WCAG 3.3.1)
  if (!authStore.isAuthenticated) {
    notify.info('Sign in to save items', 'Create a free account to build your wishlist.')
    return
  }

  wishlistLoading.value = true
  const previous = isInWishlist.value
  // Optimistic flip: the heart responds instantly instead of after a round trip
  isInWishlist.value = !previous

  try {
    const { data } = await (await import('@/api/api')).default.post('/wishlist/toggle', {
      product_id: props.product.id,
    })
    const added = data?.data?.status === 'added'
    isInWishlist.value = added
    added ? wishlistCache.add(props.product.id) : wishlistCache.delete(props.product.id)
    notify.success(added ? 'Saved to wishlist' : 'Removed from wishlist', props.product.name)
    emit('wishlist-updated')
  } catch (error) {
    isInWishlist.value = previous // roll the optimistic update back
    notify.apiError(error, 'Could not update your wishlist.')
  } finally {
    wishlistLoading.value = false
  }
}

const onAddToCart = async () => {
  if (isOutOfStock.value || addingToCart.value) return
  addingToCart.value = true
  emit('add-to-cart', props.product)
  // Short guard window: enough to stop a double-tap without stranding the button
  // if the parent forgets to signal completion.
  setTimeout(() => (addingToCart.value = false), 900)
}

onMounted(async () => {
  await primeWishlist(authStore.isAuthenticated)
  isInWishlist.value = wishlistCache.has(props.product.id)
})
</script>

<template>
  <article
    class="card card-interactive group relative flex flex-col overflow-hidden"
    :aria-busy="addingToCart || undefined"
  >
    <!-- Badges. Sale and low-stock are independent conditions, not either/or. -->
    <div class="absolute left-3 top-3 z-20 flex flex-col items-start gap-1.5">
      <span v-if="product.price.is_on_sale" class="badge bg-danger-600 text-white">
        -{{ product.price.discount }}%
      </span>
      <span v-if="isLowStock" class="badge badge-warning">Only a few left</span>
    </div>

    <!-- Wishlist toggle. aria-pressed communicates the on/off state, which the
         old heart conveyed with fill colour alone. -->
    <button
      type="button"
      class="absolute right-3 top-3 z-20 grid size-9 place-items-center rounded-full bg-white/95 shadow-card backdrop-blur transition hover:bg-danger-50 disabled:opacity-60"
      :aria-pressed="isInWishlist"
      :aria-label="isInWishlist ? `Remove ${product.name} from wishlist` : `Save ${product.name} to wishlist`"
      :disabled="wishlistLoading"
      @click="toggleWishlist"
    >
      <i
        class="pi text-sm transition"
        :class="[
          isInWishlist ? 'pi-heart-fill text-danger-600' : 'pi-heart text-ink-500 group-hover:text-danger-500',
          wishlistLoading && 'animate-pulse',
        ]"
        aria-hidden="true"
      />
    </button>

    <!-- Fixed aspect-ratio box: reserves space so nothing shifts on load -->
    <router-link
      :to="`/product/${product.slug}`"
      class="relative block aspect-[4/5] overflow-hidden bg-ink-100"
      :aria-label="`View ${product.name}`"
    >
      <img
        v-if="productImage"
        :src="productImage"
        :srcset="srcset"
        sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 280px"
        :alt="product.name"
        width="400"
        height="500"
        loading="lazy"
        decoding="async"
        class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
        @error="imageError = true"
      />
      <div v-else class="grid size-full place-items-center" role="img" :aria-label="`No image available for ${product.name}`">
        <i class="pi pi-image text-3xl text-ink-400" aria-hidden="true" />
      </div>

      <!-- Banner, not a full-card overlay: the link and heart stay usable -->
      <div
        v-if="isOutOfStock"
        class="absolute inset-x-0 bottom-0 bg-ink-900/85 py-2 text-center text-xs font-semibold uppercase tracking-wide text-white"
      >
        Out of stock
      </div>
    </router-link>

    <div class="flex flex-1 flex-col p-4">
      <h3 class="text-sm font-semibold leading-snug">
        <router-link
          :to="`/product/${product.slug}`"
          class="line-clamp-2 text-ink-800 transition hover:text-brand-700"
        >
          {{ product.name }}
        </router-link>
      </h3>

      <!-- Rating: stars are decorative, the value is real text -->
      <div class="mt-2 flex items-center gap-1.5">
        <div class="flex" aria-hidden="true">
          <i
            v-for="i in 5"
            :key="i"
            class="pi text-[0.7rem]"
            :class="i <= Math.round(rating) ? 'pi-star-fill text-warning-500' : 'pi-star text-ink-300'"
          />
        </div>
        <span v-if="reviewCount" class="text-xs text-ink-500">
          {{ rating.toFixed(1) }}
          <span class="sr-only">out of 5 stars from {{ reviewCount }} reviews</span>
          <span aria-hidden="true">({{ reviewCount }})</span>
        </span>
        <span v-else class="text-xs text-ink-400">No reviews yet</span>
      </div>

      <div class="mt-auto pt-3">
        <div class="flex flex-wrap items-baseline gap-2">
          <span class="tabular text-lg font-bold text-ink-900">{{ product.price.formatted }}</span>
          <template v-if="product.price.compare?.formatted">
            <!-- ink-400 strikethrough failed contrast at 2.5:1; ink-500 = 4.8:1 -->
            <span class="tabular text-xs text-ink-500 line-through">
              {{ product.price.compare.formatted }}
            </span>
            <span class="sr-only">, reduced from {{ product.price.compare.formatted }}</span>
          </template>
        </div>

        <button
          type="button"
          class="btn btn-sm btn-block mt-3"
          :class="isOutOfStock ? 'btn-secondary' : 'btn-primary'"
          :disabled="isOutOfStock || addingToCart"
          :aria-busy="addingToCart || undefined"
          @click="onAddToCart"
        >
          <i
            v-if="addingToCart"
            class="pi pi-spinner animate-spin text-xs"
            aria-hidden="true"
          />
          <i v-else-if="!isOutOfStock" class="pi pi-shopping-cart text-xs" aria-hidden="true" />
          <span>{{ isOutOfStock ? 'Out of stock' : addingToCart ? 'Adding...' : 'Add to cart' }}</span>
        </button>
      </div>
    </div>
  </article>
</template>
