<template>
  <v-container fluid>
    <v-card class="pa-4" v-if="!loading">
      <!-- HEADER -->
      <div class="d-flex justify-space-between align-start mb-4 flex-wrap">
        <div>
          <div class="d-flex align-center">
            <v-icon start size="28">mdi-pencil-box</v-icon>
            <h1 class="text-h5 font-weight-bold mb-0">Produkt bearbeiten</h1>
          </div>
          <p class="text-caption text-grey-darken-1 mt-1 mb-0">
            Bearbeiten Sie die Produktinformationen
          </p>
        </div>

        <div class="d-flex gap-2 mt-2 mt-sm-0">
          <v-btn variant="outlined" @click="cancel" prepend-icon="mdi-arrow-left" class="back-btn">
            Zurück
          </v-btn>
          <v-btn
            @click="submit"
            prepend-icon="mdi-content-save"
            :loading="submitting"
            :disabled="!valid"
            class="action-btn"
          >
            Speichern
          </v-btn>
        </div>
      </div>

      <v-form ref="formRef" v-model="valid" lazy-validation>
        <!-- ======================== ALLGEMEINE INFORMATIONEN ======================== -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Allgemeine Informationen
          </v-card-title>

          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="product.name" label="Produktname *" :rules="[required]" variant="outlined" />
                <v-text-field v-model="product.brand" label="Marke *" :rules="[required]" variant="outlined" class="mt-2" />
                <v-select v-model="product.category" :items="categories" label="Kategorie *" :rules="[required]" variant="outlined" class="mt-2" />
                <v-select v-model="product.article_type" :items="articleTypes" label="Artikeltyp *" :rules="[required]" variant="outlined" class="mt-2" @update:model-value="onArticleTypeChange" />
                <v-text-field v-model="product.article_number" label="Artikelnummer *" :rules="[required]" variant="outlined" class="mt-2" />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field v-model="product.price" label="Preis (€) *" type="number" :rules="[required]" variant="outlined" />
                <v-text-field v-model="product.color" label="Farbe" variant="outlined" class="mt-2" />
                <v-text-field v-model="product.warranty_years" label="Garantie (Jahre)" type="number" variant="outlined" class="mt-2" />
                <v-text-field v-model="product.weight_capacity" label="Tragfähigkeit (kg)" variant="outlined" class="mt-2" />
                <v-text-field v-model="product.power_supply" label="Stromversorgung" variant="outlined" class="mt-2" />
                <v-text-field v-model="product.application_area" label="Anwendungsbereich" variant="outlined" class="mt-2" />
              </v-col>

              <v-col cols="12">
                <v-textarea v-model="product.description" label="Beschreibung" rows="3" variant="outlined" />
              </v-col>

              <v-col cols="12">
                <div class="d-flex flex-wrap gap-4 mt-2">
                  <v-checkbox v-model="product.in_stock" label="Auf Lager" hide-details />
                  <v-checkbox v-model="product.is_new" label="Neues Produkt" hide-details />
                  <v-checkbox v-model="product.best_seller" label="Bestseller" hide-details />
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- ======================== BILDER (Hauptbild + Galerie) ======================== -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Bilder (Hauptbild + Galerie)
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col
                v-for="(img, idx) in allImages"
                :key="idx"
                cols="12"
                sm="6"
                md="4"
                lg="3"
              >
                <v-card variant="outlined" class="pa-2 h-100 d-flex flex-column">
                  <!-- Bildvorschau – jetzt mit contain und Höhe 200 -->
                  <v-img
                    :src="img.url || 'https://placehold.co/300x200?text=Kein+Bild'"
                    height="200"
                    contain
                    class="mb-2 rounded"
                    @error="handleImageError($event, idx)"
                  />
                  <!-- URL -->
                  <v-text-field
                    v-model="img.url"
                    label="Bild-URL"
                    variant="outlined"
                    density="compact"
                    hide-details
                    class="mb-1"
                  />
                  <!-- Typ + Löschen -->
                  <div class="d-flex align-center mt-1">
                    <v-select
                      v-model="img.type"
                      :items="imageTypeOptions"
                      label="Typ"
                      variant="outlined"
                      density="compact"
                      hide-details
                      class="mr-2 flex-grow-1"
                    />
                    <v-btn icon variant="text" color="error" @click="removeImage(idx)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </div>
                  <!-- Hinweis, wenn es das Hauptbild ist -->
                  <div v-if="img.type === 'main'" class="text-caption text-primary font-weight-bold mt-1">
                    ⭐ Hauptbild
                  </div>
                </v-card>
              </v-col>
            </v-row>

            <v-btn variant="tonal" @click="addImage" class="mt-4">
              <v-icon>mdi-plus</v-icon> Bild hinzufügen
            </v-btn>
          </v-card-text>
        </v-card>

        <!-- ======================== TECHNISCHE SPEZIFIKATIONEN ======================== -->
        <v-card variant="outlined" class="mb-6" v-if="product.article_type">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Technische Spezifikationen
          </v-card-title>
          <v-card-text>
            <!-- Laufband -->
            <template v-if="product.article_type === 'treadmill'">
              <v-row>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.motor_power" label="Motorleistung (PS)" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.max_speed" label="Höchstgeschwindigkeit (km/h)" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.max_inclination" label="Max. Steigung (%)" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.display_type" label="Displaytyp" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.training_programs" label="Trainingsprogramme" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.power_range" label="Leistungsbereich" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.display_info" label="Display-Info" rows="2" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.programs_info" label="Programminfo" rows="2" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.comfort_features" label="Komfortfunktionen" rows="2" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <div class="d-flex flex-wrap gap-4">
                    <v-checkbox v-model="specifics.has_ekg" label="EKG integriert" hide-details />
                    <v-checkbox v-model="specifics.is_foldable" label="Klappbar" hide-details />
                    <v-checkbox v-model="specifics.has_touchscreen" label="Touchscreen" hide-details />
                    <v-checkbox v-model="specifics.has_bluetooth" label="Bluetooth" hide-details />
                    <v-checkbox v-model="specifics.has_heart_rate_monitor" label="Herzfrequenzmessung" hide-details />
                    <v-checkbox v-model="specifics.has_wifi" label="WLAN" hide-details />
                    <v-checkbox v-model="specifics.has_speaker" label="Lautsprecher" hide-details />
                  </div>
                </v-col>
              </v-row>
            </template>

            <!-- Fahrrad -->
            <template v-if="product.article_type === 'bike'">
              <v-row>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.resistance_type" label="Widerstandsart" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.max_resistance" label="Max. Widerstand (Level)" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.pedal_type" label="Pedaltyp" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.seat_adjustment" label="Sattelverstellung" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.handlebar_adjustment" label="Lenkerverstellung" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.console_features" label="Konsolenfunktionen" rows="3" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <div class="d-flex flex-wrap gap-4">
                    <v-checkbox v-model="specifics.has_backrest" label="Rückenlehne" hide-details />
                    <v-checkbox v-model="specifics.has_pedal_straps" label="Pedalriemen" hide-details />
                  </div>
                </v-col>
              </v-row>
            </template>
          </v-card-text>
        </v-card>

        <!-- ======================== LIEFERUNG ======================== -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Lieferung
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="shipping.shipping_cost" label="Versandkosten (€)" type="number" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="shipping.free_shipping_threshold" label="Kostenloser Versand ab (€)" type="number" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="shipping.shipping_method" label="Versandart" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6" md="3">
                <v-text-field v-model="shipping.estimated_delivery_days" label="Voraussichtliche Lieferzeit (Tage)" type="number" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- ======================== AKTIONSLEISTE ======================== -->
        <v-card-actions class="justify-end">
          <v-btn variant="outlined" @click="cancel">Abbrechen</v-btn>
          <v-btn color="primary" :loading="submitting" @click="submit" :disabled="!valid">
            Änderungen speichern
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>

    <!-- Loader -->
    <v-overlay v-model="loading" class="align-center justify-center" persistent>
      <v-progress-circular indeterminate size="80" color="primary" width="6" />
      <div class="text-h6 mt-4 text-white">Produkt wird geladen...</div>
    </v-overlay>

    <v-overlay v-model="redirecting" class="align-center justify-center" persistent scrim="rgba(0,0,0,0.7)" :z-index="9999">
      <div class="text-center">
        <v-progress-circular indeterminate size="80" color="primary" width="6" />
        <div class="text-h6 mt-4 text-white">Weiterleitung...</div>
      </div>
    </v-overlay>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="top end">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>

  <!-- Floating Help Button -->
  <div class="floating-help-btn" :class="{ open: guideDrawer }" @click="guideDrawer = !guideDrawer">
    <v-icon :icon="guideDrawer ? 'mdi-close' : 'mdi-help-circle'" color="white" />
  </div>

  <!-- Guide Drawer -->
  <GuideDrawer v-model="guideDrawer" />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import GuideDrawer from '../../components/dashboard/GuideDrawer.vue'

const router = useRouter()
const route = useRoute()
const productId = ref(parseInt(route.params.id))

const valid = ref(false)
const submitting = ref(false)
const loading = ref(true)
const redirecting = ref(false)
const formRef = ref(null)
const guideDrawer = ref(false)

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Produktdaten (ohne main_image, die wird aus allImages extrahiert)
const product = reactive({
  name: '',
  brand: '',
  category: '',
  price: null,
  article_type: '',
  article_number: '',
  color: '',
  warranty_years: null,
  weight_capacity: '',
  power_supply: '',
  application_area: '',
  in_stock: true,
  is_new: false,
  best_seller: false,
  description: ''
})

const specifics = reactive({})
const shipping = reactive({
  shipping_cost: null,
  free_shipping_threshold: null,
  shipping_method: '',
  estimated_delivery_days: null
})

// Alle Bilder in einem Array (Hauptbild + Galerie)
const allImages = ref([])

// Auswahllisten
const categories = [
  { title: 'Kardio', value: 'cardio' },
  { title: 'Kraft', value: 'strength' },
  { title: 'Rehabilitation', value: 'rehabilitation' },
  { title: 'Zubehör', value: 'accessories' }
]

const articleTypes = [
  { title: 'Laufband', value: 'treadmill' },
  { title: 'Fahrrad', value: 'bike' }
]

const imageTypeOptions = [
  { title: 'Hauptbild', value: 'main' },
  { title: 'Galerie', value: 'gallery' },
  { title: 'Detail', value: 'detail' }
]

// Validierung
const required = v => !!v || 'Dieses Feld ist erforderlich'

// --------------------------------------------------------------
// Daten laden (mit Duplikatvermeidung)
// --------------------------------------------------------------
const loadProduct = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/get_product_details_stock.php?id=${productId.value}`)
    if (response.data.success) {
      const data = response.data.product

      // Allgemeine Daten
      Object.assign(product, data.article)
      Object.assign(specifics, data.specifics)
      Object.assign(shipping, data.shipping)

      // Bilder zusammenführen – Hauptbild nur einmal
      const images = []
      const mainImageUrl = data.article.main_image || ''

      // Hauptbild (falls vorhanden)
      if (mainImageUrl) {
        images.push({
          id: null,
          url: mainImageUrl,
          type: 'main'
        })
      }

      // Galeriebilder – nur hinzufügen, wenn sie nicht der Hauptbild-URL entsprechen
      if (data.images && data.images.length) {
        data.images.forEach(img => {
          if (img.url !== mainImageUrl) {
            images.push({
              id: img.id || null,
              url: img.url,
              type: img.type || 'gallery'
            })
          }
        })
      }

      allImages.value = images
    } else {
      throw new Error(response.data.error || 'Fehler beim Laden')
    }
  } catch (error) {
    console.error('Fehler:', error)
    snackbar.value = {
      show: true,
      text: `❌ Fehler beim Laden: ${error.message}`,
      color: 'error'
    }
    setTimeout(() => router.push('/products'), 2000)
  } finally {
    loading.value = false
  }
}

// --------------------------------------------------------------
// Artikeltyp ändern
// --------------------------------------------------------------
const onArticleTypeChange = () => {
  Object.keys(specifics).forEach(key => delete specifics[key])
}

// --------------------------------------------------------------
// Bildverwaltung
// --------------------------------------------------------------
const addImage = () => {
  allImages.value.push({
    id: null,
    url: '',
    type: 'gallery'
  })
}

const removeImage = (idx) => {
  const img = allImages.value[idx]
  if (img.type === 'main') {
    const nextMain = allImages.value.find((_, i) => i !== idx)
    if (nextMain) {
      nextMain.type = 'main'
    } else {
      allImages.value.push({
        id: null,
        url: '',
        type: 'main'
      })
    }
  }
  allImages.value.splice(idx, 1)
}

const handleImageError = (event, idx) => {
  event.target.src = 'https://placehold.co/300x200?text=Fehler'
}

// --------------------------------------------------------------
// Speichern
// --------------------------------------------------------------
const submit = async () => {
  const { valid: isValid } = await formRef.value.validate()
  if (!isValid) return

  const mainImageObj = allImages.value.find(img => img.type === 'main')
  if (!mainImageObj || !mainImageObj.url) {
    snackbar.value = {
      show: true,
      text: '❌ Bitte legen Sie ein Hauptbild fest (Typ "Hauptbild")',
      color: 'error'
    }
    return
  }

  product.main_image = mainImageObj.url

  const galleryImages = allImages.value
    .filter(img => img.type !== 'main')
    .map((img, idx) => ({
      id: img.id || null,
      url: img.url,
      type: img.type || 'gallery',
      order: idx + 1
    }))

  submitting.value = true
  try {
    const payload = {
      product_id: productId.value,
      article: { ...product },
      specifics: { ...specifics },
      shipping: { ...shipping },
      images: galleryImages
    }

    const response = await axios.post(
      '/api/update_product.php',
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    )

    if (response.data.success) {
      snackbar.value = {
        show: true,
        text: '✅ Produkt erfolgreich aktualisiert!',
        color: 'success'
      }
      redirecting.value = true
      setTimeout(() => {
        router.push('/products')
      }, 2000)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error('Fehler beim Aktualisieren:', error)
    snackbar.value = {
      show: true,
      text: `❌ Fehler: ${error.message}`,
      color: 'error'
    }
    redirecting.value = false
  } finally {
    submitting.value = false
  }
}

// --------------------------------------------------------------
// Abbrechen
// --------------------------------------------------------------
const cancel = () => {
  router.push('/dashboard')
}

// --------------------------------------------------------------
// Lifecycle
// --------------------------------------------------------------
onMounted(() => {
  loadProduct()
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
.gap-4 {
  gap: 16px;
}
.action-btn {
  border-radius: 50px;
  box-shadow: 5px 5px 5px rgba(0,0,0,0.2);
  color: rgb(17, 90, 10);
}
.back-btn {
  border-radius: 50px;
  box-shadow: 5px 5px 5px rgba(0,0,0,0.2);
}
.floating-help-btn {
  position: fixed;
  top: 50%;
  right: 0;
  transform: translateY(-50%);
  background: #1976d2;
  width: 50px;
  height: 50px;
  border-radius: 10px 0 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2000;
  box-shadow: -3px 3px 10px rgba(0,0,0,0.2);
  transition: right 0.3s ease;
}
.floating-help-btn.open {
  right: 400px;
}
.floating-help-btn:hover {
  background: #1565c0;
}
.h-100 {
  height: 100%;
}
</style>