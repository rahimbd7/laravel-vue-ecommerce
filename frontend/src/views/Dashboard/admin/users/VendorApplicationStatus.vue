<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Vendor Applications</h1>
          <p class="text-sm sm:text-base text-gray-600">Review and approve or reject pending vendor applications</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button
            icon="pi pi-refresh"
            label="Refresh"
            size="small"
            outlined
            :loading="loading"
            @click="refreshApplications"
          />
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
            <i class="pi pi-clock text-amber-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600">Pending</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ pagination.total }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <i class="pi pi-check-circle text-green-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600">Apps Per Page</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ pagination.per_page }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
            <i class="pi pi-list text-gray-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600">Current Page</p>
            <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ pagination.current_page }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Applications Table -->
    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        :value="applications"
        :rows="pagination.per_page"
        :first="first"
        dataKey="id"
        responsiveLayout="scroll"
        class="p-datatable-sm"
      >
        <!-- Business -->
        <Column field="business_name" header="Business" style="min-width: 200px">
          <template #body="{ data }">
            <div class="flex items-center gap-3">
              <Avatar
                :label="getInitials(data.business_name)"
                size="normal"
                :style="{
                  backgroundColor: getAvatarColor(data.business_name),
                  width: '32px',
                  height: '32px',
                  fontSize: '12px',
                  fontWeight: 'bold'
                }"
                class="text-white flex-shrink-0"
              />
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate max-w-[160px]">{{ data.business_name }}</p>
                <p class="text-xs text-gray-500 truncate max-w-[160px]">{{ data.user?.name || '—' }}</p>
              </div>
            </div>
          </template>
        </Column>

        <!-- Contact -->
        <Column field="business_email" header="Contact" style="min-width: 200px" class="hidden md:table-cell">
          <template #body="{ data }">
            <p class="text-sm text-gray-900 truncate max-w-[180px]">{{ data.business_email }}</p>
            <p class="text-xs text-gray-500">{{ data.business_phone }}</p>
          </template>
        </Column>

        <!-- Tax Number -->
        <Column field="tax_number" header="Tax / VAT" style="min-width: 110px" class="hidden lg:table-cell">
          <template #body="{ data }">
            <span v-if="data.tax_number" class="text-sm">{{ data.tax_number }}</span>
            <span v-else class="text-xs text-gray-400">—</span>
          </template>
        </Column>

        <!-- Commission -->
        <Column field="commission_rate" header="Commission" style="min-width: 110px" class="hidden lg:table-cell">
          <template #body="{ data }">
            <Tag :value="`${data.commission_rate}%`" severity="info" size="small" />
          </template>
        </Column>

        <!-- Submitted -->
        <Column field="created_at" header="Submitted" style="min-width: 120px" class="hidden sm:table-cell">
          <template #body="{ data }">
            <span class="text-sm text-gray-700">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Status -->
        <Column header="Status" style="min-width: 100px">
          <template #body>
            <Tag value="Pending" severity="warning" size="small" />
          </template>
        </Column>

        <!-- Actions -->
        <Column header="Actions" style="width: 120px; min-width: 110px" :frozen="true" alignFrozen="right">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <Button
                icon="pi pi-check"
                text
                rounded
                size="small"
                severity="success"
                :loading="actionLoadingId === data.id && actionType === 'approve'"
                @click="confirmApprove(data)"
                tooltip="Approve Application"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
              <Button
                icon="pi pi-times"
                text
                rounded
                size="small"
                severity="danger"
                :loading="actionLoadingId === data.id && actionType === 'reject'"
                @click="openRejectDialog(data)"
                tooltip="Reject Application"
                tooltipOptions="{ position: 'top' }"
                class="w-8! h-8!"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-10">
            <i class="pi pi-check-circle text-4xl text-green-300"></i>
            <p class="text-gray-500 mt-3">No pending vendor applications</p>
          </div>
        </template>
      </DataTable>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="border-t border-gray-100 p-2">
        <Paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="first"
          :rowsPerPageOptions="[5, 10, 25, 50]"
          @page="onPageChange"
          class="border-0"
        />
      </div>
    </div>

    <!-- Reject Dialog -->
    <Dialog
      v-model:visible="rejectDialogVisible"
      header="Reject Vendor Application"
      modal
      :style="{ width: '500px' }"
    >
      <div class="space-y-4">
        <div v-if="selectedApplication" class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm font-medium text-gray-900">{{ selectedApplication.business_name }}</p>
          <p class="text-xs text-gray-500">{{ selectedApplication.user?.email || selectedApplication.business_email }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Rejection Reason <span class="text-red-500">*</span>
          </label>
          <Textarea
            v-model="rejectReason"
            rows="3"
            class="w-full"
            :class="{ 'p-invalid': rejectError }"
            placeholder="Explain why this application is being rejected..."
          />
          <small v-if="rejectError" class="text-red-500">{{ rejectError }}</small>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="rejectDialogVisible = false" />
        <Button
          label="Reject Application"
          icon="pi pi-times"
          severity="danger"
          :loading="rejecting"
          @click="submitReject"
        />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Paginator from 'primevue/paginator'
import { useAdminDashboardStore, type VendorApplication } from '@/stores/DashboardStore/Admin/admin.dashboard.store'

const toast = useToast()
const confirm = useConfirm()
const adminStore = useAdminDashboardStore()

// State
const applications = ref<VendorApplication[]>([])
const first = ref(0)
const rowsPerPage = ref(10)

// Loading / action states
const loading = computed(() => adminStore.loading.applications)
const pagination = computed(() => adminStore.applicationsPagination)

const actionLoadingId = ref<number | null>(null)
const actionType = ref<'approve' | 'reject' | null>(null)

// Reject dialog
const rejectDialogVisible = ref(false)
const rejecting = ref(false)
const selectedApplication = ref<VendorApplication | null>(null)
const rejectReason = ref('')
const rejectError = ref('')

// Get a page of pending applications
const fetchApplications = async (page = 1, perPage = rowsPerPage.value, force = false) => {
  try {
    const result = await adminStore.fetchPendingApplications(force, page, perPage)
    applications.value = result.applications
    first.value = (result.pagination.current_page - 1) * result.pagination.per_page
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load vendor applications',
      life: 3000
    })
  }
}

const refreshApplications = () => {
  fetchApplications(pagination.value.current_page, rowsPerPage.value, true)
}

const onPageChange = (event: any) => {
  first.value = event.first
  rowsPerPage.value = event.rows
  fetchApplications(event.page + 1, event.rows, true)
}

// Approve
const confirmApprove = (application: VendorApplication) => {
  confirm.require({
    message: `Approve vendor application for "${application.business_name}"? ${application.user?.name ? `The user will be upgraded to a vendor: ${application.user.name}.` : ''}`,
    header: 'Approve Vendor Application',
    icon: 'pi pi-check-circle',
    acceptLabel: 'Approve',
    rejectLabel: 'Cancel',
    accept: async () => {
      actionLoadingId.value = application.id
      actionType.value = 'approve'
      try {
        const response = await adminStore.approveVendor(application.id)
        toast.add({
          severity: 'success',
          summary: 'Approved',
          detail: response.message || 'Vendor application approved successfully',
          life: 3000
        })
        await adjustPageAfterRemoval()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to approve vendor application',
          life: 3000
        })
      } finally {
        actionLoadingId.value = null
        actionType.value = null
      }
    }
  })
}

// Reject
const openRejectDialog = (application: VendorApplication) => {
  selectedApplication.value = application
  rejectReason.value = ''
  rejectError.value = ''
  rejectDialogVisible.value = true
}

const submitReject = async () => {
  if (!selectedApplication.value) return

  rejectError.value = ''
  if (!rejectReason.value.trim()) {
    rejectError.value = 'A rejection reason is required'
    return
  }

  rejecting.value = true
  actionLoadingId.value = selectedApplication.value.id
  actionType.value = 'reject'

  try {
    const response = await adminStore.rejectVendor(selectedApplication.value.id, rejectReason.value.trim())
    toast.add({
      severity: 'success',
      summary: 'Rejected',
      detail: response.message || 'Vendor application rejected successfully',
      life: 3000
    })
    rejectDialogVisible.value = false
    await adjustPageAfterRemoval()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject vendor application',
      life: 3000
    })
  } finally {
    rejecting.value = false
    actionLoadingId.value = null
    actionType.value = null
  }
}

// After approving/rejecting one item, refetch the current page; if the last
// item on the last page was removed, step back one page.
const adjustPageAfterRemoval = async () => {
  let page = pagination.value.current_page
  let result = await adminStore.fetchPendingApplications(true, page, rowsPerPage.value)
  if (result.applications.length === 0 && page > 1) {
    page -= 1
    result = await adminStore.fetchPendingApplications(true, page, rowsPerPage.value)
  }
  applications.value = result.applications
  first.value = (page - 1) * rowsPerPage.value
}

// Helpers
const avatarColors = ['#00685F', '#00A88F', '#F59E0B', '#8B5CF6', '#EF4444', '#0EA5E9']
const getAvatarColor = (value: string) => {
  let hash = 0
  for (let i = 0; i < value.length; i++) {
    hash = value.charCodeAt(i) + ((hash << 5) - hash)
  }
  return avatarColors[Math.abs(hash) % avatarColors.length]
}

const getInitials = (value: string) => {
  return value
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (value: string) => {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchApplications(1, rowsPerPage.value, false)
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
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

:deep(.p-textarea.p-invalid) {
  border-color: #ef4444;
}
</style>