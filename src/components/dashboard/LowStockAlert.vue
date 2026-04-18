<!-- src/components/LowStockAlert.vue (version Pie Chart) -->
<template>
  <v-card rounded="lg" elevation="2" style="height: 100%; display: flex; flex-direction: column;">
    <v-card-title class="text-h6 pa-3 bg-grey-lighten-4">
      ⚠️ Niedriger Lagerbestand (≤ {{ lowStockThreshold }})
    </v-card-title>

    <v-card-text class="pa-3" style="flex: 1;">
      <!-- Message si aucun produit critique -->
      <div v-if="lowStockProducts.length === 0" class="text-center pa-6 text-grey">
        ✅ Keine Produkte mit niedrigem Bestand 
      </div>

      <!-- Pie chart + légende -->
      <div v-else>
        <canvas ref="pieChartCanvas" style="max-height: 220px; width: 100%;"></canvas>
        
        <!-- Légende interactive -->
        <v-list density="compact" class="mt-2">
          <v-list-item
            v-for="(product, index) in lowStockProducts"
            :key="product.id"
            @click="handleReorder(product)"
            style="cursor: pointer;"
          >
            <template v-slot:prepend>
              <div :style="{ width: '12px', height: '12px', borderRadius: '50%', backgroundColor: colors[index] }"></div>
            </template>
            <v-list-item-title>
              {{ product.name }}
              <span class="text-caption text-grey">({{ product.brand }})</span>
            </v-list-item-title>
            <template v-slot:append>
              <div class="font-weight-bold" :class="{ 'text-red': product.stock <= criticalThreshold }">
                {{ product.stock }} St.
              </div>
            </template>
          </v-list-item>
        </v-list>
      </div>
    </v-card-text>

    <v-divider v-if="lowStockProducts.length > 0"></v-divider>
    <v-card-actions v-if="lowStockProducts.length > 0" class="pa-2">
      <v-btn color="warning" variant="tonal" block size="small" @click="handleBulkReorder">
        Alle nachbestellen
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

// Seuils
const lowStockThreshold = 5
const criticalThreshold = 2

// Données simulées (à remplacer par votre store)
const allProducts = ref([
  { id: 1, name: 'K700 Mill Tour Basic', brand: 'Ergo-Fit', stock: 2 },
  { id: 2, name: 'CYCLE MED Cardio 4000', brand: 'Ergo-Fit', stock: 5 },
  { id: 3, name: 'CYCLE 407 MED', brand: 'Ergo-Fit', stock: 3 }
])

const lowStockProducts = computed(() =>
  allProducts.value.filter(p => p.stock <= lowStockThreshold)
)

// Génération de couleurs distinctes pour chaque secteur
const colors = computed(() => {
  return lowStockProducts.value.map((p, idx) => {
    const hue = (idx * 137.5) % 360 // golden angle
    return `hsl(${hue}, 70%, 60%)`
  })
})

const pieChartCanvas = ref(null)
let chartInstance = null

const renderPieChart = () => {
  if (!pieChartCanvas.value || lowStockProducts.value.length === 0) return
  if (chartInstance) chartInstance.destroy()

  const ctx = pieChartCanvas.value.getContext('2d')
  chartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: lowStockProducts.value.map(p => p.name),
      datasets: [{
        data: lowStockProducts.value.map(p => p.stock),
        backgroundColor: colors.value,
        borderWidth: 2,
        borderColor: '#fff'
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
              const percentage = ((value / total) * 100).toFixed(1)
              return `${label}: ${value} Stück (${percentage}%)`
            }
          }
        },
        legend: {
          display: false // On utilise une légende personnalisée
        }
      }
    }
  })
}

const handleReorder = (product) => {
  alert(`Bestellung für ${product.name} (Bestand: ${product.stock} Stück) wird ausgelöst.`)
}

const handleBulkReorder = () => {
  const names = lowStockProducts.value.map(p => p.name).join('\n')
  alert(`Alle Produkte nachbestellen:\n${names}`)
}

watch(lowStockProducts, () => {
  renderPieChart()
}, { deep: true })

onMounted(() => {
  renderPieChart()
})

onBeforeUnmount(() => {
  if (chartInstance) chartInstance.destroy()
})
</script>

<style scoped>
canvas {
  max-height: 220px;
  width: 100%;
  margin-bottom: 8px;
}
</style>