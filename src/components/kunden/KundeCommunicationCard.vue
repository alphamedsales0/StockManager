<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-phone-message</v-icon>
      Kommunikation
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="addComm" />
    </v-card-title>
    <v-card-text>
      <div v-for="(k, index) in kunde.kommunikation" :key="index" class="comm-item mb-4">
        <v-row>
          <v-col cols="12" sm="4">
            <v-select
              v-model="k.typ"
              label="Typ"
              variant="outlined"
              :items="['Telefon', 'Mobil', 'Fax', 'E-Mail', 'Website', 'Sonstiges']"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="k.wert"
              label="Wert"
              variant="outlined"
              prepend-inner-icon="mdi-format-text"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-text-field
              v-model="k.bemerkung"
              label="Bemerkung"
              variant="outlined"
              prepend-inner-icon="mdi-comment-outline"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-checkbox v-model="k.ist_standard" label="Standard" color="primary" hide-details />
          </v-col>
        </v-row>
        <v-btn
          v-if="kunde.kommunikation.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="removeComm(index)"
        />
        <v-divider v-if="index < kunde.kommunikation.length - 1" class="my-3" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'

const store = useKundeStore()
const kunde = store.kunde

if (!kunde.kommunikation || kunde.kommunikation.length === 0) {
  kunde.kommunikation = [{ id: null, typ: 'Telefon', wert: '', bemerkung: '', ist_standard: true }]
}

const addComm = () => {
  kunde.kommunikation.push({ id: null, typ: 'Telefon', wert: '', bemerkung: '', ist_standard: false })
}
const removeComm = (index) => { kunde.kommunikation.splice(index, 1) }
</script>

<style scoped>
.comm-item { border-left: 3px solid #26a69a; padding-left: 16px; }
</style>