<template>
  <v-container fluid class="product-view-container">
    <!-- Loader -->
    <v-overlay v-model="loading" class="align-center justify-center" persistent>
      <v-progress-circular indeterminate size="80" color="primary" width="6" />
      <div class="text-h6 mt-4 text-white">Produkt wird geladen...</div>
    </v-overlay>

    <!-- Header -->
    <div v-if="!loading && product" class="header-section no-print">
      <div class="d-flex justify-space-between align-start flex-wrap gap-2">
        <div>
          <div class="d-flex align-center flex-wrap">
            <v-icon start size="28">mdi-eye</v-icon>
            <h1 class="text-h5 font-weight-bold mb-0">Produktdetails</h1>

            <v-chip v-if="product.is_used" color="deep-orange" size="small" class="ml-3">
              <v-icon start size="14">mdi-recycle-variant</v-icon>
              Gebraucht
            </v-chip>
            <v-chip v-else-if="product.is_new" color="success" size="small" class="ml-3">
              <v-icon start size="14">mdi-new-box</v-icon>
              Neu
            </v-chip>
            <v-chip v-if="product.best_seller" color="warning" size="small" class="ml-2">
              <v-icon start size="14">mdi-trophy</v-icon>
              Bestseller
            </v-chip>
            <v-chip v-if="product.is_offer" color="error" size="small" class="ml-2">
              <v-icon start size="14">mdi-sale</v-icon>
              Angebot
            </v-chip>
          </div>
          <p class="text-caption text-grey-darken-1 mt-1 mb-0">
            {{ canEdit ? 'Nur Ansicht – zum Bearbeiten klicken Sie auf "Bearbeiten"' : 'Nur Ansicht – Sie haben keine Bearbeitungsrechte' }}
          </p>
        </div>

        <div class="d-flex flex-wrap ga-2 mt-2 mt-sm-0">
          <v-btn variant="outlined" size="small" prepend-icon="mdi-arrow-left" @click="cancel">
            Zurück
          </v-btn>
          <v-btn variant="outlined" size="small" prepend-icon="mdi-share-variant" @click="shareProduct">
            Teilen
          </v-btn>
          <v-btn v-if="shopUrl" variant="outlined" size="small" prepend-icon="mdi-storefront" @click="viewInShop">
            Im Shop
          </v-btn>
          <v-btn variant="outlined" size="small" prepend-icon="mdi-printer" @click="exportPdf">
            PDF
          </v-btn>

          <!-- ✅ Boutons réservés aux rôles avec products-write -->
          <v-btn
            v-if="canEdit"
            variant="outlined"
            size="small"
            color="blue"
            prepend-icon="mdi-content-copy"
            @click="duplicateProduct"
          >
            Duplizieren
          </v-btn>
          <v-btn
            v-if="canEdit"
            variant="outlined"
            size="small"
            color="error"
            prepend-icon="mdi-delete"
            @click="confirmDelete"
          >
            Löschen
          </v-btn>
          <v-btn
            v-if="canEdit"
            color="primary"
            variant="elevated"
            size="small"
            prepend-icon="mdi-pencil"
            @click="editProduct"
          >
            Bearbeiten
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Contenu -->
    <v-card v-if="!loading && product" class="pa-4 printable-card">
      <!-- Bandeau Gebraucht -->
      <v-alert
        v-if="product.is_used"
        color="deep-orange"
        variant="tonal"
        class="mb-4"
        density="comfortable"
      >
        <div class="d-flex align-center flex-wrap gap-2">
          <v-icon>mdi-recycle-variant</v-icon>
          <strong>Gebrauchtgerät</strong>
          <v-chip size="small" color="deep-orange" variant="flat" class="ml-2">
            Zustand: {{ formatZustand(product.condition) }}
          </v-chip>
          <v-chip v-if="economies > 0" size="small" color="deep-orange" variant="flat" class="ml-2">
            Ersparnis: {{ formatPrice(economies) }} ({{ economiesPercent }}%)
          </v-chip>
        </div>
        <div v-if="product.used_description" class="mt-2 text-body-2">
          {{ product.used_description }}
        </div>
      </v-alert>

      <!-- Onglets -->
      <v-tabs v-model="activeTab" color="primary" class="mb-4 no-print">
        <v-tab value="details">
          <v-icon start>mdi-information</v-icon>
          Details
        </v-tab>
        <v-tab value="history">
          <v-icon start>mdi-history</v-icon>
          Verlauf
          <v-chip v-if="history.length" size="x-small" color="primary" class="ml-2">
            {{ history.length }}
          </v-chip>
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <!-- ============================ ONGLET DETAILS ============================ -->
        <v-window-item value="details">
          <v-row>
            <!-- Colonne images + sélecteur couleur -->
            <v-col cols="12" md="5">
              <v-card variant="outlined" class="pa-3">
                <!-- Image principale -->
                <v-img
                  :src="currentMainImage || placeholder"
                  height="320"
                  contain
                  class="rounded mb-3 bg-grey-lighten-5"
                >
                  <template #placeholder>
                    <div class="d-flex align-center justify-center fill-height">
                      <v-icon size="64" color="grey-lighten-1">mdi-image</v-icon>
                    </div>
                  </template>
                </v-img>

                <!-- Galerie : miniatures uniformes -->
                <div v-if="imagesForCurrentFarbe.length > 1" class="thumbnails-grid mb-3">
                  <div
                    v-for="(img, idx) in imagesForCurrentFarbe"
                    :key="idx"
                    class="thumb-box"
                    :class="{ 'thumb-box-active': img.url === currentMainImage }"
                    @click="mainImageUrl = img.url"
                  >
                    <v-img
                      :src="img.url"
                      cover
                      class="thumb-img"
                    />
                  </div>
                </div>

                <!-- Sélecteur de couleur -->
                <div v-if="farben.length" class="farben-selector">
                  <div class="text-caption text-grey-darken-1 mb-2">
                    Farbe:
                    <strong class="ml-1">{{ selectedFarbe?.bezeichnung || '—' }}</strong>
                  </div>

                  <div class="d-flex flex-wrap ga-2">
                    <button
                      v-for="farbe in farben"
                      :key="farbe.id"
                      type="button"
                      class="farbe-btn"
                      :class="{ 'farbe-btn-active': selectedFarbe?.id === farbe.id }"
                      :title="farbe.bezeichnung"
                      @click="selectFarbe(farbe)"
                    >
                      <span
                        class="farbe-swatch"
                        :style="{ backgroundColor: farbe.hex_code }"
                      >
                        <v-icon
                          v-if="selectedFarbe?.id === farbe.id"
                          size="14"
                          :color="isFarbeHell(farbe.hex_code) ? 'black' : 'white'"
                        >
                          mdi-check
                        </v-icon>
                      </span>
                      <span class="farbe-label">{{ farbe.bezeichnung }}</span>
                    </button>
                  </div>
                </div>
              </v-card>
            </v-col>

            <!-- Colonne infos -->
            <v-col cols="12" md="7">
              <v-card variant="outlined" class="pa-4 h-100">
                <h2 class="text-h5 font-weight-bold mb-1">{{ product.name }}</h2>
                <div class="text-body-1 text-grey-darken-2 mb-3">
                  <strong>{{ product.brand }}</strong>
                  <span v-if="currentArtikelnummer" class="ml-2">
                    • Art.-Nr.: {{ currentArtikelnummer }}
                  </span>
                </div>

                <!-- Prix dynamique selon la couleur -->
                <div class="price-section mb-4" :class="{ 'price-used': product.is_used }">
                  <div v-if="product.is_used && product.used_price" class="text-caption text-grey-darken-1">
                    Neupreis: <span class="text-decoration-line-through">{{ formatPrice(product.price) }}</span>
                  </div>
                  <div class="text-h4 font-weight-bold" :class="product.is_used ? 'text-deep-orange' : 'text-primary'">
                    {{ formatPrice(currentPrice) }}
                    <span class="text-body-2 text-grey"> netto</span>
                  </div>
                  <div class="text-body-2 text-grey-darken-2 mt-1">
                    {{ formatPrice(currentPrice * 1.19) }} inkl. MwSt.
                  </div>
                </div>

                <div class="d-flex flex-wrap ga-2 mb-4">
                  <v-chip :color="product.in_stock ? 'success' : 'error'" size="small" variant="flat">
                    <v-icon start size="14">
                      {{ product.in_stock ? 'mdi-check-circle' : 'mdi-close-circle' }}
                    </v-icon>
                    {{ product.in_stock ? 'Auf Lager' : 'Nicht lagernd' }}
                  </v-chip>
                  <v-chip v-if="product.warranty_years" color="primary" size="small" variant="tonal">
                    <v-icon start size="14">mdi-certificate</v-icon>
                    {{ product.warranty_years }} Jahre Garantie
                  </v-chip>
                </div>

                <!-- ======================== LIVRAISON ======================== -->
                <div class="delivery-info mb-4">
                  <h3 class="delivery-title">
                    <v-icon start size="20" color="primary">mdi-truck-fast</v-icon>
                    Lieferung
                  </h3>

                  <div class="delivery-grid">
                    <div class="delivery-card">
                      <div class="delivery-icon-wrap">
                        <v-icon size="22" color="primary">mdi-cash</v-icon>
                      </div>
                      <div class="delivery-content">
                        <div class="delivery-label">Versandkosten</div>
                        <div class="delivery-value">
                          {{ shipping.shipping_cost ? formatPrice(shipping.shipping_cost) : '—' }}
                        </div>
                      </div>
                    </div>

                    <div class="delivery-card">
                      <div class="delivery-icon-wrap delivery-icon-success">
                        <v-icon size="22" color="success">mdi-truck-check</v-icon>
                      </div>
                      <div class="delivery-content">
                        <div class="delivery-label">Kostenloser Versand ab</div>
                        <div class="delivery-value delivery-value-success">
                          {{ shipping.free_shipping_threshold ? formatPrice(shipping.free_shipping_threshold) : '—' }}
                        </div>
                      </div>
                    </div>

                    <div class="delivery-card">
                      <div class="delivery-icon-wrap">
                        <v-icon size="22" color="primary">mdi-truck-delivery</v-icon>
                      </div>
                      <div class="delivery-content">
                        <div class="delivery-label">Versandart</div>
                        <div class="delivery-value">
                          {{ shipping.shipping_method || '—' }}
                        </div>
                      </div>
                    </div>

                    <div class="delivery-card">
                      <div class="delivery-icon-wrap delivery-icon-warning">
                        <v-icon size="22" color="orange">mdi-calendar-clock</v-icon>
                      </div>
                      <div class="delivery-content">
                        <div class="delivery-label">Lieferzeit</div>
                        <div class="delivery-value">
                          {{ shipping.estimated_delivery_days ? shipping.estimated_delivery_days + ' Werktage' : '—' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="product.description" class="mb-4">
                  <h3 class="text-subtitle-2 font-weight-bold mb-2">Beschreibung</h3>
                  <p class="text-body-2" style="white-space: pre-line;">{{ product.description }}</p>
                </div>

                <div v-if="product.features && product.features.length" class="mb-4">
                  <h3 class="text-subtitle-2 font-weight-bold mb-2">Hauptmerkmale</h3>
                  <div class="d-flex flex-column ga-1">
                    <div
                      v-for="(feat, i) in product.features"
                      :key="i"
                      class="d-flex align-center ga-2 text-body-2"
                    >
                      <v-icon size="16" color="primary">mdi-check-circle</v-icon>
                      <span>{{ feat }}</span>
                    </div>
                  </div>
                </div>
              </v-card>
            </v-col>
          </v-row>

          <v-row class="mt-4">
            <v-col cols="12" md="6">
              <v-card variant="outlined" class="pa-4 h-100">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">
                  <v-icon start color="primary">mdi-information</v-icon>
                  Allgemeine Informationen
                </h3>
                <v-table density="compact" class="detail-table">
                  <tbody>
                    <tr><td class="label">Kategorie</td><td>{{ product.category || '—' }}</td></tr>
                    <tr v-if="product.subcategory"><td class="label">Unterkategorie</td><td>{{ product.subcategory }}</td></tr>
                    <tr><td class="label">Artikeltyp</td><td>{{ product.article_type || '—' }}</td></tr>
                    <tr v-if="selectedFarbe">
                      <td class="label">Farbe</td>
                      <td>
                        <span class="d-inline-flex align-center ga-2">
                          <span
                            class="farbe-dot-inline"
                            :style="{ backgroundColor: selectedFarbe.hex_code }"
                          ></span>
                          {{ selectedFarbe.bezeichnung }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="product.weight_capacity"><td class="label">Tragfähigkeit</td><td>{{ product.weight_capacity }} kg</td></tr>
                    <tr v-if="product.power_supply"><td class="label">Stromversorgung</td><td>{{ product.power_supply }}</td></tr>
                    <tr v-if="product.application_area"><td class="label">Anwendungsbereich</td><td>{{ product.application_area }}</td></tr>
                    <tr v-if="product.currency"><td class="label">Währung</td><td>{{ product.currency }}</td></tr>
                  </tbody>
                </v-table>
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card variant="outlined" class="pa-4 h-100">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">
                  <v-icon start color="primary">mdi-cog</v-icon>
                  Technische Spezifikationen
                </h3>
                <v-table v-if="hasSpecifics" density="compact" class="detail-table">
                  <tbody>
                    <tr v-for="(value, key) in specsClean" :key="key">
                      <td class="label">{{ formatSpecKey(key) }}</td>
                      <td>{{ formatSpecValue(value) }}</td>
                    </tr>
                  </tbody>
                </v-table>
                <p v-else class="text-body-2 text-grey">Keine Spezifikationen vorhanden</p>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>

        <!-- ============================ ONGLET HISTORIQUE ============================ -->
        <v-window-item value="history">
          <v-card variant="outlined" class="pa-4">
            <div class="d-flex justify-space-between align-center mb-3">
              <h3 class="text-subtitle-1 font-weight-bold">
                <v-icon start color="primary">mdi-history</v-icon>
                Änderungsverlauf
              </h3>
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-refresh"
                :loading="loadingHistory"
                @click="loadHistory"
              >
                Aktualisieren
              </v-btn>
            </div>

            <v-skeleton-loader v-if="loadingHistory" type="list-item-three-line@3" />

            <v-timeline v-else-if="history.length" density="compact" side="end">
              <v-timeline-item
                v-for="entry in history"
                :key="entry.id"
                dot-color="primary"
                size="small"
              >
                <template #icon>
                  <v-icon size="14" color="white">{{ historyIcon(entry.aktion) }}</v-icon>
                </template>

                <div class="d-flex align-center flex-wrap gap-2">
                  <strong>{{ historyLabel(entry.aktion) }}</strong>
                  <v-chip size="x-small" variant="tonal" color="primary">
                    {{ entry.benutzer || 'System' }}
                  </v-chip>
                  <span class="text-caption text-grey">
                    {{ formatDateTime(entry.erstellt_am) }}
                  </span>
                </div>

                <div v-if="entry.beschreibung" class="text-body-2 mt-1">
                  {{ entry.beschreibung }}
                </div>

                <div v-if="entry.feld && (entry.alter_wert || entry.neuer_wert)" class="mt-2 text-body-2">
                  <div class="text-caption text-grey">
                    Feld: <strong>{{ entry.feld }}</strong>
                  </div>
                  <div>
                    <span class="text-decoration-line-through text-grey">
                      {{ entry.alter_wert || '—' }}
                    </span>
                    <v-icon size="12" class="mx-1">mdi-arrow-right</v-icon>
                    <span class="font-weight-medium">{{ entry.neuer_wert || '—' }}</span>
                  </div>
                </div>
              </v-timeline-item>
            </v-timeline>

            <div v-else class="text-center py-6">
              <v-icon size="48" color="grey-lighten-1">mdi-history</v-icon>
              <p class="text-body-2 text-grey mt-2">Keine Änderungen bisher</p>
            </div>
          </v-card>
        </v-window-item>
      </v-window>

      <!-- Actions bas de page -->
      <div class="d-flex justify-end ga-2 mt-4 no-print">
        <v-btn variant="outlined" @click="cancel">Zurück zur Liste</v-btn>
        <v-btn
          v-if="canEdit"
          color="primary"
          @click="editProduct"
          prepend-icon="mdi-pencil"
        >
          Produkt bearbeiten
        </v-btn>
      </div>
    </v-card>

    <!-- Erreur -->
    <v-card v-if="!loading && error" class="pa-6 text-center">
      <v-icon size="64" color="error">mdi-alert-circle</v-icon>
      <h3 class="mt-3">Produkt konnte nicht geladen werden</h3>
      <p class="text-grey">{{ error }}</p>
      <v-btn color="primary" class="mt-4" @click="cancel">Zurück zur Liste</v-btn>
    </v-card>

    <!-- Dialog suppression -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h6">
          <v-icon color="error" start>mdi-alert</v-icon>
          Produkt wirklich löschen?
        </v-card-title>
        <v-card-text>
          Möchten Sie das Produkt <strong>{{ product?.name }}</strong> (ID: {{ productId }})
          endgültig löschen? Diese Aktion kann nicht rückgängig gemacht werden.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Abbrechen</v-btn>
          <v-btn color="error" @click="deleteProduct" :loading="deleting">
            Ja, löschen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog duplication -->
    <v-dialog v-model="duplicateDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="text-h6">
          <v-icon color="blue" start>mdi-content-copy</v-icon>
          Produkt duplizieren?
        </v-card-title>
        <v-card-text>
          Eine Kopie von <strong>{{ product?.name }}</strong> wird erstellt.
          Sie können die Kopie anschließend bearbeiten.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="duplicateDialog = false">Abbrechen</v-btn>
          <v-btn color="blue" @click="doDuplicate" :loading="duplicating">
            Ja, duplizieren
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="top end">
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { usePermissionStore } from '@/stores/permission'
import { apiUrl, imageUrl, SHOP_BASE_URL } from '@/config/api'

const route = useRoute()
const router = useRouter()
const permissionStore = usePermissionStore()

// ============================================================
// PERMISSIONS
// ============================================================
const canEdit = computed(() => permissionStore.can('products-write'))
const canDeleteTicket = computed(() => permissionStore.can('tickets-delete'))

// ============================================================
// CONFIG
// ============================================================
const placeholder = 'https://placehold.co/400x300?text=Kein+Bild'

// ============================================================
// STATE
// ============================================================
const productId = ref(parseInt(route.params.id))
const product = ref(null)
const specifics = ref({})
const shipping = ref({
  shipping_cost: null,
  free_shipping_threshold: null,
  shipping_method: '',
  estimated_delivery_days: null
})

const allImages = ref([])
const mainImageUrl = ref('')

const farben = ref([])
const selectedFarbe = ref(null)

const loading = ref(true)
const error = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const activeTab = ref('details')
const history = ref([])
const loadingHistory = ref(false)

const deleteDialog = ref(false)
const deleting = ref(false)
const duplicateDialog = ref(false)
const duplicating = ref(false)

// ============================================================
// HELPERS
// ============================================================
const buildImageUrl = (path) => imageUrl(path)

// ✅ Normalise une clé de couleur : string, trim, lowercase
const normKey = (v) => (v ?? '').toString().trim().toLowerCase()

const formatPrice = (v) => {
  if (v === null || v === undefined || isNaN(v)) return '—'
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(v)
}

const formatZustand = (zustand) => {
  const map = {
    'like_new': 'Wie neu', 'very_good': 'Sehr gut', 'good': 'Gut', 'acceptable': 'Akzeptabel',
    'neu': 'Neu', 'wie-neu': 'Wie neu', 'sehr-gut': 'Sehr gut', 'gut': 'Gut',
    'akzeptabel': 'Akzeptabel', 'defekt': 'Defekt'
  }
  return map[zustand] || zustand || '—'
}

const formatDateTime = (s) => {
  if (!s) return '—'
  const d = new Date(s.replace(' ', 'T'))
  return new Intl.DateTimeFormat('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(d)
}

const formatSpecKey = (key) => key.replace(/^spez_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

const formatSpecValue = (v) => {
  if (v === true) return 'Ja'
  if (v === false) return 'Nein'
  if (v === null || v === undefined || v === '') return '—'
  return v
}

const historyIcon = (aktion) => ({
  'create': 'mdi-plus-circle',
  'update': 'mdi-pencil-circle',
  'delete': 'mdi-delete-circle',
  'duplicate': 'mdi-content-copy'
}[aktion] || 'mdi-information')

const historyLabel = (aktion) => ({
  'create': 'Erstellt',
  'update': 'Aktualisiert',
  'delete': 'Gelöscht',
  'duplicate': 'Dupliziert'
}[aktion] || aktion)

const isFarbeHell = (hex) => {
  if (!hex || !hex.startsWith('#')) return false
  const r = parseInt(hex.substr(1, 2), 16)
  const g = parseInt(hex.substr(3, 2), 16)
  const b = parseInt(hex.substr(5, 2), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.7
}

// ============================================================
// COMPUTED
// ============================================================
const prixAffiche = computed(() => {
  if (!product.value) return 0
  if (product.value.is_used && product.value.used_price) return parseFloat(product.value.used_price)
  return parseFloat(product.value.price) || 0
})

const currentPrice = computed(() => {
  if (selectedFarbe.value?.preis) {
    return parseFloat(selectedFarbe.value.preis)
  }
  return prixAffiche.value
})

const currentArtikelnummer = computed(() => {
  if (selectedFarbe.value?.artikelnummer) {
    return selectedFarbe.value.artikelnummer
  }
  return product.value?.article_number || ''
})

const economies = computed(() => {
  if (!product.value?.is_used) return 0
  if (!product.value.used_price || !product.value.price) return 0
  return Math.max(0, parseFloat(product.value.price) - parseFloat(product.value.used_price))
})

const economiesPercent = computed(() => {
  if (!product.value?.price || economies.value === 0) return 0
  return Math.round((economies.value / parseFloat(product.value.price)) * 100)
})

const hasSpecifics = computed(() => Object.keys(specsClean.value).length > 0)

const specsClean = computed(() => {
  const raw = specifics.value || {}
  const out = {}
  Object.entries(raw).forEach(([k, v]) => {
    if (v === null || v === undefined || v === '') return
    out[k] = v
  })
  return out
})

// ============================================================
// ✅ FILTRE ROBUSTE PAR COULEUR
// ============================================================
// ✅ Déduit la couleur depuis le nom de fichier
const inferColorFromFilename = (url) => {
  if (!url) return null
  const filename = url.toLowerCase()
  const knownColors = [
    'weiss', 'schwarz', 'anthrazit', 'grau', 'silber',
    'rot', 'blau', 'gruen', 'gelb', 'orange',
    'braun', 'beige', 'rosa', 'lila', 'violett',
    'gold', 'chrom', 'pink', 'tuerkis', 'bordeaux'
  ]
  for (const kc of knownColors) {
    if (filename.includes(kc)) return kc
  }
  return null
}
const imagesForCurrentFarbe = computed(() => {
  if (!selectedFarbe.value) return allImages.value

  const farbeKey = normKey(selectedFarbe.value.schluessel)
  if (!farbeKey) return allImages.value

  let filtered = allImages.value.filter(img => {
    // Priorité 1 : couleur en base
    if (img.farbe_schluessel) {
      return normKey(img.farbe_schluessel) === farbeKey
    }
    // Priorité 2 : déduire du nom de fichier
    const inferred = inferColorFromFilename(img.url)
    return inferred === farbeKey
  })

  if (filtered.length === 0) {
    filtered = [...allImages.value]
  }

  const farbeImgUrl = selectedFarbe.value.bild_url
  if (farbeImgUrl) {
    const idx = filtered.findIndex(img => img.url === farbeImgUrl)
    if (idx > 0) {
      const [img] = filtered.splice(idx, 1)
      filtered.unshift(img)
    }
  }

  return filtered
})

const currentMainImage = computed(() => {
  if (mainImageUrl.value) {
    const found = imagesForCurrentFarbe.value.find(i => i.url === mainImageUrl.value)
    if (found) return mainImageUrl.value
  }
  return imagesForCurrentFarbe.value[0]?.url || ''
})

const shopUrl = computed(() => {
  if (!product.value?.slug) return null
  const cat = (product.value.category || '').replace(/-/g, '/')
  return `${SHOP_BASE_URL}/shop/${cat}/${product.value.slug}`
})

// ============================================================
// LOAD PRODUCT
// ============================================================
const loadProduct = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get(apiUrl(`/get_product_details_stock.php?id=${productId.value}`), {
      withCredentials: true
    })
    if (!res.data.success) throw new Error(res.data.error || 'Fehler beim Laden')

    const a = res.data.product.article
    product.value = {
      ...a,
      main_image: buildImageUrl(a.main_image || ''),
      price: a.price ?? null,
      used_price: a.used_price ?? null
    }

    specifics.value = res.data.product.specifics || {}
    shipping.value = res.data.product.shipping || shipping.value

    // ✅ Images : normalise farbe_schluessel
    const images = []
    ;(res.data.product.images || []).forEach(img => {
      const url = buildImageUrl(img.url)
      if (!url) return
      images.push({
        url,
        type: img.type,
        farbe_schluessel: img.farbe_schluessel ? normKey(img.farbe_schluessel) : null
      })
    })

    // Fallback : si aucune image de galerie, on utilise le main_image
    if (images.length === 0 && a.main_image) {
      const mainUrl = buildImageUrl(a.main_image)
      if (mainUrl) {
        images.push({
          url: mainUrl,
          type: 'main',
          farbe_schluessel: null
        })
      }
    }

    allImages.value = images

    // ✅ Farben : normalise schluessel
    farben.value = (res.data.product.farben || []).map(f => ({
      ...f,
      schluessel: normKey(f.schluessel)
    }))

    // Sélectionne la couleur standard par défaut
    if (farben.value.length) {
      const standard = farben.value.find(f => f.ist_standard) || farben.value[0]
      selectedFarbe.value = standard
    }

    // Reset l'image principale pour recalculer
    setTimeout(() => {
      mainImageUrl.value = ''
    }, 0)

    // ============================================================
    // DEBUG (à retirer en prod)
    // ============================================================
    if (import.meta.env.DEV) {
      console.log('[ProductView] Images:', images.map(i => ({
        url: i.url.split('/').pop(),
        farbe: i.farbe_schluessel
      })))
      console.log('[ProductView] Farben:', farben.value.map(f => f.schluessel))
    }

  } catch (e) {
    console.error(e)
    error.value = e.message
  } finally {
    loading.value = false
  }
}

// ============================================================
// SÉLECTION COULEUR
// ============================================================
const selectFarbe = (farbe) => {
  selectedFarbe.value = farbe
  mainImageUrl.value = ''   // reset pour reprendre la 1ère image de la couleur
}

// ============================================================
// LOAD HISTORY
// ============================================================
const loadHistory = async () => {
  loadingHistory.value = true
  try {
    const res = await axios.get(apiUrl(`/get_product_history.php?id=${productId.value}`), {
      withCredentials: true
    })
    if (res.data.success) {
      history.value = res.data.items || []
    }
  } catch (e) {
    console.error('History error:', e)
  } finally {
    loadingHistory.value = false
  }
}

// ============================================================
// ACTIONS
// ============================================================
const cancel = () => router.push('/products')

const editProduct = () => {
  if (!canEdit.value) {
    permissionStore.showAccessDenied()
    return
  }
  router.push(`/products/edit/${productId.value}`)
}

const viewInShop = () => {
  if (shopUrl.value) {
    window.open(shopUrl.value, '_blank')
  } else {
    snackbar.value = { show: true, text: 'Shop-Link nicht verfügbar', color: 'warning' }
  }
}

const shareProduct = async () => {
  const url = window.location.href
  try {
    if (navigator.share) {
      await navigator.share({
        title: product.value?.name,
        text: `Produkt: ${product.value?.name}`,
        url
      })
    } else {
      await navigator.clipboard.writeText(url)
      snackbar.value = { show: true, text: '✅ Link kopiert', color: 'success' }
    }
  } catch (e) {
    if (e.name !== 'AbortError') {
      snackbar.value = { show: true, text: '❌ Teilen fehlgeschlagen', color: 'error' }
    }
  }
}

const exportPdf = () => { window.print() }

const confirmDelete = () => {
  if (!canEdit.value) {
    permissionStore.showAccessDenied()
    return
  }
  deleteDialog.value = true
}

const deleteProduct = async () => {
  deleting.value = true
  try {
    const r = await axios.post(apiUrl('/delete_product.php'), { product_id: productId.value }, {
      withCredentials: true
    })
    if (r.data?.success === true) {
      snackbar.value = { show: true, text: '🗑️ Produkt gelöscht', color: 'success' }
      deleteDialog.value = false
      setTimeout(() => router.push('/products'), 1200)
    } else {
      throw new Error(r.data?.error || 'Unbekannter Fehler')
    }
  } catch (e) {
    snackbar.value = { show: true, text: `❌ ${e.message}`, color: 'error' }
    deleteDialog.value = false
  } finally {
    deleting.value = false
  }
}

const duplicateProduct = () => {
  if (!canEdit.value) {
    permissionStore.showAccessDenied()
    return
  }
  duplicateDialog.value = true
}

const doDuplicate = async () => {
  duplicating.value = true
  try {
    const r = await axios.post(apiUrl('/duplicate_product.php'), { product_id: productId.value }, {
      withCredentials: true
    })
    if (r.data?.success === true) {
      snackbar.value = { show: true, text: '✅ Kopie erstellt', color: 'success' }
      duplicateDialog.value = false
      setTimeout(() => router.push(`/products/edit/${r.data.new_id}`), 1000)
    } else {
      throw new Error(r.data?.error || 'Unbekannter Fehler')
    }
  } catch (e) {
    snackbar.value = { show: true, text: `❌ ${e.message}`, color: 'error' }
  } finally {
    duplicating.value = false
  }
}

// ============================================================
// WATCH
// ============================================================
watch(activeTab, (val) => {
  if (val === 'history' && history.value.length === 0) {
    loadHistory()
  }
})

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(loadProduct)
</script>


<style scoped>
.product-view-container {
  padding: 16px;
  max-width: 1400px;
  margin: 0 auto;
}

.gap-2 { gap: 8px; }
.ga-1 { gap: 4px; }
.ga-2 { gap: 8px; }

/* ============================================================
   GALERIE — Miniatures uniformes
   ============================================================ */
.thumbnails-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));
  gap: 8px;
  padding: 4px 0;
}

.thumb-box {
  position: relative;
  aspect-ratio: 1 / 1;
  width: 100%;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid #e0e0e0;
  background: #f8f9fa;
  transition: border-color 0.2s ease, transform 0.2s ease;
}
.thumb-box:hover {
  border-color: #90caf9;
  transform: translateY(-1px);
}
.thumb-box-active {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ============================================================
   SÉLECTEUR DE COULEURS
   ============================================================ */
.farben-selector {
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  margin-top: 4px;
}

.farbe-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 6px;
  border: 2px solid transparent;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
  min-width: 58px;
}
.farbe-btn:hover { background: #f5f5f5; }
.farbe-btn-active {
  border-color: #1976d2;
  background: #e3f2fd;
}

.farbe-swatch {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: 2px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.1);
}

.farbe-label {
  font-size: 0.7rem;
  color: #555;
  font-weight: 500;
  text-align: center;
  max-width: 58px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.farbe-btn-active .farbe-label {
  color: #1976d2;
  font-weight: 700;
}

.farbe-dot-inline {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid #ddd;
  vertical-align: middle;
}

/* ============================================================
   TABLES
   ============================================================ */
.detail-table {
  background: transparent;
  font-size: 0.9rem;
}
.detail-table tbody tr { border-bottom: 1px solid #f0f0f0; }
.detail-table tbody tr:last-child { border-bottom: none; }
.detail-table td { padding: 8px 4px; }
.detail-table td.label {
  font-weight: 600;
  color: #555;
  width: 45%;
}

/* ============================================================
   PRIX
   ============================================================ */
.price-section {
  padding: 12px 16px;
  border-radius: 8px;
  background: #f0f7ff;
  border-left: 4px solid #1976d2;
}
.price-section.price-used {
  background: #fff3e0;
  border-left-color: #ff9800;
}

/* ============================================================
   LIVRAISON — Grid 2×2 moderne
   ============================================================ */
.delivery-info {
  padding: 16px 18px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  border-radius: 12px;
  border: 1px solid #e0e0e0;
  border-left: 4px solid #1976d2;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.delivery-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1976d2;
  margin: 0 0 14px 0;
  padding-bottom: 10px;
  border-bottom: 1px dashed #cfd8dc;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.delivery-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.delivery-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #ffffff;
  border-radius: 10px;
  border: 1px solid #e8eaf0;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
  min-height: 72px;
}

.delivery-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #1976d2;
  opacity: 0;
  transition: opacity 0.25s ease;
}

.delivery-card:hover {
  border-color: #90caf9;
  background: #f5faff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.12);
}

.delivery-card:hover::before {
  opacity: 1;
}

.delivery-icon-wrap {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #e3f2fd 0%, #f0f7ff 100%);
  border-radius: 10px;
  border: 1px solid #e0e0e0;
  transition: transform 0.25s ease;
}

.delivery-card:hover .delivery-icon-wrap {
  transform: scale(1.06);
}

.delivery-icon-success {
  background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
}

.delivery-icon-warning {
  background: linear-gradient(135deg, #fff3e0 0%, #fffaf0 100%);
}

.delivery-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.delivery-label {
  font-size: 0.7rem;
  color: #78909c;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  line-height: 1.2;
}

.delivery-value {
  font-size: 0.95rem;
  font-weight: 700;
  color: #263238;
  line-height: 1.2;
  word-break: break-word;
}

.delivery-value-success {
  color: #2e7d32;
}

/* ============================================================
   PRINT
   ============================================================ */
@media print {
  .no-print { display: none !important; }
  .printable-card {
    box-shadow: none !important;
    border: none !important;
  }
  .product-view-container {
    padding: 0 !important;
    max-width: 100% !important;
  }
  .printable-card::before {
    content: 'Spitzner Shop – Produktdatenblatt';
    display: block;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #1976d2;
    color: #1976d2;
  }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 700px) {
  .delivery-info {
    padding: 12px 14px;
  }

  .delivery-title {
    font-size: 0.85rem;
    margin-bottom: 10px;
    padding-bottom: 8px;
  }

  .delivery-grid {
    gap: 8px;
  }

  .delivery-card {
    padding: 10px 12px;
    gap: 8px;
    min-height: 60px;
  }

  .delivery-icon-wrap {
    width: 36px;
    height: 36px;
  }

  .delivery-label {
    font-size: 0.62rem;
  }

  .delivery-value {
    font-size: 0.82rem;
  }
}

@media (max-width: 600px) {
  .product-view-container { padding: 8px; }

  .detail-table td.label { width: 50%; font-size: 0.8rem; }
  .detail-table td { font-size: 0.8rem; }

  .thumbnails-grid {
    grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));
    gap: 6px;
  }

  .farbe-btn { min-width: 50px; padding: 4px; }
  .farbe-swatch { width: 30px; height: 30px; }
  .farbe-label { font-size: 0.6rem; max-width: 50px; }
}

@media (max-width: 480px) {
  .delivery-grid {
    grid-template-columns: 1fr;
  }
}
</style>