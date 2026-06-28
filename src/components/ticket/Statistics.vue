<template>
  <v-card v-if="ticket" class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-chart-box</v-icon>
      Statistiken & Metriken
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-row>
        <v-col v-for="stat in stats" :key="stat.title" cols="6" sm="3" lg="6">
          <div class="stat-card" :class="{ 'dark-stat': darkMode }">
            <div class="stat-title">{{ stat.title }}</div>
            <div class="stat-value">{{ stat.value }}</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  ticket: Object,
  activities: Array,
  darkMode: Boolean,
})

const stats = computed(() => {
  if (!props.ticket) return []
  const created = new Date(props.ticket.created_at)
  const now = new Date()
  const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24))
  const age = diffDays === 0 ? 'Heute' : diffDays === 1 ? '1 Tag' : `${diffDays} Tage`
  const lastAction = props.activities.length
    ? new Date(props.activities[0].created_at).toLocaleString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : '-'
  return [
    { title: 'Ticket Alter', value: age },
    { title: 'Kommentare', value: props.ticket.comments?.length || 0 },
    { title: 'Aktivitäten', value: props.activities.length },
    { title: 'Letzte Aktion', value: lastAction },
  ]
})
</script>

<style scoped>
.stat-card {
  background: rgba(248, 250, 252, 0.7);
  border-radius: 20px;
  padding: 16px;
  text-align: center;
  backdrop-filter: blur(4px);
}
.dark-stat {
  background: rgba(30, 40, 60, 0.6) !important;
  color: #e2e8f0;
}
.stat-title {
  font-size: 12px;
  color: #64748b;
}
.dark-mode .stat-title {
  color: #94a3b8;
}
.stat-value {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}
.dark-mode .stat-value {
  color: white;
}
</style>