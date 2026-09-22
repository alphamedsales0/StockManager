<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-shield-account</v-icon>
      Authentifizierung
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="kunde.email"
        label="E-Mail"
        type="email"
        variant="outlined"
        prepend-inner-icon="mdi-email-outline"
        :rules="[rules.required, rules.email]"
        autocomplete="email"
      />
      <v-text-field
        v-model="kunde.passwort"
        label="Passwort"
        type="password"
        variant="outlined"
        prepend-inner-icon="mdi-lock"
        :hint="isEdit ? 'Leer lassen, um das Passwort nicht zu ändern' : 'Wird beim Anlegen vergeben'"
        persistent-hint
        autocomplete="new-password"
      />
      <v-row class="mt-3">
        <v-col cols="12" sm="4">
          <v-checkbox
            v-model="kunde.email_verifiziert"
            label="E-Mail verifiziert"
            color="primary"
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4">
          <v-checkbox
            v-model="kunde.agb_accepted"
            label="AGB akzeptiert"
            color="primary"
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4">
          <v-checkbox
            v-model="kunde.datenschutz_accepted"
            label="Datenschutz akzeptiert"
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
import { useValidationRules } from '../../composables/useValidationRules'

defineProps({
  isEdit: { type: Boolean, default: false }
})

const store = useKundeStore()
const kunde = store.kunde
const rules = useValidationRules()
</script>