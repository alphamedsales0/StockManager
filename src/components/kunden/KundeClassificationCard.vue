<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-tag-multiple</v-icon>
      Klassifizierung
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="kunde.kundengruppe"
        label="Kundengruppe"
        variant="outlined"
        prepend-inner-icon="mdi-account-group"
      />
      <v-text-field
        v-model="kunde.branche"
        label="Branche"
        variant="outlined"
        prepend-inner-icon="mdi-factory"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
const store = useKundeStore()
const kunde = store.kunde
</script>