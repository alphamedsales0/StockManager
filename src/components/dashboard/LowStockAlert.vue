<template>
  <v-card rounded="lg" elevation="2" style="height: 100%; display: flex; flex-direction: column;">
    <!-- Kopfzeile -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3">
      <span class="text-h6 font-weight-semibold">Niedriger Lagerbestand</span>
      <div class="d-flex align-center ga-1">
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-fullscreen</v-icon>
        </v-btn>
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-refresh</v-icon>
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text class="pa-3" style="flex: 1;">
      <!-- Fallback: Keine ausverkauften Produkte -> Icon + Text -->
      <div v-if="outOfStockProducts.length === 0" class="text-center pa-6">
        <v-icon size="64" color="success">mdi-check-circle</v-icon>
        <div class="text-h6 mt-2">Alle Produkte auf Lager</div>
        <div class="text-caption text-grey">Keine ausverkauften Artikel</div>
      </div>

      <!-- Chart + Liste nur bei ausverkauften Produkten -->
      <div v-else>
        <!-- kleiner Donut-Chart (zentriert) -->
        <div style="position: relative; width: 180px; height: 180px; margin: 0 auto;">
          <canvas ref="pieChartCanvas" width="180" height="180"></canvas>
        </div>

        <!-- Liste der ausverkauften Produkte -->
        <v-list density="compact" class="mt-2">
          <v-list-item
            v-for="product in outOfStockProducts"
            :key="product.id"
            @click="viewProduct(product)"
            style="cursor: pointer;"
          >
            <template v-slot:prepend>
              <div :style="{ width: '12px', height: '12px', borderRadius: '50%', backgroundColor: categoryColors[product.category] || '#607d8b' }"></div>
            </template>
            <v-list-item-title>
              {{ product.name }}
              <span class="text-caption text-grey">({{ product.brand }})</span>
            </v-list-item-title>
            <template v-slot:append>
              <v-chip color="error" size="x-small">Ausverkauft</v-chip>
            </template>
          </v-list-item>
        </v-list>
      </div>
    </v-card-text>

    <!-- Footer mit Button (nur bei ausverkauften Produkten) -->
    <template v-if="outOfStockProducts.length > 0">
      <v-divider></v-divider>
      <v-card-actions class="pa-2">
        <v-btn color="warning" variant="tonal" block size="small" @click="viewAllOutOfStock">
          Alle ausverkauften Produkte anzeigen
        </v-btn>
      </v-card-actions>
    </template>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Chart, registerables } from 'chart.js'
import { useProductStore } from '../../stores/stock_manager_products'

Chart.register(...registerables)

const router = useRouter()
const productStore = useProductStore()

// Nur Artikel mit in_stock = false oder 0
const outOfStockProducts = computed(() =>
  (productStore.products || []).filter(p => p.in_stock === false || p.in_stock === 0)
)

// Konsistente Farben pro Kategorie
const categoryColors = {
  'cardio': '#00b5e9',
  'strength': '#fa4251',
  'rehabilitation': '#00ad5f',
  'accessories': '#ffc107',
  'Autre': '#9c27b0'
}

// Daten für das Kreisdiagramm (Gruppierung nach Kategorie)
const categoryStats = computed(() => {
  const groups = {}
  outOfStockProducts.value.forEach(p => {
    const cat = p.category || 'Autre'
    groups[cat] = (groups[cat] || 0) + 1
  })
  return Object.entries(groups).map(([category, count]) => ({
    category,
    count,
    color: categoryColors[category] || '#607d8b'
  }))
})

const pieChartCanvas = ref(null)
let chartInstance = null

// Donut-Chart rendern (nur bei vorhandenen ausverkauften Produkten)
const renderPieChart = async () => {
  // Nur rendern, wenn es ausverkaufte Produkte gibt und das Canvas existiert
  if (outOfStockProducts.value.length === 0) {
    if (chartInstance) {
      chartInstance.destroy()
      chartInstance = null
    }
    return
  }

  await nextTick()
  if (!pieChartCanvas.value) return

  if (chartInstance) chartInstance.destroy()

  const ctx = pieChartCanvas.value.getContext('2d')
  if (!ctx) return

  chartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: categoryStats.value.map(s => s.category),
      datasets: [{
        data: categoryStats.value.map(s => s.count),
        backgroundColor: categoryStats.value.map(s => s.color),
        borderWidth: 2,
        borderColor: '#fff',
        cutout: '65%'    // moderner Donut-Stil
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.label || ''
              const value = context.raw
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percent = ((value / total) * 100).toFixed(1)
              return `${label}: ${value} Produkt(e) (${percent}%)`
            }
          }
        },
        legend: { display: false }
      }
    }
  })
}

// Chart neu zeichnen, wenn sich die ausverkauften Produkte ändern
watch(() => outOfStockProducts.value, () => {
  renderPieChart()
}, { deep: true })

// Zusätzlicher Watch, um sicherzustellen, dass die Produkte geladen sind
watch(() => productStore.products, () => {
  renderPieChart()
}, { immediate: true })

onMounted(() => {
  renderPieChart()
})

onBeforeUnmount(() => {
  if (chartInstance) chartInstance.destroy()
})

// Navigation zur Produktdetailseite
const viewProduct = (product) => {
  router.push(`/products/${product.id}`)
}

// Navigation zur Produktliste mit Filter "ausverkauft"
const viewAllOutOfStock = () => {
  router.push('/products?filter=out_of_stock')
}
</script>

<style scoped>
canvas {
  display: block;
  width: 100% !important;
  height: 100% !important;
}
</style>