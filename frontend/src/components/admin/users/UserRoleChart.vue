<template>
  <div class="relative">
    <canvas ref="chartRef"></canvas>
    <div v-if="!hasData" class="absolute inset-0 flex items-center justify-center text-gray-400">
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps<{
  data: Record<string, number>
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const hasData = computed(() => {
  return props.data && Object.keys(props.data).length > 0 && 
    Object.values(props.data).some(v => v > 0)
})

const colors: Record<string, string> = {
  customer: '#8B5CF6',
  vendor: '#00685F',
  admin: '#F59E0B',
}

const labels: Record<string, string> = {
  customer: 'Customers',
  vendor: 'Vendors',
  admin: 'Admins',
}

const createChart = () => {
  if (!chartRef.value || !hasData.value) return
  
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const labels_list = Object.keys(props.data)
  const values = Object.values(props.data)
  
  chartInstance = new Chart(chartRef.value, {
    type: 'doughnut',
    data: {
      labels: labels_list.map(label => labels[label] || label),
      datasets: [
        {
          data: values,
          backgroundColor: labels_list.map(label => colors[label] || '#6B7280'),
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true,
            pointStyleWidth: 8,
            font: {
              size: 12,
            },
          },
        },
        tooltip: {
          callbacks: {
            label: function(context: any) {
              const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0)
              const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0
              return `${context.label}: ${context.parsed} users (${percentage}%)`
            }
          }
        }
      },
    },
  })
}

watch(() => props.data, () => {
  createChart()
}, { deep: true })

onMounted(() => {
  createChart()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>

<style scoped>
canvas {
  height: 250px;
  width: 100%;
}

@media (max-width: 640px) {
  canvas {
    height: 200px;
  }
}
</style>