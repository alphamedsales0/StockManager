<!-- src/components/dashboard/PercentChart.vue -->
<template>
  <v-card rounded="lg" elevation="2" style="height: 100%;">
    <v-card-title class="text-h6 pa-3 bg-grey-lighten-4">
      Produkte nach Kategorie (%)
    </v-card-title>
    <v-card-text class="pa-4 text-center">
      <canvas ref="chartCanvas" width="200" height="200"></canvas>
      <div class="chart-info mt-4">
        <!-- Layout deux colonnes : gauche / droite -->
        <div class="legend-two-columns">
          <div class="legend-col legend-left">
            <div v-for="item in leftItems" :key="item.cat" class="legend-item">
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="ms-1">{{ item.cat }}: {{ item.pct }}%</span>
            </div>
          </div>
          <div class="legend-col legend-right">
            <div v-for="item in rightItems" :key="item.cat" class="legend-item">
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="ms-1">{{ item.cat }}: {{ item.pct }}%</span>
            </div>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const chartCanvas = ref(null)
let chartInstance = null

// Données fictives – à remplacer par les vraies données du store
const categories = ['Cardio', 'Kraft', 'Zubehör', 'Gebrauchtgeräte']
const percentages = [45, 30, 15, 10]

const chartData = {
  labels: categories,
  datasets: [
    {
      data: percentages,
      backgroundColor: ['#00b5e9', '#fa4251', '#00ad5f', '#ffc107'],
      borderWidth: 0,
      cutout: '65%'
    }
  ]
}

// Préparer la liste complète des éléments avec leur couleur
const items = computed(() => 
  categories.map((cat, i) => ({
    cat,
    pct: percentages[i],
    color: chartData.datasets[0].backgroundColor[i]
  }))
)

// Séparer en deux groupes : gauche (2 premiers) et droite (2 derniers)
const leftItems = computed(() => items.value.slice(0, 2))
const rightItems = computed(() => items.value.slice(2, 4))

onMounted(() => {
  if (chartCanvas.value) {
    chartInstance = new Chart(chartCanvas.value, {
      type: 'doughnut',
      data: chartData,
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

/* Layout deux colonnes : gauche et droite */
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