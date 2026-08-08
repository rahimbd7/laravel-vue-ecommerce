<script setup lang="ts">
/**
 * EmptyState
 * ---------------------------------------------------------------------------
 * The app had ~20 hand-written empty states of wildly different quality. Most
 * were a single grey line in a table cell ("No recent orders", "No products
 * sold yet") which reads as though the page failed. Several tables had none at
 * all, so an empty result set rendered as a bare header row.
 *
 * A good empty state does three things, which this component enforces by having
 * slots/props for each:
 *   1. confirms nothing is broken,
 *   2. explains why it is empty,
 *   3. offers the single next action.
 *
 * `variant="error"` reuses the same shape for failure states so users get a
 * consistent structure whether a list is empty or a request failed.
 */
withDefaults(
  defineProps<{
    icon?: string
    title: string
    description?: string
    variant?: 'empty' | 'error' | 'search'
    /** Vertical padding: `sm` for inside table/card bodies. */
    size?: 'sm' | 'md'
  }>(),
  { icon: 'pi pi-inbox', variant: 'empty', size: 'md' },
)

const TONE = {
  empty: { chip: 'bg-ink-100', icon: 'text-ink-500' },
  search: { chip: 'bg-brand-50', icon: 'text-brand-600' },
  error: { chip: 'bg-danger-50', icon: 'text-danger-600' },
} as const
</script>

<template>
  <div class="text-center" :class="size === 'sm' ? 'px-4 py-8' : 'px-6 py-14'">
    <div
      class="mx-auto mb-4 grid place-items-center rounded-full"
      :class="[TONE[variant].chip, size === 'sm' ? 'size-10' : 'size-14']"
    >
      <i
        :class="[icon, TONE[variant].icon, size === 'sm' ? 'text-lg' : 'text-2xl']"
        aria-hidden="true"
      />
    </div>

    <!-- role=status: when a filter empties a list, AT users are told why -->
    <p
      class="font-semibold text-ink-900"
      :class="size === 'sm' ? 'text-sm' : 'text-lg'"
      role="status"
    >
      {{ title }}
    </p>
    <p
      v-if="description"
      class="mx-auto mt-1.5 max-w-sm text-ink-600"
      :class="size === 'sm' ? 'text-xs' : 'text-sm'"
    >
      {{ description }}
    </p>

    <div v-if="$slots.action" class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
      <slot name="action" />
    </div>
  </div>
</template>
