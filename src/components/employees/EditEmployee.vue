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
            {{ employee?.vorname }} {{ employee?.nachname }} —
            <span class="font-monospace">{{ employee?.mitarbeiter_nummer }}</span>
          </p>
        </div>
      </v-card-title>
    </v-card>

    <v-form ref="form" @submit.prevent="submit" v-if="employee">
      <v-row>
        <!-- Linke Spalte -->
        <v-col cols="12" md="6">
          <EmployeePersonalCard />
          <EmployeeAddressCard />
          <EmployeeInsuranceCard />
          <EmployeePhotoUpload ref="photoUploadRef" :existing-photo="employee.foto_pfad" />
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

    <v-progress-circular v-else indeterminate color="primary" class="d-block mx-auto mt-10" />

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
const photoUploadRef = ref(null)
const employee = ref(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// ---------- Récupération de l'employé via son UID ----------
const fetchEmployee = async () => {
  const uid = route.params.uid
  if (!uid) {
    router.push('/employees')
    return
  }

  try {
    const response = await axios.get(`/api/employees_get.php?uid=${uid}`)
    if (response.data.success) {
      const data = response.data.employee

      // Extraire l'adresse primaire
      const addresses = data.addresses || []
      const primary = addresses.find(a => a.adresstyp === 'primär') || {}
      data.adresse = {
        strasse:   primary.strasse   || '',
        hausnummer: primary.hausnummer || '',
        plz:       primary.plz       || '',
        stadt:     primary.stadt     || '',
        land:      primary.land      || 'Deutschland'
      }

      // Assurer l'existence des tableaux enfants
      data.bank_accounts   = data.bank_accounts   || []
      data.qualifications  = data.qualifications  || []
      data.documents       = data.documents       || []

      // Hydrater le store Pinia
      store.setEmployee(data)
      employee.value = store.employee
    } else {
      snackbarText.value = 'Fehler beim Laden: ' + (response.data.error || 'Unbekannter Fehler')
      snackbarColor.value = 'error'
      snackbar.value = true
      router.push('/employees')
    }
  } catch (error) {
    console.error('Fehler beim Laden:', error)
    snackbarText.value = 'Netzwerkfehler beim Laden des Mitarbeiters.'
    snackbarColor.value = 'error'
    snackbar.value = true
    router.push('/employees')
  }
}

// ---------- Envoi du formulaire ----------
const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) return

  loading.value = true
  try {
    const uid = route.params.uid

    // Construire le FormData (pour supporter photo + documents)
    const formData = new FormData()

    const payload = { ...store.employee }
    if (!payload.password) delete payload.password
    formData.append('employee', JSON.stringify(payload))

    // Photo
    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      formData.append('photo', photoUploadRef.value.photoFile)
    }

    // Documents
    if (store.employee.documents && store.employee.documents.length > 0) {
      store.employee.documents.forEach((doc, index) => {
        if (doc.file && doc.file instanceof File) {
          formData.append(`document_${index}`, doc.file)
        }
      })
    }

    const response = await axios.post(
      `/api/employees_update.php?uid=${uid}`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
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
</style>