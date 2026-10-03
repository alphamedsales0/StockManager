<template>
  <v-container fluid>
    <v-card class="pa-4" v-if="!loading">
      <!-- HEADER -->
      <div class="d-flex justify-space-between align-start mb-4 flex-wrap">
        <div>
          <div class="d-flex align-center flex-wrap">
            <v-icon start size="28">mdi-pencil-box</v-icon>
            <h1 class="text-h5 font-weight-bold mb-0">Produkt bearbeiten</h1>

            <v-chip v-if="product.is_used" color="deep-orange" size="small" class="ml-3">
              <v-icon start size="14">mdi-recycle-variant</v-icon>
              Gebraucht
            </v-chip>
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
            color="error"
            variant="tonal"
            @click="confirmDelete"
            prepend-icon="mdi-delete"
            class="delete-btn"
          >
            Löschen
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
        <v-card
          variant="outlined"
          class="mb-6"
          :style="product.is_used ? 'border-color: #ff9800; border-width: 2px;' : ''"
        >
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2">
            <v-icon start size="20" color="primary">mdi-information</v-icon>
            Allgemeine Informationen
          </v-card-title>

          <v-card-text>
            <v-row>
              <!-- ============================================== -->
              <!-- COLONNE GAUCHE : PRODUKTIDENTIFIKATION -->
              <!-- ============================================== -->
              <v-col cols="12" md="6" class="pr-md-4">
                <div class="section-header mb-3">
                  <v-icon size="18" color="primary" class="mr-2">mdi-tag-multiple</v-icon>
                  <span class="section-title">Produktidentifikation</span>
                </div>

                <v-row dense>
                  <v-col cols="12">
                    <v-text-field
                      v-model="product.name"
                      label="Produktname *"
                      :rules="[required]"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-rename-box"
                    />
                  </v-col>

                  <!-- MARKEN : select + bouton d'ajout -->
                  <v-col cols="12">
                    <div class="d-flex ga-2 align-start">
                      <v-select
                        v-model="product.brand"
                        :items="brands"
                        item-title="name"
                        item-value="name"
                        label="Marke *"
                        :rules="[required]"
                        variant="outlined"
                        density="comfortable"
                        prepend-inner-icon="mdi-tag"
                        clearable
                        class="flex-grow-1"
                      />
                      <v-btn
                        icon="mdi-plus"
                        variant="tonal"
                        color="primary"
                        size="small"
                        class="mt-1"
                        @click="openBrandDialog"
                        title="Neue Marke anlegen"
                      />
                    </div>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="product.category"
                      :items="categories"
                      item-title="name"
                      item-value="value"
                      label="Kategorie *"
                      :rules="[required]"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-folder"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="product.article_type"
                      :items="articleTypes"
                      item-title="name"
                      item-value="value"
                      label="Artikeltyp *"
                      :rules="[required]"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-shape"
                      @update:model-value="onArticleTypeChange"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.article_number"
                      label="Artikelnummer *"
                      :rules="[required]"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-barcode"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.subcategory"
                      label="Unterkategorie"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-folder-multiple"
                    />
                  </v-col>

                  <!-- FARBE : select avec pastille -->
                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="product.color"
                      :items="farbenListe"
                      item-title="bezeichnung"
                      item-value="schluessel"
                      label="Standardfarbe"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-palette"
                      clearable
                    >
                      <template #item="{ props, item }">
                        <v-list-item v-bind="props">
                          <template #prepend>
                            <span
                              class="farbe-swatch-mini"
                              :style="{ backgroundColor: item.raw.hex_code }"
                            ></span>
                          </template>
                        </v-list-item>
                      </template>

                      <template #selection="{ item }">
                        <span class="d-flex align-center ga-2">
                          <span
                            class="farbe-swatch-mini"
                            :style="{ backgroundColor: item.raw.hex_code }"
                          ></span>
                          {{ item.raw.bezeichnung }}
                        </span>
                      </template>
                    </v-select>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="product.currency"
                      :items="['EUR', 'CHF', 'USD']"
                      label="Währung"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-currency-eur"
                    />
                  </v-col>
                </v-row>
              </v-col>

              <!-- ============================================== -->
              <!-- COLONNE DROITE : PREIS & VERFÜGBARKEIT -->
              <!-- ============================================== -->
              <v-col cols="12" md="6" class="pl-md-4">
                <div class="section-header mb-3">
                  <v-icon size="18" color="primary" class="mr-2">mdi-cash-multiple</v-icon>
                  <span class="section-title">Preis & Verfügbarkeit</span>
                </div>

                <v-row dense>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.price"
                      label="Preis (€) *"
                      type="number"
                      :rules="[required]"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-currency-eur"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.offer_price"
                      label="Angebotspreis (€)"
                      type="number"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-sale"
                      hint="Leer = kein Angebot"
                      persistent-hint
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.warranty_years"
                      label="Garantie (Jahre)"
                      type="number"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-certificate"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.weight_capacity"
                      label="Tragfähigkeit (kg)"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-weight"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.power_supply"
                      label="Stromversorgung"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-power-plug"
                    />
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="product.application_area"
                      label="Anwendungsbereich"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-map-marker"
                    />
                  </v-col>

                  <v-col cols="12">
                    <v-textarea
                      v-model="product.description"
                      label="Beschreibung"
                      rows="3"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-text"
                    />
                  </v-col>

                  <v-col cols="12">
                    <div class="status-section">
                      <div class="text-caption text-grey-darken-1 mb-2 font-weight-bold">
                        <v-icon size="14" class="mr-1">mdi-flag</v-icon>
                        Produktstatus
                      </div>
                      <div class="d-flex flex-wrap gap-3">
                        <v-checkbox
                          v-model="product.in_stock"
                          label="Auf Lager"
                          hide-details
                          color="success"
                          density="compact"
                          class="ma-0"
                        />
                        <v-checkbox
                          v-model="product.is_new"
                          label="Neu"
                          hide-details
                          color="blue"
                          density="compact"
                          class="ma-0"
                        />
                        <v-checkbox
                          v-model="product.best_seller"
                          label="Bestseller"
                          hide-details
                          color="orange"
                          density="compact"
                          class="ma-0"
                        />
                        <v-checkbox
                          v-model="product.is_offer"
                          label="Angebot"
                          hide-details
                          color="red"
                          density="compact"
                          class="ma-0"
                        />
                      </div>
                    </div>
                  </v-col>
                </v-row>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- ======================== GEBRAUCHT ======================== -->
        <v-card variant="outlined" class="mb-6">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-3 py-2 d-flex align-center">
            <v-icon start color="deep-orange">mdi-recycle-variant</v-icon>
            Gebraucht-Status
          </v-card-title>

          <v-card-text>
            <v-row>
              <v-col cols="12">
                <v-checkbox
                  v-model="product.is_used"
                  label="Dieses Produkt ist gebraucht"
                  color="deep-orange"
                  hide-details
                  class="mt-0"
                />
              </v-col>
            </v-row>

            <v-row v-if="product.is_used">
              <v-col cols="12" md="6">
                <v-select
                  v-model="product.condition"
                  :items="conditionOptions"
                  item-title="name"
                  item-value="value"
                  label="Zustand *"
                  variant="outlined"
                  :rules="product.is_used ? [required] : []"
                  color="deep-orange"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="product.used_price"
                  label="Gebrauchtpreis (€) *"
                  type="number"
                  variant="outlined"
                  :rules="product.is_used ? [required] : []"
                  color="deep-orange"
                  prefix="€"
                  hint="Preis für das Gebrauchtgerät (netto)"
                  persistent-hint
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="product.used_description"
                  label="Beschreibung des Gebrauchtzustands"
                  variant="outlined"
                  rows="3"
                  color="deep-orange"
                  hint="z.B. Geprüftes Gebrauchtgerät, 12 Monate Garantie, optische Gebrauchsspuren..."
                  persistent-hint
                />
              </v-col>

              <v-col cols="12" v-if="product.used_price && product.price">
                <v-alert
                  color="deep-orange"
                  variant="tonal"
                  density="compact"
                  class="d-flex align-center"
                >
                  <v-icon start>mdi-tag</v-icon>
                  <div>
                    <strong>Ersparnis für den Kunden :</strong>
                    {{ formatPrice(parseFloat(product.price) - parseFloat(product.used_price)) }}
                    <span class="text-caption ml-2">
                      ({{ Math.round((1 - parseFloat(product.used_price) / parseFloat(product.price)) * 100) }}% Rabatt)
                    </span>
                  </div>
                </v-alert>
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
                    @error="handleImageError(idx)"
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
            <template v-if="product.article_type === 'laufband' || product.article_type === 'treadmill'">
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
                  <v-text-field v-model="specifics.spez_display_typ" label="Displaytyp" variant="outlined" />
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
                  <v-textarea v-model="specifics.comfort_features" label="Komfortfunktionen" rows="2" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <div class="d-flex flex-wrap gap-4">
                    <v-checkbox v-model="specifics.spez_hat_ekg" label="EKG integriert" hide-details />
                    <v-checkbox v-model="specifics.spez_ist_klappbar" label="Klappbar" hide-details />
                    <v-checkbox v-model="specifics.spez_hat_touchscreen" label="Touchscreen" hide-details />
                    <v-checkbox v-model="specifics.spez_hat_bluetooth" label="Bluetooth" hide-details />
                    <v-checkbox v-model="specifics.spez_hat_wifi" label="WLAN" hide-details />
                  </div>
                </v-col>
              </v-row>
            </template>

            <template v-if="product.article_type === 'fahrrad' || product.article_type === 'bike'">
              <v-row>
                <v-col cols="12" sm="6" md="4">
                  <v-text-field v-model="specifics.spez_widerstandstyp" label="Widerstandsart" variant="outlined" />
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
                    <v-checkbox v-model="specifics.spez_hat_rueckenlehne" label="Rückenlehne" hide-details />
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
          <v-btn color="error" variant="tonal" @click="confirmDelete" prepend-icon="mdi-delete" class="mr-auto">
            Löschen
          </v-btn>
          <v-btn variant="outlined" @click="cancel">Abbrechen</v-btn>
          <v-btn color="primary" :loading="submitting" @click="submit" :disabled="!valid">
            Änderungen speichern
          </v-btn>
        </v-card-actions>
      </v-form>

      <!-- Dialog : nouvelle marque -->
      <v-dialog v-model="brandDialog" max-width="500" persistent>
        <v-card>
          <v-card-title class="text-h6 bg-primary text-white">
            <v-icon start color="white">mdi-tag-plus</v-icon>
            Neue Marke anlegen
          </v-card-title>
          <v-card-text class="pt-4">
            <v-text-field
              v-model="newBrandName"
              label="Markenname *"
              variant="outlined"
              prepend-inner-icon="mdi-tag"
              :rules="[v => !!v?.trim() || 'Name ist erforderlich']"
              @keyup.enter="saveBrand"
              autofocus
            />
            <div v-if="brandSaving" class="text-caption text-grey mt-2">
              <v-progress-circular indeterminate size="14" width="2" class="mr-2" />
              Wird gespeichert...
            </div>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="brandDialog = false">Abbrechen</v-btn>
            <v-btn color="primary" @click="saveBrand" :loading="brandSaving" :disabled="!newBrandName?.trim()">
              Anlegen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Dialog suppression -->
      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card>
          <v-card-title class="text-h6">
            <v-icon color="error" start>mdi-alert</v-icon>
            Produkt wirklich löschen?
          </v-card-title>
          <v-card-text>
            Möchten Sie das Produkt <strong>{{ product.name }}</strong> (ID: {{ productId }})
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
    </v-card>

    <!-- Loader initial -->
    <v-overlay v-model="loading" class="align-center justify-center" persistent>
      <v-progress-circular indeterminate size="80" color="primary" width="6" />
      <div class="text-h6 mt-4 text-white">Produkt wird geladen...</div>
    </v-overlay>

    <!-- Loader redirect -->
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

  <GuideDrawer v-model="guideDrawer" />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import GuideDrawer from '../../components/dashboard/GuideDrawer.vue'

// ============================================================
// CONFIG
// ============================================================
const IMAGE_BASE_URL = 'https://alpha-med-care.com'

// ============================================================
// ROUTER & STATE
// ============================================================
const router = useRouter()
const route = useRoute()
const productId = ref(parseInt(route.params.id))

const valid = ref(false)
const submitting = ref(false)
const loading = ref(true)
const redirecting = ref(false)
const formRef = ref(null)
const guideDrawer = ref(false)

const deleteDialog = ref(false)
const deleting = ref(false)

// Dialog marque
const brandDialog = ref(false)
const brandSaving = ref(false)
const newBrandName = ref('')

const snackbar = ref({ show: false, text: '', color: 'success' })
const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

// ============================================================
// MODELE
// ============================================================
const product = reactive({
  name: '',
  brand: '',
  category: '',
  subcategory: null,
  article_type: '',
  article_number: '',
  price: null,
  offer_price: null,
  currency: 'EUR',
  color: '',
  warranty_years: null,
  weight_capacity: '',
  power_supply: '',
  application_area: '',
  in_stock: true,
  is_new: false,
  best_seller: false,
  is_offer: false,
  is_used: false,
  condition: '',
  used_price: null,
  used_description: '',
  description: ''
})

const specifics = reactive({})

const shipping = reactive({
  shipping_cost: null,
  free_shipping_threshold: null,
  shipping_method: '',
  estimated_delivery_days: null
})

const allImages = ref([])

const categories = ref([])
const articleTypes = ref([])
const brands = ref([])
const farbenListe = ref([])   // ✅ couleurs de la table `farben`

const conditionOptions = [
  { name: 'Wie neu', value: 'like_new' },
  { name: 'Sehr gut', value: 'very_good' },
  { name: 'Gut', value: 'good' },
  { name: 'Akzeptabel', value: 'acceptable' }
]

const imageUploadMethods = [
  { title: 'Bild-URL', value: 'url' },
  { title: 'Bild hochladen', value: 'upload' }
]

const imageTypeOptions = [
  { title: 'Hauptbild', value: 'main' },
  { title: 'Galerie', value: 'gallery' },
  { title: 'Detail', value: 'detail' }
]

// ============================================================
// HELPERS
// ============================================================
const required = v => !!v || 'Dieses Feld ist erforderlich'

const formatPrice = (v) => {
  if (v === null || v === undefined || isNaN(v)) return '—'
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(v)
}

const buildImageUrl = (path) => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  return IMAGE_BASE_URL + '/' + String(path).replace(/^\/+/, '')
}

const typeMap = {
  'haupt':   'main',
  'galerie': 'gallery',
  'detail':  'detail',
  'main':    'main',
  'gallery': 'gallery'
}

// ============================================================
// BRANDS
// ============================================================
const loadBrands = async () => {
  try {
    const res = await axios.get('/api/get_brands_stock.php')
    if (res.data.success) brands.value = res.data.items
  } catch (e) {
    console.error('Fehler beim Laden der Marken:', e)
  }
}

const openBrandDialog = () => {
  newBrandName.value = ''
  brandDialog.value = true
}

const saveBrand = async () => {
  const name = (newBrandName.value || '').trim()
  if (!name) return

  brandSaving.value = true
  try {
    const res = await axios.post('/api/add_brand_stock.php', { name })
    if (res.data.success) {
      const item = res.data.item
      // Éviter les doublons dans la liste locale
      if (!brands.value.some(b => b.name?.toLowerCase() === item.name.toLowerCase())) {
        brands.value.push(item)
      }
      product.brand = item.name
      brandDialog.value = false
      showSnackbar(res.data.existing ? '✅ Marke bereits vorhanden' : '✅ Neue Marke angelegt', 'success')
    } else {
      throw new Error(res.data.message || 'Unbekannter Fehler')
    }
  } catch (e) {
    console.error(e)
    showSnackbar(`❌ ${e.response?.data?.message || e.message}`, 'error')
  } finally {
    brandSaving.value = false
  }
}

// ============================================================
// FARBEN
// ============================================================
const loadFarben = async () => {
  try {
    const res = await axios.get('/api/get_farben.php')
    if (res.data.success) farbenListe.value = res.data.items || []
  } catch (e) {
    console.error('Fehler beim Laden der Farben:', e)
  }
}

// ============================================================
// OPTIONS
// ============================================================
const loadOptions = async () => {
  try {
    const [catRes, typeRes] = await Promise.all([
      axios.get('/api/get_categories.php?roots=1'),
      axios.get('/api/get_article_types.php')
    ])

    let cats = catRes.data
    let types = typeRes.data
    if (cats.success !== undefined) cats = cats.items || []
    if (types.success !== undefined) types = types.items || []
    if (!Array.isArray(cats)) cats = []
    if (!Array.isArray(types)) types = []

    categories.value = cats
    articleTypes.value = types
  } catch (e) {
    console.error('Fehler beim Laden der Optionen:', e)
    showSnackbar(`❌ Optionen konnten nicht geladen werden: ${e.message}`, 'error')
  }
}

// ============================================================
// LOAD PRODUCT
// ============================================================
const loadProduct = async () => {
  loading.value = true
  try {
    const res = await axios.get(`/api/get_product_details_stock.php?id=${productId.value}`)
    if (!res.data.success) throw new Error(res.data.error || 'Fehler beim Laden')

    const a = res.data.product.article

    product.name             = a.name || ''
    product.brand            = a.brand || ''
    product.category         = a.category || ''
    product.subcategory      = a.subcategory || null
    product.article_type     = a.article_type || ''
    product.article_number   = a.article_number || ''
    product.price            = a.price ?? null
    product.offer_price      = a.offer_price ?? null
    product.currency         = a.currency || 'EUR'
    product.color            = a.color || ''       // ✅ schluessel (ex: "weiss")
    product.warranty_years   = a.warranty_years ?? null
    product.weight_capacity  = a.weight_capacity ?? ''
    product.power_supply     = a.power_supply || ''
    product.application_area = a.application_area || ''
    product.in_stock         = !!a.in_stock
    product.is_new           = !!a.is_new
    product.best_seller      = !!a.best_seller
    product.is_offer         = !!a.is_offer
    product.is_used          = !!a.is_used
    product.condition        = a.condition || ''
    product.used_price       = a.used_price ?? null
    product.used_description = a.used_description || ''
    product.description      = a.description || ''

    Object.keys(specifics).forEach(k => delete specifics[k])
    Object.assign(specifics, res.data.product.specifics || {})

    Object.assign(shipping, res.data.product.shipping || {})

    const images = []
    const main = buildImageUrl(a.main_image || '')

    if (main) {
      images.push({
        id: null,
        url: main,
        type: 'main',
        method: 'url',
        file: null,
        fileName: ''
      })
    }

    ;(res.data.product.images || []).forEach(img => {
      const fullUrl = buildImageUrl(img.url)
      if (fullUrl && fullUrl !== main) {
        images.push({
          id: img.id || null,
          url: fullUrl,
          type: typeMap[img.type] || 'gallery',
          method: 'url',
          file: null,
          fileName: ''
        })
      }
    })

    if (!images.some(i => i.type === 'main')) {
      images.unshift({
        id: null,
        url: main,
        type: 'main',
        method: 'url',
        file: null,
        fileName: ''
      })
    }

    allImages.value = images

  } catch (e) {
    console.error(e)
    showSnackbar(`❌ Fehler beim Laden: ${e.message}`, 'error')
    setTimeout(() => router.push('/products'), 2000)
  } finally {
    loading.value = false
  }
}

// ============================================================
// IMAGES
// ============================================================
const addImage = () => {
  allImages.value.push({
    id: null,
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
    const next = allImages.value.find((im, i) => i !== idx && im.url)
    if (next) next.type = 'main'
    else allImages.value.push({
      id: null,
      url: '',
      type: 'main',
      method: 'url',
      file: null,
      fileName: ''
    })
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

  const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowed.includes(file.type)) {
    alert('Nur Bilddateien (JPEG, PNG, GIF, WEBP) sind erlaubt.')
    event.target.value = ''
    return
  }

  const img = allImages.value[idx]
  img.file = file
  img.fileName = file.name
  img.url = URL.createObjectURL(file)
}

const handleImageError = (idx) => {
  const img = allImages.value[idx]
  if (img) {
    img.url = 'https://placehold.co/300x200?text=Fehler'
  }
}

// ============================================================
// ARTICLE TYPE CHANGE
// ============================================================
const onArticleTypeChange = () => {
  Object.keys(specifics).forEach(k => delete specifics[k])
}

// ============================================================
// NAV
// ============================================================
const cancel = () => router.push(`/products/view/${productId.value}`)

// ============================================================
// SUBMIT
// ============================================================
const submit = async () => {
  const { valid: isValid } = await formRef.value.validate()
  if (!isValid) return

  const mainImageObj = allImages.value.find(i => i.type === 'main')
  if (!mainImageObj || !mainImageObj.url) {
    showSnackbar('❌ Bitte legen Sie ein Hauptbild fest (Typ "Hauptbild")', 'error')
    return
  }

  const uploads = allImages.value
    .filter(i => i.method === 'upload' && i.file)
    .map(async (img) => {
      const fd = new FormData()
      fd.append('image', img.file)
      try {
        const r = await axios.post('/api/upload_image.php', fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (r.data.success) {
          img.url = r.data.url
          img.file = null
          img.fileName = ''
          img.method = 'url'
        } else {
          throw new Error(r.data.error || 'Upload fehlgeschlagen')
        }
      } catch (e) {
        throw new Error(`Upload fehlgeschlagen: ${e.message}`)
      }
    })

  try {
    await Promise.all(uploads)
  } catch (e) {
    showSnackbar(`❌ Fehler beim Upload: ${e.message}`, 'error')
    return
  }

  if (allImages.value.some(i => !i.url)) {
    showSnackbar('❌ Bitte geben Sie für alle Bilder eine URL ein oder laden Sie eine Datei hoch.', 'error')
    return
  }

  product.main_image = mainImageObj.url.replace(IMAGE_BASE_URL, '')

  const galleryImages = allImages.value
    .filter(i => i.type !== 'main')
    .map((i, idx) => ({
      id: i.id || null,
      url: i.url.replace(IMAGE_BASE_URL, ''),
      image_order: idx + 1,
      type: i.type || 'gallery',
      article_name: product.name
    }))

  product.price = parseFloat(product.price) || 0
  product.offer_price = (product.offer_price === null || product.offer_price === '')
    ? null
    : (parseFloat(product.offer_price) || null)
  product.used_price = (product.used_price === null || product.used_price === '')
    ? null
    : (parseFloat(product.used_price) || null)

  function norm(v) {
    if (v && typeof v === 'object') return v.name ?? v.value ?? ''
    return (v ?? '').toString().trim()
  }
  product.brand = norm(product.brand)
  product.category = norm(product.category)
  product.article_type = norm(product.article_type)

  const payload = {
    product_id: productId.value,
    article: { ...product },
    specifics: { ...specifics },
    shipping: { ...shipping },
    images: galleryImages
  }

  submitting.value = true
  try {
    const response = await axios.post('/api/update_product.php', payload, {
      headers: { 'Content-Type': 'application/json' }
    })
    if (response.data.success) {
      showSnackbar('✅ Produkt erfolgreich aktualisiert!', 'success')
      redirecting.value = true
      setTimeout(() => router.push(`/products/view/${productId.value}`), 2000)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (e) {
    console.error(e)
    let msg = 'Unbekannter Fehler'
    if (e.response) msg = e.response.data?.error || `Server-Fehler (${e.response.status})`
    else if (e.request) msg = 'Keine Antwort vom Server.'
    else msg = e.message
    showSnackbar(`❌ Fehler: ${msg}`, 'error')
    redirecting.value = false
  } finally {
    submitting.value = false
  }
}

// ============================================================
// DELETE
// ============================================================
const confirmDelete = () => { deleteDialog.value = true }

const deleteProduct = async () => {
  deleting.value = true
  try {
    const r = await axios.post('/api/delete_product.php', { product_id: productId.value })
    if (r.data?.success === true) {
      showSnackbar('🗑️ Produkt erfolgreich gelöscht.', 'success')
      deleteDialog.value = false
      redirecting.value = true
      setTimeout(() => router.push('/products'), 2000)
    } else {
      throw new Error(r.data?.error || 'Unbekannter Fehler')
    }
  } catch (e) {
    console.error(e)
    let msg = 'Unbekannter Fehler'
    if (e.response) msg = e.response.data?.error || `HTTP ${e.response.status}`
    else if (e.request) msg = 'Keine Antwort vom Server'
    else msg = e.message
    showSnackbar(`❌ Fehler beim Löschen: ${msg}`, 'error')
    deleteDialog.value = false
  } finally {
    deleting.value = false
  }
}

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
  loadOptions()
  loadBrands()
  loadFarben()      // ✅
  loadProduct()
})
</script>

<style scoped>
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.gap-4 { gap: 16px; }

.action-btn {
  border-radius: 50px;
  box-shadow: 5px 5px 5px rgba(0,0,0,0.2);
  color: rgb(17, 90, 10);
}
.back-btn { border-radius: 50px; box-shadow: 5px 5px 5px rgba(0,0,0,0.2); }
.delete-btn { border-radius: 50px; box-shadow: 5px 5px 5px rgba(0,0,0,0.2); }

.floating-help-btn {
  position: fixed; top: 50%; right: 0; transform: translateY(-50%);
  background: #1976d2; width: 50px; height: 50px;
  border-radius: 10px 0 0 10px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 2000; box-shadow: -3px 3px 10px rgba(0,0,0,0.2);
  transition: right 0.3s ease;
}
.floating-help-btn.open { right: 400px; }
.floating-help-btn:hover { background: #1565c0; }

.h-100 { height: 100%; }

.file-input-wrapper {
  display: flex; align-items: center; border: 1px solid #ccc; border-radius: 4px;
  padding: 4px 8px; background: #f9f9f9; min-height: 36px; position: relative; overflow: hidden;
}
.file-input { position: absolute; top: 0; left: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
.file-name { font-size: .9rem; color: #333; margin-left: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.file-placeholder { color: #999; font-size: .9rem; margin-left: 4px; }

/* ============================================================
   SECTIONS ALLGEMEINE INFORMATIONEN
   ============================================================ */
.section-header {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  background: linear-gradient(90deg, #e3f2fd 0%, #f5f5f5 100%);
  border-left: 4px solid #1976d2;
  border-radius: 4px;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1976d2;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-section {
  padding: 10px 14px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px dashed #e0e0e0;
}

/* Pastille de couleur dans le select */
.farbe-swatch-mini {
  display: inline-block;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #ddd;
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 960px) {
  .pr-md-4,
  .pl-md-4 {
    padding-right: 12px !important;
    padding-left: 12px !important;
  }
}

@media (max-width: 600px) {
  .section-title {
    font-size: 0.75rem;
  }

  .section-header {
    padding: 6px 10px;
  }

  .status-section {
    padding: 8px 10px;
  }
}
</style>