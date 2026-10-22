<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-home-outline</v-icon>
      Adresse
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="store.employee.adresse.strasse"
        label="Straße"
        variant="outlined"
        prepend-inner-icon="mdi-road"
        :rules="[rules.required]"
        autocomplete="street-address"
      />
      <v-text-field
        v-model="store.employee.adresse.hausnummer"
        label="Hausnummer"
        variant="outlined"
        prepend-inner-icon="mdi-numeric"
        :rules="[rules.required]"
        autocomplete="address-line2"
      />
      <v-row>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="store.employee.adresse.plz"
            label="PLZ"
            variant="outlined"
            prepend-inner-icon="mdi-mailbox"
            :rules="[rules.required]"
            autocomplete="postal-code"
          />
        </v-col>
        <v-col cols="12" sm="8">
          <v-text-field
            v-model="store.employee.adresse.stadt"
            label="Stadt"
            variant="outlined"
            prepend-inner-icon="mdi-city"
            :rules="[rules.required]"
            autocomplete="address-level2"
          />
        </v-col>
      </v-row>
      <v-select
        v-model="store.employee.adresse.land"
        label="Land"
        variant="outlined"
        prepend-inner-icon="mdi-earth"
        :items="countries"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useEmployeeStore()
const rules = useValidationRules()

const countries = ref(['Deutschland', 'Frankreich', 'Belgien', 'Österreich', 'Schweiz'])

// ============================================================
// 🔍 DEBUG TEMPORAIRE (à supprimer après validation)
// ============================================================
onMounted(() => {
  console.log('=== AddressCard onMounted ===')
  console.log('store.employee:', store.employee)
  console.log('store.employee.adresse:', store.employee.adresse)
})

watch(
  () => store.employee.adresse?.strasse,
  (val) => console.log('🔍 adresse.strasse changé →', val)
)

watch(
  () => store.employee.adresse?.stadt,
  (val) => console.log('🔍 adresse.stadt changé →', val)
)
</script>