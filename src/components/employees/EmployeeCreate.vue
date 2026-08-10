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
        <!-- Linke Spalte -->
        <v-col cols="12" md="6">
          <EmployeePersonalCard />
          <EmployeeInsuranceCard />
          <EmployeePhotoUpload ref="photoUploadRef" />
        </v-col>

        <!-- Rechte Spalte -->
        <v-col cols="12" md="6">
          <EmployeeProfessionalCard />
          <EmployeeBankCard />
          <EmployeeQualificationsCard />
          <EmployeeDocumentsCard />
          <EmployeeEmergencyCard />
          <EmployeeAddressCard />
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
            Mitarbeiter speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>

    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="bottom right"
      timeout="3000"
    >
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
import { useEmployeeStore } from '../../stores/employeeStore'

// Kindkomponenten importieren
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
const store = useEmployeeStore()
const form = ref(null)
const loading = ref(false)
const photoUploadRef = ref(null)

// Snackbar
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// KEINE GENERIERUNG DER MITARBEITERNUNMER MEHR IM FRONTEND

// SUBMIT
const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) return

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('employee', JSON.stringify(store.employee))

    // Foto anhängen
    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      formData.append('photo', photoUploadRef.value.photoFile)
    }

    // Dokumente (Dateien) anhängen
    if (store.employee.documents && store.employee.documents.length > 0) {
      store.employee.documents.forEach((doc, index) => {
        if (doc.file && doc.file instanceof File) {
          formData.append(`document_${index}`, doc.file)
        }
      })
    }

    const response = await axios.post('/api/employees_create.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data.success) {
      const newNumber = response.data.mitarbeiter_nummer || 'unbekannt'
      snackbarText.value =
        `Mitarbeiter erfolgreich erstellt – Nummer: ${newNumber} – Zugangsdaten wurden per E‑Mail gesendet.`
      snackbarColor.value = 'success'
      snackbar.value = true
      store.resetEmployee()
      setTimeout(() => router.push('/employees'), 1200)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error(error)
    snackbarText.value =
      error.response?.data?.error ||
      error.message ||
      'Serverfehler beim Speichern'
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