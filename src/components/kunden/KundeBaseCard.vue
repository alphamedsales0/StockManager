<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-account-box-outline</v-icon>
      Basisdaten
    </v-card-title>
    <v-card-text>
      <v-select
        v-model="kunde.person_type"
        label="Kundentyp"
        variant="outlined"
        prepend-inner-icon="mdi-account-switch"
        :items="['Privatkunde', 'Gewerbekunde']"
        :rules="[rules.required]"
      />
      <v-text-field
        v-if="kunde.kundennummer"
        v-model="kunde.kundennummer"
        label="Kundennummer"
        variant="outlined"
        prepend-inner-icon="mdi-pound"
        readonly
      />
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