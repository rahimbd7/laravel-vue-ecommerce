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
  data: Array<{ date: string; new_users: number }> | any
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const hasData = computed(() => {
  if (!props.data) return false
  if (!Array.isArray(props.data)) return false
  return props.data.length > 0 && props.data.some(d => d.new_users > 0)
})

const getChartData = () => {
  if (Array.isArray(props.data) && props.data.length > 0) {
    return props.data
  }
  return []
}

const createChart = () => {
  if (!chartCanvas.value || !hasData.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const data = getChartData()
  const labels = data.map(d => d.date)
  const values = data.map(d => d.new_users)

  const chartData: ChartData<'bar'> = {
    labels: labels,
    datasets: [
      {
        label: 'New Users',
        data: values,
        backgroundColor: 'rgba(59, 130, 246, 0.6)',
        borderColor: '#3B82F6',
        borderWidth: 1,
        borderRadius: 4,
      },
    ],
  }

  const chartOptions: ChartOptions<'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          font: { size: 10 },
          stepSize: 1,
        },
        grid: {
          color: 'rgba(0, 0, 0, 0.05)',
        },
      },
      x: {
        grid: {
          display: false,
        },
        ticks: {
          font: { size: 10 },
          maxTicksLimit: 10,
        },
      },
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
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