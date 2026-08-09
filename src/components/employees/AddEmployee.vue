<template>
  <v-container fluid class="pa-6">
    <!-- HEADER -->
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title class="d-flex align-center pa-6">
        <v-avatar color="primary" size="52" class="mr-4">
          <v-icon size="30">mdi-account-plus</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">Neuer Mitarbeiter</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Mitarbeiterkonto und persönliche Daten erstellen
          </p>
        </div>
      </v-card-title>
    </v-card>

    <v-form ref="form" @submit.prevent="submit">
      <v-row>
        <!-- LINKER BEREICH -->
        <v-col cols="12" md="6">
          <!-- PERSÖNLICHE DATEN (inkl. E‑Mail) -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="primary" class="mr-2">mdi-account-outline</v-icon>
              Persönliche Daten
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.vorname"
                    label="Vorname"
                    variant="outlined"
                    prepend-inner-icon="mdi-account"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.nachname"
                    label="Nachname"
                    variant="outlined"
                    prepend-inner-icon="mdi-account"
                    :rules="[rules.required]"
                  />
                </v-col>
              </v-row>

              <v-text-field
                v-model="employee.mitarbeiter_nummer"
                label="Mitarbeiter Nummer"
                variant="outlined"
                prepend-inner-icon="mdi-badge-account"
                readonly
              />

              <v-text-field
                v-model="employee.email"
                label="E-Mail"
                variant="outlined"
                prepend-inner-icon="mdi-email-outline"
                type="email"
                :rules="[rules.required]"
              />

              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.telefon"
                    label="Telefon"
                    variant="outlined"
                    prepend-inner-icon="mdi-phone"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.mobil"
                    label="Mobil"
                    variant="outlined"
                    prepend-inner-icon="mdi-cellphone"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- NEUE KARTE: VERSICHERUNG & STEUER-ID (zwischen Persönliche Daten und Foto) -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="primary" class="mr-2">mdi-shield-account</v-icon>
              Versicherung &amp; Steuer-ID
            </v-card-title>
            <v-card-text>
              <!-- Steuer-ID und SV-Nummer -->
              <v-text-field
                v-model="employee.steuer_id"
                label="Steuer-ID"
                variant="outlined"
                prepend-inner-icon="mdi-identifier"
              />
              <v-text-field
                v-model="employee.sozialversicherungsnummer"
                label="Sozialversicherungsnummer"
                variant="outlined"
                prepend-inner-icon="mdi-card-account-details"
              />

              <v-divider class="my-4" />

              <!-- Versicherungsdaten -->
              <v-select
                v-model="employee.versicherung_typ"
                label="Versicherungstyp"
                variant="outlined"
                prepend-inner-icon="mdi-tag"
                :items="['Krankenversicherung', 'Pflegeversicherung', 'Unfallversicherung', 'Berufsunfähigkeit', 'Sonstige']"
              />
              <v-text-field
                v-model="employee.versicherung_gesellschaft"
                label="Versicherungsgesellschaft"
                variant="outlined"
                prepend-inner-icon="mdi-domain"
              />
              <v-text-field
                v-model="employee.versicherung_nummer"
                label="Versicherungsnummer"
                variant="outlined"
                prepend-inner-icon="mdi-card-bulleted"
              />
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.versicherung_gueltig_ab"
                    label="Gültig ab"
                    type="date"
                    variant="outlined"
                    prepend-inner-icon="mdi-calendar-start"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.versicherung_gueltig_bis"
                    label="Gültig bis"
                    type="date"
                    variant="outlined"
                    prepend-inner-icon="mdi-calendar-end"
                  />
                </v-col>
              </v-row>
              <v-text-field
                v-model="employee.versicherung_beitrag"
                label="Monatlicher Beitrag (€)"
                type="number"
                variant="outlined"
                prepend-inner-icon="mdi-cash"
                prefix="€"
              />
            </v-card-text>
          </v-card>

          <!-- FOTO UPLOAD -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="primary" class="mr-2">mdi-camera-account</v-icon>
              Mitarbeiter Foto
            </v-card-title>
            <v-card-text class="text-center">
              <v-avatar size="160" color="grey-lighten-3" class="mb-5">
                <img
                  v-if="photoPreview"
                  :src="photoPreview"
                  style="width:100%;height:100%;object-fit:cover;"
                />
                <v-icon v-else size="90" color="grey">mdi-account</v-icon>
              </v-avatar>
              <v-file-input
                label="Foto auswählen"
                accept="image/*"
                variant="outlined"
                prepend-inner-icon="mdi-camera"
                @change="selectPhoto"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <!-- RECHTER BEREICH (unverändert) -->
        <v-col cols="12" md="6">
          <!-- BERUFLICHE DATEN -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="primary" class="mr-2">mdi-briefcase-outline</v-icon>
              Berufliche Daten
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="employee.position"
                label="Position"
                variant="outlined"
                prepend-inner-icon="mdi-account-tie"
              />
              <v-text-field
                v-model="employee.abteilung"
                label="Abteilung"
                variant="outlined"
                prepend-inner-icon="mdi-office-building"
              />
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.einstellungsdatum"
                    label="Einstellungsdatum"
                    type="date"
                    variant="outlined"
                    prepend-inner-icon="mdi-calendar-start"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="employee.geburtsdatum"
                    label="Geburtsdatum"
                    type="date"
                    variant="outlined"
                    prepend-inner-icon="mdi-calendar-account"
                  />
                </v-col>
              </v-row>
              <v-text-field
                v-model="employee.gehalt"
                label="Gehalt"
                prefix="€"
                type="number"
                variant="outlined"
                prepend-inner-icon="mdi-cash"
              />
            </v-card-text>
          </v-card>

          <!-- NOTFALLKONTAKT -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
              Notfallkontakt
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="employee.notfall_kontakt_name"
                label="Name"
                variant="outlined"
                prepend-inner-icon="mdi-account-alert"
              />
              <v-text-field
                v-model="employee.notfall_kontakt_telefon"
                label="Telefon"
                variant="outlined"
                prepend-inner-icon="mdi-phone-alert"
              />
            </v-card-text>
          </v-card>

          <!-- ADRESSE -->
          <v-card class="mb-5 rounded-xl" elevation="2">
            <v-card-title>
              <v-icon color="primary" class="mr-2">mdi-home-outline</v-icon>
              Adresse
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="employee.adresse.strasse"
                label="Straße"
                variant="outlined"
                prepend-inner-icon="mdi-road"
              />
              <v-text-field
                v-model="employee.adresse.hausnummer"
                label="Hausnummer"
                variant="outlined"
                prepend-inner-icon="mdi-numeric"
              />
              <v-row>
                <v-col cols="12" sm="4">
                  <v-text-field
                    v-model="employee.adresse.plz"
                    label="PLZ"
                    variant="outlined"
                    prepend-inner-icon="mdi-mailbox"
                  />
                </v-col>
                <v-col cols="12" sm="8">
                  <v-text-field
                    v-model="employee.adresse.stadt"
                    label="Stadt"
                    variant="outlined"
                    prepend-inner-icon="mdi-city"
                  />
                </v-col>
              </v-row>
              <v-select
                v-model="employee.adresse.land"
                label="Land"
                variant="outlined"
                prepend-inner-icon="mdi-earth"
                :items="['Deutschland', 'Frankreich', 'Belgien', 'Österreich', 'Schweiz']"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- BUTTONS -->
      <v-card class="mt-6 rounded-xl" elevation="2">
        <v-card-actions class="pa-5">
          <v-spacer />
          <v-btn variant="outlined" color="grey" size="large" prepend-icon="mdi-close" @click="$router.back()">
            Abbrechen
          </v-btn>
          <v-btn color="primary" variant="flat" size="large" type="submit" :loading="loading" prepend-icon="mdi-content-save" class="ml-3">
            Mitarbeiter speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>

    <!-- SNACKBAR -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" location="bottom right" timeout="3000">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const form = ref(null)
const loading = ref(false)

// PHOTO
const photoFile = ref(null)
const photoPreview = ref(null)

const selectPhoto = (event) => {
  const file = event.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

// SNACKBAR
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// EMPLOYEE DATA (mit allen Feldern)
const employee = ref({
  email: '',
  vorname: '',
  nachname: '',
  mitarbeiter_nummer: '',
  telefon: '',
  mobil: '',
  position: '',
  abteilung: '',
  einstellungsdatum: '',
  geburtsdatum: '',
  gehalt: null,
  notfall_kontakt_name: '',
  notfall_kontakt_telefon: '',
  steuer_id: '',
  sozialversicherungsnummer: '',
  versicherung_typ: 'Krankenversicherung',
  versicherung_gesellschaft: '',
  versicherung_nummer: '',
  versicherung_gueltig_ab: '',
  versicherung_gueltig_bis: '',
  versicherung_beitrag: null,
  adresse: {
    strasse: '',
    hausnummer: '',
    plz: '',
    stadt: '',
    land: 'Deutschland'
  }
})

// RULES
const rules = {
  required: value => !!value || 'Pflichtfeld erforderlich'
}

// SUBMIT
const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) return

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('employee', JSON.stringify(employee.value))
    if (photoFile.value) {
      formData.append('photo', photoFile.value)
    }

    const response = await axios.post('/api/employees_create.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data.success) {
      snackbarText.value = 'Mitarbeiter erfolgreich erstellt – Zugangsdaten wurden per E‑Mail gesendet.'
      snackbarColor.value = 'success'
      snackbar.value = true
      setTimeout(() => router.push('/employees'), 1200)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error(error)
    snackbarText.value = error.message || 'Fehler beim Speichern'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.v-card { transition: .25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-card-title { font-weight: 600; letter-spacing: .3px; }
.v-btn { text-transform: none; font-weight: 600; }
</style>