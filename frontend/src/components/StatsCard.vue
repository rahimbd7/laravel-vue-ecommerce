<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border-l-4" :class="borderColor">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-xs sm:text-sm text-gray-600">{{ title }}</p>
        <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ value }}</p>
        <p v-if="subtext" class="text-xs text-gray-500 mt-1">{{ subtext }}</p>
      </div>
      <div class="p-2 sm:p-3 rounded-lg" :class="bgColor">
       <i :class="[icon, iconColor]" class="text-lg sm:text-xl"></i>
      </div>
    </div>
    <div v-if="trend !== undefined" class="mt-2 flex items-center gap-1">
      <span :class="trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
        {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend).toFixed(1) }}%
      </span>
      <span class="text-xs text-gray-500">vs last month</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  icon: string
  color: 'green' | 'blue' | 'purple' | 'orange' | 'red' | 'yellow'
  subtext?: string
  trend?: number
}>()

const borderColor = {
  green: 'border-green-500',
  blue: 'border-blue-500',
  purple: 'border-purple-500',
  orange: 'border-orange-500',
  red: 'border-red-500',
  yellow: 'border-yellow-500',
}

const bgColor = {
  green: 'bg-green-100',
  blue: 'bg-blue-100',
  purple: 'bg-purple-100',
  orange: 'bg-orange-100',
  red: 'bg-red-100',
  yellow: 'bg-yellow-100',
}

const iconColor = {
  green: 'text-green-600',
  blue: 'text-blue-600',
  purple: 'text-purple-600',
  orange: 'text-orange-600',
  red: 'text-red-600',
  yellow: 'text-yellow-600',
}
</script>