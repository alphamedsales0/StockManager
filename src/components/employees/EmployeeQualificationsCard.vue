<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-school-outline</v-icon>
      Qualifikationen &amp; Zertifikate
      <v-spacer />
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="text"
        color="primary"
        @click="addQualification"
      />
    </v-card-title>
    <v-card-text>
      <div
        v-for="(qual, index) in employee.qualifications"
        :key="index"
        class="qual-item mb-4"
      >
        <v-row>
          <v-col cols="12" sm="4">
            <v-select
              v-model="qual.qualifikationstyp"
              label="Typ"
              variant="outlined"
              prepend-inner-icon="mdi-tag"
              :items="['Schulabschluss', 'Studium', 'Ausbildung', 'Zertifikat', 'Sprache', 'Führerschein']"
              :rules="[rules.required]"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="qual.bezeichnung"
              label="Bezeichnung"
              variant="outlined"
              prepend-inner-icon="mdi-format-title"
              :rules="[rules.required]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="qual.institution"
              label="Institution / Arbeitgeber"
              variant="outlined"
              prepend-inner-icon="mdi-domain"
            />
          </v-col>
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="qual.abschlussdatum"
              label="Abschlussdatum"
              type="date"
              variant="outlined"
              prepend-inner-icon="mdi-calendar"
            />
          </v-col>
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="qual.gueltig_bis"
              label="Gültig bis"
              type="date"
              variant="outlined"
              prepend-inner-icon="mdi-calendar-end"
            />
          </v-col>
        </v-row>
        <v-btn
          v-if="employee.qualifications.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeQualification(index)"
        />
        <v-divider v-if="index < employee.qualifications.length - 1" class="my-3" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()

if (!employee.qualifications || employee.qualifications.length === 0) {
  employee.qualifications = [
    { qualifikationstyp: 'Schulabschluss', bezeichnung: '', institution: '', abschlussdatum: '', gueltig_bis: '' }
  ]
}

const addQualification = () => {
  employee.qualifications.push({
    qualifikationstyp: 'Schulabschluss',
    bezeichnung: '',
    institution: '',
    abschlussdatum: '',
    gueltig_bis: ''
  })
}

const removeQualification = (index) => {
  employee.qualifications.splice(index, 1)
}
</script>

<style scoped>
.qual-item {
  border-left: 3px solid #4caf50;
  padding-left: 16px;
}
</style>