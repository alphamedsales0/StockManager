<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-tag-outline</v-icon>
      Preisgruppen
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="add" />
    </v-card-title>
    <v-card-text>
      <div v-for="(p, index) in kunde.preisgruppen" :key="index" class="row-item mb-3">
        <v-row align="center">
          <v-col cols="12" sm="7">
            <v-text-field
              v-model="p.preisgruppe"
              label="Preisgruppe"
              variant="outlined"
              prepend-inner-icon="mdi-tag"
            />
          </v-col>
          <v-col cols="12" sm="3">
            <v-text-field
              v-model="p.prioritaet"
              label="Priorität"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-sort-numeric-ascending"
            />
          </v-col>
          <v-col cols="12" sm="2">
            <v-btn
              v-if="kunde.preisgruppen.length > 1"
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

if (!kunde.preisgruppen || kunde.preisgruppen.length === 0) {
  kunde.preisgruppen = [{ id: null, preisgruppe: '', prioritaet: 0 }]
}
const add = () => kunde.preisgruppen.push({ id: null, preisgruppe: '', prioritaet: 0 })
const remove = (index) => kunde.preisgruppen.splice(index, 1)
</script>