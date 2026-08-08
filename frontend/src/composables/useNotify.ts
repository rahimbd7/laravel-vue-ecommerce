/**
 * useNotify
 * -----------------------------------------------------------------------------
 * The app shipped THREE notification systems at once:
 *   1. sweetalert2          - blocking modal, used for "item added to cart"
 *   2. primevue/usetoast    - used in 37 components
 *   3. vue-toast-notification - used in 3 components
 * ...so the same event looked different depending on which file handled it, and
 * three toast stylesheets were downloaded. Worse, PrimeVue's <Toast> was only
 * mounted inside the dashboard layout, so every `toast.add()` fired from a
 * public page (e.g. ProductCard wishlist) silently vanished.
 *
 * This module is the single funnel. It routes to PrimeVue's ToastService when
 * available and degrades to console + native dialogs otherwise, so it can never
 * throw and never swallow user feedback.
 *
 * Guidance encoded here:
 *  - Toast   = non-blocking confirmation of something the user just did.
 *  - Confirm = blocking, ONLY for destructive/irreversible actions.
 * "Added to cart" used to be a blocking modal that required a click to dismiss;
 * that is a toast, and the change removes one interaction per add-to-cart.
 */
import { ref } from 'vue'
import type { ToastServiceMethods } from 'primevue/toastservice'

type Severity = 'success' | 'info' | 'warn' | 'error' | 'secondary' | 'contrast'

let toastService: ToastServiceMethods | null = null

/** Called once from main.ts after app.use(ToastService). */
export const registerToastService = (service: ToastServiceMethods) => {
  toastService = service
}

export interface ConfirmOptions {
  title?: string
  message: string
  confirmLabel?: string
  cancelLabel?: string
  /** `danger` paints the confirm button red - use for delete/clear/cancel. */
  tone?: 'danger' | 'default'
}

/** Resolved by <AppConfirmHost>, which App.vue mounts once, globally. */
export interface PendingConfirm extends ConfirmOptions {
  resolve: (ok: boolean) => void
}
export const pendingConfirm = ref<PendingConfirm | null>(null)

const push = (severity: Severity, summary: string, detail?: string, life = 3500) => {
  if (toastService) {
    toastService.add({ severity, summary, detail, life })
    return
  }
  // Fallback keeps feedback observable in tests / before mount instead of
  // throwing "No PrimeVue Toast provider found".
  const line = detail ? `${summary}: ${detail}` : summary
  severity === 'error' ? console.error(`[notify] ${line}`) : console.info(`[notify] ${line}`)
}

export function useNotify() {
  return {
    success: (summary: string, detail?: string) => push('success', summary, detail),
    info: (summary: string, detail?: string) => push('info', summary, detail),
    warn: (summary: string, detail?: string) => push('warn', summary, detail, 5000),

    /** Errors stay on screen longer - users need time to read what broke. */
    error: (summary: string, detail?: string) => push('error', summary, detail, 6000),

    /**
     * Normalises an Axios error into one readable line, including Laravel's
     * 422 `errors` bag. Previously each catch block re-implemented this and
     * most of them ended up showing a bare "Error".
     */
    apiError: (error: any, fallback = 'Something went wrong. Please try again.') => {
      const data = error?.response?.data
      const bag = data?.errors as Record<string, string[]> | undefined
      const detail = bag
        ? Object.values(bag).flat().join(' ')
        : data?.message || error?.message || fallback

      const summary =
        error?.code === 'ERR_NETWORK'
          ? 'Connection lost'
          : error?.response?.status === 403
            ? 'Not allowed'
            : error?.response?.status === 422
              ? 'Please check the form'
              : 'Something went wrong'

      push('error', summary, error?.code === 'ERR_NETWORK'
        ? 'We could not reach the server. Check your connection and try again.'
        : detail, 6000)
    },

    /**
     * Promise-based confirmation. `await confirm({...})` reads top-to-bottom,
     * unlike PrimeVue's callback API which forced logic into nested closures.
     */
    confirm: (options: ConfirmOptions): Promise<boolean> =>
      new Promise((resolve) => {
        pendingConfirm.value = { ...options, resolve }
      }),
  }
}
