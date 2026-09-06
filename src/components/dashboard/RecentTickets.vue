<template>
  <v-card elevation="3" rounded="lg" class="recent-tickets-card h-100">
    <v-card-title class="text-h6 py-3 bg-grey-lighten-3 d-flex align-center">
      <span>Aktuelle Tickets</span>
      <v-spacer></v-spacer>
      <v-btn
        variant="text"
        color="primary"
        size="small"
        @click="goToAllTickets"
      >
        Alle Tickets
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text class="pa-0">
      <v-progress-linear v-if="loading" indeterminate color="primary"></v-progress-linear>
      <v-table v-else density="compact" class="table-earnings">
        <thead>
          <tr>
            <th>Datum</th>
            <th>Ref.-Nr.</th>
            <th>Betreff</th>
            <th>Kunde</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in tickets"
            :key="item.refNr"
            @click="viewDetails(item)"
            style="cursor: pointer;"
          >
            <td>{{ item.date }}</td>
            <td>{{ item.refNr }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.kundenname }}</td>
            <td>
              <v-chip :color="getStatusColor(item.status)" size="x-small" label>
                {{ item.status }}
              </v-chip>
            </td>
          </tr>
          <tr v-if="tickets.length === 0 && !loading">
            <td colspan="5" class="text-center">
              <span v-if="error">Fehler beim Laden der Tickets: {{ error }}</span>
              <span v-else>Keine Tickets vorhanden</span>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePermissionStore } from '../../stores/permission'

const router = useRouter()
const permissionStore = usePermissionStore()

// Refs
const tickets = ref([])
const loading = ref(true)
const error = ref(null)

// Farbzuordnung für Status
const getStatusColor = (status) => {
  const colors = {
    'In Bearbeitung': 'warning',
    'In Prüfung': 'info',
    'Zurückgestellt': 'orange-darken-2',
    'Abgeschlossen': 'success',
    'Storniert': 'error',
    'Abgelehnt': 'red-darken-2',
    'In Planung': 'primary'
  }
  return colors[status] || 'grey'
}

// Lädt die 7 neuesten Tickets
const loadTickets = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await fetch('/api/get_all_ticket.php?limit=7')
    if (!response.ok) {
      throw new Error(`HTTP Fehler: ${response.status}`)
    }
    const data = await response.json()
    console.log('API response:', data)
    if (data.success) {
      tickets.value = data.tickets || []
    } else {
      throw new Error(data.error || 'Unbekannter API-Fehler')
    }
  } catch (err) {
    console.error('Fehler beim Laden der Tickets:', err)
    error.value = err.message
    tickets.value = []
  } finally {
    loading.value = false
  }
}

// Navigiert zur Detailseite – nur mit Admin-Berechtigung
const viewDetails = (ticket) => {
  permissionStore.checkPermission('Admin', () => {
    router.push(`/ticket/${ticket.id}?source=${ticket.source || 'form'}`)
  })
}

// Navigiert zur vollständigen Ticketübersicht (ungeschützt, da die Übersicht selbst prüft)
const goToAllTickets = () => {
  router.push('/tickets')
}

onMounted(() => {
  loadTickets()
})
</script>

<style scoped>
.recent-tickets-card {
  background: white;
  border-left: 4px solid #1976d2;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.recent-tickets-card .v-card-text {
  flex: 1;
}
.table-earnings th,
.table-earnings td {
  padding: 8px 10px !important;
  font-size: 0.85rem;
}
tbody tr:hover {
  background-color: #f5f5f5;
}
</style>