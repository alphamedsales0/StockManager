<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-bank</v-icon>
      Bankverbindungen
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="addBank" />
    </v-card-title>
    <v-card-text>
      <div v-for="(b, index) in kunde.bankverbindungen" :key="index" class="bank-item mb-4">
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="b.kontoinhaber"
              label="Kontoinhaber"
              variant="outlined"
              prepend-inner-icon="mdi-account"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="b.iban"
              label="IBAN"
              variant="outlined"
              prepend-inner-icon="mdi-bank-transfer"
              :rules="[rules.iban]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="b.bic"
              label="BIC"
              variant="outlined"
              prepend-inner-icon="mdi-bank"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="b.bankname"
              label="Bankname"
              variant="outlined"
              prepend-inner-icon="mdi-domain"
            />
          </v-col>
        </v-row>
        <v-checkbox v-model="b.ist_standard" label="Standardkonto" color="primary" />
        <v-btn
          v-if="kunde.bankverbindungen.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeBank(index)"
        />
        <v-divider v-if="index < kunde.bankverbindungen.length - 1" class="my-3" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
import { useValidationRules } from '../../composables/useValidationRules'

const store = useKundeStore()
const kunde = store.kunde
const rules = useValidationRules()

if (!kunde.bankverbindungen || kunde.bankverbindungen.length === 0) {
  kunde.bankverbindungen = [{ id: null, kontoinhaber: '', iban: '', bic: '', bankname: '', ist_standard: true }]
}

const addBank = () => {
  kunde.bankverbindungen.push({ id: null, kontoinhaber: '', iban: '', bic: '', bankname: '', ist_standard: false })
}
const removeBank = (index) => { kunde.bankverbindungen.splice(index, 1) }
</script>

<style scoped>
.bank-item { border-left: 3px solid #7e57c2; padding-left: 16px; }
</style>