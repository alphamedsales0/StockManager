<template>
  <v-toolbar :color="darkMode ? '#0a0f1a' : '#0f172a'" dark flat class="toolbar-header px-4">
    <div class="d-flex align-center">
      <v-avatar color="primary" size="42" class="mr-4 floating-avatar">
        <v-icon size="24">mdi-ticket-confirmation</v-icon>
      </v-avatar>
      <div>
        <div class="text-h6 font-weight-bold">Ticket {{ ticket.reference_number }}</div>
        <div class="text-caption text-grey-lighten-1">
          {{ entryDateLabel }}: {{ entryDateFormatted }}
        </div>
      </div>
    </div>
    <v-spacer />
    <v-btn icon variant="outlined" color="white" rounded="circle" @click="goBack" class="mr-3" size="small">
      <v-icon>mdi-home</v-icon>
    </v-btn>
    <v-chip :color="priorityColor" class="priority-chip mr-2" size="small" variant="flat">
      <v-icon start size="14">mdi-alert</v-icon>
      {{ priorityLabel }}
    </v-chip>
    <v-chip :color="statusColor" class="status-chip mr-4" size="default" variant="flat">
      <v-icon start size="18">{{ statusIcon }}</v-icon>
      {{ translateStatus(ticket.status) }}
    </v-chip>
    <v-btn icon variant="text" @click="exportToPDF" class="mr-2" title="PDF exportieren">
      <v-icon>mdi-file-pdf-box</v-icon>
    </v-btn>
    <v-btn icon variant="text" @click="toggleDarkMode" class="mr-2">
      <v-icon>{{ darkMode ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
    </v-btn>
  </v-toolbar>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  ticket: Object,
  darkMode: Boolean,
  entryDateLabel: String,
  entryDateFormatted: String,
})

const emit = defineEmits(['toggleDarkMode', 'goBack'])
const router = useRouter()

const goBack = () => emit('goBack')
const toggleDarkMode = () => emit('toggleDarkMode')

const priorityLabel = computed(() => {
  const urgency = props.ticket?.form_data?.urgency
  if (urgency === 'hoch') return 'Höchste Priorität'
  if (urgency === 'mittel') return 'Mittlere Priorität'
  return 'Normale Priorität'
})

const priorityColor = computed(() => {
  const urgency = props.ticket?.form_data?.urgency
  if (urgency === 'hoch') return 'error'
  if (urgency === 'mittel') return 'warning'
  return 'success'
})

const statusColor = computed(() => {
  const map = { pending: 'warning', in_progress: 'info', completed: 'success', cancelled: 'error' }
  return map[props.ticket?.status] || 'grey'
})

const statusIcon = computed(() => {
  const map = { pending: 'mdi-clock-outline', in_progress: 'mdi-progress-clock', completed: 'mdi-check-circle', cancelled: 'mdi-cancel' }
  return map[props.ticket?.status]
})

const translateStatus = (status) => {
  const map = { pending: 'In Bearbeitung', in_progress: 'In Prüfung', completed: 'Abgeschlossen', cancelled: 'Storniert' }
  return map[status] || status
}

const exportToPDF = () => window.print()
</script>

<style scoped>
.toolbar-header {
  min-height: 90px;
}
.floating-avatar {
  transition: transform 0.2s;
}
.floating-avatar:hover {
  transform: scale(1.05);
}
.priority-chip,
.status-chip {
  color: white !important;
}
@media (max-width: 600px) {
  .toolbar-header {
    min-height: 70px;
    flex-wrap: wrap;
    gap: 8px;
  }
  .toolbar-header .v-toolbar-title {
    font-size: 1rem;
  }
}
</style>