<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-alert-octagon-outline</v-icon>
      Mahngebühren (Staffel)
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="add" />
    </v-card-title>
    <v-card-text>
      <div v-for="(m, index) in kunde.mahngebuehren" :key="index" class="row-item mb-3">
        <v-row align="center">
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="m.stufe"
              label="Stufe"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-numeric"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="m.betrag"
              label="Betrag (€)"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-cash"
              prefix="€"
              :rules="[rules.number]"
            />
          </v-col>
          <v-col cols="12" sm="2">
            <v-btn
              v-if="kunde.mahngebuehren.length > 1"
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

if (!kunde.mahngebuehren || kunde.mahngebuehren.length === 0) {
  kunde.mahngebuehren = [{ id: null, stufe: 1, betrag: null }]
}
const add = () => kunde.mahngebuehren.push({ id: null, stufe: kunde.mahngebuehren.length + 1, betrag: null })
const remove = (index) => kunde.mahngebuehren.splice(index, 1)
</script>