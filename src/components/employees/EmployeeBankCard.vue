<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-bank</v-icon>
      Bankverbindung
      <v-spacer />
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="text"
        color="primary"
        @click="addBankAccount"
      />
    </v-card-title>
    <v-card-text>
      <div
        v-for="(account, index) in employee.bank_accounts"
        :key="index"
        class="bank-item mb-4"
      >
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="account.kontoinhaber"
              label="Kontoinhaber"
              variant="outlined"
              prepend-inner-icon="mdi-account"
              :rules="[rules.required]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="account.iban"
              label="IBAN"
              variant="outlined"
              prepend-inner-icon="mdi-bank-transfer"
              :rules="[rules.iban]"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="account.bic"
              label="BIC"
              variant="outlined"
              prepend-inner-icon="mdi-bank"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="account.bankname"
              label="Bankname"
              variant="outlined"
              prepend-inner-icon="mdi-domain"
            />
          </v-col>
        </v-row>
        <v-checkbox
          v-model="account.ist_aktiv"
          label="Aktives Konto für Gehaltszahlung"
          color="primary"
        />
        <v-btn
          v-if="employee.bank_accounts.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeBankAccount(index)"
        />
        <v-divider v-if="index < employee.bank_accounts.length - 1" class="my-3" />
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

// Initiales Konto anlegen, falls noch keins existiert
if (!employee.bank_accounts || employee.bank_accounts.length === 0) {
  employee.bank_accounts = [
    { kontoinhaber: '', iban: '', bic: '', bankname: '', ist_aktiv: true }
  ]
}

const addBankAccount = () => {
  employee.bank_accounts.push({
    kontoinhaber: '',
    iban: '',
    bic: '',
    bankname: '',
    ist_aktiv: false
  })
}

const removeBankAccount = (index) => {
  employee.bank_accounts.splice(index, 1)
}
</script>

<style scoped>
.bank-item {
  border-left: 3px solid #1976d2;
  padding-left: 16px;
}
</style>