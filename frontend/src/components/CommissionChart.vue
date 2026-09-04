<template>
  <div class="relative">
    <canvas ref="chartCanvas"></canvas>
    <div v-if="!data || data.length === 0" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const props = defineProps<{
  data: Array<{ date: string; commission: number }>
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const createChart = () => {
  if (!chartCanvas.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const labels = props.data.map(d => d.date)
  const values = props.data.map(d => d.commission)

  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Commission',
          data: values,
          backgroundColor: 'rgba(139, 92, 246, 0.6)',
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
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => '$' + value,
            font: { size: 10 },
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
    },
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
