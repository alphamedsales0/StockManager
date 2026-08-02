<template>
  <v-container fluid>
    <v-card class="pa-4">

      <!-- HEADER -->
      <div class="d-flex justify-space-between align-start mb-4">
        <div>
          <div class="d-flex align-center">
            <v-icon start size="28">mdi-plus-box</v-icon>
            <h1 class="text-h5 font-weight-bold mb-0">Neues Produkt</h1>
          </div>
          <p class="text-caption text-grey-darken-1 mt-1 mb-0">
            Fügen Sie ein neues Produkt in Ihrem Online Shop ein
          </p>
        </div>

        <div class="d-flex gap-2">
          <v-btn variant="outlined" @click="cancel" prepend-icon="mdi-arrow-left" class="back-btn">
            Zurück
          </v-btn>
          <v-btn @click="submit"
                 prepend-icon="mdi-check"
                 :loading="submitting"
                 :disabled="!valid"
                 class="action-btn">
            Hinzufügen
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
              <!-- 1. Zeile: name + brand (als Combobox) -->
              <v-col cols="12" md="6">
                <v-text-field v-model="product.name" label="Produktname *" :rules="[required]" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="6">
                <!-- ====== NEU: Combobox für Marke ====== -->
                <v-combobox
                  v-model="product.brand"
                  :items="brands"
                  item-title="name"
                  label="Marke *"
                  :rules="[required]"
                  variant="outlined"
                  @update:model-value="onBrandChange"
                  no-filter
                >
              <template #no-data>
                <v-list-item>
                  <span class="text-caption">Keine Marke gefunden. Drücken Sie Enter, um eine neue Marke anzulegen.</span>
                </v-list-item>
              </template>
              </v-combobox>
              </v-col>

              <!-- 2. Zeile: category, article_type, article_number -->
              <v-col cols="12" md="4">
                <v-select 
                  v-model="product.category" 
                  :items="categories" 
                  item-title="name" 
                  item-value="value"
                  label="Kategorie *" 
                  :rules="[required]" 
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select 
                  v-model="product.article_type" 
                  :items="articleTypes" 
                  item-title="name" 
                  item-value="value"
                  label="Artikeltyp *" 
                  :rules="[required]" 
                  variant="outlined"
                  @update:model-value="onArticleTypeChange"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="product.article_number" label="Artikelnummer *" :rules="[required]" variant="outlined"/>
              </v-col>

              <!-- 3. Zeile: price, color, warranty_years -->
              <v-col cols="12" md="4">
                <v-text-field v-model="product.price" label="Preis (€) *" type="number" :rules="[required]" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="product.color" label="Farbe" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="product.warranty_years" label="Garantie (Jahre)" type="number" variant="outlined"/>
              </v-col>

              <!-- 4. Zeile: weight_capacity, power_supply, application_area -->
              <v-col cols="12" md="4">
                <v-text-field v-model="product.weight_capacity" label="Tragfähigkeit" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="product.power_supply" label="Stromversorgung" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="product.application_area" label="Anwendungsbereich" variant="outlined"/>
              </v-col>

              <!-- Beschreibung -->
              <v-col cols="12">
                <v-textarea v-model="product.description" label="Beschreibung" rows="3" variant="outlined"/>
              </v-col>

              <!-- Checkboxen -->
              <v-col cols="12">
                <div class="d-flex flex-wrap gap-4 mt-2">
                  <v-checkbox v-model="product.in_stock" label="Auf Lager" hide-details></v-checkbox>
                  <v-checkbox v-model="product.is_new" label="Neues Produkt" hide-details></v-checkbox>
                  <v-checkbox v-model="product.best_seller" label="Bestseller" hide-details></v-checkbox>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- ======================== BILDER ======================== -->
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
                  <v-img
                    :src="img.url || 'https://placehold.co/300x200?text=Kein+Bild'"
                    height="200"
                    contain
                    class="mb-2 rounded"
                    @error="handleImageError($event, idx)"
                  />

                  <v-select
                    v-model="img.method"
                    :items="imageUploadMethods"
                    label="Quelle"
                    variant="outlined"
                    density="compact"
                    hide-details
                    class="mb-1"
                    @update:model-value="onImageMethodChange(img)"
                  />

                  <v-text-field
                    v-if="img.method === 'url'"
                    v-model="img.url"
                    label="Bild-URL"
                    variant="outlined"
                    density="compact"
                    hide-details
                    class="mb-1"
                    :rules="img.method === 'url' ? [requiredImage] : []"
                  />

                  <div v-else class="file-input-wrapper mb-1">
                    <input
                      type="file"
                      accept="image/*"
                      @change="onFileSelected($event, idx)"
                      class="file-input"
                    />
                    <span v-if="img.fileName" class="file-name">{{ img.fileName }}</span>
                    <span v-else class="file-placeholder">Keine Datei ausgewählt</span>
                  </div>

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
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.motor_power" label="Motorleistung" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.max_speed" label="Höchstgeschwindigkeit" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.max_inclination" label="Max. Steigung" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.display_type" label="Displaytyp" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.training_programs" label="Trainingsprogramme" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.power_range" label="Leistungsbereich" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.display_info" label="Display-Info" rows="2" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.programs_info" label="Programminfo" rows="2" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.comfort_features" label="Komfortfunktionen" rows="2" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-checkbox v-model="specifics.has_ekg" label="EKG integriert" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.is_foldable" label="Klappbar" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_touchscreen" label="Touchscreen" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_bluetooth" label="Bluetooth" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_heart_rate_monitor" label="Herzfrequenzmessung" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_wifi" label="WLAN" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_speaker" label="Lautsprecher" hide-details></v-checkbox>
                </v-col>
              </v-row>
            </template>

            <!-- Fahrrad -->
            <template v-if="product.article_type === 'bike'">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.resistance_type" label="Widerstandsart" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.max_resistance" label="Max. Widerstand" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.pedal_type" label="Pedaltyp" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.seat_adjustment" label="Sattelverstellung" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.handlebar_adjustment" label="Lenkerverstellung" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.console_features" label="Konsolenfunktionen" rows="3" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-checkbox v-model="specifics.has_backrest" label="Rückenlehne" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_pedal_straps" label="Pedalriemen" hide-details></v-checkbox>
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
              <v-col cols="12" md="4">
                <v-text-field v-model="shipping.shipping_cost" label="Versandkosten (€)" type="number" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="shipping.free_shipping_threshold" label="Kostenloser Versand ab (€)" type="number" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="shipping.shipping_method" label="Versandart" variant="outlined"/>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="shipping.estimated_delivery_days" label="Voraussichtliche Lieferzeit (Tage)" type="number" variant="outlined"/>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card-actions class="justify-end">
          <v-btn variant="outlined" @click="cancel">Abbrechen</v-btn>
          <v-btn color="primary" :loading="submitting" @click="submit" :disabled="!valid">
            Produkt speichern
          </v-btn>
        </v-card-actions>

      </v-form>
    </v-card>

    <!-- Overlay & Snackbar -->
    <v-overlay
      v-model="redirecting"
      class="align-center justify-center"
      persistent
      scrim="rgba(0,0,0,0.7)"
      :z-index="9999"
    >
      <div class="text-center">
        <v-progress-circular indeterminate size="80" color="primary" width="6"/>
        <div class="text-h6 mt-4 text-white">Weiterleitung zum Dashboard...</div>
      </div>
    </v-overlay>

    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      timeout="3000"
      location="top end"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"/>
      </template>
    </v-snackbar>
  </v-container>

  <!-- Floating Help Button -->
  <div
    class="floating-help-btn"
    :class="{ open: guideDrawer }"
    @click="guideDrawer = !guideDrawer"
  >
    <v-icon :icon="guideDrawer ? 'mdi-close' : 'mdi-help-circle'" color="white"/>
  </div>

  <GuideDrawer v-model="guideDrawer"/>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import GuideDrawer from '../../components/dashboard/GuideDrawer.vue'

const router = useRouter()

const valid = ref(false)
const submitting = ref(false)
const redirecting = ref(false)
const formRef = ref(null)
const guideDrawer = ref(false)

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Produktstammdaten
const product = reactive({
  name: '',
  brand: '', // wird über Combobox gesetzt
  category: '',
  price: null,
  main_image: '',
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

// ---------- BILDER ----------
const allImages = ref([])

const imageUploadMethods = [
  { title: 'Bild-URL', value: 'url' },
  { title: 'Bild hochladen', value: 'upload' }
]

const imageTypeOptions = [
  { title: 'Hauptbild', value: 'main' },
  { title: 'Galerie', value: 'gallery' },
  { title: 'Detail', value: 'detail' }
]

// Dynamische Listen für Kategorien, Artikeltypen und Marken
const categories = ref([])
const articleTypes = ref([])
const brands = ref([]) // <-- NEU: Marken

// Regeln
const required = v => !!v || 'Dieses Feld ist erforderlich'
const requiredImage = v => !!v || 'Bitte geben Sie eine Bild-URL ein'

// ---------- MARKEN (NEU) ----------
const loadBrands = async () => {
  try {
    const res = await axios.get('/api/get_brands_stock.php')
    if (res.data.success) {
      brands.value = res.data.items
    }
  } catch (error) {
    console.error('Fehler beim Laden der Marken:', error)
  }
}

const onBrandChange = async (val) => {
  // Wenn der Benutzer einen neuen Text eingegeben hat (String)
  if (typeof val === 'string' && val.trim() !== '') {
    const exists = brands.value.some(b => b.name.toLowerCase() === val.trim().toLowerCase())
    if (!exists) {
      try {
        const newBrand = {
          name: val.trim(),
          value: val.trim().toLowerCase().replace(/\s+/g, '-')
        }
        const res = await axios.post('/api/add_brand.php', newBrand)
        if (res.data.success) {
          brands.value.push(res.data.item)
          product.brand = res.data.item.value // technischer Wert
          snackbar.value = {
            show: true,
            text: '✅ Neue Marke angelegt',
            color: 'success'
          }
        }
      } catch (error) {
        console.error('Fehler beim Anlegen der Marke:', error)
        snackbar.value = {
          show: true,
          text: '❌ Marke konnte nicht angelegt werden',
          color: 'error'
        }
      }
    }
  }
}

// ---------- BILDVERWALTUNG ----------
const addImage = () => {
  allImages.value.push({
    url: '',
    type: 'gallery',
    method: 'url',
    file: null,
    fileName: ''
  })
}

const removeImage = (idx) => {
  const img = allImages.value[idx]
  if (img.type === 'main') {
    const nextMain = allImages.value.find((_, i) => i !== idx && allImages.value[i].url)
    if (nextMain) {
      nextMain.type = 'main'
    }
  }
  allImages.value.splice(idx, 1)
}

const onImageMethodChange = (img) => {
  if (img.method === 'upload') {
    img.url = ''
    img.file = null
    img.fileName = ''
  } else {
    img.file = null
    img.fileName = ''
  }
}

const onFileSelected = (event, idx) => {
  const file = event.target.files[0]
  if (!file) return

  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    alert('Nur Bilddateien (JPEG, PNG, GIF, WEBP) sind erlaubt.')
    event.target.value = ''
    return
  }

  const img = allImages.value[idx]
  img.file = file
  img.fileName = file.name
  img.url = URL.createObjectURL(file)
}

const handleImageError = (event) => {
  event.target.src = 'https://placehold.co/300x200?text=Fehler'
}

// ---------- ARTICLE TYPE CHANGE ----------
const onArticleTypeChange = () => {
  Object.keys(specifics).forEach(key => delete specifics[key])
}

// ---------- OPTIONEN LADEN (Kategorien, Artikeltypen) ----------
const loadOptions = async () => {
  try {
    const [catRes, typeRes] = await Promise.all([
      axios.get('/api/get_categories.php'),
      axios.get('/api/get_article_types.php')
    ])

    let cats = catRes.data
    let types = typeRes.data

    if (cats.success !== undefined) {
      cats = cats.items || []
    } else if (!Array.isArray(cats)) {
      cats = []
    }

    if (types.success !== undefined) {
      types = types.items || []
    } else if (!Array.isArray(types)) {
      types = []
    }

    categories.value = cats
    articleTypes.value = types
  } catch (error) {
    console.error('Fehler beim Laden der Optionen:', error)
    categories.value = []
    articleTypes.value = []
    snackbar.value = {
      show: true,
      text: `❌ Optionen konnten nicht geladen werden: ${error.message}`,
      color: 'error'
    }
  }
}

// ---------- NAVIGATION ----------
const cancel = () => {
  router.push('/products')
}

// ---------- SUBMIT ----------
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

  // Uploads
  const uploadPromises = allImages.value
    .filter(img => img.method === 'upload' && img.file)
    .map(async (img) => {
      const formData = new FormData()
      formData.append('image', img.file)
      try {
        const response = await axios.post(
          '/api/upload_image.php',
          formData,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        if (response.data.success) {
          img.url = response.data.url
          img.file = null
          img.fileName = ''
          img.method = 'url'
        } else {
          throw new Error(response.data.error || 'Upload fehlgeschlagen')
        }
      } catch (error) {
        console.error('Upload error:', error)
        throw new Error(`Upload fehlgeschlagen: ${error.message}`)
      }
    })

  try {
    await Promise.all(uploadPromises)
  } catch (error) {
    snackbar.value = {
      show: true,
      text: `❌ Fehler beim Upload: ${error.message}`,
      color: 'error'
    }
    return
  }

  const missingUrl = allImages.value.some(img => !img.url)
  if (missingUrl) {
    snackbar.value = {
      show: true,
      text: '❌ Bitte geben Sie für alle Bilder eine URL ein oder laden Sie eine Datei hoch.',
      color: 'error'
    }
    return
  }

  product.main_image = mainImageObj.url

  const galleryImages = allImages.value
    .filter(img => img.type !== 'main')
    .map((img, idx) => ({
      url: img.url,
      image_order: idx + 1,
      type: img.type || 'gallery',
      article_name: product.name
    }))

  product.price = parseFloat(product.price) || 0

  const payload = {
    article: {
      name: product.name,
      brand: product.brand, // <-- hier wird die Marke (technischer Wert) übergeben
      category: product.category,
      price: product.price,
      main_image: product.main_image,
      article_type: product.article_type,
      article_number: product.article_number,
      color: product.color || null,
      warranty_years: product.warranty_years || null,
      weight_capacity: product.weight_capacity || null,
      power_supply: product.power_supply || null,
      application_area: product.application_area || null,
      in_stock: product.in_stock ? 1 : 0,
      is_new: product.is_new ? 1 : 0,
      best_seller: product.best_seller ? 1 : 0,
      description: product.description || null
    },
    specifics: { ...specifics },
    shipping: { ...shipping },
    images: galleryImages
  }

  submitting.value = true
  try {
    const response = await axios.post(
      '/api/stock_manager_products.php',
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    )

    if (response.data.success) {
      snackbar.value = {
        show: true,
        text: '✅ Produkt erfolgreich erstellt!',
        color: 'success'
      }
      redirecting.value = true
      setTimeout(() => router.push('/products'), 2000)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler beim Speichern')
    }
  } catch (error) {
    console.error('Fehler beim Erstellen des Produkts:', error)
    let errorMsg = 'Unbekannter Fehler'
    if (error.response) {
      errorMsg = error.response.data?.error || error.response.data?.message || `Server-Fehler (${error.response.status})`
    } else if (error.request) {
      errorMsg = 'Keine Antwort vom Server. Bitte Netzwerk prüfen.'
    } else {
      errorMsg = error.message
    }
    snackbar.value = {
      show: true,
      text: `❌ Fehler: ${errorMsg}`,
      color: 'error'
    }
    redirecting.value = false
  } finally {
    submitting.value = false
  }
}

// ---------- LIFECYCLE ----------
onMounted(() => {
  loadOptions()
  loadBrands()   // <-- Marken laden
  // Standardmäßig ein leeres Hauptbild anlegen
  allImages.value.push({
    url: '',
    type: 'main',
    method: 'url',
    file: null,
    fileName: ''
  })
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
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
.file-input-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 4px 8px;
  background: #f9f9f9;
  min-height: 36px;
  position: relative;
  overflow: hidden;
}
.file-input {
  position: absolute;
  top: 0;
  left: 0;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}
.file-name {
  font-size: 0.9rem;
  color: #333;
  margin-left: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.file-placeholder {
  color: #999;
  font-size: 0.9rem;
  margin-left: 4px;
}
</style>