<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-shield-account</v-icon>
      Versicherung &amp; Steuer‑ID
    </v-card-title>
    <v-card-text>
      <!-- Steuer-ID -->
      <v-text-field
        v-model="employee.steuer_id"
        label="Steuer‑ID"
        variant="outlined"
        prepend-inner-icon="mdi-identifier"
        :rules="[rules.number]"
        autocomplete="tax-id"
      />
      

      <!-- 👇 NOUVEAUX CHAMPS : Steuerklasse & Konfession -->
      <v-row>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.steuerklasse"
            label="Steuerklasse"
            variant="outlined"
            prepend-inner-icon="mdi-currency-eur"
            :items="['1', '2', '3', '4', '5', '6']"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-select
            v-model="employee.konfession"
            label="Konfession"
            variant="outlined"
            prepend-inner-icon="mdi-church"
            :items="['rk', 'ev', 'sonstige', 'keine']"
          />
        </v-col>
      </v-row>

      <!-- Sozialversicherungsnummer -->
      <v-text-field
        v-model="employee.sozialversicherungsnummer"
        label="Sozialversicherungsnummer"
        variant="outlined"
        prepend-inner-icon="mdi-card-account-details"
        :rules="[rules.number]"
        autocomplete="social-security-number"
      />

      <!-- Versicherungsdaten (inchangés) -->
      <v-select
        v-model="employee.versicherung_typ"
        label="Versicherungstyp"
        variant="outlined"
        prepend-inner-icon="mdi-tag"
        :items="['Krankenversicherung', 'Pflegeversicherung', 'Unfallversicherung', 'Berufsunfähigkeit', 'Sonstige']"
      />
      <v-text-field
        v-model="employee.versicherung_gesellschaft"
        label="Versicherungsgesellschaft"
        variant="outlined"
        prepend-inner-icon="mdi-domain"
        autocomplete="organization"
      />
      <v-text-field
        v-model="employee.versicherung_nummer"
        label="Versicherungsnummer"
        variant="outlined"
        prepend-inner-icon="mdi-card-bulleted"
        :rules="[rules.alphanumeric]"
      />
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.versicherung_gueltig_ab"
            label="Gültig ab"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-start"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.versicherung_gueltig_bis"
            label="Gültig bis"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-end"
          />
        </v-col>
      </v-row>
      <v-text-field
        v-model="employee.versicherung_beitrag"
        label="Monatlicher Beitrag (€)"
        type="number"
        variant="outlined"
        prepend-inner-icon="mdi-cash"
        prefix="€"
        :rules="[rules.number]"
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