<template>
  <v-card v-if="kunde.person_type === 'Gewerbekunde'" class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-domain</v-icon>
      Gewerbedaten
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="kunde.firma"
        label="Firma"
        variant="outlined"
        prepend-inner-icon="mdi-domain"
        :rules="[rules.required]"
        autocomplete="organization"
      />
      <v-text-field
        v-model="kunde.zusatz"
        label="Zusatz (z. B. Standort, Abteilung)"
        variant="outlined"
        prepend-inner-icon="mdi-plus-box-outline"
      />
      <v-text-field
        v-model="kunde.kontakt"
        label="Ansprechpartner"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        autocomplete="name"
      />
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.hrb_register_nummer"
            label="HRB-Registernummer"
            variant="outlined"
            prepend-inner-icon="mdi-book-open-variant"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.hrb_gerichtsstand"
            label="HRB-Gerichtsstand"
            variant="outlined"
            prepend-inner-icon="mdi-gavel"
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useKundeStore()
const kunde = store.kunde
const rules = useValidationRules()
</script>