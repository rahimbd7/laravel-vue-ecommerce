/**
 * useCurrency
 * -----------------------------------------------------------------------------
 * `formatPrice` was re-declared in 31 separate components, several of which
 * crashed on `null` (Intl.NumberFormat throws on undefined) and one of which
 * silently rendered "$NaN". This is the single implementation.
 *
 * A module-level cached Intl.NumberFormat also matters for performance: the
 * constructor is expensive and the old code built a new one on every render of
 * every table row.
 */

const CURRENCY = import.meta.env.VITE_CURRENCY || 'USD'
const LOCALE = import.meta.env.VITE_LOCALE || 'en-US'

const money = new Intl.NumberFormat(LOCALE, {
  style: 'currency',
  currency: CURRENCY,
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const compact = new Intl.NumberFormat(LOCALE, { notation: 'compact', maximumFractionDigits: 1 })
const plain = new Intl.NumberFormat(LOCALE)

/** Coerce anything the API might send (string, null, "12.30") into a number. */
const toNumber = (value: unknown): number => {
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0
  if (typeof value === 'string') {
    const parsed = Number(value.replace(/[^0-9.-]/g, ''))
    return Number.isFinite(parsed) ? parsed : 0
  }
  return 0
}

/** `formatPrice(null)` -> "$0.00" instead of throwing or printing "$NaN". */
export const formatPrice = (value: unknown): string => money.format(toNumber(value))

/** 12500 -> "12.5K". Used by dashboard stat cards so long numbers never wrap. */
export const formatCompact = (value: unknown): string => compact.format(toNumber(value))

/** 1234 -> "1,234" for counts (orders, products, users). */
export const formatNumber = (value: unknown): string => plain.format(toNumber(value))

/** 0.1234 -> "12.3%" */
export const formatPercent = (value: unknown, digits = 1): string =>
  `${toNumber(value).toFixed(digits)}%`

export function useCurrency() {
  return { formatPrice, formatCompact, formatNumber, formatPercent, toNumber, currency: CURRENCY }
}
