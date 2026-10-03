<template>
  <v-container fluid class="pa-4">
    <v-card elevation="3" rounded="lg">
      <v-card-title class="text-h5 py-3 bg-primary text-white d-flex align-center">
        <span>Alle Tickets</span>
        <v-spacer></v-spacer>
        <v-btn variant="text" color="white" @click="goBack">
          <v-icon left>mdi-arrow-left</v-icon> Zurück
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pt-4">
        <v-row align="center">
          <v-col cols="12" sm="3">
            <v-select
              v-model="filterStatus"
              :items="statusFilterOptions"
              label="Status filtern"
              clearable
              variant="outlined"
              density="compact"
              @update:modelValue="applyFilters"
            ></v-select>
          </v-col>
          <v-col cols="12" sm="3">
            <v-select
              v-model="filterFormType"
              :items="formTypeFilterOptions"
              label="Ticket-Typ filtern"
              clearable
              variant="outlined"
              density="compact"
              @update:modelValue="applyFilters"
            ></v-select>
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="searchQuery"
              variant="outlined"
              density="compact"
              placeholder="Suchen (Ref., Betreff, Kunde…)"
              append-inner-icon="mdi-magnify"
              clearable
              @click:append-inner="applyFilters"
              @input="onSearchInput"
              @keyup.enter="applyFilters"
              @click:clear="clearSearch"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="2" class="text-right">
            <v-btn color="primary" @click="loadTickets" :loading="loading">
              <v-icon left>mdi-refresh</v-icon> Aktualisieren
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>

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
              <th class="text-center">Aktion</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in tickets"
              :key="item.refNr"
              style="cursor: pointer;"
              @click="viewDetails(item)"
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
              <td class="text-center">
                <v-btn
                  icon
                  variant="text"
                  size="small"
                  color="primary"
                  @click.stop="viewDetails(item)"
                  title="Ansehen"
                >
                  <v-icon>mdi-eye</v-icon>
                </v-btn>
                <v-btn
                  v-if="canDeleteTicket"
                  icon
                  variant="text"
                  size="small"
                  color="error"
                  @click.stop="confirmDelete(item)"
                  title="Löschen"
                >
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </td>
            </tr>
            <tr v-if="tickets.length === 0 && !loading">
              <td colspan="6" class="text-center">Keine Tickets gefunden</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>

      <v-divider></v-divider>
      <v-card-actions class="justify-center">
        <v-pagination
          v-model="page"
          :length="totalPages"
          :total-visible="7"
          @update:modelValue="loadTickets"
        ></v-pagination>
      </v-card-actions>
    </v-card>

    <!-- Dialog de confirmation de suppression -->
    <v-dialog v-model="deleteDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="text-h6 bg-error text-white">
          <v-icon start color="white">mdi-alert</v-icon>
          Ticket wirklich löschen?
        </v-card-title>
        <v-card-text class="pt-4">
          Möchten Sie das Ticket
          <strong>{{ selectedTicket?.refNr }}</strong>
          ({{ selectedTicket?.name }}) endgültig löschen?
          <br><br>
          <v-alert type="warning" variant="tonal" density="compact">
            <strong>Achtung :</strong> Alle Kommentare, internen Notizen, Anhänge und Zeiteinträge zu diesem Ticket werden ebenfalls gelöscht.
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false" :disabled="deleting">Abbrechen</v-btn>
          <v-btn color="error" @click="handleDelete" :loading="deleting">Ja, löschen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="top end">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { usePermissionStore } from '../stores/permission'
import { apiUrl } from '@/config/api'

const router = useRouter()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()

const canDeleteTicket = computed(() => permissionStore.can('tickets-delete'))

// Refs
const tickets = ref([])
const loading = ref(true)
const filterStatus = ref(null)
const filterFormType = ref(null)
const searchQuery = ref('')
const page = ref(1)
const totalPages = ref(1)

// État suppression
const deleteDialog = ref(false)
const deleting = ref(false)
const selectedTicket = ref(null)

// Snackbar
const snackbar = ref({ show: false, text: '', color: 'success' })

// Filteroptionen
const statusFilterOptions = [
  { title: 'In Bearbeitung', value: 'pending' },
  { title: 'In Prüfung', value: 'in_progress' },
  { title: 'Abgeschlossen', value: 'completed' },
  { title: 'Storniert', value: 'cancelled' }
]

const formTypeFilterOptions = [
  { title: 'Serviceanfrage', value: 'service_request' },
  { title: 'Wartung', value: 'maintenance' },
  { title: 'Installation', value: 'installation' },
  { title: 'Ersatzteile', value: 'ersatzteile' },
  { title: 'Angebotsanfrage', value: 'angebot' }
]

// Farbzuordnung
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

// Tickets laden
const loadTickets = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filterStatus.value) params.append('status', filterStatus.value)
    if (filterFormType.value) params.append('form_type', filterFormType.value)
    if (searchQuery.value) params.append('search', searchQuery.value)
    params.append('page', page.value)
    params.append('limit', 20)

    const response = await fetch(apiUrl(`/get_all_ticket.php?${params.toString()}`), {
      credentials: 'include'
    })
    const data = await response.json()
    if (data.success) {
      tickets.value = data.tickets
      totalPages.value = data.totalPages || 1
    } else {
      console.error('API error:', data.error)
    }
  } catch (error) {
    console.error('Failed to load tickets:', error)
  } finally {
    loading.value = false
  }
}

// Bei Filter- oder Suchänderung
const applyFilters = () => {
  page.value = 1
  loadTickets()
}

// Debounce für die Sucheingabe
let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const clearSearch = () => {
  searchQuery.value = ''
  applyFilters()
}

// Navigation vers le détail — accessible à TOUS les rôles
const viewDetails = (ticket) => {
  router.push(`/ticket/${ticket.id}?source=${ticket.source || 'form'}`)
}

// Ouvrir le dialog de suppression
const confirmDelete = (ticket) => {
  if (!canDeleteTicket.value) {
    snackbar.value = {
      show: true,
      text: '⚠️ Nur Administratoren können Tickets löschen.',
      color: 'warning'
    }
    return
  }
  selectedTicket.value = ticket
  deleteDialog.value = true
}

// Effectuer la suppression
const handleDelete = async () => {
  if (!canDeleteTicket.value || !selectedTicket.value) return

  deleting.value = true
  try {
    const response = await fetch(apiUrl('/delete_ticket.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        ticket_id: selectedTicket.value.id,
        source: selectedTicket.value.source || 'form'
      })
    })
    const data = await response.json()

    if (data.success) {
      snackbar.value = { show: true, text: '🗑️ Ticket gelöscht', color: 'success' }
      deleteDialog.value = false
      await loadTickets()
    } else {
      throw new Error(data.error || 'Löschen fehlgeschlagen')
    }
  } catch (error) {
    console.error('Delete ticket failed:', error)
    snackbar.value = {
      show: true,
      text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`,
      color: 'error'
    }
    deleteDialog.value = false
  } finally {
    deleting.value = false
  }
}

const goBack = () => {
  router.go(-1)
}

onMounted(() => {
  if (!authStore.user) {
    authStore.initialize().then(() => {
      loadTickets()
    }).catch(() => {
      loadTickets()
    })
  } else {
    loadTickets()
  }
})
</script>

<style scoped>
.table-earnings th,
.table-earnings td { padding: 8px 12px !important; font-size: 0.85rem; }
tbody tr:hover { background-color: #f5f5f5; }
</style>