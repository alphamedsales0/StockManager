<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-file-document-outline</v-icon>
      Rechnungsdaten
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.zahlungsweise"
            label="Zahlungsweise"
            variant="outlined"
            prepend-inner-icon="mdi-cash-multiple"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.versandart"
            label="Versandart"
            variant="outlined"
            prepend-inner-icon="mdi-truck-outline"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.zahlungsziel"
            label="Zahlungsziel (Tage)"
            type="number"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-clock"
            :rules="[rules.number]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.gesamtrabatt"
            label="Gesamtrabatt (%)"
            type="number"
            variant="outlined"
            prepend-inner-icon="mdi-percent"
            :rules="[rules.number]"
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