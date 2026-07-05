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

        <!-- Allgemeine Informationen -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Allgemeine Informationen
          </v-card-title>

          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="product.name" label="Produktname *" :rules="[required]" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field v-model="product.brand" label="Marke *" :rules="[required]" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-select v-model="product.category" :items="categories" label="Kategorie *" :rules="[required]" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-select v-model="product.article_type" :items="articleTypes"
                          label="Artikeltyp *" :rules="[required]"
                          variant="outlined"
                          @update:model-value="onArticleTypeChange"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.article_number" label="Artikelnummer *" :rules="[required]" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.price" label="Preis (€) *" type="number" :rules="[required]" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.color" label="Farbe" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.warranty_years" label="Garantie (Jahre)" type="number" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.weight_capacity" label="Tragfähigkeit" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.power_supply" label="Stromversorgung" variant="outlined"/>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field v-model="product.application_area" label="Anwendungsbereich" variant="outlined"/>
              </v-col>

              <v-col cols="12">
                <v-textarea v-model="product.description" label="Beschreibung" rows="3" variant="outlined"/>
              </v-col>

              <v-col cols="12">
                <v-checkbox v-model="product.in_stock" label="Auf Lager" hide-details></v-checkbox>
                <v-checkbox v-model="product.is_new" label="Neues Produkt" hide-details></v-checkbox>
                <v-checkbox v-model="product.best_seller" label="Bestseller" hide-details></v-checkbox>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Hauptbild -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Hauptbild
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="product.main_image"
              label="URL des Hauptbilds *"
              :rules="[required]"
              variant="outlined"
              hint="URL des Hauptbilds des Produkts"
            ></v-text-field>
            <v-img
              v-if="product.main_image"
              :src="product.main_image"
              height="150"
              class="mt-2"
              cover
            ></v-img>
          </v-card-text>
        </v-card>

        <!-- Artikelspezifische Details -->
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

            <!-- NEU: Kraftgeräte -->
            <template v-if="product.article_type === 'strength'">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.weight_stack_kg" label="Gewichtsstapel (kg)" type="number" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.max_user_weight_kg" label="Max. Benutzergewicht (kg)" type="number" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.dimensions" label="Abmessungen (L x B x H)" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.adjustment_range" label="Verstellbereich" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.color_options" label="Farboptionen" variant="outlined"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="specifics.frame_material" label="Rahmenmaterial" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="specifics.muscle_groups_targeted" label="Trainierte Muskelgruppen" rows="2" variant="outlined"/>
                </v-col>
                <v-col cols="12">
                  <v-checkbox v-model="specifics.has_adjustable_seat" label="Verstellbarer Sitz" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_adjustable_backrest" label="Verstellbare Rückenlehne" hide-details></v-checkbox>
                  <v-checkbox v-model="specifics.has_digital_display" label="Digitales Display" hide-details></v-checkbox>
                </v-col>
              </v-row>
            </template>
          </v-card-text>
        </v-card>

        <!-- Bildergalerie -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Bildergalerie
          </v-card-title>
          <v-card-text>
            <div v-for="(img, idx) in additionalImages" :key="idx" class="d-flex align-center mb-2">
              <v-text-field
                v-model="img.url"
                label="Bild-URL"
                variant="outlined"
                density="compact"
                class="mr-2"
              ></v-text-field>
              <v-select
                v-model="img.type"
                :items="['gallery', 'main']"
                label="Typ"
                variant="outlined"
                density="compact"
                class="mr-2"
                style="width: 120px"
              ></v-select>
              <v-btn icon variant="text" color="error" @click="removeImage(idx)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </div>
            <v-btn variant="tonal" @click="addImage">
              <v-icon>mdi-plus</v-icon> Bild hinzufügen
            </v-btn>
          </v-card-text>
        </v-card>

        <!-- Lieferregel -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            Lieferung
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="shipping.shipping_cost"
                  label="Versandkosten (€)"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="shipping.free_shipping_threshold"
                  label="Kostenloser Versand ab (€)"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="shipping.shipping_method"
                  label="Versandart"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="shipping.estimated_delivery_days"
                  label="Voraussichtliche Lieferzeit (Tage)"
                  type="number"
                  variant="outlined"
                />
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

    <!-- GANZSEITIGER LOADER BEI WEITERLEITUNG -->
    <v-overlay
      v-model="redirecting"
      class="align-center justify-center"
      persistent
      scrim="rgba(0,0,0,0.7)"
      :z-index="9999"
    >
      <div class="text-center">
        <v-progress-circular
          indeterminate
          size="80"
          color="primary"
          width="6"
        ></v-progress-circular>
        <div class="text-h6 mt-4 text-white">Weiterleitung zum Dashboard...</div>
      </div>
    </v-overlay>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      timeout="3000"
      location="top end"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-container>

  <!-- FLOATING HELP BUTTON -->
  <div
    class="floating-help-btn"
    :class="{ open: guideDrawer }"
    @click="guideDrawer = !guideDrawer"
  >
    <v-icon :icon="guideDrawer ? 'mdi-close' : 'mdi-help-circle'" color="white"></v-icon>
  </div>

  <!-- DRAWER GUIDE -->
  <GuideDrawer v-model="guideDrawer" />
</template>

<script setup>
import { ref, reactive } from 'vue'
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
  brand: '',
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

// Artikelspezifische Details (dynamisch)
const specifics = reactive({})

// Versand
const shipping = reactive({
  shipping_cost: null,
  free_shipping_threshold: null,
  shipping_method: '',
  estimated_delivery_days: null
})

// Zusätzliche Bilder
const additionalImages = ref([])

// Auswahllisten
const categories = [
  { title: 'Kardio', value: 'cardio' },
  { title: 'Kraft', value: 'strength' },        // <- neu
  { title: 'Rehabilitation', value: 'rehabilitation' },
  { title: 'Zubehör', value: 'accessories' }
]

const articleTypes = [
  { title: 'Laufband', value: 'treadmill' },
  { title: 'Fahrrad', value: 'bike' },
  { title: 'Kraftgerät', value: 'strength' }    // <- neu
]

const required = v => !!v || 'Dieses Feld ist erforderlich'

const onArticleTypeChange = () => {
  // Leert die spezifischen Felder beim Wechsel
  Object.keys(specifics).forEach(key => delete specifics[key])
}

// Bildergalerie
const addImage = () => {
  additionalImages.value.push({ url: '', type: 'gallery', order: additionalImages.value.length + 1 })
}
const removeImage = (idx) => {
  additionalImages.value.splice(idx, 1)
}

// Abbrechen
const cancel = () => {
  router.push('/dashboard')
}

// Submit
const submit = async () => {
  const { valid: isValid } = await formRef.value.validate()
  if (!isValid) return

  submitting.value = true
  try {
    const payload = {
      article: { ...product },
      specifics: { ...specifics },
      shipping: { ...shipping },
      images: additionalImages.value.map((img, idx) => ({
        ...img,
        image_order: idx + 1,
        article_name: product.name
      }))
    }

    const response = await axios.post(
      'https://alpha-med-care.com/api/stock_manager_products.php',
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    )

    if (response.data.success) {
      snackbar.value = {
        show: true,
        text: '✅ Das Produkt wurde erfolgreich erstellt!',
        color: 'success'
      }
      redirecting.value = true
      setTimeout(() => {
        router.push('/dashboard')
      }, 2000)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error('Fehler beim Erstellen des Produkts:', error)
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
</style>