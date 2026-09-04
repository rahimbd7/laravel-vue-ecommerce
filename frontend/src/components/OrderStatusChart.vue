<template>
  <div class="relative">
    <canvas ref="chartCanvas"></canvas>
    <div v-if="!hasData" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount, computed } from 'vue'
import { Chart, registerables, type ChartData, type ChartOptions } from 'chart.js'
Chart.register(...registerables)

const props = defineProps<{
  data: Record<string, number> | any
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const hasData = computed(() => {
  if (!props.data) return false
  if (typeof props.data !== 'object') return false
  if (Array.isArray(props.data)) return false
  
  const dataObj = props.data as Record<string, number>
  const keys = Object.keys(dataObj).filter(key => (dataObj[key] || 0) > 0)
  return keys.length > 0
})

const colorMap: Record<string, string> = {
  pending: '#F59E0B',
  processing: '#3B82F6',
  confirmed: '#8B5CF6',
  shipped: '#06B6D4',
  delivered: '#10B981',
  completed: '#059669',
  cancelled: '#EF4444',
  refunded: '#6B7280',
  failed: '#DC2626',
}

const labelMap: Record<string, string> = {
  pending: 'Pending',
  processing: 'Processing',
  confirmed: 'Confirmed',
  shipped: 'Shipped',
  delivered: 'Delivered',
  completed: 'Completed',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
  failed: 'Failed',
}

const createChart = () => {
  if (!chartCanvas.value || !hasData.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const dataObj = props.data as Record<string, number>
  const validKeys = Object.keys(dataObj).filter(key => (dataObj[key] || 0) > 0)
  const labels = validKeys.map(key => labelMap[key] || key)
  const values: number[] = validKeys.map(key => dataObj[key] || 0)
  const backgroundColors = validKeys.map(key => colorMap[key] || '#6B7280')
  const borderColors = validKeys.map(key => colorMap[key] || '#6B7280')

  const chartData: ChartData<'doughnut'> = {
    labels: labels,
    datasets: [
      {
        data: values,
        backgroundColor: backgroundColors,
        borderColor: borderColors,
        borderWidth: 2,
      },
    ],
  }

  const chartOptions: ChartOptions<'doughnut'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 15,
          usePointStyle: true,
          pointStyle: 'circle',
          font: {
            size: 11,
          },
        },
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const dataValues = context.dataset.data as number[]
            const total = dataValues.reduce((a: number, b: number) => a + b, 0)
            const value = context.parsed as number
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
            return `${context.label}: ${value} (${percentage}%)`
          }
        }
      }
    },
    cutout: '65%',
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: chartData,
    options: chartOptions,
  })
}

watch(() => props.data, () => {
  createChart()
}, { deep: true })

onMounted(() => {
  createChart()
})

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>

<style scoped>
canvas {
  height: 250px !important;
  width: 100% !important;
}
</style>
