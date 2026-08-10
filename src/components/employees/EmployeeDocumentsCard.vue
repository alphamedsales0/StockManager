<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-folder-upload-outline</v-icon>
      Dokumente
      <v-spacer />
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="text"
        color="primary"
        @click="addDocument"
      />
    </v-card-title>
    <v-card-text>
      <div
        v-for="(doc, index) in employee.documents"
        :key="index"
        class="doc-item mb-4"
      >
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="doc.name"
              label="Dokumentenname"
              variant="outlined"
              prepend-inner-icon="mdi-file-outline"
              :rules="[rules.required]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="doc.typ"
              label="Dokumententyp"
              variant="outlined"
              prepend-inner-icon="mdi-tag"
              :items="['Vertrag', 'Zeugnis', 'Zertifikat', 'Pass', 'Arbeitserlaubnis', 'Sonstiges']"
              :rules="[rules.required]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-file-input
              v-model="doc.file"
              label="Datei auswählen"
              accept=".pdf,.doc,.docx,.jpg,.png"
              variant="outlined"
              prepend-inner-icon="mdi-file-upload"
              @change="handleFileUpload(index, $event)"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="doc.gueltig_bis"
              label="Gültig bis (falls befristet)"
              type="date"
              variant="outlined"
              prepend-inner-icon="mdi-calendar-end"
            />
          </v-col>
        </v-row>
        <v-btn
          v-if="employee.documents.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeDocument(index)"
        />
        <v-divider v-if="index < employee.documents.length - 1" class="my-3" />
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

if (!employee.documents || employee.documents.length === 0) {
  employee.documents = [
    { name: '', typ: 'Vertrag', file: null, gueltig_bis: '' }
  ]
}

const addDocument = () => {
  employee.documents.push({
    name: '',
    typ: 'Vertrag',
    file: null,
    gueltig_bis: ''
  })
}

const removeDocument = (index) => {
  employee.documents.splice(index, 1)
}

const handleFileUpload = (index, event) => {
  const file = event.target.files[0]
  if (file) {
    employee.documents[index].file = file
    // Optional: Dateiname automatisch setzen, falls name leer
    if (!employee.documents[index].name) {
      employee.documents[index].name = file.name
    }
  }
}
</script>

<style scoped>
.doc-item {
  border-left: 3px solid #ff9800;
  padding-left: 16px;
}
</style>