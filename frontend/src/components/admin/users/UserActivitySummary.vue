<template>
  <div class="space-y-4">
    <!-- Activity Metrics -->
    <div class="grid grid-cols-2 gap-3">
      <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <span class="text-xs sm:text-sm text-gray-600">Total Users</span>
          <i class="pi pi-users text-purple-500"></i>
        </div>
        <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ formatNumber(total) }}</p>
      </div>
      
      <div class="bg-green-50 rounded-lg p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <span class="text-xs sm:text-sm text-green-600">Active</span>
          <i class="pi pi-user-check text-green-500"></i>
        </div>
        <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ formatNumber(active) }}</p>
        <p class="text-xs text-green-600">{{ activePercentage }}% of total</p>
      </div>
      
      <div class="bg-red-50 rounded-lg p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <span class="text-xs sm:text-sm text-red-600">Inactive</span>
          <i class="pi pi-user-minus text-red-500"></i>
        </div>
        <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ formatNumber(inactive) }}</p>
        <p class="text-xs text-red-600">{{ inactivePercentage }}% of total</p>
      </div>
      
      <div class="bg-blue-50 rounded-lg p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <span class="text-xs sm:text-sm text-blue-600">New Today</span>
          <i class="pi pi-user-plus text-blue-500"></i>
        </div>
        <p class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ formatNumber(newUsers) }}</p>
        <p class="text-xs text-blue-600">registered today</p>
      </div>
    </div>

    <!-- Activity Status Bar -->
    <div class="mt-2">
      <div class="flex h-2 rounded-full overflow-hidden">
        <div 
          class="bg-green-500 transition-all duration-500"
          :style="{ width: `${activePercentage}%` }"
        ></div>
        <div 
          class="bg-red-400 transition-all duration-500"
          :style="{ width: `${inactivePercentage}%` }"
        ></div>
      </div>
      <div class="flex justify-between mt-1 text-xs text-gray-500">
        <span>Active</span>
        <span>Inactive</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  total: number
  active: number
  inactive: number
  newUsers: number
}>()

const activePercentage = computed(() => {
  if (props.total === 0) return 0
  return ((props.active / props.total) * 100).toFixed(1)
})

const inactivePercentage = computed(() => {
  if (props.total === 0) return 0
  return ((props.inactive / props.total) * 100).toFixed(1)
})

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return String(num)
}
</script>
