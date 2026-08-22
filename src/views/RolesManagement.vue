<template>
  <v-container>
    <!-- === KARTE: HINZUFÜGEN === -->
    <v-card class="mb-6" elevation="3">
      <v-card-title class="bg-primary text-white py-3">
        <v-icon left color="white" class="mr-2">mdi-account-cog</v-icon>
        Rollenverwaltung
      </v-card-title>
      <v-card-text class="pt-4">
        <v-form ref="addForm" @submit.prevent="addRole">
          <v-row align="center">
            <v-col cols="12" sm="3">
              <v-text-field
                v-model="newRole.name"
                label="Rollenname (technisch)"
                variant="outlined"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col cols="12" sm="3">
              <v-text-field
                v-model="newRole.display_name"
                label="Anzeigename"
                variant="outlined"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col cols="12" sm="3">
              <v-text-field
                v-model="newRole.description"
                label="Beschreibung"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" sm="3" class="d-flex justify-end" style="height: 100%; align-self: flex-start; padding-top: 10px;">
              <v-btn
                type="submit"
                color="primary"
                :loading="submittingAdd"
                :disabled="submittingAdd"
                height="56"
                class="text-none"
                size="large"
              >
                <v-icon left>mdi-plus</v-icon>
                Rolle hinzufügen
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- === KARTE: VORHANDENE ROLLEN === -->
    <v-card elevation="2">
      <!-- HEADER MIT SUCHE & SEITENANZAHL -->
      <v-card-title class="bg-grey-lighten-3 py-3">
        <v-row align="center" no-gutters>
          <v-col cols="12" sm="6" md="4" class="d-flex align-center">
            <v-icon left color="primary" class="mr-2">mdi-format-list-bulleted</v-icon>
            Vorhandene Rollen
            <v-chip size="small" color="primary" class="ml-2">{{ filteredRoles.length }}</v-chip>
          </v-col>

          <v-col cols="12" sm="6" md="8" class="d-flex align-center justify-sm-end mt-2 mt-sm-0">
            <!-- SUCHFELD -->
            <v-text-field
              v-model="searchQuery"
              label="Suchen..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              class="mr-3"
              style="max-width: 200px;"
            />

            <!-- SEITENANZAHL-AUSWAHL -->
            <v-select
              v-model="itemsPerPage"
              :items="itemsPerPageOptions"
              label="Pro Seite"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 120px;"
              class="mr-2"
            />
          </v-col>
        </v-row>
      </v-card-title>

      <v-card-text class="pt-4">
        <v-progress-circular v-if="loadingRoles" indeterminate color="primary" class="d-block mx-auto my-4" />
        <v-alert v-if="error" type="error" dismissible class="mb-4">{{ error }}</v-alert>

        <!-- ✅ Correction : striped et hover avec binding booléen -->
        <v-table
          v-else-if="!loadingRoles"
          :striped="true"
          :hover="true"
          density="compact"
          class="rounded-lg"
        >
          <thead>
            <tr>
              <th class="text-uppercase text-caption font-weight-bold">Name</th>
              <th class="text-uppercase text-caption font-weight-bold">Anzeigename</th>
              <th class="text-uppercase text-caption font-weight-bold">Beschreibung</th>
              <th class="text-uppercase text-caption font-weight-bold text-center" style="width: 150px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in paginatedRoles" :key="role.id">
              <td><span class="font-weight-medium">{{ role.name }}</span></td>
              <td>{{ role.display_name }}</td>
              <td>{{ role.description || '-' }}</td>
              <td class="text-center">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  color="primary"
                  variant="tonal"
                  class="mr-1"
                  @click="openEditDialog(role)"
                />
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  color="error"
                  variant="tonal"
                  @click="confirmDelete(role)"
                />
              </td>
            </tr>
            <tr v-if="filteredRoles.length === 0">
              <td colspan="4" class="text-center py-6 text-grey">
                <v-icon size="48" color="grey-lighten-2">mdi-database-off</v-icon>
                <div class="text-body-2 mt-2">
                  <span v-if="searchQuery">Keine Rollen gefunden für „{{ searchQuery }}“</span>
                  <span v-else>Keine Rollen vorhanden</span>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- === PAGINIERUNG === -->
        <div v-if="filteredRoles.length > 0" class="d-flex justify-center mt-4">
          <v-pagination
            v-model="currentPage"
            :length="totalPages"
            :total-visible="5"
            color="primary"
            rounded="circle"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- BEARBEITUNGSDIALOG -->
    <v-dialog v-model="editDialog" max-width="500">
      <v-card>
        <v-card-title class="bg-primary text-white">
          <v-icon left color="white">mdi-pencil</v-icon>
          Rolle bearbeiten
        </v-card-title>
        <v-card-text class="pt-4">
          <v-form ref="editForm" @submit.prevent="updateRole">
            <v-text-field
              v-model="editRole.name"
              label="Rollenname (technisch)"
              variant="outlined"
              :rules="[rules.required]"
            />
            <v-text-field
              v-model="editRole.display_name"
              label="Anzeigename"
              variant="outlined"
              :rules="[rules.required]"
            />
            <v-text-field
              v-model="editRole.description"
              label="Beschreibung"
              variant="outlined"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="closeEditDialog">Abbrechen</v-btn>
          <v-btn color="primary" @click="updateRole" :loading="submittingUpdate" class="text-none">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- LÖSCHBESTÄTIGUNG -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 bg-error text-white">
          <v-icon left color="white">mdi-alert</v-icon>
          Rolle löschen
        </v-card-title>
        <v-card-text class="pt-4">
          Soll die Rolle <strong class="text-error">{{ deleteRole?.display_name }}</strong> wirklich gelöscht werden?
          Diese Aktion kann nicht rückgängig gemacht werden.
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="closeDeleteDialog">Abbrechen</v-btn>
          <v-btn color="error" @click="deleteRoleConfirm" :loading="deleting" class="text-none">Löschen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- OVERLAYS -->
    <v-overlay v-model="processing" class="align-center justify-center" persistent>
      <v-progress-circular indeterminate size="80" color="primary" width="6" />
      <div class="text-h6 mt-4 text-white">{{ processingText }}</div>
    </v-overlay>

    <v-overlay v-model="redirecting" class="align-center justify-center" persistent scrim="rgba(0,0,0,0.7)" :z-index="9999">
      <div class="text-center">
        <v-progress-circular indeterminate size="80" color="primary" width="6" />
        <div class="text-h6 mt-4 text-white">Weiterleitung...</div>
      </div>
    </v-overlay>

    <!-- SNACKBAR -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="top end">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" color="white" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import { useValidationRules } from '../composables/useValidationRules'

const rules = useValidationRules()

// Daten
const roles = ref([])
const loadingRoles = ref(false)
const error = ref(null)

// Paginierung & Suche
const currentPage = ref(1)
const itemsPerPage = ref(5)
const searchQuery = ref('')

// Optionen für Seitenanzahl
const itemsPerPageOptions = [
  { title: '5', value: 5 },
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: 'Alle', value: -1 }
]

// Neue Rolle
const newRole = ref({
  name: '',
  display_name: '',
  description: ''
})
const submittingAdd = ref(false)
const addForm = ref(null)

// Bearbeitung
const editDialog = ref(false)
const editForm = ref(null)
const editRole = ref({ id: 0, name: '', display_name: '', description: '' })
const submittingUpdate = ref(false)

// Löschen
const deleteDialog = ref(false)
const deleteRole = ref(null)
const deleting = ref(false)

// Overlay und Snackbar
const processing = ref(false)
const processingText = ref('')
const redirecting = ref(false)
const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

// API-Basis – avec URL absolue pour éviter CORS en dev
const API_BASE = 'https://alpha-med-care.com/api'

// ----- Berechnete Werte für Filter & Paginierung -----
const filteredRoles = computed(() => {
  if (!searchQuery.value.trim()) {
    return roles.value
  }
  const query = searchQuery.value.toLowerCase().trim()
  return roles.value.filter(role =>
    role.name.toLowerCase().includes(query) ||
    role.display_name.toLowerCase().includes(query) ||
    (role.description && role.description.toLowerCase().includes(query))
  )
})

const totalItems = computed(() => filteredRoles.value.length)

const totalPages = computed(() => {
  if (itemsPerPage.value === -1) return 1
  return Math.ceil(totalItems.value / itemsPerPage.value)
})

const paginatedRoles = computed(() => {
  if (itemsPerPage.value === -1) {
    return filteredRoles.value
  }
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredRoles.value.slice(start, end)
})

// ----- Watch: Wenn Suche oder Seitenanzahl sich ändert, zurück auf Seite 1 -----
watch([searchQuery, itemsPerPage], () => {
  currentPage.value = 1
})

// ----- Rollen laden -----
const fetchRoles = async () => {
  loadingRoles.value = true
  error.value = null
  try {
    const res = await axios.get(`${API_BASE}/get_roles.php`)
    if (res.data.success && Array.isArray(res.data.roles)) {
      roles.value = res.data.roles
      if (currentPage.value > totalPages.value && totalPages.value > 0) {
        currentPage.value = 1
      }
    } else {
      error.value = 'Ungültige Antwort der API'
    }
  } catch (err) {
    error.value = 'Netzwerkfehler: ' + err.message
  } finally {
    loadingRoles.value = false
  }
}

// ----- Neue Rolle anlegen -----
const addRole = async () => {
  if (!newRole.value.name || !newRole.value.display_name) {
    showSnackbar('❌ Bitte füllen Sie alle Pflichtfelder aus.', 'error')
    return
  }

  submittingAdd.value = true
  processing.value = true
  processingText.value = 'Rolle wird angelegt...'

  try {
    const res = await axios.post(`${API_BASE}/create_role.php`, newRole.value)
    if (res.data.success) {
      newRole.value = { name: '', display_name: '', description: '' }
      addForm.value?.reset()
      showSnackbar('✅ Rolle erfolgreich angelegt!', 'success')
      await fetchRoles()
    } else {
      showSnackbar('❌ Fehler: ' + (res.data.error || 'Unbekannter Fehler'), 'error')
    }
  } catch (err) {
    console.error(err)
    let errorMsg = '❌ Fehler beim Anlegen der Rolle'
    if (err.response) {
      errorMsg += `: ${err.response.status} – ${JSON.stringify(err.response.data)}`
    } else if (err.request) {
      errorMsg += ': Keine Antwort vom Server'
    } else {
      errorMsg += `: ${err.message}`
    }
    showSnackbar(errorMsg, 'error')
  } finally {
    submittingAdd.value = false
    processing.value = false
  }
}

// ----- Bearbeitungsdialog öffnen -----
const openEditDialog = (role) => {
  editRole.value = { ...role }
  editDialog.value = true
  editForm.value?.resetValidation()
}

// ----- Bearbeitungsdialog schließen (Abbrechen) -----
const closeEditDialog = () => {
  editDialog.value = false
  editForm.value?.reset()
}

// ----- Rolle aktualisieren -----
const updateRole = async () => {
  const { valid } = await editForm.value?.validate() || { valid: true }
  if (!valid) return

  submittingUpdate.value = true
  processing.value = true
  processingText.value = 'Rolle wird aktualisiert...'
  try {
    const res = await axios.post(`${API_BASE}/update_role.php`, editRole.value)
    if (res.data.success) {
      editDialog.value = false
      editForm.value?.reset()
      showSnackbar('✅ Rolle erfolgreich aktualisiert!', 'success')
      await fetchRoles()
    } else {
      showSnackbar('❌ Fehler: ' + res.data.error, 'error')
    }
  } catch (err) {
    console.error(err)
    showSnackbar('❌ Fehler beim Aktualisieren der Rolle', 'error')
  } finally {
    submittingUpdate.value = false
    processing.value = false
  }
}

// ----- Löschbestätigung -----
const confirmDelete = (role) => {
  deleteRole.value = role
  deleteDialog.value = true
}

const closeDeleteDialog = () => {
  deleteDialog.value = false
  deleteRole.value = null
}

// ----- Rolle löschen -----
const deleteRoleConfirm = async () => {
  if (!deleteRole.value) return
  deleting.value = true
  processing.value = true
  processingText.value = 'Rolle wird gelöscht...'
  try {
    const res = await axios.post(`${API_BASE}/delete_role.php`, { id: deleteRole.value.id })
    if (res.data.success) {
      deleteDialog.value = false
      showSnackbar('✅ Rolle erfolgreich gelöscht!', 'success')
      await fetchRoles()
    } else {
      showSnackbar('❌ Fehler: ' + res.data.error, 'error')
    }
  } catch (err) {
    console.error(err)
    showSnackbar('❌ Fehler beim Löschen der Rolle', 'error')
  } finally {
    deleting.value = false
    deleteRole.value = null
    processing.value = false
  }
}

onMounted(fetchRoles)
</script>