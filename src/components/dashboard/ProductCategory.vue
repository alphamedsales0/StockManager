<template>
  <v-card rounded="lg" elevation="2" style="height: 100%;" class="category-distribution">
    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3 flex-wrap gap-2">
      <span class="text-h6 font-weight-semibold">
        <v-icon start size="20">mdi-chart-doughnut</v-icon>
        Produktverteilung
        <v-chip
          v-if="totalProducts > 0"
          size="x-small"
          color="primary"
          variant="tonal"
          class="ml-2"
        >
          {{ totalProducts }}
        </v-chip>
      </span>

      <div class="d-flex align-center ga-1 flex-wrap">
        <!-- ✅ Filtre Neu / Gebraucht / Alle -->
        <v-btn-toggle
          v-model="filterMode"
          density="compact"
          variant="outlined"
          divided
          mandatory
          class="filter-toggle"
        >
          <v-btn value="all" size="small">
            <v-icon size="16" start>mdi-view-grid</v-icon>
            Alle
          </v-btn>
          <v-btn value="new" size="small">
            <v-icon size="16" start>mdi-new-box</v-icon>
            Neu
          </v-btn>
          <v-btn value="used" size="small">
            <v-icon size="16" start>mdi-recycle-variant</v-icon>
            Gebraucht
          </v-btn>
        </v-btn-toggle>

        <v-btn
          icon
          variant="text"
          size="small"
          :color="refreshing ? 'primary' : 'grey-darken-1'"
          :disabled="refreshing"
          @click="refresh"
        >
          <v-icon size="20" :class="{ 'spin-icon': refreshing }">
            {{ refreshing ? 'mdi-loading' : 'mdi-refresh' }}
          </v-icon>
          <v-tooltip activator="parent" location="top">
            {{ refreshing ? 'Aktualisiere...' : 'Aktualisieren' }}
          </v-tooltip>
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text class="pa-4">
      <!-- ============================================================ -->
      <!-- LOADING -->
      <!-- ============================================================ -->
      <div v-if="productStore.loading && !categoryStats.length" class="py-8 text-center">
        <div class="chart-skeleton">
          <div class="skeleton-circle" />
          <div class="skeleton-legend">
            <div class="skeleton-line" v-for="n in 4" :key="n" />
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- ERREUR -->
      <!-- ============================================================ -->
      <div v-else-if="productStore.error" class="py-8 text-center">
        <v-icon size="48" color="error">mdi-alert-circle</v-icon>
        <p class="text-caption text-error mt-2 mb-3">{{ productStore.error }}</p>
        <v-btn size="small" color="primary" variant="tonal" @click="refresh">
          Erneut laden
        </v-btn>
      </div>

      <!-- ============================================================ -->
      <!-- VIDE -->
      <!-- ============================================================ -->
      <div v-else-if="categoryStats.length === 0" class="py-8 text-center">
        <v-icon size="56" color="grey-lighten-2">mdi-chart-doughnut</v-icon>
        <p class="text-body-2 text-grey-darken-1 mt-2 mb-0">
          {{ emptyMessage }}
        </p>
      </div>

      <!-- ============================================================ -->
      <!-- CHART + LÉGENDE -->
      <!-- ============================================================ -->
      <div v-else>
        <!-- Stats globales Neu vs Gebraucht -->
        <div class="global-stats mb-3">
          <v-chip
            size="small"
            color="success"
            variant="tonal"
            prepend-icon="mdi-new-box"
          >
            {{ newCount }} Neu
          </v-chip>
          <v-chip
            size="small"
            color="deep-orange"
            variant="tonal"
            prepend-icon="mdi-recycle-variant"
          >
            {{ usedCount }} Gebraucht
          </v-chip>
        </div>

        <!-- Chart -->
        <div class="chart-wrapper" :class="{ 'is-refreshing': refreshing }">
          <canvas ref="chartCanvas" width="200" height="200"></canvas>
          <div class="chart-center">
            <div class="chart-center-value">{{ totalProducts }}</div>
            <div class="chart-center-label">
              {{ filterMode === 'all' ? 'Produkte' : filterMode === 'new' ? 'Neu' : 'Gebraucht' }}
            </div>
          </div>
        </div>

        <!-- Légende 2 colonnes -->
        <div class="legend-two-columns mt-4">
          <div class="legend-col">
            <div
              v-for="item in leftItems"
              :key="item.key"
              class="legend-item"
              @click="goToCategory(item)"
            >
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <span class="legend-count">{{ item.count }}</span>
              <span class="legend-percent">{{ item.percentage }}%</span>
            </div>
          </div>
          <div class="legend-col">
            <div
              v-for="item in rightItems"
              :key="item.key"
              class="legend-item"
              @click="goToCategory(item)"
            >
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <span class="legend-count">{{ item.count }}</span>
              <span class="legend-percent">{{ item.percentage }}%</span>
            </div>
          </div>
        </div>

        <!-- Footer stats -->
        <div class="stats-footer mt-3">
          <v-icon size="14" color="grey">mdi-information-outline</v-icon>
          <span class="text-caption text-grey">
            {{ categoryStats.length }} Kategorien · {{ totalProducts }} Produkte
          </span>
        </div>
      </div>
    </v-card-text>

    <!-- ============================================================ -->
    <!-- SNACKBAR -->
    <!-- ============================================================ -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="2000"
      location="bottom right"
      variant="tonal"
    >
      {{ snackbar.text }}
    </v-snackbar>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Chart, registerables } from 'chart.js'
import { useProductStore } from '../../stores/stock_manager_products'

Chart.register(...registerables)

const router = useRouter()
const productStore = useProductStore()

// ============================================================
// STATE
// ============================================================
const chartCanvas = ref(null)
const refreshing = ref(false)
const filterMode = ref('all')   // 'all' | 'new' | 'used'
const snackbar = ref({ show: false, text: '', color: 'success' })

let chartInstance = null

// ============================================================
// PALETTE
// ============================================================
const colorPalette = [
  '#00b5e9', '#fa4251', '#00ad5f', '#ffc107',
  '#9c27b0', '#ff9800', '#795548', '#607d8b',
  '#e91e63', '#3f51b5', '#009688', '#cddc39',
  '#673ab7', '#ff5722', '#03a9f4', '#8bc34a'
]

// ============================================================
// LABELS CONVIVIAUX
// ============================================================
const categoryLabels = {
  'fitness': 'Fitnessgeräte',
  'hanteln': 'Hanteln',
  'cardio': 'Cardiogeräte',
  'rehabilitation': 'Rehabilitation',
  'rehabilitation_accessory': 'Zubehör',
  'accessories': 'Zubehör',
  'supplements': 'Nahrungsergänzung',
  'bekleidung': 'Bekleidung',
  'sonstiges': 'Sonstiges',
  'kraftgeraete': 'Kraftgeräte',
  'laufbaender': 'Laufbänder',
  'fahrrader': 'Fahrräder',
  'rudergeraete': 'Rudergeräte'
}

const prettyCategory = (key) => {
  if (!key) return 'Unbekannt'
  return categoryLabels[key] || key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}

// ============================================================
// HELPERS
// ============================================================
// ✅ Détecte si un produit est gebraucht
const isUsed = (p) => {
  const v = p.ist_gebraucht ?? p.is_used ?? p.isUsed
  return v === 1 || v === true || v === '1' || v === 'true'
}

// ✅ Récupère la catégorie avec fallback
const getCategory = (p) => {
  return (
    p.kategorie_schluessel ||
    p.category ||
    p.kategorie ||
    p.category_key ||
    'sonstiges'
  )
}

// ============================================================
// COMPUTED
// ============================================================
const allProducts = computed(() => productStore.products || [])

const newCount = computed(() => allProducts.value.filter(p => !isUsed(p)).length)
const usedCount = computed(() => allProducts.value.filter(p => isUsed(p)).length)

const filteredProducts = computed(() => {
  if (filterMode.value === 'new') return allProducts.value.filter(p => !isUsed(p))
  if (filterMode.value === 'used') return allProducts.value.filter(p => isUsed(p))
  return allProducts.value
})

const totalProducts = computed(() => filteredProducts.value.length)

const categoryStats = computed(() => {
  const products = filteredProducts.value
  if (!products.length) return []

  const categoryCount = {}
  products.forEach(p => {
    const cat = getCategory(p)
    categoryCount[cat] = (categoryCount[cat] || 0) + 1
  })

  const total = products.length
  return Object.entries(categoryCount)
    .map(([key, count], idx) => ({
      key,
      label: prettyCategory(key),
      count,
      percentage: Math.round((count / total) * 100),
      color: colorPalette[idx % colorPalette.length]
    }))
    .sort((a, b) => b.count - a.count)
})

const leftItems = computed(() => {
  const mid = Math.ceil(categoryStats.value.length / 2)
  return categoryStats.value.slice(0, mid)
})

const rightItems = computed(() => {
  const mid = Math.ceil(categoryStats.value.length / 2)
  return categoryStats.value.slice(mid)
})

const emptyMessage = computed(() => {
  if (filterMode.value === 'new') return 'Keine neuen Produkte verfügbar'
  if (filterMode.value === 'used') return 'Keine Gebrauchtgeräte verfügbar'
  return 'Keine Produktdaten verfügbar'
})

// ============================================================
// RENDER CHART
// ============================================================
const renderChart = async () => {
  await nextTick()

  if (!chartCanvas.value) return

  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  if (categoryStats.value.length === 0) return

  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: categoryStats.value.map(s => s.label),
      datasets: [{
        data: categoryStats.value.map(s => s.count),
        backgroundColor: categoryStats.value.map(s => s.color),
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.85)',
          padding: 10,
          cornerRadius: 6,
          callbacks: {
            label: (ctx) => {
              const stat = categoryStats.value[ctx.dataIndex]
              return ` ${stat.label}: ${stat.count} (${stat.percentage}%)`
            }
          }
        }
      },
      onClick: (evt, elements) => {
        if (elements.length > 0) {
          const idx = elements[0].index
          goToCategory(categoryStats.value[idx])
        }
      }
    }
  })
}

// ============================================================
// REFRESH
// ============================================================
const refresh = async () => {
  refreshing.value = true
  const start = Date.now()

  try {
    await productStore.fetchProducts()
    snackbar.value = {
      show: true,
      text: `${productStore.products.length} Produkte geladen`,
      color: 'success'
    }
  } catch (e) {
    snackbar.value = {
      show: true,
      text: 'Fehler beim Laden',
      color: 'error'
    }
  } finally {
    const elapsed = Date.now() - start
    if (elapsed < 400) {
      await new Promise(r => setTimeout(r, 400 - elapsed))
    }
    refreshing.value = false
  }
}

// ============================================================
// NAVIGATION
// ============================================================
const goToCategory = (item) => {
  const query = { category: item.key }
  if (filterMode.value === 'used') {
    query.used = '1'
  } else if (filterMode.value === 'new') {
    query.used = '0'
  }
  router.push({ path: '/products', query })
}

// ============================================================
// WATCH
// ============================================================
watch(categoryStats, () => {
  renderChart()
}, { immediate: false })

watch(filterMode, () => {
  renderChart()
})

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(async () => {
  if (productStore.products.length === 0 && !productStore.loading) {
    await productStore.fetchProducts()
  }
  renderChart()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<style scoped>
/* ============================================================
   BASE
   ============================================================ */
.category-distribution {
  height: 100%;
  display: flex;
  flex-direction: column;
}

/* ============================================================
   FILTER TOGGLE
   ============================================================ */
.filter-toggle {
  background: #f5f5f5;
  border-radius: 6px;
}

.filter-toggle :deep(.v-btn) {
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 0.72rem !important;
  letter-spacing: 0.2px !important;
  padding: 0 8px !important;
}

/* ============================================================
   SPIN ICON
   ============================================================ */
.spin-icon {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ============================================================
   GLOBAL STATS
   ============================================================ */
.global-stats {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* ============================================================
   CHART
   ============================================================ */
.chart-wrapper {
  position: relative;
  width: 200px;
  height: 200px;
  margin: 0 auto;
  transition: opacity 0.25s ease;
}

.chart-wrapper.is-refreshing {
  opacity: 0.5;
}

canvas {
  max-width: 200px;
  max-height: 200px;
}

.chart-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}

.chart-center-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: #1a202c;
  line-height: 1;
}

.chart-center-label {
  font-size: 0.7rem;
  color: #90a4ae;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-top: 4px;
}

/* ============================================================
   LÉGENDE
   ============================================================ */
.legend-two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 16px;
  max-width: 100%;
  max-height: 240px;
  overflow-y: auto;
  padding-right: 4px;
}

.legend-two-columns::-webkit-scrollbar {
  width: 4px;
}
.legend-two-columns::-webkit-scrollbar-track {
  background: transparent;
}
.legend-two-columns::-webkit-scrollbar-thumb {
  background: #cfd8dc;
  border-radius: 2px;
}

.legend-col {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  font-size: 0.8rem;
  min-width: 0;
}

.legend-item:hover {
  background: #f5f5f5;
  transform: translateX(2px);
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.legend-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #37474f;
  font-weight: 500;
}

.legend-count {
  font-weight: 700;
  color: #1a202c;
  font-size: 0.78rem;
}

.legend-percent {
  color: #90a4ae;
  font-size: 0.72rem;
  font-weight: 600;
  min-width: 32px;
  text-align: right;
}

/* ============================================================
   STATS FOOTER
   ============================================================ */
.stats-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding-top: 8px;
  border-top: 1px dashed #e0e0e0;
}

/* ============================================================
   SKELETON
   ============================================================ */
.chart-skeleton {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.skeleton-circle {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  max-width: 280px;
}

.skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 600px) {
  .chart-wrapper {
    width: 180px;
    height: 180px;
  }

  canvas {
    max-width: 180px;
    max-height: 180px;
  }

  .chart-center-value {
    font-size: 1.5rem;
  }

  .legend-two-columns {
    grid-template-columns: 1fr;
    max-height: 200px;
  }

  .legend-item {
    font-size: 0.75rem;
  }

  .filter-toggle :deep(.v-btn) {
    font-size: 0.68rem !important;
    padding: 0 6px !important;
  }
}
</style>