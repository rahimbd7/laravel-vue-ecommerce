<script setup lang="ts">
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
