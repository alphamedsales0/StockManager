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

      <!-- Filter- und Suchleiste -->
      <v-card-text class="pt-4">
        <v-row align="center">
          <!-- Statusfilter -->
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

          <!-- Ticket-Typ-Filter -->
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

          <!-- Suchleiste (neu) -->
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

          <!-- Aktualisierungs-Button -->
          <v-col cols="12" sm="2" class="text-right">
            <v-btn color="primary" @click="loadTickets" :loading="loading">
              <v-icon left>mdi-refresh</v-icon> Aktualisieren
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider></v-divider>

      <!-- Tabelle -->
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
                <v-btn icon variant="text" size="small" @click.stop="viewDetails(item)">
                  <v-icon>mdi-eye</v-icon>
                </v-btn>
              </td>
            </tr>
            <tr v-if="tickets.length === 0 && !loading">
              <td colspan="6" class="text-center">Keine Tickets gefunden</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>

      <!-- Paginierung -->
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
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Refs
const tickets = ref([])
const loading = ref(true)
const filterStatus = ref(null)
const filterFormType = ref(null)
const searchQuery = ref('')            // Neue Suchabfrage
const page = ref(1)
const totalPages = ref(1)

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
  { title: 'Angebotsanfrage', value: 'angebot' }   // neu
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

// Tickets laden (mit Filtern, Suche und Paginierung)
const loadTickets = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filterStatus.value) params.append('status', filterStatus.value)
    if (filterFormType.value) params.append('form_type', filterFormType.value)
    if (searchQuery.value) params.append('search', searchQuery.value)   // Suchparameter
    params.append('page', page.value)
    params.append('limit', 20)

    const response = await fetch(`/api/get_all_ticket.php?${params.toString()}`)
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

// Bei Filter‑ oder Suchänderung: Seite zurücksetzen und neu laden
const applyFilters = () => {
  page.value = 1
  loadTickets()
}

// Debounce für die Sucheingabe (500 ms)
let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

// Löschen der Suche
const clearSearch = () => {
  searchQuery.value = ''
  applyFilters()
}

// Navigation zur Detailseite
const viewDetails = (ticket) => {
  router.push(`/ticket/${ticket.id}?source=${ticket.source || 'form'}`)
}

const goBack = () => {
  router.go(-1)
}

onMounted(() => {
  loadTickets()
})
</script>

<style scoped>
.table-earnings th,
.table-earnings td {
  padding: 8px 12px !important;
  font-size: 0.85rem;
}
tbody tr:hover {
  background-color: #f5f5f5;
}
</style>