export type OrderStatus =
  | 'pending' | 'confirmed' | 'processing' | 'packed' | 'shipped'
  | 'out_for_delivery' | 'delivered' | 'completed'
  | 'cancelled' | 'refunded' | 'returned' | 'failed' | 'on_hold'

export type PaymentStatus = 'pending' | 'paid' | 'partially_paid' | 'failed' | 'refunded'

export interface StatusMeta {
  /** Human label - never show a raw snake_case enum to a customer. */
  label: string
  /** Design-system badge tone. */
  tone: 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info'
  /** PrimeVue <Tag severity> equivalent, for views still using PrimeVue. */
  severity: 'secondary' | 'success' | 'info' | 'warn' | 'danger' | 'contrast'
  icon: string
  /** 0-100 position on the fulfilment timeline; null = off-track (cancelled). */
  progress: number | null
  /** One line of plain English the customer can act on. */
  description: string
}

const STATUS: Record<string, StatusMeta> = {
  pending:          { label: 'Pending',          tone: 'warning', severity: 'warn',      icon: 'pi pi-clock',          progress: 10,  description: 'We have received your order and are waiting for confirmation.' },
  on_hold:          { label: 'On Hold',          tone: 'warning', severity: 'warn',      icon: 'pi pi-pause-circle',   progress: 10,  description: 'This order is paused. Our team will be in touch shortly.' },
  confirmed:        { label: 'Confirmed',        tone: 'info',    severity: 'info',      icon: 'pi pi-check-circle',   progress: 30,  description: 'Your order is confirmed and queued for packing.' },
  processing:       { label: 'Processing',       tone: 'info',    severity: 'info',      icon: 'pi pi-sync',           progress: 45,  description: 'The seller is preparing your items.' },
  packed:           { label: 'Packed',           tone: 'info',    severity: 'info',      icon: 'pi pi-box',            progress: 60,  description: 'Your parcel is packed and awaiting pickup.' },
  shipped:          { label: 'Shipped',          tone: 'brand',   severity: 'info',      icon: 'pi pi-truck',          progress: 75,  description: 'Your parcel is on its way.' },
  out_for_delivery: { label: 'Out for Delivery', tone: 'brand',   severity: 'info',      icon: 'pi pi-map-marker',     progress: 90,  description: 'Arriving today - please keep your phone nearby.' },
  delivered:        { label: 'Delivered',        tone: 'success', severity: 'success',   icon: 'pi pi-check',          progress: 100, description: 'Delivered. Enjoy your purchase!' },
  completed:        { label: 'Completed',        tone: 'success', severity: 'success',   icon: 'pi pi-check-circle',   progress: 100, description: 'This order is complete.' },
  cancelled:        { label: 'Cancelled',        tone: 'danger',  severity: 'danger',    icon: 'pi pi-times-circle',   progress: null, description: 'This order was cancelled.' },
  failed:           { label: 'Failed',           tone: 'danger',  severity: 'danger',    icon: 'pi pi-exclamation-triangle', progress: null, description: 'Something went wrong with this order. Please contact support.' },
  returned:         { label: 'Returned',         tone: 'neutral', severity: 'secondary', icon: 'pi pi-undo',           progress: null, description: 'The items were returned to the seller.' },
  refunded:         { label: 'Refunded',         tone: 'neutral', severity: 'secondary', icon: 'pi pi-replay',         progress: null, description: 'Your refund has been issued.' },
}

const FALLBACK: StatusMeta = {
  label: 'Unknown', tone: 'neutral', severity: 'secondary',
  icon: 'pi pi-question-circle', progress: null,
  description: 'Status unavailable.',
}

const PAYMENT: Record<string, StatusMeta> = {
  pending:         { label: 'Payment Pending', tone: 'warning', severity: 'warn',      icon: 'pi pi-clock',          progress: null, description: 'Awaiting payment.' },
  paid:            { label: 'Paid',            tone: 'success', severity: 'success',   icon: 'pi pi-check-circle',   progress: null, description: 'Payment received in full.' },
  partially_paid:  { label: 'Partially Paid',  tone: 'warning', severity: 'warn',      icon: 'pi pi-percentage',     progress: null, description: 'A partial payment has been received.' },
  failed:          { label: 'Payment Failed',  tone: 'danger',  severity: 'danger',    icon: 'pi pi-times-circle',   progress: null, description: 'The payment did not go through.' },
  refunded:        { label: 'Refunded',        tone: 'neutral', severity: 'secondary', icon: 'pi pi-replay',         progress: null, description: 'Payment refunded.' },
}

/** Turn any status string (or null) into safe display metadata. */
export const orderStatusMeta = (status?: string | null): StatusMeta =>
  STATUS[String(status ?? '').toLowerCase()] ?? {
    ...FALLBACK,
    label: status ? String(status).replace(/_/g, ' ') : FALLBACK.label,
  }

export const paymentStatusMeta = (status?: string | null): StatusMeta =>
  PAYMENT[String(status ?? '').toLowerCase()] ?? {
    ...FALLBACK,
    label: status ? String(status).replace(/_/g, ' ') : FALLBACK.label,
  }

/** Ordered fulfilment milestones for the customer-facing tracking timeline. */
export const FULFILMENT_STEPS: OrderStatus[] = [
  'pending', 'confirmed', 'processing', 'shipped', 'delivered',
]

export const isCancellable = (status?: string | null): boolean =>
  ['pending', 'confirmed', 'on_hold'].includes(String(status ?? '').toLowerCase())

export const isReturnable = (status?: string | null): boolean =>
  ['delivered', 'completed'].includes(String(status ?? '').toLowerCase())

export function useOrderStatus() {
  return {
    orderStatusMeta,
    paymentStatusMeta,
    isCancellable,
    isReturnable,
    FULFILMENT_STEPS,
    /** Back-compat shim so existing `getStatusSeverity(...)` calls keep working. */
    getStatusSeverity: (s?: string | null) => orderStatusMeta(s).severity,
    getStatusLabel: (s?: string | null) => orderStatusMeta(s).label,
  }
}
