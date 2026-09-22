<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-note-text-outline</v-icon>
      Bemerkungen
      <v-spacer />
      <v-btn icon="mdi-plus" size="small" variant="text" color="primary" @click="add" />
    </v-card-title>
    <v-card-text>
      <div v-for="(b, index) in kunde.bemerkungen" :key="index" class="note-item mb-4">
        <v-row>
          <v-col cols="12" sm="4">
            <v-select
              v-model="b.typ"
              label="Typ"
              variant="outlined"
              :items="[
                { title: 'Intern', value: 'internal' },
                { title: 'Kunde', value: 'customer' }
              ]"
            />
          </v-col>
          <v-col cols="12" sm="8">
            <v-textarea
              v-model="b.bemerkung"
              label="Bemerkung"
              variant="outlined"
              prepend-inner-icon="mdi-comment-text-outline"
              rows="2"
              auto-grow
            />
          </v-col>
        </v-row>
        <v-btn
          v-if="kunde.bemerkungen.length > 1"
          icon="mdi-delete"
          size="small"
          color="error"
          variant="text"
          @click="remove(index)"
        />
        <v-divider v-if="index < kunde.bemerkungen.length - 1" class="my-3" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useKundeStore } from '../../stores/kundeStore'
const store = useKundeStore()
const kunde = store.kunde

if (!kunde.bemerkungen || kunde.bemerkungen.length === 0) {
  kunde.bemerkungen = [{ id: null, typ: 'internal', bemerkung: '' }]
}
const add = () => kunde.bemerkungen.push({ id: null, typ: 'internal', bemerkung: '' })
const remove = (index) => kunde.bemerkungen.splice(index, 1)
</script>

<style scoped>
.note-item { border-left: 3px solid #ff9800; padding-left: 16px; }
</style>