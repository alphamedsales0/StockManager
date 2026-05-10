<template>
  <v-card rounded="lg" elevation="2" style="height: 100%;">
     <!-- Header modernisé -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3">
      <span class="text-h6 font-weight-semibold">
        Produktverteilung
      </span>

      <div class="d-flex align-center ga-1">
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-fullscreen</v-icon>
        </v-btn>
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-refresh</v-icon>
        </v-btn>
      </div>

    </v-card-title>
    <v-card-text class="pa-4 text-center">
      <canvas ref="chartCanvas" width="200" height="200"></canvas>
      <div class="chart-info mt-4">
        <div class="legend-two-columns">
          <div class="legend-col legend-left">
            <div v-for="item in leftItems" :key="item.category" class="legend-item">
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="ms-1">{{ item.category }}: {{ item.percentage }}%</span>
            </div>
          </div>
          <div class="legend-col legend-right">
            <div v-for="item in rightItems" :key="item.category" class="legend-item">
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="ms-1">{{ item.category }}: {{ item.percentage }}%</span>
            </div>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import { useProductStore } from '../../stores/stock_manager_products'

Chart.register(...registerables)

const productStore = useProductStore()
const chartCanvas = ref(null)
let chartInstance = null

const colorPalette = ['#00b5e9', '#fa4251', '#00ad5f', '#ffc107', '#9c27b0', '#ff9800', '#795548', '#607d8b']

// Kategorien aus den Produkten berechnen
const categoryStats = computed(() => {
  const products = productStore.products || []
  const categoryCount = {}
  products.forEach(p => {
    const cat = p.category || 'Autre'
    categoryCount[cat] = (categoryCount[cat] || 0) + 1
  })
  const total = products.length
  if (total === 0) return []
  return Object.entries(categoryCount).map(([category, count], idx) => ({
    category,
    count,
    percentage: Math.round((count / total) * 100),
    color: colorPalette[idx % colorPalette.length]
  })).sort((a, b) => b.count - a.count)
})

// Chart-Daten
const chartData = computed(() => ({
  labels: categoryStats.value.map(s => s.category),
  datasets: [{
    data: categoryStats.value.map(s => s.percentage),
    backgroundColor: categoryStats.value.map(s => s.color),
    borderWidth: 0,
    cutout: '65%'
  }]
}))

// Legende – jetzt direkt aus categoryStats
const leftItems = computed(() => {
  const stats = categoryStats.value
  const mid = Math.ceil(stats.length / 2)
  return stats.slice(0, mid)
})

const rightItems = computed(() => {
  const stats = categoryStats.value
  const mid = Math.ceil(stats.length / 2)
  return stats.slice(mid)
})

// Chart rendern
const renderChart = () => {
  if (!chartCanvas.value || categoryStats.value.length === 0) return
  if (chartInstance) chartInstance.destroy()
  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}%` } }
      }
    }
  })
}

onMounted(() => {
  renderChart()
})

watch(categoryStats, () => renderChart(), { deep: true })
</script>

<style scoped>
.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
}
canvas {
  max-width: 200px;
  margin: 0 auto;
}
.legend-two-columns {
  display: flex;
  justify-content: space-between;
  max-width: 300px;
  margin: 0 auto;
  gap: 16px;
}
.legend-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.legend-left {
  align-items: flex-start;
}
.legend-right {
  align-items: flex-end;
}
.legend-item {
  display: flex;
  align-items: center;
  white-space: nowrap;
}
</style>