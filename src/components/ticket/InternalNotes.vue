<!-- components/ticket/InternalNotes.vue -->
<template>
  <v-card v-if="ticket" class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-lock-outline</v-icon>
      Interne Notizen (nur für Mitarbeiter)
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-list class="transparent-list">
        <v-list-item v-for="note in notes" :key="note.id">
          <template #prepend><v-icon>mdi-note-text</v-icon></template>
          <div>
            <div class="item-label">{{ note.author }} – {{ formatDateTime(note.created_at) }}</div>
            <div class="item-value">{{ note.text }}</div>
          </div>
        </v-list-item>
        <v-list-item v-if="!notes.length">
          <div class="text-grey text-center py-2">Keine internen Notizen</div>
        </v-list-item>
      </v-list>
      <v-textarea v-model="newNote" label="Neue interne Notiz" rows="3" variant="outlined" class="mt-3" />
      <v-btn color="secondary" block @click="addNote" :disabled="!newNote.trim()" class="mt-2">
        Notiz speichern
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  ticket: Object,
  notes: Array,
  darkMode: Boolean,
})

const emit = defineEmits(['addNote'])

const newNote = ref('')

const addNote = () => {
  if (!newNote.value.trim()) return
  emit('addNote', newNote.value)
  newNote.value = ''
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
</script>