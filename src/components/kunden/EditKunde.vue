<template>
  <v-container fluid class="pa-6">
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title class="d-flex align-center pa-6">
        <v-avatar color="primary" size="52" class="mr-4">
          <v-icon size="30">mdi-account-edit</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">Kunde bearbeiten</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            {{ displayName }} —
            <span class="font-monospace">{{ kunde?.kundennummer }}</span>
          </p>
        </div>
      </v-card-title>
    </v-card>

    <v-form ref="form" @submit.prevent="submit" v-if="kunde && !loading">
      <v-row align="start">
        <!-- ======================== COLONNE GAUCHE ======================== -->
        <v-col cols="12" md="6" class="d-flex flex-column">
          <KundeBaseCard />
          <KundePersonalCard />
          <KundeGewerbeCard />
          <KundeAuthCard :is-edit="true" />
          <KundeIdentificationCard />
          <KundeClassificationCard />
          <KundeAddressCard />
          <KundeCommunicationCard />
        </v-col>

        <!-- ======================== COLONNE DROITE ======================== -->
        <v-col cols="12" md="6" class="d-flex flex-column">
          <KundePhotoUpload ref="photoUploadRef" :existing-photo="kunde?.profilbild" />
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
          <v-btn variant="outlined" color="grey" size="large" prepend-icon="mdi-close" @click="$router.back()">
            Abbrechen
          </v-btn>
          <v-btn color="primary" variant="flat" size="large" type="submit" :loading="saving" prepend-icon="mdi-content-save" class="ml-3">
            Aktualisieren
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>

    <v-progress-circular v-else indeterminate color="primary" class="d-block mx-auto mt-10" />

    <v-snackbar v-model="snackbar" :color="snackbarColor" location="bottom right" timeout="3500">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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
const route = useRoute()
const store = useKundeStore()
const form = ref(null)
const loading = ref(true)
const saving = ref(false)
const photoUploadRef = ref(null)

const kunde = computed(() => store.kunde)

const displayName = computed(() => {
  if (!kunde.value) return ''
  if (kunde.value.person_type === 'Gewerbekunde') {
    return kunde.value.firma || 'Gewerbekunde'
  }
  return `${kunde.value.vorname || ''} ${kunde.value.nachname || ''}`.trim()
})

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const fetchKunde = async () => {
  const id = route.params.id
  if (!id) {
    router.push('/customers')
    return
  }
  loading.value = true
  try {
    const response = await axios.get(`/api/kunden_get.php?id=${id}`)
    if (response.data.success) {
      store.setKunde(response.data.kunde)
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error(error)
    snackbarText.value = error.response?.data?.error || error.message || 'Fehler beim Laden'
    snackbarColor.value = 'error'
    snackbar.value = true
    router.push('/customers')
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  const result = await form.value.validate()
  if (!result.valid) return

  saving.value = true
  try {
    const id = route.params.id
    const formData = new FormData()

    const payload = { ...store.kunde }
    if (!payload.passwort) delete payload.passwort

    formData.append('kunde', JSON.stringify(payload))

    if (photoUploadRef.value && photoUploadRef.value.photoFile) {
      formData.append('photo', photoUploadRef.value.photoFile)
    }

    const response = await axios.post(`/api/kunden_update.php?id=${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (!response.data.success) {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }

    snackbarText.value = 'Kunde erfolgreich aktualisiert.'
    snackbarColor.value = 'success'
    snackbar.value = true
    setTimeout(() => router.push('/customers'), 1200)

  } catch (error) {
    console.error(error)
    snackbarText.value = error.response?.data?.error || error.message || 'Serverfehler'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    saving.value = false
  }
}

onMounted(fetchKunde)
</script>

<style scoped>
.v-card { transition: 0.25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-card-title { font-weight: 600; letter-spacing: 0.3px; }
.v-btn { text-transform: none; font-weight: 600; }
.font-monospace { font-family: monospace; }

/* Équilibrage des colonnes */
.v-form .v-row > .v-col {
  display: flex;
  flex-direction: column;
}

.v-form .v-row > .v-col > * {
  flex: 0 0 auto;
}

/* Étire la dernière carte de la colonne la plus courte */
@media (min-width: 960px) {
  .v-form .v-row > .v-col > *:last-child {
    flex: 1 1 auto;
  }
}
</style>