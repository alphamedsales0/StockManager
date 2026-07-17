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
        <v-data-table
          :headers="headers"
          :items="employees"
          :loading="loading"
          class="elevation-1"
        >
          <template v-slot:item.actions="{ item }">
            <v-btn icon size="small" color="info" @click="editEmployee(item.id)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon size="small" color="error" @click="deleteEmployee(item.id)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
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
const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'E-Mail', key: 'email' },
  { title: 'Aktionen', key: 'actions', sortable: false }
]

const fetchEmployees = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/employees') // ou votre endpoint
    employees.value = response.data.users
  } catch (error) {
    console.error('Fehler beim Laden der Mitarbeiter:', error)
  } finally {
    loading.value = false
  }
}

const editEmployee = (id) => {
  router.push(`/employees/edit/${id}`)
}

const deleteEmployee = async (id) => {
  if (confirm('Möchten Sie diesen Mitarbeiter wirklich löschen?')) {
    try {
      await axios.delete(`/api/employees/${id}`)
      await fetchEmployees() // Aktualisieren
    } catch (error) {
      console.error('Löschen fehlgeschlagen:', error)
    }
  }
}

onMounted(fetchEmployees)
</script>