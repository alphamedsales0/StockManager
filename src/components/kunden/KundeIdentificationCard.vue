<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-card-account-details</v-icon>
      Identifikation & Steuer
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.steuernummer"
            label="Steuernummer"
            variant="outlined"
            prepend-inner-icon="mdi-identifier"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="kunde.ust_id_nr"
            label="USt-IdNr."
            variant="outlined"
            prepend-inner-icon="mdi-identifier"
          />
        </v-col>
      </v-row>
      <v-row class="mt-2">
        <v-col cols="12" sm="6">
          <v-checkbox
            v-model="kunde.auslandskunden"
            label="Auslandskunde"
            color="primary"
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-checkbox
            v-model="kunde.eg_ausland"
            label="EG-Ausland"
            color="primary"
            hide-details
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
const store = useKundeStore()
const kunde = store.kunde
</script>