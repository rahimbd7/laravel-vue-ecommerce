<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
      <div class="flex-1 min-w-0">
        <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">{{ title }}</p>
        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mt-1">
          {{ formatValue(value) }}
        </p>
        <div v-if="trend !== undefined" class="flex items-center gap-1 mt-2">
          <i 
            :class="[
              'pi',
              trend >= 0 ? 'pi-arrow-up text-green-500' : 'pi-arrow-down text-red-500',
              'text-xs sm:text-sm'
            ]"
          />
          <span 
            :class="[
              'text-xs sm:text-sm font-medium',
              trend >= 0 ? 'text-green-500' : 'text-red-500'
            ]"
          >
            {{ trend >= 0 ? '+' : '' }}{{ trend.toFixed(1) }}%
          </span>
          <span class="text-xs text-gray-400 hidden sm:inline">vs previous period</span>
        </div>
        <p v-if="subtext" class="text-xs text-gray-500 mt-1 truncate">{{ subtext }}</p>
      </div>
      <div 
        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center flex-shrink-0 ml-3"
        :class="iconBgColor"
      >
        <i :class="[icon, 'text-white text-base sm:text-lg']"></i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  title: string
  value: string | number
  icon: string
  color: 'green' | 'blue' | 'purple' | 'orange' | 'red' | 'indigo' | 'teal'
  trend?: number
  subtext?: string
}>()

const iconBgColor = computed(() => {
  const colors = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    indigo: 'bg-indigo-500',
    teal: 'bg-teal-500',
  }
  return colors[props.color] || 'bg-gray-500'
})

const formatValue = (value: string | number): string => {
  if (typeof value === 'number' && value >= 1000) {
    if (value >= 1000000) {
      return (value / 1000000).toFixed(1) + 'M'
    }
    return (value / 1000).toFixed(1) + 'K'
  }
  return String(value)
}
</script>
