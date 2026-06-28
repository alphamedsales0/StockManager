<!-- components/ticket/TicketInfo.vue -->
<template>
  <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-information-outline</v-icon>
      Ticket Informationen
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-list class="transparent-list">
        <v-list-item>
          <template #prepend><v-icon>mdi-calendar-plus</v-icon></template>
          <div>
            <div class="item-label">System-Erstelldatum</div>
            <div class="item-value">{{ formatDateTime(ticket.created_at) }}</div>
          </div>
        </v-list-item>
        <v-list-item>
          <template #prepend><v-icon>mdi-calendar-edit</v-icon></template>
          <div>
            <div class="item-label">Aktualisiert</div>
            <div class="item-value">{{ formatDateTime(ticket.updated_at) }}</div>
          </div>
        </v-list-item>
        <v-list-item>
          <template #prepend><v-icon>mdi-information</v-icon></template>
          <div>
            <div class="item-label">Ticket-Status</div>
            <div class="item-value">
              <v-chip :color="statusColor" size="small" label>{{ translateStatus(ticket.status) }}</v-chip>
            </div>
          </div>
        </v-list-item>
        <v-list-item>
          <template #prepend><v-icon>mdi-account-tie</v-icon></template>
          <div>
            <div class="item-label">Bearbeiter</div>
            <div class="item-value">{{ ticket.assigned_to || '-' }}</div>
          </div>
        </v-list-item>
        <v-list-item>
          <template #prepend><v-icon>mdi-account-clock</v-icon></template>
          <div>
            <div class="item-label">Letzte Änderung</div>
            <div class="item-value">{{ ticket.last_updated_by || '-' }} – {{ formatDateTime(ticket.updated_at) }}</div>
          </div>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  ticket: Object,
  darkMode: Boolean,
})

const statusColor = computed(() => {
  const map = { pending: 'warning', in_progress: 'info', completed: 'success', cancelled: 'error' }
  return map[props.ticket?.status] || 'grey'
})

const translateStatus = (status) => {
  const map = { pending: 'In Bearbeitung', in_progress: 'In Prüfung', completed: 'Abgeschlossen', cancelled: 'Storniert' }
  return map[status] || status
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
</script>