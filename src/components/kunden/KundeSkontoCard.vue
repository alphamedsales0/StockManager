<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-percent-circle-outline</v-icon>
      Skonto (Staffel)
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="add" />
    </v-card-title>
    <v-card-text>
      <div v-for="(s, index) in kunde.skonto" :key="index" class="row-item mb-3">
        <v-row align="center">
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="s.stufe"
              label="Stufe"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-numeric"
            />
          </v-col>
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="s.prozent"
              label="Prozent (%)"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-percent"
              :rules="[rules.number]"
            />
          </v-col>
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="s.tage"
              label="Tage"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-calendar"
              :rules="[rules.number]"
            />
          </v-col>
          <v-col cols="12" sm="2">
            <v-btn
              v-if="kunde.skonto.length > 1"
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
import { useValidationRules } from '../../composables/useValidationRules'
const store = useKundeStore()
const kunde = store.kunde
const rules = useValidationRules()

if (!kunde.skonto || kunde.skonto.length === 0) {
  kunde.skonto = [{ id: null, stufe: 1, prozent: null, tage: null }]
}
const add = () => kunde.skonto.push({ id: null, stufe: kunde.skonto.length + 1, prozent: null, tage: null })
const remove = (index) => kunde.skonto.splice(index, 1)
</script>