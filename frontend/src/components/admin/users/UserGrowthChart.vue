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
  data: Array<{ date: string; new_users: number }>
  range?: string
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const hasData = computed(() => props.data && props.data.length > 0)

const getFilteredData = () => {
  if (!hasData.value) return []
  const days = parseInt(props.range || '30')
  const sorted = [...props.data].sort((a, b) => 
    new Date(a.date).getTime() - new Date(b.date).getTime()
  )
  return sorted.slice(-days)
}

const createChart = () => {
  if (!chartRef.value || !hasData.value) return
  
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const filteredData = getFilteredData()
  
  chartInstance = new Chart(chartRef.value, {
    type: 'bar',
    data: {
      labels: filteredData.map(item => {
        const date = new Date(item.date)
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      }),
      datasets: [
        {
          label: 'New Users',
          data: filteredData.map(item => item.new_users),
          backgroundColor: 'rgba(139, 92, 246, 0.7)',
          borderColor: '#8B5CF6',
          borderWidth: 1,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(context: any) {
              return `${context.parsed.y} new users`
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            font: {
              size: 10,
            },
          },
        },
        x: {
          ticks: {
            maxTicksLimit: 15,
            font: {
              size: 10,
            },
          },
        },
      },
    },
  })
}

watch(() => props.data, () => {
  createChart()
}, { deep: true })

watch(() => props.range, () => {
  createChart()
})

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