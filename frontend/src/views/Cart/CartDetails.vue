<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useCartStore } from '@/stores/cart.store'
import { useNotify } from '@/composables/useNotify'
import { formatPrice } from '@/composables/useCurrency'
import { useDebounceFn } from '@/composables/useDebounce'
import EmptyState from '@/components/ui/EmptyState.vue'

const cartStore = useCartStore()
const notify = useNotify()

const initialLoading = ref(true)
const busyId = ref<number | null>(null)

/** Local overrides so quantity updates are instant */
const optimisticQty = reactive<Record<number, number>>({})

const FREE_SHIPPING_THRESHOLD = 100

const items = computed(() => cartStore.activeItems)
const savedItems = computed(() => cartStore.savedForLaterItems)

const displayQty = (item: any) => optimisticQty[item.id] ?? item.quantity

/** Line total computed locally to track optimistic quantity */
const lineTotal = (item: any) => Number(item.unit_price) * displayQty(item)

const subtotal = computed(() =>
  items.value.reduce((sum, item: any) => sum + lineTotal(item), 0),
)

const amountToFreeShipping = computed(() =>
  Math.max(0, FREE_SHIPPING_THRESHOLD - subtotal.value),
)
const freeShippingProgress = computed(() =>
  Math.min(100, (subtotal.value / FREE_SHIPPING_THRESHOLD) * 100),
)

const outOfStockItems = computed(() =>
  items.value.filter((item: any) => item.product?.inventory?.status === 'out_of_stock'),
)

const getProductImage = (item: any): string => {
  const p = item?.product
  if (!p) return ''
  const media = p.media
  const first = Array.isArray(p.images) ? p.images[0] : null
  return (
    media?.thumbnail || media?.image || p.image_url ||
    first?.thumbnail_url || first?.thumbnail || first?.image_url ||
    first?.secure_url || first?.url || first?.urls?.thumbnail || first?.urls?.original || ''
  )
}

/** Debounced writer per cart line for smooth updates */
const writers = new Map<number, ReturnType<typeof useDebounceFn>>()

const commitQuantity = (itemId: number) => {
  if (!writers.has(itemId)) {
    writers.set(
      itemId,
      useDebounceFn(async () => {
        const quantity = optimisticQty[itemId]
        if (quantity === undefined) return
        busyId.value = itemId
        const result = await cartStore.updateItem(itemId, quantity)
        if (!result.success) {
          delete optimisticQty[itemId] // roll back
          notify.error('Could not update quantity', result.error || 'Please try again.')
        } else {
          delete optimisticQty[itemId]
        }
        busyId.value = null
      }, 550),
    )
  }
  writers.get(itemId)!()
}

const setQuantity = (item: any, next: number) => {
  const max = item.product?.inventory?.total_stock
  if (next < 1) return
  if (typeof max === 'number' && max > 0 && next > max) {
    notify.warn('Not enough stock', `Only ${max} left of ${item.product_name}.`)
    return
  }
  optimisticQty[item.id] = next
  commitQuantity(item.id)
}

const removeItem = async (item: any) => {
  const result = await cartStore.removeItem(item.id)
  if (result.success) {
    notify.info('Removed from cart', `${item.product_name} - can be restored from product page.`)
  } else {
    notify.error('Could not remove item', result.error || 'Please try again.')
  }
}

const saveForLater = async (item: any) => {
  busyId.value = item.id
  try {
    const api = (await import('@/api/api')).default
    await api.put(`/cart/items/${item.id}`, { is_saved_for_later: true })
    await cartStore.fetchCart()
    notify.success('Saved for later', item.product_name)
  } catch (error) {
    notify.apiError(error, 'We could not move that item.')
  } finally {
    busyId.value = null
  }
}

const moveToCart = async (item: any) => {
  busyId.value = item.id
  try {
    const api = (await import('@/api/api')).default
    await api.put(`/cart/items/${item.id}`, { is_saved_for_later: false })
    await cartStore.fetchCart()
    notify.success('Moved to cart', item.product_name)
  } catch (error) {
    notify.apiError(error, 'We could not move that item.')
  } finally {
    busyId.value = null
  }
}

const clearCart = async () => {
  const ok = await notify.confirm({
    title: 'Empty your cart?',
    message: `This removes all ${items.value.length} item(s). You cannot undo this.`,
    confirmLabel: 'Yes, empty cart',
    tone: 'danger',
  })
  if (!ok) return

  const result = await cartStore.clear()
  if (result.success) notify.success('Cart emptied')
  else notify.error('Could not empty cart', result.error || 'Please try again.')
}

onMounted(async () => {
  try {
    await cartStore.fetchCart()
  } finally {
    initialLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-ink-50">
    <!-- Header -->
    <div class="border-b border-ink-200 bg-white">
      <div class="page-container py-4">
        <nav aria-label="Breadcrumb" class="text-sm">
          <ol class="flex items-center gap-2 text-ink-500">
            <li><router-link to="/" class="link">Home</router-link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-ink-700">Cart</li>
          </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-ink-900 sm:text-3xl">
          Shopping cart
          <span v-if="!initialLoading && items.length" class="text-base font-normal text-ink-500">
            ({{ items.length }} {{ items.length === 1 ? 'item' : 'items' }})
          </span>
        </h1>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="initialLoading" class="page-container py-8">
      <div class="grid gap-6 lg:grid-cols-3 lg:gap-8">
        <div class="space-y-3 lg:col-span-2">
          <div v-for="n in 3" :key="n" class="card flex gap-4 p-4">
            <div class="skeleton size-20 shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="skeleton h-4 w-3/4"></div>
              <div class="skeleton h-3 w-1/3"></div>
              <div class="skeleton h-9 w-32"></div>
            </div>
          </div>
        </div>
        <div class="card space-y-3 p-5">
          <div class="skeleton h-5 w-32"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-12 w-full"></div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="cartStore.isEmpty && !savedItems.length" class="page-container py-16">
      <div class="card mx-auto max-w-lg">
        <EmptyState
          icon="pi pi-shopping-cart"
          title="Your cart is empty"
          description="Browse the shop and add something you like - it will appear here."
        >
          <template #action>
            <router-link to="/shop" class="btn btn-primary">Start shopping</router-link>
          </template>
        </EmptyState>
      </div>
    </div>

    <!-- Cart -->
    <div v-else class="page-container py-6 lg:py-8">
      <div class="grid gap-6 lg:grid-cols-3 lg:gap-8">
        <div class="space-y-4 lg:col-span-2">
          <!-- Stock warning -->
          <div
            v-if="outOfStockItems.length"
            class="flex items-start gap-3 rounded-card border border-danger-100 bg-danger-50 p-4"
            role="alert"
          >
            <i class="pi pi-exclamation-triangle mt-0.5 text-danger-700" aria-hidden="true" />
            <p class="text-sm text-danger-700">
              {{ outOfStockItems.length }} item(s) in your cart are out of stock.
              Remove them to continue to checkout.
            </p>
          </div>

          <!-- ONE responsive list - replaces duplicated table + cards -->
          <ul class="card divide-y divide-ink-100">
            <li
              v-for="item in items"
              :key="item.id"
              class="flex gap-3 p-4 transition-opacity sm:gap-4"
              :class="busyId === item.id && 'opacity-60'"
            >
              <router-link
                :to="item.product?.slug ? `/product/${item.product.slug}` : '/cart'"
                class="shrink-0"
                tabindex="-1"
                aria-hidden="true"
              >
                <img
                  v-if="getProductImage(item)"
                  :src="getProductImage(item)"
                  :alt="item.product_name"
                  width="80" height="80" loading="lazy" decoding="async"
                  class="size-20 rounded-control object-cover sm:size-24"
                />
                <div v-else class="grid size-20 place-items-center rounded-control bg-ink-100 sm:size-24">
                  <i class="pi pi-image text-xl text-ink-400" aria-hidden="true" />
                </div>
              </router-link>

              <div class="flex min-w-0 flex-1 flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <router-link
                      v-if="item.product?.slug"
                      :to="`/product/${item.product.slug}`"
                      class="line-clamp-2 text-sm font-semibold text-ink-900 transition hover:text-brand-700"
                    >{{ item.product_name }}</router-link>
                    <p v-else class="line-clamp-2 text-sm font-semibold text-ink-900">
                      {{ item.product_name }}
                    </p>

                    <p v-if="item.product_variation_name" class="mt-0.5 text-xs text-ink-500">
                      {{ item.product_variation_name }}
                    </p>
                    <p class="mt-0.5 text-xs text-ink-500">SKU: {{ item.product_sku }}</p>
                    <p
                      v-if="item.product?.inventory?.status === 'out_of_stock'"
                      class="badge badge-danger mt-1.5"
                    >Out of stock</p>
                  </div>

                  <p class="tabular shrink-0 text-sm font-bold text-ink-900">
                    {{ formatPrice(lineTotal(item)) }}
                  </p>
                </div>

                <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2">
                  <!-- Stepper -->
                  <div class="inline-flex items-center rounded-control border border-ink-300">
                    <button
                      type="button"
                      class="grid size-9 place-items-center rounded-l-control text-ink-600 transition hover:bg-ink-100 disabled:opacity-40"
                      :disabled="displayQty(item) <= 1"
                      :aria-label="`Decrease quantity of ${item.product_name}`"
                      @click="setQuantity(item, displayQty(item) - 1)"
                    >
                      <i class="pi pi-minus text-xs" aria-hidden="true" />
                    </button>
                    <span
                      class="tabular w-10 border-x border-ink-300 py-1.5 text-center text-sm font-medium"
                      aria-live="polite"
                      :aria-label="`Quantity: ${displayQty(item)}`"
                    >{{ displayQty(item) }}</span>
                    <button
                      type="button"
                      class="grid size-9 place-items-center rounded-r-control text-ink-600 transition hover:bg-ink-100 disabled:opacity-40"
                      :aria-label="`Increase quantity of ${item.product_name}`"
                      @click="setQuantity(item, displayQty(item) + 1)"
                    >
                      <i class="pi pi-plus text-xs" aria-hidden="true" />
                    </button>
                  </div>

                  <span class="tabular text-xs text-ink-500">
                    {{ formatPrice(item.unit_price) }} each
                  </span>

                  <div class="ml-auto flex items-center gap-1">
                    <!-- Save for later -->
                    <button
                      type="button"
                      class="btn btn-ghost btn-xs"
                      :disabled="busyId === item.id"
                      @click="saveForLater(item)"
                    >
                      <i class="pi pi-bookmark text-[0.65rem]" aria-hidden="true" />
                      <span class="hidden sm:inline">Save for later</span>
                      <span class="sr-only sm:hidden">Save {{ item.product_name }} for later</span>
                    </button>
                    <button
                      type="button"
                      class="btn btn-ghost btn-xs text-danger-700"
                      :disabled="busyId === item.id"
                      @click="removeItem(item)"
                    >
                      <i class="pi pi-trash text-[0.65rem]" aria-hidden="true" />
                      <span class="hidden sm:inline">Remove</span>
                      <span class="sr-only sm:hidden">Remove {{ item.product_name }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <router-link to="/shop" class="btn btn-ghost btn-sm">
              <i class="pi pi-arrow-left text-xs" aria-hidden="true" />
              Continue shopping
            </router-link>
            <button v-if="items.length" type="button" class="btn btn-danger-soft btn-sm" @click="clearCart">
              <i class="pi pi-trash text-xs" aria-hidden="true" />
              Empty cart
            </button>
          </div>

          <!-- Saved for later -->
          <section v-if="savedItems.length" aria-labelledby="saved-heading">
            <h2 id="saved-heading" class="mb-3 mt-8 text-base font-semibold text-ink-900">
              Saved for later ({{ savedItems.length }})
            </h2>
            <ul class="card divide-y divide-ink-100">
              <li v-for="item in savedItems" :key="item.id" class="flex items-center gap-3 p-4">
                <img
                  v-if="getProductImage(item)"
                  :src="getProductImage(item)" :alt="item.product_name"
                  width="56" height="56" loading="lazy"
                  class="size-14 shrink-0 rounded-control object-cover"
                />
                <div v-else class="grid size-14 shrink-0 place-items-center rounded-control bg-ink-100">
                  <i class="pi pi-image text-ink-400" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="line-clamp-1 text-sm font-medium text-ink-900">{{ item.product_name }}</p>
                  <p class="tabular text-xs text-ink-500">{{ formatPrice(item.unit_price) }}</p>
                </div>
                <button type="button" class="btn btn-outline btn-xs" @click="moveToCart(item)">
                  Move to cart
                </button>
              </li>
            </ul>
          </section>
        </div>

        <!-- Summary -->
        <aside class="lg:col-span-1" aria-label="Order summary">
          <div class="card p-5 lg:sticky lg:top-20">
            <h2 class="mb-4 text-base font-semibold text-ink-900">Order summary</h2>

            <!-- Free shipping nudge -->
            <div v-if="items.length" class="mb-4 rounded-control bg-brand-50 p-3">
              <p v-if="amountToFreeShipping > 0" class="text-xs font-medium text-brand-800">
                Add {{ formatPrice(amountToFreeShipping) }} more for free standard shipping.
              </p>
              <p v-else class="flex items-center gap-1.5 text-xs font-semibold text-success-700">
                <i class="pi pi-check-circle" aria-hidden="true" />
                You have earned free standard shipping!
              </p>
              <div
                class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-100"
                role="progressbar"
                :aria-valuenow="Math.round(freeShippingProgress)"
                aria-valuemin="0" aria-valuemax="100"
                aria-label="Progress towards free shipping"
              >
                <div class="h-full bg-brand-600 transition-all duration-500" :style="{ width: `${freeShippingProgress}%` }" />
              </div>
            </div>

            <dl class="space-y-2 border-t border-ink-200 pt-4 text-sm" aria-live="polite">
              <div class="flex justify-between text-ink-600">
                <dt>Subtotal</dt>
                <dd class="tabular">{{ formatPrice(subtotal) }}</dd>
              </div>
              <div v-if="cartStore.discountTotal > 0" class="flex justify-between font-medium text-success-700">
                <dt>Discount</dt>
                <dd class="tabular">-{{ formatPrice(cartStore.discountTotal) }}</dd>
              </div>
              <div class="flex justify-between text-ink-500">
                <dt>Shipping &amp; tax</dt>
                <dd class="text-xs">Calculated at checkout</dd>
              </div>
            </dl>

            <div class="mt-4 flex items-baseline justify-between border-t border-ink-200 pt-4">
              <span class="text-base font-bold text-ink-900">Estimated total</span>
              <span class="tabular text-xl font-bold text-brand-700">
                {{ formatPrice(Math.max(0, subtotal - cartStore.discountTotal)) }}
              </span>
            </div>

            <button
  type="button"
  class="btn btn-primary btn-lg btn-block mt-5"
  :class="{ 'pointer-events-none opacity-55': outOfStockItems.length }"
  :disabled="!!outOfStockItems.length" 
  :aria-disabled="outOfStockItems.length ? 'true' : undefined"
  @click="!outOfStockItems.length && $router.push('/checkout')"
>
  <i class="pi pi-lock" aria-hidden="true" />
  Proceed to checkout
</button>


            <p v-if="outOfStockItems.length" class="mt-2 text-center text-xs text-danger-700">
              Remove out-of-stock items to continue.
            </p>

            <ul class="mt-5 space-y-2 border-t border-ink-100 pt-4 text-xs text-ink-500">
              <li class="flex items-center gap-2">
                <i class="pi pi-lock text-success-600" aria-hidden="true" />
                Encrypted, secure checkout
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-undo text-success-600" aria-hidden="true" />
                30-day returns on eligible items
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>