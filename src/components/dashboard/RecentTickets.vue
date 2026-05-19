<template>
  <v-card elevation="3" rounded="lg" class="recent-tickets-card h-100">
    <v-card-title class="text-h6 py-3 bg-grey-lighten-3">
      Aktuelle Tickets
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
            <td colspan="5" class="text-center">Keine Tickets vorhanden</td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const tickets = ref([])
const loading = ref(true)



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

const loadTickets = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/get_all_ticket.php')
    const data = await response.json()
    if (data.success) {
      tickets.value = data.tickets
    } else {
      console.error('API error:', data.error)
    }
  } catch (error) {
    console.error('Failed to load tickets:', error)
  } finally {
    loading.value = false
  }
}

const viewDetails = (ticket) => {
  console.log('Ticket angeklickt:', ticket);
  router.push(`/ticket/${ticket.id}`)
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