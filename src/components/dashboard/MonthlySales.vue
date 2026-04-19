<template>
  <v-card elevation="3" rounded="lg" class="sales-chart-card h-100">
    <v-card-title class="text-h6 py-3 bg-grey-lighten-3">
      <v-icon start size="small">mdi-chart-line</v-icon>
      Monatliche Verkäufe
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text class="pa-3">
      <canvas ref="salesChartCanvas" style="width: 100%; height: 200px;"></canvas>
      <div class="text-center text-body-2 text-grey mt-2 font-weight-medium">
        {{ totalSales }} € Gesamtumsatz (letzte 6 Monate)
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const monthlySales = ref([
  { month: 'Aug', sales: 12450 },
  { month: 'Sep', sales: 18920 },
  { month: 'Okt', sales: 15780 },
  { month: 'Nov', sales: 22340 },
  { month: 'Dez', sales: 28900 },
  { month: 'Jan', sales: 20560 }
])

const totalSales = computed(() => {
  return monthlySales.value.reduce((sum, item) => sum + item.sales, 0).toLocaleString('de-DE')
})

const salesChartCanvas = ref(null)
let chartInstance = null

const renderSalesChart = () => {
  if (!salesChartCanvas.value) return
  if (chartInstance) chartInstance.destroy()

  const labels = monthlySales.value.map(item => item.month)
  const data = monthlySales.value.map(item => item.sales)

  const ctx = salesChartCanvas.value.getContext('2d')
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Umsatz (€)',
        data: data,
        borderColor: '#2e7d32',
        backgroundColor: 'rgba(46, 125, 50, 0.1)',
        borderWidth: 2.5,
        pointBackgroundColor: '#2e7d32',
        pointBorderColor: '#fff',
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.raw.toLocaleString('de-DE')} €`
          },
          titleFont: { size: 12, weight: 'bold' },
          bodyFont: { size: 11 }
        },
        legend: {
          position: 'top',
          labels: {
            font: { size: 11 },
            boxWidth: 10,
            padding: 8
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => value.toLocaleString('de-DE') + ' €',
            font: { size: 10 }
          },
          title: {
            display: true,
            text: 'Umsatz (€)',
            font: { size: 11 }
          }
        },
        x: {
          ticks: { font: { size: 10 } },
          title: {
            display: true,
            text: 'Monat',
            font: { size: 11 }
          }
        }
      }
    }
  })
}

onMounted(() => renderSalesChart())
</script>

<style scoped>
.sales-chart-card {
  background: white;
  border-left: 4px solid #2e7d32;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.sales-chart-card .v-card-text {
  flex: 1;
}
canvas {
  max-height: 200px;
  width: 100%;
}
</style>