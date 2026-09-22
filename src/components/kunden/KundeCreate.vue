<template>
  <v-container fluid class="pa-6">
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title class="d-flex align-center pa-6">
        <v-avatar color="primary" size="52" class="mr-4">
          <v-icon size="30">mdi-account-plus</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">Neuer Kunde</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Kundenkonto und persönliche Daten erstellen
          </p>
        </div>
      </v-card-title>
    </v-card>

    <v-form ref="form" @submit.prevent="submit">
      <v-row align="start">
        <!-- ======================== COLONNE GAUCHE ======================== -->
        <v-col cols="12" md="6" class="d-flex flex-column">
          <KundeBaseCard />
          <KundePersonalCard />
          <KundeGewerbeCard />
          <KundeAuthCard :is-edit="false" />
          <KundeIdentificationCard />
          <KundeClassificationCard />
          <KundeAddressCard />
          <KundeCommunicationCard />
        </v-col>

        <!-- ======================== COLONNE DROITE ======================== -->
        <v-col cols="12" md="6" class="d-flex flex-column">
          <KundePhotoUpload ref="photoUploadRef" :existing-photo="null" />
          <KundeBankCard />
          <KundeInvoiceCard />
          <KundePriceGroupsCard />
          <KundeDiscountsCard />
          <KundeRemindersCard />
          <KundeSkontoCard />
          <KundeNotesCard />
          <KundeCustomFieldsCard />
        </v-col>
      </v-row>

      <v-card class="mt-6 rounded-xl" elevation="2">
        <v-card-actions class="pa-5">
          <v-spacer />
          <v-btn variant="outlined" color="grey" size="large" prepend-icon="mdi-close" :disabled="loading" @click="$router.back()">
            Abbrechen
          </v-btn>
          <v-btn color="primary" variant="flat" size="large" type="submit" :loading="loading" prepend-icon="mdi-content-save" class="ml-3">
            Kunde speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>

    <v-snackbar v-model="snackbar" :color="snackbarColor" location="bottom right" timeout="3500">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useKundeStore } from '../../stores/kundeStore'

import KundeBaseCard             from './KundeBaseCard.vue'
import KundePersonalCard         from './KundePersonalCard.vue'
import KundeGewerbeCard          from './KundeGewerbeCard.vue'
import KundeAuthCard             from './KundeAuthCard.vue'
import KundeIdentificationCard   from './KundeIdentificationCard.vue'
import KundeClassificationCard   from './KundeClassificationCard.vue'
import KundeAddressCard          from './KundeAddressCard.vue'
import KundeCommunicationCard    from './KundeCommunicationCard.vue'
import KundeBankCard             from './KundeBankCard.vue'
import KundeInvoiceCard          from './KundeInvoiceCard.vue'
import KundePriceGroupsCard      from './KundePriceGroupsCard.vue'
import KundeDiscountsCard        from './KundeDiscountsCard.vue'
import KundeRemindersCard        from './KundeRemindersCard.vue'
import KundeSkontoCard           from './KundeSkontoCard.vue'
import KundeNotesCard            from './KundeNotesCard.vue'
import KundeCustomFieldsCard     from './KundeCustomFieldsCard.vue'
import KundePhotoUpload          from './KundePhotoUpload.vue'

const router = useRouter()
const store = useKundeStore()
const form = ref(null)
const loading = ref(false)
const photoUploadRef = ref(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

onMounted(() => {
  store.resetKunde()
})

const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) {
    snackbarText.value = 'Bitte überprüfen Sie Ihre Eingaben.'
    snackbarColor.value = 'warning'
    snackbar.value = true
    return
  }

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('kunde', JSON.stringify(store.kunde))

    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      formData.append('photo', photoUploadRef.value.photoFile)
    }

    const response = await axios.post('/api/kunden_create.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (!response.data.success) {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }

    snackbarText.value = `Kunde erfolgreich erstellt – Nummer: ${response.data.kundennummer}`
    snackbarColor.value = 'success'
    snackbar.value = true

    store.resetKunde()
    if (photoUploadRef.value?.reset) photoUploadRef.value.reset()

    setTimeout(() => router.push('/customers'), 1500)

  } catch (error) {
    console.error(error)
    snackbarText.value = error.response?.data?.error || error.message || 'Serverfehler beim Speichern'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.v-card { transition: 0.25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-card-title { font-weight: 600; letter-spacing: 0.3px; }
.v-btn { text-transform: none; font-weight: 600; }

/* Équilibrage des colonnes */
.v-form .v-row > .v-col {
  display: flex;
  flex-direction: column;
}

/* Les cartes prennent leur hauteur naturelle, pas d'étirement */
.v-form .v-row > .v-col > * {
  flex: 0 0 auto;
}
</style>