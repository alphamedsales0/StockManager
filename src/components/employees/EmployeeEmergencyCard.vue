<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
      Notfallkontakt
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="employee.notfall_kontakt_name"
        label="Name"
        variant="outlined"
        prepend-inner-icon="mdi-account-alert"
        autocomplete="name"
      />
      <v-text-field
        v-model="employee.notfall_kontakt_telefon"
        label="Telefon"
        variant="outlined"
        prepend-inner-icon="mdi-phone-alert"
        :rules="[rules.phone]"
        autocomplete="tel"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()
</script>