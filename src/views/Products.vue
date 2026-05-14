<template>
  <v-container fluid class="products-container px-2 px-sm-3">
    <!-- Header -->
    <v-row class="mb-3">
      <v-col cols="12">
        <div class="d-flex flex-wrap align-center justify-space-between gap-2">
          <div>
            <h1 class="text-h4 font-weight-bold gradient-text">Unsere Produkte</h1>
            <p class="text-caption text-medium-emphasis mt-1">Fitnessgeräte & Zubehör</p>
          </div>
          <v-btn color="primary" variant="elevated" prepend-icon="mdi-plus" size="small" rounded="lg" @click="addProduct">
            Neu
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- Filtres -->
    <v-row class="mb-3">
      <v-col cols="12">
        <v-card variant="flat" class="filter-card" rounded="lg">
          <v-card-text class="pa-2 pa-sm-3">
            <v-row dense align="center" class="mx-0">
              <v-col cols="12" sm="5" md="4" class="px-1">
                <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  placeholder="Suchen..."
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                  @update:model-value="onFilterChange"
                />
              </v-col>
              <v-col cols="6" sm="3" md="2" class="px-1">
                <v-select
                  v-model="selectedCategory"
                  :items="categoryOptions"
                  label="Kategorie"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                  @update:model-value="onFilterChange"
                />
              </v-col>
              <v-col cols="6" sm="3" md="2" class="px-1">
                <v-select
                  v-model="stockFilter"
                  :items="stockOptions"
                  label="Lager"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                />
              </v-col>
              <v-col cols="12" sm="1" class="text-end px-1">
                <v-btn
                  icon="mdi-filter-remove"
                  variant="text"
                  size="small"
                  color="grey"
                  @click="resetFilters"
                  v-if="hasActiveFilters"
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Chargement -->
    <v-row v-if="loading" class="compact-grid">
      <v-col v-for="n in itemsPerPage" :key="n" :cols="6" :sm="4" :md="3" :lg="12/5" class="pa-1 pa-sm-2">
        <v-skeleton-loader type="image, article, actions" class="rounded-lg" />
      </v-col>
    </v-row>

    <!-- Grille produits -->
    <v-row v-else-if="paginatedProducts.length" class="compact-grid">
      <v-col
        v-for="product in paginatedProducts"
        :key="product.id"
        :cols="6"
        :sm="4"
        :md="3"
        :lg="12/5"
        class="pa-1 pa-sm-2"
      >
        <v-card class="product-card" elevation="2">
          <div class="card-image" @click="editProduct(product.id)">
            <div class="image-container">
              <v-img
                :src="getImageUrl(product)"
                :alt="product.name"
                height="200"
                cover
                class="product-image"
                @error="() => handleImageError(product)"
                :lazy-src="placeholderImage"
              >
                <template v-slot:placeholder>
                  <div class="image-placeholder">
                    <v-icon size="64" color="grey-lighten-1">mdi-image</v-icon>
                    <div class="placeholder-text">{{ product.brand }}</div>
                  </div>
                </template>
              </v-img>

              <!-- Badges -->
              <div class="absolute-badges">
                <div class="badges-container">
                  <v-chip v-if="product.isNew" color="success" size="small" class="new-badge">
                    <v-icon small left>mdi-new-box</v-icon>
                    Neu
                  </v-chip>
                  <v-chip v-if="product.bestSeller" color="warning" size="small" class="best-badge">
                    <v-icon small left>mdi-trophy</v-icon>
                    Bestseller
                  </v-chip>
                </div>
              </div>
            </div>
          </div>

          <v-card-title class="product-title" @click="editProduct(product.id)">
            {{ truncate(product.name, 40) }}
          </v-card-title>

          <v-card-subtitle class="product-brand">
            {{ product.brand }}
            <span v-if="product.article_number" class="article-number">
              (Art.-Nr.: {{ product.article_number }})
            </span>
          </v-card-subtitle>

          <v-card-text class="product-features">
            <div v-if="product.features && product.features.length">
              <div v-for="feature in product.features.slice(0, 3)" :key="feature" class="feature">
                <v-icon small color="primary">mdi-check</v-icon>
                <span>{{ feature }}</span>
              </div>
            </div>
            <div v-else>
              <div v-if="product.weight_capacity" class="feature">
                <v-icon small color="primary">mdi-check</v-icon>
                <span>Belastbarkeit: {{ product.weight_capacity }} kg</span>
              </div>
              <div v-if="product.warranty_years" class="feature">
                <v-icon small color="primary">mdi-check</v-icon>
                <span>Garantie: {{ product.warranty_years }} Jahre</span>
              </div>
              <div v-if="product.category" class="feature">
                <v-icon small color="primary">mdi-check</v-icon>
                <span>Kategorie: {{ product.category }}</span>
              </div>
            </div>
          </v-card-text>

          <v-card-actions class="product-actions">
            <div class="price">{{ formatPrice(product.price) }}</div>
            <v-spacer />
            <!-- Bouton Bearbeiten -->
            <v-btn color="primary" @click.stop="editProduct(product.id)">
              <v-icon>mdi-pencil</v-icon>
              <span class="ml-1">Bearbeiten</span>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Pagination -->
    <v-row v-if="filteredProducts.length && !loading" class="mt-2">
      <v-col cols="12" class="d-flex flex-wrap justify-center align-center ga-2">
        <div class="d-flex align-center">
          <span class="text-caption me-1">Zeilen:</span>
          <v-select v-model="itemsPerPage" :items="[8,12,16,24,32]" variant="outlined" density="compact" style="width:70px" hide-details rounded="lg" />
        </div>
        <v-pagination v-model="currentPage" :length="totalPages" :total-visible="5" size="small" rounded="circle" color="primary" variant="tonal" />
        <span class="text-caption text-medium-emphasis">{{ (currentPage-1)*itemsPerPage+1 }}–{{ Math.min(currentPage*itemsPerPage, filteredProducts.length) }}</span>
      </v-col>
    </v-row>

    <!-- Erreur -->
    <v-row v-if="error" class="mt-4">
      <v-col cols="12" class="text-center">
        <v-alert type="error" variant="tonal" closable @click:close="error = null">
          {{ error }}
        </v-alert>
      </v-col>
    </v-row>

    <!-- Aucun résultat -->
    <v-row v-if="!loading && !error && filteredProducts.length === 0" class="mt-8">
      <v-col cols="12" class="text-center">
        <v-icon size="48" color="grey-lighten-1" icon="mdi-package-variant-closed" />
        <p class="text-body-2 mt-2 mb-0">Keine Produkte gefunden</p>
        <v-btn variant="text" size="small" @click="resetFilters">Filter zurücksetzen</v-btn>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="2000" location="top" variant="tonal">
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// État
const products = ref([])
const loading = ref(false)
const error = ref(null)

// Filtres UI
const search = ref('')
const selectedCategory = ref(null)
const stockFilter = ref(null)

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(12)

const snackbar = ref({ show: false, text: '', color: 'success' })
const imageErrors = ref({})

// API
const API_BASE = '/api'

async function loadProducts() {
  loading.value = true
  error.value = null
  try {
    const url = `${API_BASE}/get_all_products.php`
    const response = await fetch(url)
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const data = await response.json()
    if (!data.success) throw new Error(data.error || 'Erreur API')
    products.value = data.products || []
    imageErrors.value = {}
  } catch (err) {
    console.error(err)
    error.value = err.message
    products.value = []
  } finally {
    loading.value = false
  }
}

// Helper images
const placeholderImage = 'https://placehold.co/400x300?text=Kein+Bild'

function getImageUrl(product) {
  if (imageErrors.value[product.id]) return placeholderImage
  const image = product.main_image || product.image
  if (!image) return placeholderImage
  if (image.startsWith('http://') || image.startsWith('https://')) return image
  if (image.startsWith('/')) return `https://alpha-med-care.com${image}`
  return image
}

function handleImageError(product) {
  imageErrors.value[product.id] = true
}

function formatPrice(price) {
  if (!price && price !== 0) return 'Preis auf Anfrage'
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(price)
}

// Filtres
const categoryOptions = computed(() => {
  const cats = new Set(products.value.map(p => p.category).filter(Boolean))
  return Array.from(cats).sort()
})

const stockOptions = [
  { title: 'Auf Lager', value: true },
  { title: 'Nicht lagernd', value: false },
]

const filteredByCategoryAndStock = computed(() => {
  let result = products.value
  if (selectedCategory.value) result = result.filter(p => p.category === selectedCategory.value)
  if (stockFilter.value !== null) result = result.filter(p => p.inStock === stockFilter.value)
  return result
})

const filteredProducts = computed(() => {
  if (!search.value) return filteredByCategoryAndStock.value
  const term = search.value.toLowerCase()
  return filteredByCategoryAndStock.value.filter(p =>
    p.name?.toLowerCase().includes(term) ||
    p.brand?.toLowerCase().includes(term) ||
    p.article_number?.toLowerCase().includes(term)
  )
})

const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage.value))
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredProducts.value.slice(start, start + itemsPerPage.value)
})

const onFilterChange = () => { currentPage.value = 1 }
watch(stockFilter, () => { currentPage.value = 1 })

const hasActiveFilters = computed(() => !!selectedCategory.value || stockFilter.value !== null || !!search.value)

function resetFilters() {
  search.value = ''
  selectedCategory.value = null
  stockFilter.value = null
  onFilterChange()
}

// Navigation
function viewProduct(id) {
  router.push(`/product/${id}`)
}
function addProduct() {
  router.push('/products/add')
}
function editProduct(id) {
  router.push(`/products/edit/${id}`)
}

const truncate = (str, len) => str?.length > len ? str.slice(0, len) + '…' : str

loadProducts()
</script>

<style scoped>
/* Tous les styles identiques à votre version (inchangés) */
.products-container { max-width: 1800px;
   margin: 0 auto;
}
.gradient-text { background: linear-gradient(135deg, #1976D2, #42A5F5);
   -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.filter-card { background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.05); }
.compact-grid { margin: -4px -4px 0 -4px; }
.compact-grid > [class*="col-"] { padding: 4px !important; }
.product-card {
  height: 100%;
  transition: transform 0.3s;
  display: flex;
  flex-direction: column;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
}
.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.card-image {
  position: relative;
  overflow: hidden;
  cursor: pointer;
  min-height: 200px;
}
.image-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
}
.product-image {
  width: auto;
  max-width: 50%;
  height: auto;
  max-height: 100px;
  object-fit: contain;
}
.absolute-badges {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 10;
}
.badges-container {
  position: absolute;
  top: 10px;
  left: 10px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  pointer-events: auto;
}
.new-badge {
  background-color: #4CAF50 !important;
  color: white !important;
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.best-badge {
  background-color: #FF9800 !important;
  color: white !important;
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}
.placeholder-text {
  font-size: 0.9rem;
  color: #999;
  font-weight: 500;
  text-align: center;
  max-width: 100%;
  padding: 0 8px;
  word-break: break-word;
}
.product-title {
  font-size: 1.1rem;
  font-weight: 600;
  padding: 16px 16px 8px;
  line-height: 1.3;
  min-height: 52px;
  cursor: pointer;
  color: #333;
}
.product-brand {
  color: #666;
  font-size: 0.9rem;
  padding: 0 16px 8px;
  min-height: 20px;
}
.article-number {
  font-size: 0.8rem;
  color: #04080fdc;
  font-style: italic;
  margin-left: 5px;
}
.product-features {
  padding: 8px 16px;
  flex-grow: 1;
}
.feature {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 6px;
  font-size: 0.85rem;
  color: #555;
  line-height: 1.4;
}
.feature .v-icon {
  flex-shrink: 0;
  margin-top: 2px;
}
.product-actions {
  padding: 8px 16px 16px;
}
.price {
  font-size: 1.3rem;
  font-weight: 700;
  color: #1976d2;
}
@media (max-width: 600px) {
  .compact-grid { margin: -2px -2px 0 -2px; }
  .compact-grid > [class*="col-"] { padding: 2px !important; }
  .product-title { font-size: 1rem; min-height: 44px; padding: 12px 12px 6px; }
  .product-brand { padding: 0 12px 6px; }
  .product-features { padding: 6px 12px; }
  .feature { font-size: 0.8rem; }
  .price { font-size: 1.1rem; }
  .card-image { min-height: 180px; }
  .product-image { max-height: 180px; }
  .image-container { padding: 12px; }
  .product-actions .v-btn span { display: inline; } /* On garde le texte sur mobile maintenant */
}
@media (min-width: 960px) {
  .card-image { min-height: 220px; }
  .product-image { max-height: 220px; }
}
</style>