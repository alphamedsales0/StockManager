<template>
  <v-card
    rounded="lg"
    elevation="2"
    class="low-stock-card"
    :class="{ 'is-fullscreen': isFullscreen }"
  >
    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3">
      <span class="text-h6 font-weight-semibold">
        <v-icon start size="20" color="warning">mdi-alert-outline</v-icon>
        Niedriger Lagerbestand
        <v-chip
          v-if="outOfStockProducts.length"
          size="x-small"
          color="error"
          variant="flat"
          class="ml-2"
        >
          {{ outOfStockProducts.length }}
        </v-chip>
      </span>

      <div class="d-flex align-center ga-1">
        <v-btn
          icon
          variant="text"
          size="small"
          color="grey-darken-1"
          @click="toggleFullscreen"
        >
          <v-icon size="20">{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
          <v-tooltip activator="parent" location="top">
            {{ isFullscreen ? 'Verkleinern' : 'Vergrößern' }}
          </v-tooltip>
        </v-btn>

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

    <v-card-text class="pa-3" style="flex: 1; overflow-y: auto;">
      <!-- ============================================================ -->
      <!-- LOADING -->
      <!-- ============================================================ -->
      <div v-if="productStore.loading && !outOfStockProducts.length" class="py-6">
        <div class="chart-skeleton">
          <div class="skeleton-circle" />
          <div class="skeleton-lines">
            <div class="skeleton-line" v-for="n in 3" :key="n" />
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- ERREUR -->
      <!-- ============================================================ -->
      <div v-else-if="productStore.error" class="text-center pa-6">
        <v-icon size="48" color="error">mdi-alert-circle</v-icon>
        <p class="text-caption text-error mt-2 mb-3">{{ productStore.error }}</p>
        <v-btn size="small" color="primary" variant="tonal" @click="refresh">
          Erneut laden
        </v-btn>
      </div>

      <!-- ============================================================ -->
      <!-- VIDE — Tout est en stock -->
      <!-- ============================================================ -->
      <div v-else-if="outOfStockProducts.length === 0" class="text-center pa-6">
        <v-icon size="64" color="success">mdi-check-circle</v-icon>
        <div class="text-h6 mt-2">Alle Produkte auf Lager</div>
        <div class="text-caption text-grey">Keine ausverkauften Artikel</div>
      </div>

      <!-- ============================================================ -->
      <!-- CHART + LISTE -->
      <!-- ============================================================ -->
      <div v-else>
        <!-- Donut chart avec total au centre -->
        <div class="chart-wrapper" :class="{ 'is-refreshing': refreshing }">
          <canvas ref="pieChartCanvas" width="180" height="180"></canvas>
          <div class="chart-center">
            <div class="chart-center-value">{{ outOfStockProducts.length }}</div>
            <div class="chart-center-label">Ausverkauft</div>
          </div>
        </div>

        <!-- Légende 2 colonnes compacte -->
        <div v-if="categoryStats.length" class="legend-two-columns mt-3 mb-2">
          <div class="legend-col">
            <div
              v-for="item in leftCategories"
              :key="item.category"
              class="legend-item"
              @click="goToCategory(item.category)"
            >
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <span class="legend-count">{{ item.count }}</span>
            </div>
          </div>
          <div class="legend-col">
            <div
              v-for="item in rightCategories"
              :key="item.category"
              class="legend-item"
              @click="goToCategory(item.category)"
            >
              <span class="dot" :style="{ backgroundColor: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <span class="legend-count">{{ item.count }}</span>
            </div>
          </div>
        </div>

        <!-- Liste des produits en rupture -->
        <div class="out-of-stock-list">
          <transition-group name="list">
            <div
              v-for="product in outOfStockProducts.slice(0, maxItems)"
              :key="product.id"
              class="stock-item"
              @click="viewProduct(product)"
            >
              <div
                class="stock-dot"
                :style="{ backgroundColor: categoryColors[getCategory(product)] || '#607d8b' }"
              ></div>

              <div class="stock-info">
                <div class="stock-name">{{ getProductName(product) }}</div>
                <div class="stock-meta">
                  <span v-if="product.brand">{{ product.brand }}</span>
                  <span v-if="product.article_number" class="stock-article">
                    · {{ product.article_number }}
                  </span>
                </div>
              </div>

              <v-chip color="error" size="x-small" variant="flat" class="flex-shrink-0">
                <v-icon start size="10">mdi-close-circle</v-icon>
                Ausverkauft
              </v-chip>
            </div>
          </transition-group>

          <!-- Indication "+ X weitere" -->
          <div v-if="outOfStockProducts.length > maxItems" class="more-indicator">
            <v-btn
              variant="text"
              size="small"
              color="primary"
              block
              @click="viewAllOutOfStock"
            >
              <v-icon start size="16">mdi-plus</v-icon>
              {{ outOfStockProducts.length - maxItems }} weitere anzeigen
            </v-btn>
          </div>
        </div>
      </div>
    </v-card-text>

    <!-- ============================================================ -->
    <!-- FOOTER avec bouton "Alle anzeigen" -->
    <!-- ============================================================ -->
    <template v-if="outOfStockProducts.length > 0">
      <v-divider></v-divider>
      <v-card-actions class="pa-2">
        <v-btn
          color="warning"
          variant="tonal"
          block
          size="small"
          prepend-icon="mdi-arrow-right"
          @click="viewAllOutOfStock"
        >
          Alle ausverkauften Produkte anzeigen
        </v-btn>
      </v-card-actions>
    </template>

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
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Chart, registerables } from 'chart.js'
import { useProductStore } from '../../stores/stock_manager_products'

Chart.register(...registerables)

const router = useRouter()
const productStore = useProductStore()

// ============================================================
// CONFIG
// ============================================================
const maxItems = 4
const POLL_INTERVAL = 60000

// ============================================================
// STATE
// ============================================================
const pieChartCanvas = ref(null)
const isFullscreen = ref(false)
const refreshing = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

let chartInstance = null
let pollInterval = null

// ============================================================
// PALETTE
// ============================================================
const colorPalette = [
  '#00b5e9', '#fa4251', '#00ad5f', '#ffc107',
  '#9c27b0', '#ff9800', '#795548', '#607d8b',
  '#e91e63', '#3f51b5', '#009688', '#cddc39'
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
  'strength': 'Kraftgeräte',
  'supplements': 'Nahrungsergänzung',
  'bekleidung': 'Bekleidung',
  'sonstiges': 'Sonstiges',
  'Autre': 'Sonstiges'
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
// Détecte si un produit est EN STOCK
const isInStock = (p) => {
  const v = p.auf_lager ?? p.in_stock ?? p.inStock
  return v === 1 || v === true || v === '1' || v === 'true'
}

// Récupère la catégorie avec fallback
const getCategory = (p) => {
  return (
    p.kategorie_schluessel ||
    p.category ||
    p.kategorie ||
    'sonstiges'
  )
}

// Récupère le nom
const getProductName = (p) => p.bezeichnung || p.name || '—'

// ============================================================
// COMPUTED
// ============================================================
const allProducts = computed(() => productStore.products || [])

const outOfStockProducts = computed(() =>
  allProducts.value.filter(p => !isInStock(p))
)

const categoryColors = computed(() => {
  const map = {}
  const cats = new Set(outOfStockProducts.value.map(p => getCategory(p)))
  Array.from(cats).forEach((cat, idx) => {
    map[cat] = colorPalette[idx % colorPalette.length]
  })
  return map
})

const categoryStats = computed(() => {
  const groups = {}
  outOfStockProducts.value.forEach(p => {
    const cat = getCategory(p)
    groups[cat] = (groups[cat] || 0) + 1
  })
  return Object.entries(groups)
    .map(([category, count]) => ({
      category,
      label: prettyCategory(category),
      count,
      color: categoryColors.value[category] || '#607d8b'
    }))
    .sort((a, b) => b.count - a.count)
})

const leftCategories = computed(() => {
  const mid = Math.ceil(categoryStats.value.length / 2)
  return categoryStats.value.slice(0, mid)
})

const rightCategories = computed(() => {
  const mid = Math.ceil(categoryStats.value.length / 2)
  return categoryStats.value.slice(mid)
})

// ============================================================
// RENDER CHART
// ============================================================
const renderPieChart = async () => {
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
      labels: categoryStats.value.map(s => s.label),
      datasets: [{
        data: categoryStats.value.map(s => s.count),
        backgroundColor: categoryStats.value.map(s => s.color),
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '68%',
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.85)',
          padding: 10,
          cornerRadius: 6,
          callbacks: {
            label: (context) => {
              const label = context.label || ''
              const value = context.raw
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percent = ((value / total) * 100).toFixed(1)
              return ` ${label}: ${value} (${percent}%)`
            }
          }
        },
        legend: { display: false }
      },
      onClick: (evt, elements) => {
        if (elements.length > 0) {
          const idx = elements[0].index
          goToCategory(categoryStats.value[idx].category)
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
      text: `${outOfStockProducts.value.length} ausverkaufte Produkte`,
      color: outOfStockProducts.value.length ? 'warning' : 'success'
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
const viewProduct = (product) => {
  router.push(`/products/view/${product.id}`)
}

const goToCategory = (category) => {
  router.push({ path: '/products', query: { category } })
}

const viewAllOutOfStock = () => {
  router.push({ path: '/products', query: { stock: 'out' } })
}

// ============================================================
// FULLSCREEN
// ============================================================
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

// ============================================================
// WATCH
// ============================================================
watch(outOfStockProducts, () => {
  renderPieChart()
}, { deep: true })

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(async () => {
  if (productStore.products.length === 0 && !productStore.loading) {
    await productStore.fetchProducts()
  }
  renderPieChart()

  pollInterval = setInterval(() => {
    if (!refreshing.value) {
      productStore.fetchProducts()
    }
  }, POLL_INTERVAL)
})

onBeforeUnmount(() => {
  if (chartInstance) chartInstance.destroy()
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
/* ============================================================
   BASE
   ============================================================ */
.low-stock-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
}

.low-stock-card.is-fullscreen {
  position: fixed;
  inset: 20px;
  z-index: 2000;
  max-height: calc(100vh - 40px);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3) !important;
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
   CHART WRAPPER
   ============================================================ */
.chart-wrapper {
  position: relative;
  width: 180px;
  height: 180px;
  margin: 0 auto;
  transition: opacity 0.25s ease;
}

.chart-wrapper.is-refreshing {
  opacity: 0.5;
}

canvas {
  display: block;
  width: 100% !important;
  height: 100% !important;
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
  font-size: 1.6rem;
  font-weight: 800;
  color: #c62828;
  line-height: 1;
}

.chart-center-label {
  font-size: 0.65rem;
  color: #90a4ae;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  font-weight: 600;
  margin-top: 4px;
}

/* ============================================================
   LÉGENDE 2 COLONNES
   ============================================================ */
.legend-two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 12px;
  padding: 0 4px;
}

.legend-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 3px 6px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
  font-size: 0.75rem;
  min-width: 0;
}

.legend-item:hover {
  background: #f5f5f5;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
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
  font-size: 0.72rem;
  flex-shrink: 0;
}

/* ============================================================
   LISTE PRODUITS
   ============================================================ */
.out-of-stock-list {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #e0e0e0;
}

.stock-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
  border-bottom: 1px solid #f5f5f5;
}

.stock-item:last-child {
  border-bottom: none;
}

.stock-item:hover {
  background: #fff8e1;
  transform: translateX(2px);
}

.stock-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

.stock-info {
  flex: 1;
  min-width: 0;
}

.stock-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #1a202c;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

.stock-meta {
  font-size: 0.7rem;
  color: #90a4ae;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 1px;
}

.stock-article {
  font-style: italic;
  margin-left: 2px;
}

/* ============================================================
   "+ X weitere"
   ============================================================ */
.more-indicator {
  margin-top: 6px;
  padding-top: 6px;
  border-top: 1px dashed #e0e0e0;
}

.more-indicator .v-btn {
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 0.78rem !important;
  letter-spacing: 0.2px !important;
}

/* ============================================================
   ANIMATION LISTE
   ============================================================ */
.list-enter-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-leave-active {
  transition: all 0.3s ease;
  position: absolute;
  width: 100%;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.list-move {
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ============================================================
   SKELETON
   ============================================================ */
.chart-skeleton {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.skeleton-circle {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-lines {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  max-width: 240px;
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
    width: 160px;
    height: 160px;
  }

  .chart-center-value {
    font-size: 1.4rem;
  }

  .legend-two-columns {
    grid-template-columns: 1fr;
  }

  .stock-name {
    font-size: 0.78rem;
  }

  .stock-meta {
    font-size: 0.65rem;
  }

  .low-stock-card.is-fullscreen {
    inset: 8px;
  }
}
</style>