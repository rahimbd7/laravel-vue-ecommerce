<template>
  <div class="space-y-4">
    <div v-if="!hasData" class="text-center py-8 text-gray-500">
      <i class="pi pi-users text-4xl text-gray-300 mb-2 block"></i>
      <p class="text-sm">No user data available</p>
    </div>

    <div v-else class="space-y-3">
      <div 
        v-for="(count, role) in data" 
        :key="role"
        class="flex items-center gap-3"
      >
        <!-- Role Label -->
        <div class="w-24 flex-shrink-0">
          <span class="text-sm font-medium text-gray-700 capitalize">{{ getRoleLabel(role) }}</span>
        </div>

        <!-- Progress Bar -->
        <div class="flex-1">
          <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div 
              class="h-full rounded-full transition-all duration-700 ease-out"
              :style="{ 
                width: `${getPercentage(count)}%`,
                backgroundColor: getColor(role)
              }"
            ></div>
          </div>
        </div>

        <!-- Count & Percentage -->
        <div class="w-20 flex-shrink-0 text-right">
          <span class="text-sm font-semibold text-gray-900">{{ count }}</span>
          <span class="text-xs text-gray-500 ml-1">({{ getPercentage(count) }}%)</span>
        </div>
      </div>
    </div>

    <!-- Total Users -->
    <div v-if="hasData" class="pt-3 border-t border-gray-200">
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">Total Users</span>
        <span class="font-semibold text-gray-900">{{ total }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  data: Record<string, number>
  total: number
}>()

const hasData = computed(() => {
  return props.data && Object.keys(props.data).length > 0 && 
    Object.values(props.data).some(v => v > 0)
})

const getPercentage = (count: number): number => {
  if (props.total === 0) return 0
  return Math.round((count / props.total) * 100)
}

const getColor = (role: string): string => {
  const colors: Record<string, string> = {
    customer: '#8B5CF6',  // Purple
    vendor: '#00685F',    // Teal
    admin: '#F59E0B',     // Amber
  }
  return colors[role] || '#6B7280' // Gray fallback
}

const getRoleLabel = (role: string): string => {
  const labels: Record<string, string> = {
    customer: 'Customers',
    vendor: 'Vendors',
    admin: 'Admins',
  }
  return labels[role] || role
}
</script>

<style scoped>
/* Smooth animation for progress bars */
.h-3 {
  height: 0.75rem;
}
</style>