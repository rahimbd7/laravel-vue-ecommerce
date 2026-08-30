<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    title: string
    value: string | number
    icon: string
    color?: 'green' | 'blue' | 'purple' | 'orange' | 'red' | 'yellow' | 'brand'
    subtext?: string
    /** Percentage change vs the previous period. */
    trend?: number
    trendLabel?: string
    loading?: boolean
    /** When set the whole card becomes a router-link. */
    to?: string
  }>(),
  { color: 'brand', trendLabel: 'vs last month' },
)

/** Static maps - Tailwind must see the literal class strings to emit them. */
const TONE = {
  brand:  { border: 'border-l-brand-600',  chip: 'bg-brand-50',   icon: 'text-brand-700' },
  green:  { border: 'border-l-success-500', chip: 'bg-success-50', icon: 'text-success-700' },
  blue:   { border: 'border-l-info-500',    chip: 'bg-info-50',    icon: 'text-info-700' },
  purple: { border: 'border-l-purple-500',  chip: 'bg-purple-50',  icon: 'text-purple-700' },
  orange: { border: 'border-l-warning-500', chip: 'bg-warning-50', icon: 'text-warning-700' },
  yellow: { border: 'border-l-warning-500', chip: 'bg-warning-50', icon: 'text-warning-700' },
  red:    { border: 'border-l-danger-500',  chip: 'bg-danger-50',  icon: 'text-danger-700' },
} as const

const tone = computed(() => TONE[props.color] ?? TONE.brand)
const isUp = computed(() => (props.trend ?? 0) >= 0)
</script>

<template>
  <!-- Skeleton mirrors the real card's box model so nothing shifts on load -->
  <div v-if="loading" class="card border-l-4 border-l-ink-200 p-4 sm:p-5" aria-hidden="true">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex-1 space-y-2">
        <div class="skeleton h-3 w-24"></div>
        <div class="skeleton h-7 w-32"></div>
        <div class="skeleton h-3 w-20"></div>
      </div>
      <div class="skeleton size-10 rounded-lg"></div>
    </div>
  </div>

  <component
    v-else
    :is="to ? 'router-link' : 'div'"
    :to="to"
    class="card border-l-4 p-4 sm:p-5"
    :class="[tone.border, to && 'card-interactive block hover:-translate-y-0.5']"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="truncate text-xs font-medium text-ink-500 sm:text-sm">{{ title }}</p>
        <!-- tabular-nums stops the number jittering as digits change on refresh -->
        <p class="tabular mt-0.5 text-xl font-bold text-ink-900 sm:text-2xl">{{ value }}</p>
        <p v-if="subtext" class="mt-1 truncate text-xs text-ink-500">{{ subtext }}</p>
      </div>

      <div class="grid size-10 shrink-0 place-items-center rounded-lg sm:size-11" :class="tone.chip">
        <i :class="[icon, tone.icon]" class="text-lg sm:text-xl" aria-hidden="true" />
      </div>
    </div>

    <div v-if="trend !== undefined && trend !== null" class="mt-3 flex items-center gap-1.5">
      <span
        class="badge"
        :class="isUp ? 'badge-success' : 'badge-danger'"
      >
        <i :class="isUp ? 'pi pi-arrow-up-right' : 'pi pi-arrow-down-right'" class="text-[0.65rem]" aria-hidden="true" />
        <span class="tabular">{{ Math.abs(trend).toFixed(1) }}%</span>
      </span>
      <span class="text-xs text-ink-500">{{ trendLabel }}</span>
      <!-- Direction is conveyed by icon+colour visually; spelled out for AT -->
      <span class="sr-only">
        {{ isUp ? 'Increased' : 'Decreased' }} by {{ Math.abs(trend).toFixed(1) }} percent {{ trendLabel }}
      </span>
    </div>
  </component>
</template>
