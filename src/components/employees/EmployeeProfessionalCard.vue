<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-briefcase-outline</v-icon>
      Berufliche Daten
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="employee.position"
        label="Position"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        autocomplete="job-title"
      />
      <v-text-field
        v-model="employee.abteilung"
        label="Abteilung"
        variant="outlined"
        prepend-inner-icon="mdi-office-building"
        autocomplete="organization"
      />
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.einstellungsdatum"
            label="Einstellungsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-start"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.geburtsdatum"
            label="Geburtsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-account"
          />
        </v-col>
      </v-row>
      <v-text-field
        v-model="employee.gehalt"
        label="Gehalt"
        prefix="€"
        type="number"
        variant="outlined"
        prepend-inner-icon="mdi-cash"
        :rules="[rules.number]"
        autocomplete="salary"
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