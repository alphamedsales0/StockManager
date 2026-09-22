<template>
  <v-card v-if="kunde.person_type === 'Privatkunde'" class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-account-outline</v-icon>
      Persönliche Daten
    </v-card-title>
    <v-card-text>
      <v-select
        v-model="kunde.anrede"
        label="Anrede"
        variant="outlined"
        prepend-inner-icon="mdi-account-voice"
        :items="['Herr', 'Frau', 'Divers', 'Firma']"
        clearable
      />
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.vorname"
            label="Vorname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="given-name"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.nachname"
            label="Nachname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="family-name"
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