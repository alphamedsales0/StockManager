<template>
  <v-container>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Artikeltypen</span>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openAddDialog">
          Neu
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-list>
          <v-list-item v-for="type in articleTypes" :key="type.id">
            <v-list-item-title>{{ type.name }}</v-list-item-title>
            <v-list-item-subtitle>Wert: {{ type.value }}</v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-card-text>
    </v-card>

    <!-- Dialog zum Hinzufügen -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card>
        <v-card-title>Neuen Artikeltyp anlegen</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newType.name"
            label="Name *"
            :rules="[required]"
            variant="outlined"
          />
          <v-text-field
            v-model="newType.value"
            label="Technischer Wert *"
            hint="z.B. 'treadmill' (wird in der URL verwendet)"
            :rules="[required]"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-btn text @click="dialog = false">Abbrechen</v-btn>
          <v-btn color="primary" @click="saveType" :loading="saving">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar für Meldungen -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const articleTypes = ref([])
const dialog = ref(false)
const saving = ref(false)
const newType = ref({ name: '', value: '' })
const snackbar = ref({ show: false, text: '', color: 'success' })

const required = v => !!v || 'Dieses Feld ist erforderlich'

const loadTypes = async () => {
  try {
    const res = await axios.get('https://alpha-med-care.com/api/get_article_types.php')
    articleTypes.value = res.data
  } catch (error) {
    console.error('Fehler beim Laden der Artikeltypen:', error)
    let msg = 'Artikeltypen konnten nicht geladen werden.'
    if (error.response) {
      msg += ` (Status ${error.response.status})`
      if (error.response.data?.error) msg += ': ' + error.response.data.error
    } else if (error.request) {
      msg += ' Keine Antwort vom Server.'
    } else {
      msg += ' ' + error.message
    }
    snackbar.value = { show: true, text: '❌ ' + msg, color: 'error' }
  }
}

const openAddDialog = () => {
  newType.value = { name: '', value: '' }
  dialog.value = true
}

const saveType = async () => {
  if (!newType.value.name || !newType.value.value) {
    snackbar.value = { show: true, text: 'Bitte füllen Sie alle Felder aus.', color: 'warning' }
    return
  }

  saving.value = true
  try {
    const response = await axios.post(
      'https://alpha-med-care.com/api/add_article_type.php',
      newType.value,
      { headers: { 'Content-Type': 'application/json' } }
    )

    if (response.data.success) {
      snackbar.value = { show: true, text: '✅ Artikeltyp erfolgreich angelegt!', color: 'success' }
      dialog.value = false
      await loadTypes()
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error('Fehler beim Speichern:', error)
    let msg = 'Fehler beim Speichern des Artikeltyps.'
    if (error.response) {
      msg += ` Status ${error.response.status}: `
      if (error.response.data?.error) msg += error.response.data.error
      else msg += error.response.statusText
    } else if (error.request) {
      msg += ' Keine Antwort vom Server.'
    } else {
      msg += ' ' + error.message
    }
    snackbar.value = { show: true, text: '❌ ' + msg, color: 'error' }
  } finally {
    saving.value = false
  }
}

onMounted(loadTypes)
</script>