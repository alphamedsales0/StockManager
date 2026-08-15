<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-account-outline</v-icon>
      Persönliche Daten
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.vorname"
            label="Vorname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="given-name"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.nachname"
            label="Nachname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="family-name"
          />
        </v-col>
      </v-row>

      <v-text-field
        v-model="employee.email"
        label="E-Mail"
        variant="outlined"
        prepend-inner-icon="mdi-email-outline"
        type="email"
        :rules="[rules.required, rules.email]"
        autocomplete="email"
      />

      <!-- ROLLE – dynamisch aus API geladen -->
      <v-select
        v-model="employee.role"
        label="Rolle"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        :items="roles"
        :loading="loadingRoles"
        :rules="[rules.required]"
      />

      <v-row>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.vertragsart"
            label="Vertragsart"
            variant="outlined"
            prepend-inner-icon="mdi-file-document"
            :items="['unbefristet', 'befristet', 'Praktikum', 'Werkstudent', 'Minijob', 'Freelancer']"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.wochenarbeitszeit"
            label="Wochenarbeitszeit (Std.)"
            variant="outlined"
            prepend-inner-icon="mdi-clock"
            type="number"
            :rules="[rules.number]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.steuerklasse"
            label="Steuerklasse"
            variant="outlined"
            prepend-inner-icon="mdi-currency-eur"
            :items="['1', '2', '3', '4', '5', '6']"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.konfession"
            label="Konfession"
            variant="outlined"
            prepend-inner-icon="mdi-church"
            :items="['rk', 'ev', 'sonstige', 'keine']"
          />
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.telefon"
            label="Telefon"
            variant="outlined"
            prepend-inner-icon="mdi-phone"
            :rules="[rules.phone]"
            autocomplete="tel"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.mobil"
            label="Mobil"
            variant="outlined"
            prepend-inner-icon="mdi-cellphone"
            :rules="[rules.phone]"
            autocomplete="tel-mobile"
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()

// Rollen aus der API laden
const roles = ref([])
const loadingRoles = ref(false)

const fetchRoles = async () => {
  loadingRoles.value = true
  try {
    const response = await axios.get('/api/get_roles.php')
    if (response.data.success) {
      // Wir speichern nur die Namen (oder display_name) für die Anzeige
      roles.value = response.data.roles.map(r => r.name)
      // Falls du display_name anzeigen möchtest: 
      // roles.value = response.data.roles.map(r => r.display_name)
    } else {
      console.warn('Rollen konnten nicht geladen werden, verwende Fallback')
      roles.value = ['admin', 'manager', 'employee', 'technician']
    }
  } catch (error) {
    console.error('Fehler beim Laden der Rollen:', error)
    roles.value = ['admin', 'manager', 'employee', 'technician']
  } finally {
    loadingRoles.value = false
  }
}

onMounted(() => {
  fetchRoles()
})
</script>