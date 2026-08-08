<script setup lang="ts">
/**
 * App shell
 * ---------------------------------------------------------------------------
 * The old shell was three lines:
 *
 *     <Navbar /> <RouterView /> <Footer />
 *
 * which caused several structural problems:
 *
 * 1. DOUBLE CHROME. The dashboard has its own Menubar + sidebar, so every admin
 *    and vendor page rendered TWO stacked navbars, and the marketing footer
 *    (newsletter box, social links, payment icons) appeared underneath the admin
 *    order tables.
 * 2. STOREFRONT CHROME ON THE LOGIN SCREEN. The login view is a full-height
 *    brand gradient; the shop navbar sat on top of it and the footer below,
 *    which also meant the navbar fired an authenticated cart request on /login.
 * 3. NO LANDMARKS, NO SKIP LINK. Keyboard and screen-reader users had to tab
 *    through the entire navigation on every single page (WCAG 2.4.1).
 * 4. SILENT ROUTE CHANGES. In an SPA, navigation does not reload the document,
 *    so screen readers announce nothing. Users had no confirmation that their
 *    click did anything.
 * 5. NO PENDING FEEDBACK. Now that routes are code-split, a slow connection
 *    shows a blank gap while the chunk downloads. A top progress bar covers it.
 * 6. TOASTS DID NOT WORK OUTSIDE THE DASHBOARD. <Toast> was only mounted in the
 *    dashboard layout, so wishlist/cart feedback on public pages went nowhere.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import Navbar from './components/common/Navbar.vue'
import Footer from './components/common/Footer.vue'
import { pendingConfirm } from './composables/useNotify'

const route = useRoute()

/** 'dashboard' has its own chrome; 'bare' (auth) wants none. */
const showStorefrontChrome = computed(() => {
  const layout = route.meta.layout
  return layout !== 'dashboard' && layout !== 'bare'
})

// --- Route change announcement (WCAG 4.1.3 Status Messages) ---------------
const routeAnnouncement = ref('')
watch(
  () => route.fullPath,
  () => {
    const title = (route.meta.title as string) || 'Page'
    // Cleared first so identical consecutive titles still trigger the live region
    routeAnnouncement.value = ''
    setTimeout(() => (routeAnnouncement.value = `${title} loaded`), 60)
  },
)

// --- Offline awareness ---------------------------------------------------
const isOffline = ref(!navigator.onLine)
const setOnline = () => (isOffline.value = false)
const setOffline = () => (isOffline.value = true)

onMounted(() => {
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOffline)
})
onUnmounted(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOffline)
})

// --- Global confirm dialog ----------------------------------------------
// useNotify().confirm() is awaitable from anywhere - including plain .ts stores,
// which cannot use PrimeVue's useConfirm() composable. This host renders it.
const dialogRef = ref<HTMLElement | null>(null)

const resolveConfirm = (ok: boolean) => {
  pendingConfirm.value?.resolve(ok)
  pendingConfirm.value = null
}

/** Move focus into the dialog and trap Tab inside it (WCAG 2.4.3 / 2.1.2). */
watch(pendingConfirm, async (value) => {
  if (!value) return
  await new Promise((r) => requestAnimationFrame(r))
  dialogRef.value?.querySelector<HTMLElement>('[data-autofocus]')?.focus()
})

const onDialogKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    resolveConfirm(false)
    return
  }
  if (event.key !== 'Tab') return

  const focusables = dialogRef.value?.querySelectorAll<HTMLElement>('button')
  if (!focusables?.length) return
  const first = focusables[0]!
  const last = focusables[focusables.length - 1]!

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}
</script>

<template>
  <!-- WCAG 2.4.1 Bypass Blocks: first tab stop on every page -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Connectivity banner. Previously a dropped connection just made every
       action fail silently with a spinner that never stopped. -->
  <div
    v-if="isOffline"
    class="sticky top-0 z-[60] bg-warning-500 px-4 py-2 text-center text-sm font-medium text-ink-900"
    role="status"
  >
    <i class="pi pi-wifi mr-1.5" aria-hidden="true" />
    You are offline. Some features will not work until your connection returns.
  </div>

  <Navbar v-if="showStorefrontChrome" />

  <!-- The single main landmark, and the skip-link target. tabindex="-1" lets
       the anchor move real keyboard focus here, not just the scroll position. -->
  <main id="main-content" tabindex="-1" class="min-h-[60vh] outline-none">
    <RouterView v-slot="{ Component }">
      <Transition name="fade-slide" mode="out-in">
        <component :is="Component" />
      </Transition>
    </RouterView>
  </main>

  <Footer v-if="showStorefrontChrome" />

  <!-- Mounted once, app-wide: public pages could not show toasts before -->
  <Toast position="top-right" />

  <!-- Global confirmation dialog -->
  <Transition name="fade-slide">
    <div
      v-if="pendingConfirm"
      class="fixed inset-0 z-[100] grid place-items-center bg-ink-900/50 p-4 backdrop-blur-sm"
      @click.self="resolveConfirm(false)"
    >
      <div
        ref="dialogRef"
        class="w-full max-w-md rounded-card bg-white p-6 shadow-popover"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
        aria-describedby="confirm-message"
        @keydown="onDialogKeydown"
      >
        <div class="flex gap-4">
          <div
            class="grid size-11 shrink-0 place-items-center rounded-full"
            :class="pendingConfirm.tone === 'danger' ? 'bg-danger-50' : 'bg-brand-50'"
            aria-hidden="true"
          >
            <i
              class="pi text-lg"
              :class="pendingConfirm.tone === 'danger'
                ? 'pi-exclamation-triangle text-danger-600'
                : 'pi-question-circle text-brand-600'"
            />
          </div>
          <div class="min-w-0">
            <h2 id="confirm-title" class="text-base font-semibold text-ink-900">
              {{ pendingConfirm.title || 'Please confirm' }}
            </h2>
            <p id="confirm-message" class="mt-1.5 text-sm text-ink-600">
              {{ pendingConfirm.message }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <!--
            Cancel is autofocused for destructive actions so an accidental
            Enter/Space keypress cannot delete anything (WCAG 3.3.4 Error
            Prevention). The old PrimeVue dialogs focused "Yes" by default.
          -->
          <button
            type="button"
            class="btn btn-secondary"
            :data-autofocus="pendingConfirm.tone === 'danger' ? '' : undefined"
            @click="resolveConfirm(false)"
          >
            {{ pendingConfirm.cancelLabel || 'Cancel' }}
          </button>
          <button
            type="button"
            class="btn"
            :class="pendingConfirm.tone === 'danger' ? 'btn-danger' : 'btn-primary'"
            :data-autofocus="pendingConfirm.tone !== 'danger' ? '' : undefined"
            @click="resolveConfirm(true)"
          >
            {{ pendingConfirm.confirmLabel || 'Confirm' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Announces SPA navigation, which is otherwise completely silent to AT -->
  <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
    {{ routeAnnouncement }}
  </div>
</template>
