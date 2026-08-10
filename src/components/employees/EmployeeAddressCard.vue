<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-home-outline</v-icon>
      Adresse
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="employee.adresse.strasse"
        label="Straße"
        variant="outlined"
        prepend-inner-icon="mdi-road"
        autocomplete="street-address"
      />
      <v-text-field
        v-model="employee.adresse.hausnummer"
        label="Hausnummer"
        variant="outlined"
        prepend-inner-icon="mdi-numeric"
        autocomplete="address-line2"
      />
      <v-row>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="employee.adresse.plz"
            label="PLZ"
            variant="outlined"
            prepend-inner-icon="mdi-mailbox"
            :rules="[rules.number]"
            autocomplete="postal-code"
          />
        </v-col>
        <v-col cols="12" sm="8">
          <v-text-field
            v-model="employee.adresse.stadt"
            label="Stadt"
            variant="outlined"
            prepend-inner-icon="mdi-city"
            autocomplete="address-level2"
          />
        </v-col>
      </v-row>
      <v-select
        v-model="employee.adresse.land"
        label="Land"
        variant="outlined"
        prepend-inner-icon="mdi-earth"
        :items="countries"
        :loading="loadingCountries"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'
import { ref, onMounted } from 'vue'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()

const countries = ref(['Deutschland', 'Frankreich', 'Belgien', 'Österreich', 'Schweiz'])
const loadingCountries = ref(false)

// Beispiel: Länderliste von einer API laden
const fetchCountries = async () => {
  loadingCountries.value = true
  try {
    const response = await fetch('https://restcountries.com/v3.1/all?fields=name')
    const data = await response.json()
    countries.value = data.map(c => c.name.common).sort()
  } catch (error) {
    console.warn('Länderliste konnte nicht geladen werden, verwende Fallback')
  } finally {
    loadingCountries.value = false
  }
}

onMounted(() => {
  fetchCountries()
})
</script>