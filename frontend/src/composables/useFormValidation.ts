import { computed, reactive, ref, nextTick } from 'vue'

export type Validator<T = any> = (value: any, form: T) => string | true

/** ---- Rule builders. Messages are user-facing: plain, specific, no jargon. -- */
export const required = (label = 'This field'): Validator => (v) =>
  (v !== null && v !== undefined && String(v).trim() !== '' && v !== false) || `${label} is required.`

export const email = (): Validator => (v) =>
  !v || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v)) || 'Enter a valid email address, e.g. name@example.com.'

export const minLength = (n: number, label = 'This field'): Validator => (v) =>
  !v || String(v).length >= n || `${label} must be at least ${n} characters.`

export const maxLength = (n: number, label = 'This field'): Validator => (v) =>
  !v || String(v).length <= n || `${label} must be ${n} characters or fewer.`

/** Deliberately permissive: over-strict phone regexes reject valid intl numbers. */
export const phone = (): Validator => (v) =>
  !v || /^[+]?[\d\s()-]{7,20}$/.test(String(v)) || 'Enter a valid phone number.'

export const postalCode = (): Validator => (v) =>
  !v || /^[A-Za-z0-9][A-Za-z0-9\s-]{2,11}$/.test(String(v)) || 'Enter a valid postal / ZIP code.'

export const numeric = (label = 'This field'): Validator => (v) =>
  v === '' || v === null || v === undefined || !Number.isNaN(Number(v)) || `${label} must be a number.`

export const min = (n: number, label = 'This field'): Validator => (v) =>
  v === '' || v === null || v === undefined || Number(v) >= n || `${label} must be ${n} or more.`

export const max = (n: number, label = 'This field'): Validator => (v) =>
  v === '' || v === null || v === undefined || Number(v) <= n || `${label} must be ${n} or less.`

export const matches = (field: string, label = 'Passwords'): Validator => (v, form: any) =>
  !v || v === form?.[field] || `${label} do not match.`

export const accepted = (label = 'This box'): Validator => (v) =>
  v === true || `${label} must be checked to continue.`

/** Strength meter for the register form: 0-4 plus an actionable hint. */
export const passwordStrength = (value: string) => {
  const v = value || ''
  const checks = [v.length >= 8, /[a-z]/.test(v) && /[A-Z]/.test(v), /\d/.test(v), /[^A-Za-z0-9]/.test(v)]
  const score = checks.filter(Boolean).length
  const labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'] as const
  const hints = [
    'Use at least 8 characters.',
    'Add an uppercase letter.',
    'Add a number.',
    'Add a symbol for extra strength.',
    'Great password.',
  ]
  return { score, label: labels[score], hint: hints[score], percent: (score / 4) * 100 }
}

export function useFormValidation<T extends Record<string, any>>(
  form: T,
  rules: Partial<Record<keyof T & string, Validator<T>[]>>,
) {
  /**
   * Keyed by plain string rather than `keyof T`: Vue's `Reactive<>` wrapper
   * cannot be indexed by a generic `keyof T`, and server-side 422 bags can name
   * fields the local form does not declare.
   */
  const errors = reactive<Record<string, string>>({})
  const touched = reactive<Record<string, boolean>>({})
  const submitted = ref(false)

  const ruleKeys = () => Object.keys(rules) as (keyof T & string)[]

  const runField = (field: string): string => {
    for (const rule of rules[field as keyof T & string] ?? []) {
      const result = rule(form[field], form)
      if (result !== true) return result
    }
    return ''
  }

  /** Only surfaces a message once the user has left the field (or submitted). */
  const validateField = (field: string) => {
    const message = runField(field)
    if (message) errors[field] = message
    else delete errors[field]
    return !message
  }

  const handleBlur = (field: string) => {
    touched[field] = true
    validateField(field)
  }

  /** Re-validate as they type, but only after the field has already errored. */
  const handleInput = (field: string) => {
    if (touched[field] || submitted.value) validateField(field)
  }

  const validateAll = (): boolean => {
    submitted.value = true
    let ok = true
    for (const field of ruleKeys()) {
      touched[field] = true
      if (!validateField(field)) ok = false
    }
    return ok
  }

  /**
   * Moves keyboard focus + screen-reader cursor to the first bad field and
   * scrolls it into view. Without this, an error 900px up the checkout page is
   * invisible and the user just sees a button that "does nothing".
   */
  const focusFirstError = async () => {
    await nextTick()
    const first = Object.keys(errors)[0]
    if (!first) return
    const el = document.querySelector<HTMLElement>(
      `[name="${first}"], #field-${first}, [data-field="${first}"]`,
    )
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el?.focus({ preventScroll: true })
  }

  const errorCount = computed(() => Object.keys(errors).length)
  const isValid = computed(() => ruleKeys().every((f) => runField(f) === ''))

  const reset = () => {
    submitted.value = false
    for (const key of Object.keys(errors)) delete errors[key]
    for (const key of Object.keys(touched)) delete touched[key]
  }

  /** Merge a Laravel 422 `errors` bag straight into the inline messages. */
  const applyServerErrors = (bag?: Record<string, string[]>) => {
    if (!bag) return
    for (const [field, messages] of Object.entries(bag)) {
      errors[field] = messages[0] ?? 'Invalid value.'
      touched[field] = true
    }
    focusFirstError()
  }

  return {
    errors, touched, submitted, errorCount, isValid,
    validateField, validateAll, handleBlur, handleInput,
    focusFirstError, reset, applyServerErrors,
  }
}
