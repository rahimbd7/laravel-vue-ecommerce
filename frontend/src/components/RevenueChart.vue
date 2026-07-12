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
  data: Array<{ date: string; revenue: number }>
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const createChart = () => {
  if (!chartCanvas.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const labels = props.data.map(d => d.date)
  const values = props.data.map(d => d.revenue)

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Revenue',
          data: values,
          borderColor: '#00685F',
          backgroundColor: 'rgba(0, 104, 95, 0.1)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#00685F',
          pointRadius: 3,
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