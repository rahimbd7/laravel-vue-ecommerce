<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Payment History</h1>
          <p class="text-sm sm:text-base text-gray-600">View all of your payments and transactions</p>
        </div>
        <Button
          icon="pi pi-refresh"
          label="Refresh"
          size="small"
          outlined
          :loading="loading"
          @click="refreshPayments"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-[#00685F]">
        <p class="text-xs sm:text-sm text-gray-600">Total Payments</p>
        <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ pagination.total }}</p>
        <p class="text-xs text-gray-400">All-time payment records</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-green-500">
        <p class="text-xs sm:text-sm text-gray-600">Total Paid</p>
        <p class="text-xl sm:text-2xl font-bold text-green-600">{{ formatPrice(totalPaid) }}</p>
        <p class="text-xs text-gray-400">Successful payments on this view</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-yellow-500">
        <p class="text-xs sm:text-sm text-gray-600">Pending Amount</p>
        <p class="text-xl sm:text-2xl font-bold text-yellow-600">{{ formatPrice(pendingAmount) }}</p>
        <p class="text-xs text-gray-400">Awaiting confirmation on this view</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white rounded-lg shadow-sm p-8 text-center">
      <i class="pi pi-exclamation-triangle text-4xl text-red-400 mb-3 block"></i>
      <p class="text-gray-600 mb-4">{{ error }}</p>
      <Button label="Try Again" icon="pi pi-refresh" @click="refreshPayments" class="bg-[#00685F] border-[#00685F] hover:bg-[#004F45]" />
    </div>

    <!-- Empty State -->
    <div v-else-if="payments.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
      <i class="pi pi-credit-card text-6xl text-gray-300 mb-6 block"></i>
      <h3 class="text-xl font-semibold text-gray-900 mb-2">No Payments Yet</h3>
      <p class="text-gray-600 mb-6">Your payment history will appear here after your first order</p>
      <router-link
        to="/shop"
        class="inline-block bg-[#00685F] text-white px-6 py-2 rounded-lg hover:bg-[#004F45] transition"
      >
        Start Shopping
      </router-link>
    </div>

    <!-- Payments Table -->
    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Payment History</h3>
        <span class="text-xs sm:text-sm text-gray-500">
          Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} -
          {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of {{ pagination.total }}
        </span>
      </div>

      <DataTable
        :value="payments"
        dataKey="id"
        responsiveLayout="scroll"
        class="p-datatable-sm"
      >
        <!-- Order # -->
        <Column header="Order" style="min-width: 120px">
          <template #body="{ data }">
            <button
              class="text-[#00685F] hover:text-[#004F45] font-medium text-sm truncate"
              @click="viewOrder(data)"
            >
              {{ data.order?.order_number || '#' + data.order_id }}
            </button>
          </template>
        </Column>

        <!-- Method -->
        <Column field="payment_method" header="Method" style="min-width: 110px">
          <template #body="{ data }">
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-700">
              <i :class="getMethodIcon(data.payment_method)" class="text-gray-400"></i>
              {{ formatMethod(data.payment_method) }}
            </span>
          </template>
        </Column>

        <!-- Amount -->
        <Column field="amount" header="Amount" style="min-width: 110px">
          <template #body="{ data }">
            <span class="text-sm font-semibold text-gray-900">{{ formatPrice(data.amount) }}</span>
          </template>
        </Column>

        <!-- Status -->
        <Column field="status" header="Status" style="min-width: 110px">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" size="small" />
          </template>
        </Column>

        <!-- Transaction ID (hidden on mobile) -->
        <Column header="Transaction" style="min-width: 140px" class="hidden md:table-cell">
          <template #body="{ data }">
            <span v-if="data.transaction_id" class="text-xs text-gray-500 font-mono truncate block max-w-[150px]">
              {{ data.transaction_id }}
            </span>
            <span v-else class="text-xs text-gray-400">—</span>
          </template>
        </Column>

        <!-- Date (hidden on mobile) -->
        <Column header="Date" style="min-width: 120px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <span class="text-sm text-gray-600">{{ formatDate(data.paid_at || data.created_at) }}</span>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-8">
            <i class="pi pi-inbox text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-3">No payment records found</p>
          </div>
        </template>
      </DataTable>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="border-t border-gray-100 p-2">
        <Paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="first"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          @page="onPageChange"
          class="border-0"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'
import Button from 'primevue/button'
import { useCustomerDashboardStore } from '@/stores/DashboardStore/Customer/dashboard.customer.store'
import type { Payment } from '@/stores/DashboardStore/Customer/dashboard.customer.store'

const router = useRouter()
const dashboardStore = useCustomerDashboardStore()

// Local state mirroring the store slice so the page renders instantly from cache
const payments = ref<Payment[]>([])
const loading = ref(true)
const error = ref('')
const first = ref(0)
const rowsPerPage = ref(15)

const pagination = computed(() => dashboardStore.paymentPagination)

// This-page summary stats
const totalPaid = computed(() => {
  return payments.value
    .filter(p => p.status === 'success')
    .reduce((sum, p) => sum + (Number(p.amount) || 0), 0)
})

const pendingAmount = computed(() => {
  return payments.value
    .filter(p => p.status === 'pending' || p.status === 'processing')
    .reduce((sum, p) => sum + (Number(p.amount) || 0), 0)
})

const fetchPayments = async (page = 1, perPage = rowsPerPage.value, force = false) => {
  loading.value = true
  error.value = ''
  try {
    const result = await dashboardStore.fetchPayments(force, page, perPage)
    payments.value = result.payments
    first.value = (result.pagination.current_page - 1) * result.pagination.per_page
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load payment history'
  } finally {
    loading.value = false
  }
}

const refreshPayments = () => {
  fetchPayments(pagination.value.current_page, rowsPerPage.value, true)
}

const onPageChange = (event: any) => {
  first.value = event.first
  rowsPerPage.value = event.rows
  fetchPayments(event.page + 1, event.rows, true)
}

const viewOrder = (payment: Payment) => {
  router.push({ name: 'CustomerOrderDetails', params: { id: String(payment.order_id) } })
}

// Formatters
const formatPrice = (price: any) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(Number(price) || 0)
}

const formatDate = (date: string | null) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatMethod = (method: string): string => {
  const map: Record<string, string> = {
    cod: 'Cash on Delivery',
    card: 'Card',
    paypal: 'PayPal',
    bank_transfer: 'Bank Transfer',
    bkash: 'bKash'
  }
  return map[method] || method || '—'
}

const getMethodIcon = (method: string): string => {
  const map: Record<string, string> = {
    cod: 'pi pi-money-bill',
    card: 'pi pi-credit-card',
    paypal: 'pi pi-paypal',
    bank_transfer: 'pi pi-bank',
    bkash: 'pi pi-mobile'
  }
  return map[method] || 'pi pi-credit-card'
}

const formatStatus = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    success: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded'
  }
  return map[status] || status
}

const getStatusSeverity = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'warning',
    processing: 'info',
    success: 'success',
    failed: 'danger',
    refunded: 'secondary'
  }
  return classes[status] || 'info'
}

onMounted(() => {
  fetchPayments(1, rowsPerPage.value, false)
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f9fafb;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  color: #6b7280;
  padding: 0.6rem 0.75rem;
  white-space: nowrap;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

:deep(.p-paginator) {
  border: none !important;
  background: transparent !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: #00685F !important;
  color: white !important;
  border-color: #00685F !important;
}
</style>
