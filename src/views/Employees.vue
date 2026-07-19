<template>
  <v-container>
    <v-row>
      <v-col>
        <h1 class="text-h4 mb-4">Mitarbeiter</h1>
        <v-btn color="primary" @click="$router.push('/employees/add')" prepend-icon="mdi-account-plus">
          Neuer Mitarbeiter
        </v-btn>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          closable
          @click:close="errorMessage = ''"
          class="mb-4"
        >
          {{ errorMessage }}
        </v-alert>

        <v-data-table
          :headers="headers"
          :items="employees"
          :loading="loading"
          class="elevation-1"
          items-per-page="15"
        >
          <template v-slot:item.fullname="{ item }">
            {{ item.nachname }}, {{ item.vorname }}
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn icon size="small" color="info" @click="editEmployee(item.benutzer_id)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon size="small" color="error" @click="deleteEmployee(item.benutzer_id)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </template>
          <template v-slot:item.is_active="{ item }">
            <v-chip :color="item.is_active ? 'success' : 'grey'" size="small">
              {{ item.is_active ? 'Aktiv' : 'Inaktiv' }}
            </v-chip>
          </template>
          <template v-slot:no-data>
            <v-alert type="info" variant="tonal" class="my-4">
              Keine Mitarbeiter gefunden.
            </v-alert>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const employees = ref([])
const loading = ref(false)
const errorMessage = ref('')

const headers = [
  { title: 'ID', key: 'benutzer_id' },
  { title: 'Name', key: 'fullname', sortable: true },
  { title: 'E-Mail', key: 'email' },
  { title: 'Position', key: 'position' },
  { title: 'Abteilung', key: 'abteilung' },
  { title: 'Telefon', key: 'telefon' },
  { title: 'Status', key: 'is_active' },
  { title: 'Aktionen', key: 'actions', sortable: false }
]

const fetchEmployees = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await axios.get('/api/employees_list.php')
    if (response.data.success) {
      employees.value = response.data.employees || []
    } else {
      errorMessage.value = 'API-Fehler: ' + (response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error('Fehler beim Laden:', error)
    let msg = 'Netzwerkfehler beim Laden der Mitarbeiter'
    if (error.response) {
      msg += ` (Status ${error.response.status})`
      if (error.response.data?.error) msg += `: ${error.response.data.error}`
    } else if (error.request) {
      msg += ' - Aucune réponse du serveur'
    } else {
      msg += ` - ${error.message}`
    }
    errorMessage.value = msg
  } finally {
    loading.value = false
  }
}

const editEmployee = (id) => {
  router.push(`/employees/edit/${id}`)
}

const deleteEmployee = async (id) => {
  if (!confirm('Möchten Sie diesen Mitarbeiter wirklich löschen?')) return
  loading.value = true
  try {
    const response = await axios.delete(`/api/employees.php?id=${id}`)
    if (response.data.success) {
      await fetchEmployees()
    } else {
      errorMessage.value = 'Fehler beim Löschen: ' + (response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    console.error('Löschen fehlgeschlagen:', error)
    errorMessage.value = 'Netzwerkfehler beim Löschen'
  } finally {
    loading.value = false
  }
}

onMounted(fetchEmployees)
</script>