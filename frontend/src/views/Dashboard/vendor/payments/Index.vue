<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Payments & Payouts</h1>
      <p class="text-sm sm:text-base text-gray-600">Track your earnings and payment history</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-[#00685F]">
        <p class="text-xs sm:text-sm text-gray-600">Total Earnings</p>
        <p class="text-xl sm:text-2xl font-bold text-[#00685F]">{{ formatPrice(balance.total_earned || 0) }}</p>
        <p class="text-xs text-gray-400">Lifetime earnings</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-yellow-500">
        <p class="text-xs sm:text-sm text-gray-600">Pending Balance</p>
        <p class="text-xl sm:text-2xl font-bold text-yellow-600">{{ formatPrice(balance.pending_balance || 0) }}</p>
        <p class="text-xs text-gray-400">Earned but not paid</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4 border-green-500">
        <p class="text-xs sm:text-sm text-gray-600">Available Balance</p>
        <p class="text-xl sm:text-2xl font-bold text-green-600">{{ formatPrice(balance.available_balance || 0) }}</p>
        <p class="text-xs text-gray-400">Already paid out</p>
      </div>
    </div>

    <!-- Commission Summary -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
        <div>
          <p class="text-xs sm:text-sm text-gray-600">Total Commission Paid</p>
          <p class="text-lg sm:text-xl font-bold text-red-600">{{ formatPrice(balance.total_commission || 0) }}</p>
        </div>
        <div>
          <p class="text-xs sm:text-sm text-gray-600">Net Earnings</p>
          <p class="text-lg sm:text-xl font-bold text-[#00685F]">{{ formatPrice(balance.total_earned || 0) }}</p>
        </div>
      </div>
    </div>

    <!-- Payout History -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Payout History</h3>
        <span class="text-xs sm:text-sm text-gray-500">Total: {{ payouts.length }} payouts</span>
      </div>

      <!-- Empty State -->
      <div v-if="payouts.length === 0 && !loading" class="text-center py-12 text-gray-500">
        <i class="pi pi-wallet text-4xl text-gray-300 mb-4 block"></i>
        <p class="font-medium text-sm sm:text-base">No payouts yet</p>
        <p class="text-xs sm:text-sm">When you start earning, your payouts will appear here</p>
      </div>

      <!-- Payouts Table -->
      <DataTable v-else :value="payouts" class="p-datatable-sm" paginator :rows="10" responsiveLayout="scroll">
        <Column field="order.order_number" header="Order #" style="min-width: 90px; max-width: 130px;">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm font-medium text-gray-900">{{ data.order?.order_number || data.order_number || 'N/A' }}</span>
          </template>
        </Column>
        <Column field="total_amount" header="Total" style="min-width: 80px; max-width: 120px;">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ formatPrice(data.total_amount) }}</span>
          </template>
        </Column>
        <Column field="commission" header="Commission" style="min-width: 70px; max-width: 100px;" class="commission-column">
          <template #body="{ data }">
            <span class="text-red-600 text-xs sm:text-sm">{{ formatPrice(data.commission) }}</span>
          </template>
        </Column>
        <Column field="net_amount" header="Net" style="min-width: 80px; max-width: 120px;">
          <template #body="{ data }">
            <span class="font-bold text-[#00685F] text-xs sm:text-sm">{{ formatPrice(data.net_amount) }}</span>
          </template>
        </Column>
        <Column field="status" header="Status" style="min-width: 80px; max-width: 110px;">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" class="text-xs px-1 sm:px-2" />
          </template>
        </Column>
        <Column field="created_at" header="Date" style="min-width: 80px; max-width: 110px;" class="date-column">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column field="paid_at" header="Paid" style="min-width: 80px; max-width: 110px;" class="paid-column">
          <template #body="{ data }">
            <span class="text-xs sm:text-sm">{{ data.paid_at ? formatDate(data.paid_at) : '—' }}</span>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import api from '@/api/api'

const loading = ref(false)
const balance = ref({
  total_earned: 0,
  total_commission: 0,
  pending_balance: 0,
  available_balance: 0
})
const payouts = ref<any[]>([])

const fetchData = async () => {
  loading.value = true
  try {
    const balanceRes = await api.get('/vendor/payouts/dashboard')
    console.log('✅ Balance response:', balanceRes.data)
    
    if (balanceRes.data.data?.balance) {
      balance.value = balanceRes.data.data.balance
    } else if (balanceRes.data.data) {
      balance.value = balanceRes.data.data
    }

    const historyRes = await api.get('/vendor/payouts/history')
    console.log('✅ History response:', historyRes.data)
    
    if (historyRes.data.data?.data) {
      payouts.value = historyRes.data.data.data
    } else if (Array.isArray(historyRes.data.data)) {
      payouts.value = historyRes.data.data
    } else if (historyRes.data.data?.payouts) {
      payouts.value = historyRes.data.data.payouts
    } else {
      payouts.value = []
    }
    
    console.log('📊 Payouts loaded:', payouts.value.length)
  } catch (error: any) {
    console.error('❌ Failed to fetch payment data:', error)
    console.error('❌ Error response:', error.response?.data)
    payouts.value = []
    balance.value = {
      total_earned: 0,
      total_commission: 0,
      pending_balance: 0,
      available_balance: 0
    }
  } finally {
    loading.value = false
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price || 0)
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatStatus = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    paid: 'Paid',
    failed: 'Failed',
    cancelled: 'Cancelled'
  }
  return map[status] || status
}

const getStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'warning',
    processing: 'info',
    paid: 'success',
    failed: 'danger',
    cancelled: 'danger'
  }
  return map[status] || 'info'
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
/* ✅ Responsive table fixes */

/* Desktop (1024px and above) */
@media (min-width: 1024px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.7rem !important;
    font-size: 0.8rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.7rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.8rem !important;
  }
}

/* Tablet (768px to 1023px) */
@media (min-width: 768px) and (max-width: 1023px) {
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.3rem 0.4rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.3rem 0.4rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.3rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.65rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.6rem !important;
    padding: 0.1rem 0.3rem !important;
  }
}

/* Mobile (below 768px) - Hide Commission, Paid Date */
@media (max-width: 767px) {
  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.3rem 0.25rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.65rem !important;
  }
  
  /* ✅ Hide Commission (col 3), Date (col 6), Paid (col 7) on mobile */
  :deep(.p-datatable .p-datatable-thead > tr > th.commission-column),
  :deep(.p-datatable .p-datatable-tbody > tr > td.commission-column),
  :deep(.p-datatable .p-datatable-thead > tr > th.date-column),
  :deep(.p-datatable .p-datatable-tbody > tr > td.date-column),
  :deep(.p-datatable .p-datatable-thead > tr > th.paid-column),
  :deep(.p-datatable .p-datatable-tbody > tr > td.paid-column) {
    display: none !important;
  }
  
  /* ✅ Ensure Order #, Total, Net, Status visible */
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(1)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(1)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(2)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(2)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(4)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)),
  :deep(.p-datatable .p-datatable-thead > tr > th:nth-child(5)),
  :deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(5)) {
    display: table-cell !important;
  }
  
  :deep(.p-datatable .p-paginator) {
    padding: 0.25rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-paginator .p-paginator-first),
  :deep(.p-datatable .p-paginator .p-paginator-prev),
  :deep(.p-datatable .p-paginator .p-paginator-next),
  :deep(.p-datatable .p-paginator .p-paginator-last) {
    min-width: 1.6rem !important;
    height: 1.6rem !important;
    font-size: 0.6rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.55rem !important;
    padding: 0.1rem 0.3rem !important;
  }
}

/* Extra small devices (below 480px) */
@media (max-width: 480px) {
  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.2rem 0.15rem !important;
  }
  
  :deep(.p-datatable .p-datatable-tbody > tr) {
    font-size: 0.55rem !important;
  }
  
  :deep(.p-datatable .p-tag) {
    font-size: 0.45rem !important;
    padding: 0.05rem 0.2rem !important;
  }
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  white-space: nowrap;
}

:deep(.p-datatable .p-paginator) {
  border: none !important;
  background: transparent !important;
}
</style>