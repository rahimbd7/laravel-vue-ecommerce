<script setup lang="ts">
/**
 * AppInput
 * ---------------------------------------------------------------------------
 * Every field in the app was a bare label + input with NO for/id pairing, no
 * error slot, and no way for assistive tech to know it was required or invalid.
 *
 * Fixes, WCAG-mapped:
 *  - 1.3.1 / 3.3.2  a real <label for> gives the field a programmatic name.
 *  - 3.3.1  aria-invalid + aria-describedby wire the message to the field, so it
 *    is announced on focus instead of being red text only sighted users notice.
 *  - 1.4.1  the error carries an icon and text, not colour alone.
 *  - 2.5.3  the asterisk is decorative; "(required)" is exposed to AT instead.
 */
import { computed, ref, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    label: string
    /** Stable name; also used by useFormValidation().focusFirstError(). */
    name?: string
    type?: string
    placeholder?: string
    hint?: string
    error?: string
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    /** Critical on checkout: without it browsers offer no address autofill. */
    autocomplete?: string
    inputmode?: 'text' | 'numeric' | 'decimal' | 'tel' | 'email' | 'url' | 'search'
    maxlength?: number
    min?: number | string
    max?: number | string
    step?: number | string
    icon?: string
    hideLabel?: boolean
    /** Adds a show/hide toggle for password fields. */
    revealable?: boolean
  }>(),
  { type: 'text', required: false },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
  (e: 'blur', event: FocusEvent): void
  (e: 'enter'): void
}>()

const uid = useId()
const fieldId = computed(() => `field-${props.name || uid}`)
const errorId = computed(() => `${fieldId.value}-error`)
const hintId = computed(() => `${fieldId.value}-hint`)

const revealed = ref(false)
const resolvedType = computed(() => (props.revealable && revealed.value ? 'text' : props.type))

/** Only reference IDs that exist, or AT announces "undefined". */
const describedBy = computed(
  () =>
    [props.error ? errorId.value : null, props.hint ? hintId.value : null]
      .filter(Boolean)
      .join(' ') || undefined,
)

const onInput = (event: Event) => {
  const el = event.target as HTMLInputElement
  emit('update:modelValue', props.type === 'number' && el.value !== '' ? Number(el.value) : el.value)
}
</script>

<template>
  <div class="w-full" :data-field="name">
    <label :for="fieldId" class="field-label" :class="{ 'sr-only': hideLabel }">
      {{ label }}
      <span v-if="required" aria-hidden="true" class="text-danger-600">*</span>
      <span v-if="required" class="sr-only">(required)</span>
    </label>

    <div class="relative">
      <i
        v-if="icon"
        :class="icon"
        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"
        aria-hidden="true"
      />
      <input
        :id="fieldId"
        :name="name"
        :type="resolvedType"
        :value="modelValue ?? ''"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        class="field-control"
        :class="[icon && 'pl-10', revealable && 'pr-12', error && 'animate-shake']"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        @input="onInput"
        @blur="emit('blur', $event)"
        @keyup.enter="emit('enter')"
      />
      <button
        v-if="revealable"
        type="button"
        class="absolute right-1 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-md text-ink-500 transition hover:bg-ink-100 hover:text-ink-700"
        :aria-label="revealed ? 'Hide password' : 'Show password'"
        :aria-pressed="revealed"
        @click="revealed = !revealed"
      >
        <i :class="revealed ? 'pi pi-eye-slash' : 'pi pi-eye'" aria-hidden="true" />
      </button>
    </div>

    <!-- role=alert so the message is announced the moment it appears -->
    <p v-if="error" :id="errorId" class="field-error" role="alert">
      <i class="pi pi-exclamation-circle mt-px shrink-0 text-[0.9em]" aria-hidden="true" />
      <span>{{ error }}</span>
    </p>
    <p v-else-if="hint" :id="hintId" class="field-hint">{{ hint }}</p>
  </div>
</template>
