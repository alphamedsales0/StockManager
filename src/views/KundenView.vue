<template>
  <v-container fluid class="pa-6">
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title class="d-flex align-center justify-space-between pa-6">
        <div class="d-flex align-center">
          <v-avatar color="primary" size="52" class="mr-4">
            <v-icon size="30">mdi-account-group</v-icon>
          </v-avatar>
          <div>
            <h1 class="text-h5 font-weight-bold">Kunden</h1>
            <p class="text-body-2 text-medium-emphasis mb-0">Kundenverwaltung</p>
          </div>
        </div>
        <v-btn color="primary" variant="flat" size="large" prepend-icon="mdi-account-plus" @click="router.push('/customers/add')">
          Neuer Kunde
        </v-btn>
      </v-card-title>
    </v-card>

    <v-row class="mb-6">
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl" elevation="2">
          <v-card-text class="d-flex align-center">
            <v-avatar color="primary" size="48" class="mr-4"><v-icon>mdi-account-multiple</v-icon></v-avatar>
            <div>
              <div class="text-caption">Gesamt Kunden</div>
              <div class="text-h5 font-weight-bold">{{ kunden.length }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl" elevation="2">
          <v-card-text class="d-flex align-center">
            <v-avatar color="success" size="48" class="mr-4"><v-icon>mdi-account-check</v-icon></v-avatar>
            <div>
              <div class="text-caption">Verifiziert</div>
              <div class="text-h5 font-weight-bold">{{ verifiedCount }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl" elevation="2">
          <v-card-text class="d-flex align-center">
            <v-avatar color="warning" size="48" class="mr-4"><v-icon>mdi-domain</v-icon></v-avatar>
            <div>
              <div class="text-caption">Gewerbekunden</div>
              <div class="text-h5 font-weight-bold">{{ gewerbeCount }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card class="mb-6 rounded-xl" elevation="2">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="5">
            <v-text-field
              v-model="search"
              label="Suche Kunden"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-magnify"
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="selectedType"
              label="Kundentyp"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-account-switch"
              :items="['Privatkunde', 'Gewerbekunde']"
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="selectedGroup"
              label="Kundengruppe"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-tag-multiple"
              :items="groups"
              clearable
            />
          </v-col>
          <v-col cols="12" md="1" class="text-center">
            <v-btn icon variant="text" @click="loadKunden"><v-icon>mdi-refresh</v-icon></v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card class="rounded-xl" elevation="3">
      <v-data-table :headers="headers" :items="filteredKunden" :loading="loading" items-per-page="10">
        <template #item="{ item, columns }">
          <tr class="clickable-row" @click="openDetail(item)">
            <td v-for="col in columns" :key="col.key" :class="{ 'text-center': col.key === 'actions' }">
              <template v-if="col.key === 'photo'">
                <v-avatar size="42" color="grey-lighten-3">
                  <img v-if="item.profilbild" :src="item.profilbild" class="kunde-photo" />
                  <v-icon v-else>mdi-account</v-icon>
                </v-avatar>
              </template>
              <template v-else-if="col.key === 'name'">
                <div>
                  <div class="font-weight-bold">{{ displayName(item) }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
                </div>
              </template>
              <template v-else-if="col.key === 'type'">
                <v-chip :color="item.person_type === 'Gewerbekunde' ? 'warning' : 'primary'" size="small">
                  {{ item.person_type }}
                </v-chip>
              </template>
              <template v-else-if="col.key === 'status'">
                <v-chip :color="item.email_verifiziert ? 'success' : 'grey'" size="small">
                  {{ item.email_verifiziert ? 'Verifiziert' : 'Offen' }}
                </v-chip>
              </template>
              <template v-else-if="col.key === 'actions'">
                <v-btn icon variant="text" color="warning" @click.stop="editKunde(item)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon variant="text" color="error" @click.stop="deleteKunde(item)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </template>
              <template v-else>
                {{ item[col.key] }}
              </template>
            </td>
          </tr>
        </template>
      </v-data-table>
    </v-card>

    <v-snackbar v-model="snackbar" :color="snackbarColor" location="bottom right" timeout="3000">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>

    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 bg-error text-white">
          <v-icon left color="white">mdi-alert</v-icon>
          Löschen bestätigen
        </v-card-title>
        <v-card-text class="pt-4">
          Möchten Sie den Kunden <strong class="text-error">{{ selectedItem?.kundennummer }}</strong> wirklich löschen?
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="deleteDialog=false">Abbrechen</v-btn>
          <v-btn color="error" @click="confirmDelete" :loading="deleting" class="text-none">Löschen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// ✅ Variable d'environnement (même système que profile.js / auth.js)
const API_BASE = import.meta.env.VITE_API_BASE || '/api'

const kunden = ref([])
const loading = ref(false)
const search = ref('')
const selectedType = ref(null)
const selectedGroup = ref(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const deleteDialog = ref(false)
const deleting = ref(false)
const selectedItem = ref(null)

const headers = [
  { title: 'Foto',         key: 'photo',          sortable: false },
  { title: 'Kunde',        key: 'name' },
  { title: 'Nummer',       key: 'kundennummer' },
  { title: 'Typ',          key: 'type' },
  { title: 'E-Mail',       key: 'email' },
  { title: 'Kundengruppe', key: 'kundengruppe' },
  { title: 'Status',       key: 'status' },
  { title: 'Aktionen',     key: 'actions',        sortable: false }
]

const displayName = (item) => {
  if (item.person_type === 'Gewerbekunde') return item.firma || 'Gewerbekunde'
  return `${item.vorname || ''} ${item.nachname || ''}`.trim() || '—'
}

const loadKunden = async () => {
  loading.value = true
  try {
    const response = await axios.get(`${API_BASE}/kunden_list.php`)
    if (!response.data.success) throw new Error(response.data.error || 'Unbekannter Fehler')
    kunden.value = response.data.kunden || []
  } catch (error) {
    console.error('[loadKunden]', error)
    snackbarText.value = error.response?.data?.error || 'Fehler beim Laden der Kunden'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}

const groups = computed(() => {
  return [...new Set(kunden.value.map(k => k.kundengruppe).filter(Boolean))]
})

const filteredKunden = computed(() => {
  return kunden.value.filter(k => {
    const text = `
      ${k.vorname || ''} ${k.nachname || ''} ${k.firma || ''}
      ${k.email || ''} ${k.kundennummer || ''}
    `.toLowerCase()
    const matchesSearch = !search.value || text.includes(search.value.toLowerCase())
    const matchesType = !selectedType.value || k.person_type === selectedType.value
    const matchesGroup = !selectedGroup.value || k.kundengruppe === selectedGroup.value
    return matchesSearch && matchesType && matchesGroup
  })
})

const verifiedCount = computed(() => kunden.value.filter(k => k.email_verifiziert).length)
const gewerbeCount = computed(() => kunden.value.filter(k => k.person_type === 'Gewerbekunde').length)

const openDetail = (item) => router.push(`/customers/${item.id}`)
const editKunde = (item) => router.push(`/customers/edit/${item.id}`)

const deleteKunde = (item) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const confirmDelete = async () => {
  deleting.value = true
  try {
    const response = await axios.delete(`${API_BASE}/kunden_delete.php?id=${selectedItem.value.id}`)
    if (response.data.success) {
      snackbarText.value = 'Kunde gelöscht'
      snackbarColor.value = 'success'
      snackbar.value = true
      deleteDialog.value = false
      await loadKunden()
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    snackbarText.value = error.response?.data?.error || 'Fehler beim Löschen'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    deleting.value = false
  }
}

onMounted(loadKunden)
</script>

<style scoped>
.v-card { transition: transform 0.25s ease, box-shadow 0.25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-card-title { font-weight: 600; letter-spacing: 0.3px; }
.v-btn { text-transform: none; font-weight: 600; }
.v-data-table { border-radius: 18px; }
.kunde-photo { width: 100%; height: 100%; object-fit: cover; display: block; }
.clickable-row { cursor: pointer; transition: background-color 0.2s ease; }
.clickable-row:hover { background-color: rgba(var(--v-theme-primary), 0.06); }
</style>