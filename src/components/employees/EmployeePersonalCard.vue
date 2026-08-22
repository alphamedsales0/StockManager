<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-account-outline</v-icon>
      Persönliche Daten
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.vorname"
            label="Vorname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="given-name"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.nachname"
            label="Nachname"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            autocomplete="family-name"
          />
        </v-col>
      </v-row>

      <v-text-field
        v-model="employee.email"
        label="E-Mail"
        variant="outlined"
        prepend-inner-icon="mdi-email-outline"
        type="email"
        :rules="[rules.required, rules.email]"
        autocomplete="email"
      />

      <!-- SUPPRESSION de Steuerklasse et Konfession -->

      <v-row>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.vertragsart"
            label="Vertragsart"
            variant="outlined"
            prepend-inner-icon="mdi-file-document"
            :items="['unbefristet', 'befristet', 'Praktikum', 'Werkstudent', 'Minijob', 'Freelancer']"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.wochenarbeitszeit"
            label="Wochenarbeitszeit (Std.)"
            variant="outlined"
            prepend-inner-icon="mdi-clock"
            type="number"
            :rules="[rules.number]"
          />
        </v-col>
        <!-- Les colonnes Steuerklasse et Konfession ont été supprimées -->
      </v-row>

      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.telefon"
            label="Telefon"
            variant="outlined"
            prepend-inner-icon="mdi-phone"
            :rules="[rules.phone]"
            autocomplete="tel"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.mobil"
            label="Mobil"
            variant="outlined"
            prepend-inner-icon="mdi-cellphone"
            :rules="[rules.phone]"
            autocomplete="tel-mobile"
          />
        </v-col>
      </v-row>
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