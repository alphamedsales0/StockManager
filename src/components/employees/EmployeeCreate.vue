<template>
  <v-container fluid class="pa-6">
    <!-- ================= HEADER ================= -->
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

    <!-- ================= FORMULAIRE ================= -->
    <v-form ref="form" @submit.prevent="submit">
      <v-row>
        <!-- Linke Spalte -->
        <v-col cols="12" md="6">
          <EmployeePersonalCard />
          <EmployeeAddressCard />
          <EmployeeInsuranceCard />
          <EmployeePhotoUpload
            ref="photoUploadRef"
            :existing-photo="null"
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

      <!-- ================= BUTTONS ================= -->
      <v-card class="mt-6 rounded-xl" elevation="2">
        <v-card-actions class="pa-5">
          <v-spacer />
          <v-btn
            variant="outlined"
            color="grey"
            size="large"
            prepend-icon="mdi-close"
            :disabled="loading"
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

    <!-- ================= SNACKBAR ================= -->
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useEmployeeStore } from '../../stores/employeeStore'

// ================= COMPOSANTS ENFANTS =================
import EmployeePersonalCard      from '../../components/employees/EmployeePersonalCard.vue'
import EmployeeInsuranceCard     from '../../components/employees/EmployeeInsuranceCard.vue'
import EmployeePhotoUpload       from '../../components/employees/EmployeePhotoUpload.vue'
import EmployeeProfessionalCard  from '../../components/employees/EmployeeProfessionalCard.vue'
import EmployeeBankCard          from '../../components/employees/EmployeeBankCard.vue'
import EmployeeQualificationsCard from '../../components/employees/EmployeeQualificationsCard.vue'
import EmployeeDocumentsCard     from '../../components/employees/EmployeeDocumentsCard.vue'
import EmployeeEmergencyCard     from '../../components/employees/EmployeeEmergencyCard.vue'
import EmployeeAddressCard       from '../../components/employees/EmployeeAddressCard.vue'

/* =====================================================
   SETUP
===================================================== */
const router        = useRouter()
const store         = useEmployeeStore()
const form          = ref(null)
const loading       = ref(false)
const photoUploadRef = ref(null)

// Snackbar
const snackbar      = ref(false)
const snackbarText  = ref('')
const snackbarColor = ref('success')

/* =====================================================
   SUBMIT — Création d'un employé
===================================================== */
const submit = async () => {
  // --- 1. Validation du formulaire ---
  const result = await form.value.validate()
  if (!result.valid) {
    snackbarText.value = 'Bitte überprüfen Sie Ihre Eingaben.'
    snackbarColor.value = 'warning'
    snackbar.value = true
    return
  }

  loading.value = true

  try {
    // --- 2. Préparation du FormData ---
    const formData = new FormData()

    // 2.1 Payload JSON (toutes les données employé)
    formData.append('employee', JSON.stringify(store.employee))

    // 2.2 Photo
    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      const photoFile = photoUploadRef.value.photoFile

      // Validation côté client (en plus du backend)
      const maxSize = 5 * 1024 * 1024
      if (photoFile.size > maxSize) {
        throw new Error('Das Foto ist zu groß (max. 5 MB).')
      }

      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
      if (!allowedTypes.includes(photoFile.type)) {
        throw new Error('Nur JPG, PNG oder WebP sind als Foto erlaubt.')
      }

      formData.append('photo', photoFile)
      console.log('[EmployeeCreate] Photo appended:', photoFile.name, photoFile.size, 'bytes')
    } else {
      console.log('[EmployeeCreate] Aucune photo sélectionnée')
    }

    // 2.3 Documents
    if (store.employee.documents && store.employee.documents.length > 0) {
      store.employee.documents.forEach((doc, index) => {
        if (doc.file && doc.file instanceof File) {
          formData.append(`document_${index}`, doc.file)
          console.log(`[EmployeeCreate] Document ${index} appended:`, doc.file.name)
        }
      })
    }

    // --- 3. Envoi au serveur ---
    console.log('[EmployeeCreate] Submitting employee data...')
    const response = await axios.post(
      '/api/employees_create.php',
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
            console.log(`[EmployeeCreate] Upload progress: ${percent}%`)
          }
        }
      }
    )

    // --- 4. Traitement de la réponse ---
    if (!response.data.success) {
      throw new Error(response.data.error || 'Unbekannter Fehler vom Server.')
    }

    const newNumber = response.data.mitarbeiter_nummer || 'unbekannt'
    const photoUrl  = response.data.data?.photo_url || null
    const mailSent  = response.data.mail_sent
    const mailError = response.data.mail_error

    // --- 5. Logs de debug ---
    console.log('[EmployeeCreate] ✅ Success:')
    console.log('  - Mitarbeiternummer:', newNumber)
    console.log('  - Employee UID:', response.data.employee_uid)
    console.log('  - User UID:', response.data.user_uid)
    console.log('  - Username:', response.data.username)
    console.log('  - Photo URL:', photoUrl)
    console.log('  - Mail sent:', mailSent)
    if (mailError) console.warn('  - Mail error:', mailError)

    // --- 6. Message de succès ---
    let successMessage = `Mitarbeiter erfolgreich erstellt – Nummer: ${newNumber}.`

    if (photoUrl) {
      successMessage += ' Foto wurde hochgeladen.'
    }

    if (mailSent) {
      successMessage += ' Zugangsdaten wurden per E-Mail gesendet.'
    } else if (mailError) {
      successMessage += ' ⚠️ E-Mail konnte nicht gesendet werden.'
    }

    snackbarText.value = successMessage
    snackbarColor.value = 'success'
    snackbar.value = true

    // --- 7. Reset + redirection ---
    store.resetEmployee()

    // Reset du composant photo
    if (photoUploadRef.value?.reset) {
      photoUploadRef.value.reset()
    }

    setTimeout(() => {
      router.push('/employees')
    }, 1500)

  } catch (error) {
    // --- Gestion fine des erreurs ---
    console.error('[EmployeeCreate] ❌ Error:', error)

    let errorMessage = 'Serverfehler beim Speichern.'

    // Erreur axios avec réponse du serveur
    if (error.response) {
      errorMessage = error.response.data?.error
        || error.response.data?.message
        || `Serverfehler (${error.response.status})`
    }
    // Erreur axios sans réponse (réseau, timeout)
    else if (error.request) {
      errorMessage = 'Keine Antwort vom Server. Bitte prüfen Sie Ihre Verbindung.'
    }
    // Erreur levée manuellement (validation, etc.)
    else if (error.message) {
      errorMessage = error.message
    }

    snackbarText.value = errorMessage
    snackbarColor.value = 'error'
    snackbar.value = true

  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.v-card {
  transition: 0.25s ease;
}

.v-card:hover {
  transform: translateY(-2px);
}

.v-card-title {
  font-weight: 600;
  letter-spacing: 0.3px;
}

.v-btn {
  text-transform: none;
  font-weight: 600;
}
</style>