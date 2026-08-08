<script setup lang="ts">
/**
 * AppButton
 * ---------------------------------------------------------------------------
 * Replaces ~180 hand-rolled `bg-[#00685F] text-white px-4 py-2 rounded-lg
 * hover:bg-[#004F45]` buttons. Beyond consistency it fixes three defects that
 * were repeated in nearly every view:
 *
 *  1. NO LOADING STATE - users could double-submit orders because the button
 *     stayed clickable while the request was in flight. `loading` disables it
 *     and announces progress with aria-busy + a live region.
 *  2. NO ACCESSIBLE NAME on icon-only buttons - the dashboards are full of
 *     `<Button icon="pi pi-check" />`, which a screen reader announces as just
 *     "button". `label` is always rendered, visually hidden when icon-only.
 *  3. TOUCH TARGETS under 44px (WCAG 2.5.5) - the .btn base now enforces it.
 *
 * Renders as <button>, <a> or <router-link> so navigation is never faked with
 * a button element (WCAG 4.1.2 Name, Role, Value).
 */
import { computed } from 'vue'

type Variant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'danger-soft' | 'success'

const props = withDefaults(
  defineProps<{
    variant?: Variant
    size?: 'xs' | 'sm' | 'md' | 'lg'
    label?: string
    icon?: string
    iconRight?: string
    /** Hides the label visually but keeps it for assistive tech. */
    iconOnly?: boolean
    loading?: boolean
    loadingLabel?: string
    disabled?: boolean
    block?: boolean
    type?: 'button' | 'submit' | 'reset'
    /** Renders a <router-link>. */
    to?: string | Record<string, any>
    /** Renders an <a>. */
    href?: string
  }>(),
  { variant: 'primary', size: 'md', type: 'button', loadingLabel: 'Working...' },
)

const tag = computed(() => (props.to ? 'router-link' : props.href ? 'a' : 'button'))
const isInert = computed(() => props.disabled || props.loading)

const classes = computed(() => [
  'btn',
  `btn-${props.variant}`,
  props.size !== 'md' && `btn-${props.size}`,
  props.block && 'btn-block',
  props.iconOnly && 'btn-icon',
])

const bindings = computed(() => {
  if (tag.value === 'button') return { type: props.type, disabled: isInert.value }
  // Links cannot be natively disabled; aria-disabled + tabindex="-1" removes
  // them from the tab order without changing the DOM structure.
  return {
    ...(props.to ? { to: props.to } : { href: props.href }),
    ...(isInert.value ? { 'aria-disabled': 'true', tabindex: -1 } : {}),
  }
})
</script>

<template>
  <component :is="tag" v-bind="bindings" :class="classes" :aria-busy="loading || undefined">
    <!-- Spinner replaces the leading icon so the button width barely shifts -->
    <svg v-if="loading" class="size-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <i v-else-if="icon" :class="icon" class="shrink-0 text-[0.9em]" aria-hidden="true" />

    <slot>
      <span :class="iconOnly ? 'sr-only' : undefined">
        {{ loading && !iconOnly ? loadingLabel : label }}
      </span>
    </slot>

    <i v-if="iconRight && !loading" :class="iconRight" class="shrink-0 text-[0.9em]" aria-hidden="true" />

    <!-- Announces the state change to screen readers without a visual change -->
    <span v-if="loading" class="sr-only" role="status">{{ loadingLabel }}</span>
  </component>
</template>
