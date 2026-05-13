<template>
  <v-container fluid>
    <!-- Suchleiste & Filter -->
    <v-row class="mb-4" align="center">
      <v-col cols="12" md="6">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Produkt suchen"
          variant="outlined"
          density="compact"
          hide-details
          clearable
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="selectedCategory"
          :items="categoryOptions"
          label="Kategorie"
          variant="outlined"
          density="compact"
          hide-details
          clearable
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="stockFilter"
          :items="stockOptions"
          label="Verfügbarkeit"
          variant="outlined"
          density="compact"
          hide-details
          clearable
        />
      </v-col>
    </v-row>

    <!-- Tabelle -->
    <v-data-table
      :headers="headers"
      :items="filteredProducts"
      :items-per-page="10"
      :search="search"
      class="elevation-1 rounded-lg"
      hover
      fixed-header
    >
      <!-- Bildspalte -->
      <template v-slot:item.main_image="{ value }">
        <v-avatar size="40" rounded="0">
          <v-img
            :src="value"
            cover
            :lazy-src="placeholderImage"
            @error="handleImageError"
          >
            <template v-slot:placeholder>
              <v-progress-circular indeterminate size="24" />
            </template>
          </v-img>
        </v-avatar>
      </template>

      <!-- Preis formatieren -->
      <template v-slot:item.price="{ value }">
        <span class="font-weight-medium">{{ value.toFixed(2) }} €</span>
      </template>

      <!-- Lagerbestand als Chip -->
      <template v-slot:item.in_stock="{ value }">
        <v-chip :color="value ? 'success' : 'error'" size="small" variant="tonal">
          {{ value ? 'Auf Lager' : 'Nicht auf Lager' }}
        </v-chip>
      </template>

      <!-- Aktionen (bearbeiten/löschen) -->
      <template v-slot:item.actions="{ item }">
        <v-icon size="small" class="me-2" @click="editProduct(item)">mdi-pencil</v-icon>
        <v-icon size="small" color="error" @click="deleteProduct(item)">mdi-delete</v-icon>
      </template>

      <!-- Keine Daten Meldung -->
      <template v-slot:no-data>
        <v-alert type="info" variant="tonal" class="ma-2">
          Keine Produkte entsprechen Ihren Kriterien.
        </v-alert>
      </template>
    </v-data-table>
  </v-container>
</template>

<script setup>
import { ref, computed } from 'vue'

// ---------- MOCK-DATEN (DEUTSCH) ----------
const mockProducts = ref([
  {
    id: 1,
    name: 'Ellipsentrainer Pro 3000',
    brand: 'BodyFit',
    category: 'Ellipsentrainer',
    price: 599.99,
    main_image: 'https://picsum.photos/id/12/300/200',
    in_stock: true,
    best_seller: true,
  },
  {
    id: 2,
    name: 'Laufband UltraRun',
    brand: 'SpeedFit',
    category: 'Laufband',
    price: 899.0,
    main_image: 'https://picsum.photos/id/20/300/200',
    in_stock: true,
    best_seller: false,
  },
  {
    id: 3,
    name: 'Heimtrainer Comfort',
    brand: 'HomeGym',
    category: 'Fahrrad',
    price: 349.9,
    main_image: 'https://picsum.photos/id/25/300/200',
    in_stock: false,
    best_seller: false,
  },
  {
    id: 4,
    name: 'Hydraulischer Ruderer',
    brand: 'RowingPlus',
    category: 'Ruderer',
    price: 429.0,
    main_image: 'https://picsum.photos/id/29/300/200',
    in_stock: true,
    best_seller: true,
  },
  {
    id: 5,
    name: 'Multifunktions-Kraftstation',
    brand: 'IronGym',
    category: 'Krafttraining',
    price: 279.99,
    main_image: 'https://picsum.photos/id/32/300/200',
    in_stock: true,
    best_seller: false,
  },
])

// Reaktive Filter
const search = ref('')
const selectedCategory = ref(null)
const stockFilter = ref(null)

// Dynamische Kategorien aus den Mock-Daten
const categoryOptions = computed(() => [
  ...new Set(mockProducts.value.map(p => p.category)),
])

const stockOptions = [
  { title: 'Auf Lager', value: true },
  { title: 'Nicht auf Lager', value: false },
]

// Gefilterte Produkte (Kategorie + Lager)
const filteredProducts = computed(() => {
  let products = [...mockProducts.value]

  if (selectedCategory.value) {
    products = products.filter(p => p.category === selectedCategory.value)
  }
  if (stockFilter.value !== null && stockFilter.value !== undefined) {
    products = products.filter(p => p.in_stock === stockFilter.value)
  }
  return products
})

// Tabellen-Header (Deutsch)
const headers = [
  { title: 'Bild', key: 'main_image', sortable: false, width: 80 },
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Marke', key: 'brand', sortable: true },
  { title: 'Kategorie', key: 'category', sortable: true },
  { title: 'Preis (€)', key: 'price', sortable: true },
  { title: 'Lager', key: 'in_stock', sortable: true },
  { title: 'Aktionen', key: 'actions', sortable: false, width: 100, align: 'center' },
]

// Platzhalter-Bild bei Fehler
const placeholderImage = 'https://placehold.co/400x400?text=Kein+Bild'

// Bildfehler behandeln
const handleImageError = (event) => {
  event.target.src = placeholderImage
}

// Mock-Aktionen
const editProduct = (product) => {
  alert(`Produkt bearbeiten: ${product.name}`)
}

const deleteProduct = (product) => {
  if (confirm(`Möchten Sie ${product.name} wirklich löschen?`)) {
    const index = mockProducts.value.findIndex(p => p.id === product.id)
    if (index !== -1) mockProducts.value.splice(index, 1)
  }
}
</script>

<style scoped>
.v-data-table :deep(th) {
  font-weight: 600;
  background-color: #fafafa;
}
</style>