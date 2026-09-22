<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-home-outline</v-icon>
      Adressen
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="addAddress" />
    </v-card-title>
    <v-card-text>
      <div v-for="(addr, index) in kunde.adressen" :key="index" class="addr-item mb-4">
        <v-row>
          <v-col cols="12" sm="4">
            <v-select
              v-model="addr.typ"
              label="Typ"
              variant="outlined"
              :items="['Rechnungsadresse', 'Lieferadresse', 'Hauptadresse', 'Sonstiges']"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="addr.adresszusatz"
              label="Adresszusatz"
              variant="outlined"
              prepend-inner-icon="mdi-plus-box-outline"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="addr.strasse"
              label="Straße"
              variant="outlined"
              prepend-inner-icon="mdi-road"
              autocomplete="street-address"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="addr.hausnummer"
              label="Nr."
              variant="outlined"
              prepend-inner-icon="mdi-numeric"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="addr.plz"
              label="PLZ"
              variant="outlined"
              prepend-inner-icon="mdi-mailbox"
              autocomplete="postal-code"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="addr.ort"
              label="Ort"
              variant="outlined"
              prepend-inner-icon="mdi-city"
              autocomplete="address-level2"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="addr.bundesland"
              label="Bundesland"
              variant="outlined"
              prepend-inner-icon="mdi-map"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="addr.land"
              label="Land"
              variant="outlined"
              prepend-inner-icon="mdi-earth"
              :items="countries"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="addr.postfach"
              label="Postfach"
              variant="outlined"
              prepend-inner-icon="mdi-mailbox-open"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-checkbox v-model="addr.ist_standard" label="Standardadresse" color="primary" hide-details />
          </v-col>
        </v-row>
        <v-btn
          v-if="kunde.adressen.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeAddress(index)"
        />
        <v-divider v-if="index < kunde.adressen.length - 1" class="my-3" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue'
import { useKundeStore } from '../../stores/kundeStore'

const store = useKundeStore()
const kunde = store.kunde

const countries = ref(['Deutschland', 'Frankreich', 'Belgien', 'Österreich', 'Schweiz', 'Niederlande', 'Luxemburg'])

if (!kunde.adressen || kunde.adressen.length === 0) {
  kunde.adressen = [{
    id: null, typ: 'Rechnungsadresse', strasse: '', hausnummer: '',
    adresszusatz: '', plz: '', ort: '', bundesland: '', land: 'Deutschland',
    postfach: '', ist_standard: true
  }]
}

const addAddress = () => {
  kunde.adressen.push({
    id: null, typ: 'Rechnungsadresse', strasse: '', hausnummer: '',
    adresszusatz: '', plz: '', ort: '', bundesland: '', land: 'Deutschland',
    postfach: '', ist_standard: false
  })
}

const removeAddress = (index) => { kunde.adressen.splice(index, 1) }
</script>

<style scoped>
.addr-item { border-left: 3px solid #1976d2; padding-left: 16px; }
</style>