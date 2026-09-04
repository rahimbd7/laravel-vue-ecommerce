<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
      <h4 class="font-semibold text-gray-900">Activity Log</h4>
      <div class="flex flex-wrap items-center gap-2">
        <Dropdown 
          v-model="activityTypeFilter" 
          :options="activityTypeOptions" 
          optionLabel="label"
          optionValue="value"
          placeholder="All Activities"
          class="w-full sm:w-36"
          size="small"
        />
        <Button 
          icon="pi pi-refresh" 
          size="small"
          text
          rounded
          :loading="loading"
          @click="fetchActivities"
        />
        <Button 
          icon="pi pi-download" 
          size="small"
          text
          rounded
          @click="exportActivities"
          tooltip="Export Activities"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Activity Feed -->
    <div v-else class="relative">
      <!-- Timeline Line -->
      <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

      <div v-if="filteredActivities.length === 0" class="text-center py-8">
        <i class="pi pi-clock text-4xl text-gray-300"></i>
        <p class="text-gray-500 mt-2">No activities found</p>
      </div>

      <div v-else class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
        <div 
          v-for="activity in filteredActivities" 
          :key="activity.id"
          class="flex gap-4 relative"
        >
          <!-- Timeline Dot -->
          <div class="relative z-10 flex-shrink-0">
            <div 
              class="w-8 h-8 rounded-full flex items-center justify-center"
              :class="getActivityColorClass(activity.color || 'gray')"
            >
              <i :class="[activity.icon || 'pi pi-circle', 'text-white text-sm']"></i>
            </div>
          </div>

          <!-- Activity Content -->
          <div class="flex-1 bg-gray-50 rounded-lg p-3 sm:p-4 hover:bg-gray-100 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
              <div class="flex-1">
                <p class="text-sm text-gray-900">
                  {{ activity.description }}
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                  <span class="text-xs text-gray-500">
                    <i class="pi pi-user mr-1"></i>
                    {{ activity.user_name || 'System' }}
                  </span>
                  <span class="text-xs text-gray-400">•</span>
                  <span class="text-xs text-gray-500">
                    <i class="pi pi-tag mr-1"></i>
                    {{ activity.user_role || 'system' }}
                  </span>
                  <span class="text-xs text-gray-400">•</span>
                  <span class="text-xs text-gray-400">
                    {{ formatTime(activity.created_at) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-xs text-gray-400 whitespace-nowrap">
                  {{ getTimeAgo(activity.created_at) }}
                </span>
                <Button 
                  v-if="activity.reference_id"
                  icon="pi pi-arrow-right" 
                  text 
                  rounded 
                  size="small"
                  @click="navigateToReference(activity)"
                  tooltip="View Details"
                  class="text-xs"
                />
              </div>
            </div>

            <!-- Metadata if available -->
            <div v-if="activity.metadata" class="mt-2 pt-2 border-t border-gray-200">
              <div class="flex flex-wrap gap-2 text-xs">
                <span 
                  v-for="(value, key) in activity.metadata" 
                  :key="key"
                  class="bg-white px-2 py-1 rounded border border-gray-200"
                >
                  <span class="text-gray-500">{{ key }}:</span>
                  <span class="font-medium text-gray-700">{{ value }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMoreActivities" class="text-center mt-4">
        <Button 
          label="Load More" 
          icon="pi pi-chevron-down" 
          size="small"
          outlined
          :loading="loadingMore"
          @click="loadMore"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import api from '@/api/api'

const props = defineProps<{
  userId: string
}>()

const router = useRouter()
const toast = useToast()

// State
const activities = ref<any[]>([])
const loading = ref(false)
const loadingMore = ref(false)
const activityTypeFilter = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const totalActivities = ref(0)

const activityTypeOptions = [
  { label: 'All Activities', value: '' },
  { label: 'Orders', value: 'order' },
  { label: 'Login', value: 'login' },
  { label: 'Profile', value: 'profile' },
  { label: 'Reviews', value: 'review' },
  { label: 'Wishlist', value: 'wishlist' },
  { label: 'Payments', value: 'payment' },
  { label: 'Admin Actions', value: 'admin' },
]

const activityColorMap: Record<string, string> = {
  green: 'bg-green-500',
  blue: 'bg-blue-500',
  purple: 'bg-purple-500',
  orange: 'bg-orange-500',
  red: 'bg-red-500',
  indigo: 'bg-indigo-500',
  teal: 'bg-teal-500',
  yellow: 'bg-yellow-500',
  pink: 'bg-pink-500',
  gray: 'bg-gray-500',
}

const filteredActivities = computed(() => {
  if (!activityTypeFilter.value) return activities.value
  return activities.value.filter(a => 
    a.type?.includes(activityTypeFilter.value) ||
    a.reference_type?.includes(activityTypeFilter.value)
  )
})

const hasMoreActivities = computed(() => {
  return activities.value.length < totalActivities.value
})

// Methods
const getActivityColorClass = (color: string): string => {
  return activityColorMap[color] || activityColorMap.gray as string
}

const getTimeAgo = (date: string): string => {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diff = Math.floor((now.getTime() - past.getTime()) / 1000)
  
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
  if (diff < 2592000) return `${Math.floor(diff / 604800)}w ago`
  return formatDate(date)
}

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatTime = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

const fetchActivities = async () => {
  loading.value = true
  try {
    const response = await api.get(`/admin/users/${props.userId}/activities`, {
      params: {
        page: 1,
        per_page: perPage.value,
        type: activityTypeFilter.value,
      }
    })
    activities.value = response.data.data || []
    totalActivities.value = response.data.meta?.total || 0
    currentPage.value = 1
  } catch (error) {
    console.error('Failed to fetch user activities:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load activity log',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadMore = async () => {
  if (loadingMore.value || !hasMoreActivities.value) return
  
  loadingMore.value = true
  const nextPage = currentPage.value + 1
  
  try {
    const response = await api.get(`/admin/users/${props.userId}/activities`, {
      params: {
        page: nextPage,
        per_page: perPage.value,
        type: activityTypeFilter.value,
      }
    })
    activities.value = [...activities.value, ...(response.data.data || [])]
    totalActivities.value = response.data.meta?.total || 0
    currentPage.value = nextPage
  } catch (error) {
    console.error('Failed to load more activities:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load more activities',
      life: 3000
    })
  } finally {
    loadingMore.value = false
  }
}

const navigateToReference = (activity: any) => {
  if (!activity.reference_type || !activity.reference_id) return
  
  const routes: Record<string, string> = {
    order: `/dashboard/admin/orders/${activity.reference_id}`,
    user: `/dashboard/admin/users/${activity.reference_id}`,
    vendor: `/dashboard/admin/vendors/${activity.reference_id}`,
    product: `/dashboard/admin/products/${activity.reference_id}`,
    payment: `/dashboard/admin/payments/${activity.reference_id}`,
  }
  
  const route = routes[activity.reference_type]
  if (route) {
    router.push(route)
  }
}

const exportActivities = async () => {
  try {
    const response = await api.get(`/admin/users/${props.userId}/activities/export`, {
      params: { type: activityTypeFilter.value },
      responseType: 'blob',
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `user_activities_${props.userId}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.add({
      severity: 'success',
      summary: 'Export Started',
      detail: 'Activities export has started',
      life: 3000
    })
  } catch (error) {
    console.error('Failed to export activities:', error)
    toast.add({
      severity: 'error',
      summary: 'Export Failed',
      detail: 'Failed to export activities',
      life: 3000
    })
  }
}

// Watch for filter changes
watch(activityTypeFilter, () => {
  fetchActivities()
})

// Lifecycle
onMounted(() => {
  fetchActivities()
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .custom-scrollbar {
    max-height: 400px !important;
  }
}
</style>
