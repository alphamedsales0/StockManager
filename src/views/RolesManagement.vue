<template>
  <v-container>
    <v-card class="mb-6">
      <v-card-title>
        <v-icon left>mdi-account-cog</v-icon>
        Rollenverwaltung
      </v-card-title>
      <v-card-text>
        <v-form @submit.prevent="addRole">
          <v-row>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="newRole.name"
                label="Rollenname (technisch)"
                variant="outlined"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="newRole.display_name"
                label="Anzeigename"
                variant="outlined"
                :rules="[rules.required]"
              />
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="newRole.description"
                label="Beschreibung"
                variant="outlined"
              />
            </v-col>
          </v-row>
          <v-btn type="submit" color="primary" :loading="loading">Rolle hinzufügen</v-btn>
        </v-form>
      </v-card-text>
    </v-card>

    <v-card>
      <v-card-title>Vorhandene Rollen</v-card-title>
      <v-card-text>
        <v-progress-circular v-if="loadingRoles" indeterminate color="primary" />

        <v-alert v-if="error" type="error" dismissible>
          {{ error }}
        </v-alert>

        <v-table v-else-if="!loadingRoles">
          <thead>
            <tr>
              <th>Name</th>
              <th>Anzeigename</th>
              <th>Beschreibung</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in roles" :key="role.id">
              <td>{{ role.name }}</td>
              <td>{{ role.display_name }}</td>
              <td>{{ role.description || '-' }}</td>
            </tr>
            <tr v-if="roles.length === 0">
              <td colspan="3" class="text-center">Keine Rollen vorhanden.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useValidationRules } from '../composables/useValidationRules'

const rules = useValidationRules()
const roles = ref([])
const loadingRoles = ref(false)
const error = ref(null)

const newRole = ref({
  name: '',
  display_name: '',
  description: ''
})

const fetchRoles = async () => {
  loadingRoles.value = true
  error.value = null
  try {
    // ⬇️ ABSOLUTE URL verwenden
    const res = await axios.get('https://alpha-med-care.com/api/get_roles.php')
    console.log('API-Antwort:', res.data)

    if (res.data.success === true && Array.isArray(res.data.roles)) {
      roles.value = res.data.roles
    } else {
      error.value = res.data.error || 'Die API lieferte kein gültiges Rollen-Array.'
    }
  } catch (err) {
    console.error('Fehler beim Laden der Rollen:', err)
    if (err.response) {
      error.value = 'Server-Fehler: ' + JSON.stringify(err.response.data)
    } else {
      error.value = 'Netzwerkfehler: ' + err.message
    }
  } finally {
    loadingRoles.value = false
  }
}

const addRole = async () => {
  if (!newRole.value.name || !newRole.value.display_name) return
  loading.value = true
  try {
    // ⬇️ Auch hier absolute URL für POST
    const res = await axios.post('https://alpha-med-care.com/api/create_role.php', newRole.value)
    if (res.data.success) {
      newRole.value = { name: '', display_name: '', description: '' }
      await fetchRoles()
    } else {
      alert('Fehler: ' + res.data.error)
    }
  } catch (error) {
    console.error(error)
    alert('Fehler beim Anlegen der Rolle')
  } finally {
    loading.value = false
  }
}

onMounted(fetchRoles)
</script>