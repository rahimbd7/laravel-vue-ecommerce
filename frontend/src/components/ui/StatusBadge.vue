<script setup lang="ts">
/**
 * StatusBadge
 * ---------------------------------------------------------------------------
 * Replaces `<Tag :value="order.status" :severity="getStatusSeverity(...)" />`,
 * which appeared in 11 views with 11 slightly different colour maps - the same
 * order read "shipped = blue" on the admin dashboard and "shipped = orange" in
 * the vendor list. Seeing two colours for one state destroys trust.
 *
 * Accessibility: the old Tag printed the raw enum (`out_for_delivery`) and
 * conveyed meaning through colour alone, failing WCAG 1.4.1. This renders a
 * human label plus an icon, so the state survives greyscale and colour-blindness.
 */
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
