<template>
  <v-card v-if="ticket" class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-clock-outline</v-icon>
      Zeitaufwand ({{ totalHours }} h)
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-list class="transparent-list">
        <v-list-item v-for="entry in timeEntries" :key="entry.id">
          <template #prepend><v-icon>mdi-timer</v-icon></template>
          <div>
            <div class="item-label">{{ entry.date }} – {{ entry.user }}</div>
            <div class="item-value">{{ entry.hours }} h – {{ entry.description }}</div>
          </div>
        </v-list-item>
        <v-list-item v-if="!timeEntries.length">
          <div class="text-grey text-center py-2">Keine Zeit erfasst</div>
        </v-list-item>
      </v-list>
      <v-divider class="my-3" />
      <v-row>
        <v-col cols="5">
          <v-text-field v-model="newHours" type="number" label="Stunden" step="0.5" variant="outlined" density="compact" />
        </v-col>
        <v-col cols="7">
          <v-text-field v-model="newDesc" label="Beschreibung" variant="outlined" density="compact" />
        </v-col>
        <v-col cols="12">
          <v-btn color="primary" block @click="addTime" :disabled="!newHours">Zeit hinzufügen</v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  ticket: Object,
  timeEntries: Array,
  darkMode: Boolean,
})
const emit = defineEmits(['addTime'])

const newHours = ref('')
const newDesc = ref('')
const totalHours = computed(() => props.timeEntries.reduce((sum, e) => sum + parseFloat(e.hours), 0).toFixed(1))

const addTime = () => {
  if (!newHours.value) return
  emit('addTime', { hours: parseFloat(newHours.value), description: newDesc.value })
  newHours.value = ''
  newDesc.value = ''
}
</script>

<style scoped>
.item-label {
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
}
.item-value {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.dark-mode .item-label {
  color: #94a3b8;
}
.dark-mode .item-value {
  color: #e2e8f0;
}
</style>