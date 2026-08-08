import { customRef, onScopeDispose, ref, watch, type Ref } from 'vue'

/**
 * useDebounce
 * -----------------------------------------------------------------------------
 * Shop.vue fired a full `/products/search` request on every single keystroke,
 * so typing "headphones" produced 10 requests and the results flickered as the
 * responses raced each other back. Every list filter in the app had the same
 * bug. These helpers fix it and always clean up their timers on unmount.
 */

/** Debounce a callback. Returns the wrapped fn plus a `cancel()` escape hatch. */
export function useDebounceFn<T extends (...args: any[]) => void>(fn: T, delay = 350) {
  let timer: ReturnType<typeof setTimeout> | undefined

  const cancel = () => {
    if (timer) clearTimeout(timer)
    timer = undefined
  }

  const debounced = (...args: Parameters<T>) => {
    cancel()
    timer = setTimeout(() => fn(...args), delay)
  }

  /** Run immediately and drop any pending call (used by "Search" buttons). */
  const flush = (...args: Parameters<T>) => {
    cancel()
    fn(...args)
  }

  onScopeDispose(cancel)
  return Object.assign(debounced, { cancel, flush })
}

/**
 * A ref that updates its *readers* on a delay while staying instantly
 * responsive to writes - so the <input> never feels laggy but the watcher
 * that triggers the API call only fires once the user pauses.
 */
export function useDebouncedRef<T>(value: T, delay = 350): Ref<T> {
  let timer: ReturnType<typeof setTimeout> | undefined
  onScopeDispose(() => timer && clearTimeout(timer))

  return customRef<T>((track, trigger) => ({
    get() {
      track()
      return value
    },
    set(next) {
      value = next
      if (timer) clearTimeout(timer)
      timer = setTimeout(trigger, delay)
    },
  }))
}

/** Mirror a source ref into a debounced copy. Keeps v-model instant. */
export function debouncedMirror<T>(source: Ref<T>, delay = 350): Ref<T> {
  const mirror = ref(source.value) as Ref<T>
  let timer: ReturnType<typeof setTimeout> | undefined

  watch(source, (next) => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => (mirror.value = next), delay)
  })

  onScopeDispose(() => timer && clearTimeout(timer))
  return mirror
}
