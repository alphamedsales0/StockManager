<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-form-textbox</v-icon>
      Zusatzfelder
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="add" />
    </v-card-title>
    <v-card-text>
      <div v-for="(z, index) in kunde.zusatzfelder" :key="index" class="row-item mb-3">
        <v-row align="center">
          <v-col cols="12" sm="5">
            <v-text-field
              v-model="z.feldname"
              label="Feldname"
              variant="outlined"
              prepend-inner-icon="mdi-tag-outline"
            />
          </v-col>
          <v-col cols="12" sm="5">
            <v-text-field
              v-model="z.wert"
              label="Wert"
              variant="outlined"
              prepend-inner-icon="mdi-format-text"
            />
          </v-col>
          <v-col cols="12" sm="2">
            <v-btn
              v-if="kunde.zusatzfelder.length > 1"
              icon="mdi-delete"
              size="small"
              color="error"
              variant="text"
              @click="remove(index)"
            />
          </v-col>
        </v-row>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
const store = useKundeStore()
const kunde = store.kunde

if (!kunde.zusatzfelder || kunde.zusatzfelder.length === 0) {
  kunde.zusatzfelder = [{ id: null, feldname: '', wert: '' }]
}
const add = () => kunde.zusatzfelder.push({ id: null, feldname: '', wert: '' })
const remove = (index) => kunde.zusatzfelder.splice(index, 1)
</script>