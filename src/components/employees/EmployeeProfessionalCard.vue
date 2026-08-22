<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-briefcase-outline</v-icon>
      Berufliche Daten
    </v-card-title>
    <v-card-text>
      <!-- Position -->
      <v-text-field
        v-model="employee.position"
        label="Position"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        autocomplete="job-title"
      />
      <!-- Abteilung -->
      <v-text-field
        v-model="employee.abteilung"
        label="Abteilung"
        variant="outlined"
        prepend-inner-icon="mdi-office-building"
        autocomplete="organization"
      />

      <!-- Rolle : maintenant avec display_name -->
      <v-select
        v-model="employee.role"
        label="Rolle"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        :items="roles"
        :loading="loadingRoles"
        :rules="[rules.required]"
        item-title="title"
        item-value="value"
      />

      <!-- Vorgesetzte/r -->
      <div class="mb-2">
        <span class="text-caption text-grey">
          Wählen Sie eine Person aus der Liste
        </span>
        <v-select
          v-model="employee.vorgesetzter"
          :items="users"
          label="Vorgesetzte/r"
          variant="outlined"
          prepend-inner-icon="mdi-account-supervisor"
          item-title="name"
          item-value="name"
          :loading="loadingUsers"
          :items-per-page="20"
          clearable
          persistent-hint
          no-data-text="Keine Benutzer gefunden"
        >
          <template #prepend-item>
            <v-list-item
              v-if="!loadingUsers"
              title="Keine Auswahl (leer lassen)"
              value=""
              @click="employee.vorgesetzter = ''"
            />
          </template>
          <template #item="{ item, props }">
            <v-list-item v-bind="props">
              <template #title>
                <span>{{ item.raw.name }}</span>
                <span class="text-caption text-grey ml-2">({{ item.raw.email }})</span>
              </template>
            </v-list-item>
          </template>
        </v-select>
      </div>

      <!-- Einstellungsdatum & Geburtsdatum -->
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.einstellungsdatum"
            label="Einstellungsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-start"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.geburtsdatum"
            label="Geburtsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-account"
          />
        </v-col>
      </v-row>
      <!-- Gehalt -->
      <v-text-field
        v-model="employee.gehalt"
        label="Gehalt"
        prefix="€"
        type="number"
        variant="outlined"
        prepend-inner-icon="mdi-cash"
        :rules="[rules.number]"
        autocomplete="salary"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'
import axios from 'axios'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()

// --- Liste des utilisateurs ---
const users = ref([])
const loadingUsers = ref(false)

const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await axios.get('/api/get_users.php')
    if (response.data.success) {
      users.value = response.data.users
    } else {
      console.error('Erreur chargement utilisateurs:', response.data.error)
    }
  } catch (error) {
    console.error('Network error:', error)
  } finally {
    loadingUsers.value = false
  }
}

// --- Liste des rôles (corrigée) ---
const roles = ref([])
const loadingRoles = ref(false)

const fetchRoles = async () => {
  loadingRoles.value = true
  try {
    const response = await axios.get('https://alpha-med-care.com/api/get_roles.php')
    if (response.data.success) {
      // On mappe pour avoir un objet { title: display_name, value: name }
      // Si display_name manque, on prend name, sinon id en dernier recours
      roles.value = response.data.roles.map(r => ({
        title: r.display_name || r.name || `Rolle #${r.id}`,
        value: r.name || String(r.id)
      }))
    } else {
      console.warn('Rollen konnten nicht geladen werden, verwende Fallback')
      roles.value = [
        { title: 'Admin', value: 'admin' },
        { title: 'Manager', value: 'manager' },
        { title: 'Employee', value: 'employee' },
        { title: 'Technician', value: 'technician' }
      ]
    }
  } catch (error) {
    console.error('Fehler beim Laden der Rollen:', error)
    roles.value = [
      { title: 'Admin', value: 'admin' },
      { title: 'Manager', value: 'manager' },
      { title: 'Employee', value: 'employee' },
      { title: 'Technician', value: 'technician' }
    ]
  } finally {
    loadingRoles.value = false
  }
}

onMounted(() => {
  loadUsers()
  fetchRoles()
})
</script>