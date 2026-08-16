<template>
  <v-container>
    <v-card class="mb-6">
      <v-card-title>
        <v-icon left>mdi-account-cog</v-icon>
        Rollenverwaltung
      </v-card-title>
      <v-card-text>
        <v-form ref="addForm" @submit.prevent="addRole">
          <v-row align="center">
            <!-- 3 Felder, je 3/12 auf sm, 12/12 auf xs -->
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
            <!-- Button-Spalte: 3/12, rechtsbündig -->
            <v-col cols="12" sm="3" class="d-flex justify-end" style="height: 100%; align-self: flex-start; padding-top: 10px;">
              <v-btn
                type="submit"
                color="primary"
                :loading="submittingAdd"
                :disabled="submittingAdd"
                height="56"
                class="text-none"
              >
                Rolle hinzufügen
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- Rest unverändert -->
    <v-card>
      <v-card-title>Vorhandene Rollen</v-card-title>
      <v-card-text>
        <v-progress-circular v-if="loadingRoles" indeterminate color="primary" />
        <v-alert v-if="error" type="error" dismissible>{{ error }}</v-alert>

        <v-table v-else-if="!loadingRoles">
          <thead>
            <tr>
              <th>Name</th>
              <th>Anzeigename</th>
              <th>Beschreibung</th>
              <th style="width: 150px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in roles" :key="role.id">
              <td>{{ role.name }}</td>
              <td>{{ role.display_name }}</td>
              <td>{{ role.description || '-' }}</td>
              <td>
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  color="primary"
                  variant="text"
                  @click="openEditDialog(role)"
                />
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  color="error"
                  variant="text"
                  @click="confirmDelete(role)"
                />
              </td>
            </tr>
            <tr v-if="roles.length === 0">
              <td colspan="4" class="text-center">Keine Rollen vorhanden.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- BEARBEITUNGSDIALOG -->
    <v-dialog v-model="editDialog" max-width="500">
      <v-card>
        <v-card-title>Rolle bearbeiten</v-card-title>
        <v-card-text>
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
        <v-card-actions>
          <v-btn variant="text" @click="closeEditDialog">Abbrechen</v-btn>
          <v-btn color="primary" @click="updateRole" :loading="submittingUpdate">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- LÖSCHBESTÄTIGUNG -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">Rolle löschen</v-card-title>
        <v-card-text>
          Soll die Rolle <strong>{{ deleteRole?.display_name }}</strong> wirklich gelöscht werden?
          Diese Aktion kann nicht rückgängig gemacht werden.
        </v-card-text>
        <v-card-actions>
          <v-btn variant="text" @click="closeDeleteDialog">Abbrechen</v-btn>
          <v-btn color="error" @click="deleteRoleConfirm" :loading="deleting">Löschen</v-btn>
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
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useValidationRules } from '../composables/useValidationRules'

const rules = useValidationRules()

// Daten
const roles = ref([])
const loadingRoles = ref(false)
const error = ref(null)

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

// API-Basis
const API_BASE = 'https://alpha-med-care.com/api'

// ----- Rollen laden -----
const fetchRoles = async () => {
  loadingRoles.value = true
  error.value = null
  try {
    const res = await axios.get(`${API_BASE}/get_roles.php`)
    if (res.data.success && Array.isArray(res.data.roles)) {
      roles.value = res.data.roles
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

// ----- Rolle löschen (hard delete) -----
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