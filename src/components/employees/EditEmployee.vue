<template>
  <v-container fluid class="pa-6">
    <!-- HEADER -->
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title class="d-flex align-center pa-6">
        <v-avatar color="primary" size="52" class="mr-4">
          <v-icon size="30">mdi-account-edit</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">Mitarbeiter bearbeiten</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            {{ store.employee.vorname }} {{ store.employee.nachname }} —
            <span class="font-monospace">{{ store.employee.mitarbeiter_nummer }}</span>
          </p>
        </div>
      </v-card-title>
    </v-card>

    <v-form ref="form" @submit.prevent="submit" v-if="loaded">
      <v-row>
        <!-- Linke Spalte -->
        <v-col cols="12" md="6">
          <EmployeePersonalCard />
          <EmployeeAddressCard />
          <EmployeeInsuranceCard />
          <EmployeePhotoUpload
            ref="photoUploadRef"
            :existing-photo="store.employee.foto_pfad"
          />
        </v-col>

        <!-- Rechte Spalte -->
        <v-col cols="12" md="6">
          <EmployeeProfessionalCard />
          <EmployeeBankCard />
          <EmployeeQualificationsCard />
          <EmployeeDocumentsCard />
          <EmployeeEmergencyCard />
        </v-col>
      </v-row>

      <!-- BUTTONS -->
      <v-card class="mt-6 rounded-xl" elevation="2">
        <v-card-actions class="pa-5">
          <v-spacer />
          <v-btn
            variant="outlined"
            color="grey"
            size="large"
            prepend-icon="mdi-close"
            @click="$router.back()"
          >
            Abbrechen
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            size="large"
            type="submit"
            :loading="loading"
            prepend-icon="mdi-content-save"
            class="ml-3"
          >
            Aktualisieren
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>

    <!-- ERREUR DE CHARGEMENT -->
    <v-alert
      v-else-if="loadError"
      type="error"
      variant="tonal"
      class="ma-6"
    >
      <div class="font-weight-bold mb-1">
        Mitarbeiter konnte nicht geladen werden
      </div>
      <div class="mb-2">{{ loadError }}</div>

      <v-expansion-panels v-if="rawResponse" variant="accordion">
        <v-expansion-panel>
          <v-expansion-panel-title>
            Technische Details anzeigen
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <pre class="raw-response">{{ rawResponse }}</pre>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <template #append>
        <v-btn
          variant="text"
          color="error"
          @click="$router.push('/employees')"
        >
          Zurück zur Liste
        </v-btn>
      </template>
    </v-alert>

    <!-- CHARGEMENT -->
    <v-progress-circular
      v-else
      indeterminate
      color="primary"
      class="d-block mx-auto mt-10"
    />

    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="bottom right"
      timeout="3500"
    >
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useEmployeeStore } from '../../stores/employeeStore'

// Kindkomponenten
import EmployeePersonalCard from '../../components/employees/EmployeePersonalCard.vue'
import EmployeeInsuranceCard from '../../components/employees/EmployeeInsuranceCard.vue'
import EmployeePhotoUpload from '../../components/employees/EmployeePhotoUpload.vue'
import EmployeeProfessionalCard from '../../components/employees/EmployeeProfessionalCard.vue'
import EmployeeBankCard from '../../components/employees/EmployeeBankCard.vue'
import EmployeeQualificationsCard from '../../components/employees/EmployeeQualificationsCard.vue'
import EmployeeDocumentsCard from '../../components/employees/EmployeeDocumentsCard.vue'
import EmployeeEmergencyCard from '../../components/employees/EmployeeEmergencyCard.vue'
import EmployeeAddressCard from '../../components/employees/EmployeeAddressCard.vue'

const router = useRouter()
const route = useRoute()
const store = useEmployeeStore()

const form = ref(null)
const loading = ref(false)
const loaded = ref(false)
const photoUploadRef = ref(null)

const loadError = ref(null)
const rawResponse = ref(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// ============================================================
// LECTURE DE L'EMPLOYÉ
// ============================================================
const fetchEmployee = async () => {
  const uid = route.params.uid
  console.log('[EditEmployee] mounted, uid =', uid)

  if (!uid) {
    loadError.value = "Aucun UID fourni dans l'URL."
    return
  }

  loadError.value = null
  rawResponse.value = null

  try {
    // Cache-buster
    const url = `/api/employees_get.php?uid=${encodeURIComponent(uid)}&_t=${Date.now()}`
    console.log('[EditEmployee] GET', url)

    const response = await axios.get(url, {
      withCredentials: true,
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    })

    console.log('[EditEmployee] Status:', response.status)
    console.log('[EditEmployee] response.data:', response.data)

    // Vérifie le Content-Type
    const contentType = response.headers['content-type'] || ''
    if (!contentType.includes('application/json')) {
      rawResponse.value = String(response.data).slice(0, 2000)
      loadError.value =
        "Le serveur a répondu avec du HTML au lieu de JSON. " +
        "Videz le cache du navigateur (F12 → Application → Clear site data)."
      return
    }

    if (response.status !== 200 || response.data?.success !== true) {
      rawResponse.value = JSON.stringify(response.data, null, 2)
      loadError.value =
        response.data?.error ||
        response.data?.message ||
        `HTTP ${response.status} — Réponse inattendue du serveur`
      return
    }

    const data = response.data.employee

    // ---------- Adresse primaire (objet) ----------
    const addresses = data.addresses || []
    const primary =
      addresses.find(a => a.adresstyp === 'primär') ||
      addresses[0] ||
      {}

    data.adresse = {
      strasse:    primary.strasse    || '',
      hausnummer: primary.hausnummer || '',
      plz:        primary.plz        || '',
      stadt:      primary.stadt      || '',
      land:       primary.land       || 'Deutschland'
    }

    // ---------- Sécurité : tableaux ----------
    data.bank_accounts  = data.bank_accounts  || []
    data.qualifications = data.qualifications || []
    data.documents      = data.documents      || []

    // ---------- ✅ Store Pinia : remplit l'objet réactif ----------
    store.setEmployee(data)

  

    // ✅ Active le formulaire
    loaded.value = true

  } catch (error) {
    console.error('[EditEmployee] fetchEmployee error:', error)

    rawResponse.value = JSON.stringify(
      {
        message: error.message,
        status: error.response?.status,
        contentType: error.response?.headers?.['content-type'],
        data: typeof error.response?.data === 'string'
          ? error.response.data.slice(0, 2000)
          : error.response?.data
      },
      null,
      2
    )

    loadError.value =
      error.response?.data?.error ||
      error.response?.data?.message ||
      error.message ||
      'Netzwerkfehler beim Laden des Mitarbeiters.'
  }
}

// ============================================================
// ENVOI DU FORMULAIRE
// ============================================================
const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) return

  loading.value = true
  try {
    const uid = route.params.uid
    const formData = new FormData()

    // ✅ Payload = store.employee direct (déjà réactif et rempli)
    const payload = { ...store.employee }
    if (!payload.password) delete payload.password

    // 🔍 DEBUG TEMPORAIRE
    console.log('=== PAYLOAD UPDATE ===')
    console.log('adresse:', JSON.stringify(payload.adresse, null, 2))
    console.log('Full payload:', JSON.stringify(payload, null, 2))

    formData.append('employee', JSON.stringify(payload))

    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      formData.append('photo', photoUploadRef.value.photoFile)
    }

    if (store.employee.documents && store.employee.documents.length > 0) {
      store.employee.documents.forEach((doc, index) => {
        if (doc.file && doc.file instanceof File) {
          formData.append(`document_${index}`, doc.file)
        }
      })
    }

    const response = await axios.post(
      `/api/employees_update.php?uid=${encodeURIComponent(uid)}&_t=${Date.now()}`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        withCredentials: true
      }
    )

    if (response.data.success) {
      snackbarText.value = 'Mitarbeiter erfolgreich aktualisiert.'
      snackbarColor.value = 'success'
      snackbar.value = true
      setTimeout(() => router.push('/employees'), 1200)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error(error)
    snackbarText.value =
      error.response?.data?.error ||
      error.message ||
      'Serverfehler beim Aktualisieren'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}

onMounted(fetchEmployee)
</script>

<style scoped>
.v-card { transition: .25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-card-title { font-weight: 600; letter-spacing: .3px; }
.v-btn { text-transform: none; font-weight: 600; }
.font-monospace { font-family: monospace; }

.raw-response {
  background: rgba(0, 0, 0, 0.05);
  padding: 12px;
  border-radius: 8px;
  font-size: 12px;
  max-height: 320px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>