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
        <v-btn icon variant="text" size="small" color="grey-darken-1" @click="refresh">
          <v-icon size="20">mdi-refresh</v-icon>
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text class="pa-4 text-center">
      <!-- Ladezustand -->
      <div v-if="productStore.loading" class="py-8">
        <v-progress-circular indeterminate color="primary" />
        <p class="text-caption mt-2">Lade Produktdaten...</p>
      </div>

      <!-- Fehler -->
      <div v-else-if="productStore.error" class="py-8">
        <v-icon size="48" color="error">mdi-alert-circle</v-icon>
        <p class="text-caption text-error mt-2">{{ productStore.error }}</p>
        <v-btn size="small" color="primary" @click="refresh">Erneut laden</v-btn>
      </div>

      <!-- Keine Daten -->
      <div v-else-if="categoryStats.length === 0" class="py-8">
        <v-icon size="48" color="grey-lighten-2">mdi-chart-doughnut</v-icon>
        <p class="text-caption text-grey-darken-1 mt-2">Keine Produktdaten verfügbar</p>
      </div>

      <!-- Chart und Legende -->
      <div v-else>
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
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, computed, watchEffect } from 'vue'
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

// Legende auf zwei Spalten verteilen
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

// Chart rendern oder zerstören
const renderChart = () => {
  if (!chartCanvas.value) return

  // Bestehenden Chart entfernen
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  // Nur zeichnen, wenn Daten vorhanden sind
  if (categoryStats.value.length === 0) return

  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: categoryStats.value.map(s => s.category),
      datasets: [{
        data: categoryStats.value.map(s => s.percentage),
        backgroundColor: categoryStats.value.map(s => s.color),
        borderWidth: 0,
        cutout: '65%'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.label}: ${ctx.raw}%`
          }
        }
      }
    }
  })
}

// Manuelles Neuladen (über Refresh‑Button)
const refresh = () => {
  productStore.fetchProducts()
}

// watchEffect rendert bei jeder Änderung von categoryStats neu
watchEffect(() => {
  renderChart()
})

// Falls beim Mounten noch keine Daten vorhanden sind, nachladen
onMounted(() => {
  if (productStore.products.length === 0 && !productStore.loading) {
    productStore.fetchProducts()
  }
})
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