<script setup lang="ts">
import { computed } from 'vue'
import { orderStatusMeta, paymentStatusMeta } from '@/composables/useOrderStatus'

const props = withDefaults(
  defineProps<{
    status?: string | null
    /** `payment` switches to the payment-status vocabulary. */
    kind?: 'order' | 'payment'
    size?: 'sm' | 'md'
    /** Hide the icon in dense table cells. */
    iconless?: boolean
  }>(),
  { kind: 'order', size: 'md' },
)

const meta = computed(() =>
  props.kind === 'payment' ? paymentStatusMeta(props.status) : orderStatusMeta(props.status),
)
</script>

<template>
  <span
    class="badge"
    :class="[`badge-${meta.tone}`, size === 'md' && 'px-2.5 py-1 text-xs']"
    :title="meta.description"
  >
    <i v-if="!iconless" :class="meta.icon" class="text-[0.7em]" aria-hidden="true" />
    {{ meta.label }}
  </span>
</template>
