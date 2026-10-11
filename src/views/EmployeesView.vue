<template>
  <v-container fluid class="pa-4 pa-md-6">

    <!-- HEADER -->
    <v-card class="mb-6 rounded-xl" elevation="3">
      <v-card-title
        class="d-flex align-center justify-space-between flex-wrap ga-4 pa-5 pa-md-6"
      >
        <div class="d-flex align-center">
          <v-avatar color="primary" size="52" class="mr-4">
            <v-icon size="30">mdi-account-group</v-icon>
          </v-avatar>

          <div>
            <h1 class="text-h5 font-weight-bold">Mitarbeiter</h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Personalverwaltung
            </p>
          </div>
        </div>

        <v-btn
          color="primary"
          variant="flat"
          size="large"
          prepend-icon="mdi-account-plus"
          @click="router.push('/employees/add')"
        >
          Neuer Mitarbeiter
        </v-btn>
      </v-card-title>
    </v-card>

    <!-- KPI CARDS -->
    <v-row class="mb-4">
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl h-100" elevation="2">
          <v-card-text class="d-flex align-center pa-5">
            <v-avatar color="primary" size="48" class="mr-4">
              <v-icon>mdi-account-multiple</v-icon>
            </v-avatar>

            <div>
              <div class="text-caption text-medium-emphasis">
                Gesamt Mitarbeiter
              </div>
              <div class="text-h5 font-weight-bold">
                {{ employees.length }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="rounded-xl h-100" elevation="2">
          <v-card-text class="d-flex align-center pa-5">
            <v-avatar color="success" size="48" class="mr-4">
              <v-icon>mdi-account-check</v-icon>
            </v-avatar>

            <div>
              <div class="text-caption text-medium-emphasis">Aktiv</div>
              <div class="text-h5 font-weight-bold">
                {{ activeEmployees }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="rounded-xl h-100" elevation="2">
          <v-card-text class="d-flex align-center pa-5">
            <v-avatar color="warning" size="48" class="mr-4">
              <v-icon>mdi-office-building</v-icon>
            </v-avatar>

            <div>
              <div class="text-caption text-medium-emphasis">
                Abteilungen
              </div>
              <div class="text-h5 font-weight-bold">
                {{ departmentsCount }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- FILTER BAR -->
    <v-card class="mb-5 rounded-xl" elevation="2">
      <v-card-text class="pa-4">
        <v-row align="center">
          <v-col cols="12" md="5">
            <v-text-field
              v-model="search"
              label="Mitarbeiter suchen"
              placeholder="Name, E-Mail oder Mitarbeiternummer"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-magnify"
              clearable
              hide-details
            />
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="selectedDepartment"
              label="Abteilung"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-office-building"
              :items="departments"
              clearable
              hide-details
            />
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="selectedPosition"
              label="Position"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-account-tie"
              :items="positions"
              clearable
              hide-details
            />
          </v-col>

          <v-col cols="12" md="1" class="d-flex justify-center">
            <v-btn
              icon="mdi-refresh"
              variant="text"
              :loading="loading"
              :disabled="loading"
              aria-label="Mitarbeiter aktualisieren"
              @click="loadEmployees"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- ERROR MESSAGE -->
    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="error = null"
    >
      <div class="font-weight-bold mb-1">
        Mitarbeiter konnten nicht geladen werden
      </div>

      <div>{{ error }}</div>

      <template #append>
        <v-btn
          variant="text"
          color="error"
          :loading="loading"
          @click="loadEmployees"
        >
          Erneut versuchen
        </v-btn>
      </template>
    </v-alert>

    <!-- EMPLOYEE TABLE -->
    <v-card class="rounded-xl" elevation="3">
      <v-card-title
        class="d-flex align-center justify-space-between flex-wrap ga-2 pa-5"
      >
        <div class="text-h6 font-weight-bold">
          Mitarbeiterübersicht
        </div>

        <v-chip variant="tonal" color="primary">
          {{ filteredEmployees.length }} Mitarbeiter
        </v-chip>
      </v-card-title>

      <v-divider />

      <!-- ✅ AJOUT : @click:row ouvre les détails -->
      <v-data-table
        :headers="headers"
        :items="filteredEmployees"
        :loading="loading"
        :items-per-page="10"
        item-value="employee_uid"
        hover
        class="employee-table"
        loading-text="Mitarbeiter werden geladen..."
        no-data-text="Keine Mitarbeiter vorhanden"
        items-per-page-text="Einträge pro Seite"
        @click:row="onRowClick"
      >
        <!-- PHOTO -->
        <template #item.photo="{ item }">
          <v-avatar size="42" color="grey-lighten-3">
            <v-img
              v-if="getPhoto(item)"
              :src="getPhoto(item)"
              :alt="`${item.vorname || ''} ${item.nachname || ''}`"
              cover
              @error="onImageError(item)"
            />

            <v-icon v-else>mdi-account</v-icon>
          </v-avatar>
        </template>

        <!-- NAME AND EMAIL -->
        <template #item.name="{ item }">
          <div class="py-2">
            <div class="font-weight-bold">
              {{ item.vorname || '' }} {{ item.nachname || '' }}
            </div>

            <div class="text-caption text-medium-emphasis">
              {{ item.email || 'Keine E-Mail hinterlegt' }}
            </div>
          </div>
        </template>

        <!-- EMPLOYEE NUMBER -->
        <template #item.mitarbeiter_nummer="{ item }">
          <span>
            {{ item.mitarbeiter_nummer || '–' }}
          </span>
        </template>

        <!-- DEPARTMENT -->
        <template #item.abteilung="{ item }">
          <v-chip
            v-if="item.abteilung"
            size="small"
            variant="tonal"
            color="primary"
          >
            {{ item.abteilung }}
          </v-chip>
          <span v-else class="text-medium-emphasis">–</span>
        </template>

        <!-- POSITION -->
        <template #item.position="{ item }">
          {{ item.position || '–' }}
        </template>

        <!-- PHONE -->
        <template #item.telefon="{ item }">
          {{ item.telefon || item.mobil || '–' }}
        </template>

        <!-- STATUS -->
        <template #item.status="{ item }">
          <v-chip
            :color="isActive(item) ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            <v-icon
              start
              size="small"
            >
              {{ isActive(item) ? 'mdi-check-circle' : 'mdi-close-circle' }}
            </v-icon>

            {{ isActive(item) ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
        </template>

        <!-- ACTIONS -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center">
            <v-tooltip text="Mitarbeiterdetails öffnen">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-eye-outline"
                  variant="text"
                  size="small"
                  color="primary"
                  aria-label="Details anzeigen"
                  @click.stop="openDetail(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Mitarbeiter bearbeiten">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-pencil-outline"
                  variant="text"
                  size="small"
                  color="warning"
                  aria-label="Mitarbeiter bearbeiten"
                  @click.stop="editEmployee(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- EMPTY STATE -->
        <template #no-data>
          <div class="text-center pa-8">
            <v-icon
              size="52"
              color="grey"
              class="mb-3"
            >
              mdi-account-search-outline
            </v-icon>

            <div class="text-h6 mb-1">
              Keine Mitarbeiter gefunden
            </div>

            <div class="text-body-2 text-medium-emphasis">
              Passe deine Suchbegriffe oder Filter an.
            </div>

            <v-btn
              class="mt-4"
              variant="tonal"
              color="primary"
              @click="resetFilters"
            >
              Filter zurücksetzen
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="bottom right"
      :timeout="3500"
    >
      {{ snackbarText }}

      <template #actions>
        <v-btn variant="text" @click="snackbar = false">
          OK
        </v-btn>
      </template>
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// =====================================================
// STATE
// =====================================================

const employees = ref([])
const loading = ref(false)
const error = ref(null)

const search = ref('')
const selectedDepartment = ref(null)
const selectedPosition = ref(null)

const failedImages = ref(new Set())

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// =====================================================
// API CONFIGURATION
// =====================================================

const API_BASE = (
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_BASE ||
  '/api'
).replace(/\/+$/, '')

const EMPLOYEES_API = `${API_BASE}/employees_list.php`

// =====================================================
// TABLE HEADERS
// =====================================================

const headers = [
  {
    title: 'Foto',
    key: 'photo',
    sortable: false,
    width: '76px'
  },
  {
    title: 'Mitarbeiter',
    key: 'name',
    sortable: false,
    minWidth: '220px'
  },
  {
    title: 'Nummer',
    key: 'mitarbeiter_nummer',
    minWidth: '120px'
  },
  {
    title: 'Abteilung',
    key: 'abteilung',
    minWidth: '130px'
  },
  {
    title: 'Position',
    key: 'position',
    minWidth: '140px'
  },
  {
    title: 'Telefon',
    key: 'telefon',
    minWidth: '130px'
  },
  {
    title: 'Status',
    key: 'status',
    sortable: false,
    width: '110px'
  },
  {
    title: 'Aktionen',
    key: 'actions',
    sortable: false,
    align: 'center',
    width: '110px'
  }
]

// =====================================================
// NOTIFICATION
// =====================================================

const notify = (message, color = 'success') => {
  snackbarText.value = message
  snackbarColor.value = color
  snackbar.value = true
}

// =====================================================
// LOAD EMPLOYEES
// =====================================================

const loadEmployees = async () => {
  if (loading.value) return

  loading.value = true
  error.value = null

  try {
    const separator = EMPLOYEES_API.includes('?') ? '&' : '?'
    const url = `${EMPLOYEES_API}${separator}diagnostic=${Date.now()}`

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      },
      credentials: 'omit',
      cache: 'no-store'
    })

    const contentType = response.headers.get('content-type') || ''
    const raw = await response.text()

    console.log('[Employees] URL:', url)
    console.log('[Employees] Status:', response.status)
    console.log('[Employees] Content-Type:', contentType)
    console.log('[Employees] Antwort:', raw.slice(0, 500))

    if (!response.ok) {
      throw new Error(`HTTP-Fehler ${response.status}`)
    }

    if (!contentType.includes('application/json')) {
      throw new Error(
        'Der Server liefert HTML statt JSON. Bitte die Proxy-Konfiguration prüfen.'
      )
    }

    let data

    try {
      data = JSON.parse(raw)
    } catch {
      throw new Error('Die API-Antwort ist kein gültiges JSON.')
    }

    if (data.success !== true || !Array.isArray(data.employees)) {
      throw new Error(data.error || 'Ungültige Mitarbeiterliste.')
    }

    employees.value = data.employees
    failedImages.value = new Set()
  } catch (err) {
    console.error('[Employees] Fehler:', err)
    error.value = err.message || 'Mitarbeiter konnten nicht geladen werden.'
  } finally {
    loading.value = false
  }
}

// =====================================================
// EMPLOYEE STATUS
// =====================================================

const isActive = (employee) => {
  const value = employee?.is_active
  return value === true || value === 1 || value === '1'
}

// =====================================================
// EMPLOYEE PHOTO
// =====================================================

const getPhoto = (employee) => {
  const uid = employee.employee_uid || employee.uid

  if (uid && failedImages.value.has(uid)) {
    return null
  }

  const photo = employee.photo || employee.foto_pfad

  if (!photo || typeof photo !== 'string') {
    return null
  }

  if (/^https?:\/\//i.test(photo)) {
    return photo
  }

  if (photo.startsWith('//')) {
    return `${window.location.protocol}${photo}`
  }

  if (photo.startsWith('/')) {
    return photo
  }

  return `/${photo.replace(/^\.\/+/, '')}`
}

const onImageError = (employee) => {
  const uid = employee.employee_uid || employee.uid
  if (!uid) return

  const updated = new Set(failedImages.value)
  updated.add(uid)
  failedImages.value = updated

  console.warn('[Employee photo] Bild konnte nicht geladen werden:', {
    uid,
    photo: employee.photo || employee.foto_pfad
  })
}

// =====================================================
// FILTER OPTIONS
// =====================================================

const departments = computed(() => {
  return [
    ...new Set(
      employees.value
        .map(employee => employee.abteilung)
        .filter(value => typeof value === 'string' && value.trim())
    )
  ].sort((a, b) => a.localeCompare(b, 'de'))
})

const positions = computed(() => {
  return [
    ...new Set(
      employees.value
        .map(employee => employee.position)
        .filter(value => typeof value === 'string' && value.trim())
    )
  ].sort((a, b) => a.localeCompare(b, 'de'))
})

// =====================================================
// SEARCH AND FILTER
// =====================================================

const filteredEmployees = computed(() => {
  const query = String(search.value || '').trim().toLocaleLowerCase('de')

  return employees.value.filter(employee => {
    const searchableText = [
      employee.vorname,
      employee.nachname,
      employee.email,
      employee.username,
      employee.mitarbeiter_nummer,
      employee.telefon,
      employee.mobil,
      employee.abteilung,
      employee.position
    ]
      .filter(Boolean)
      .join(' ')
      .toLocaleLowerCase('de')

    const matchesSearch =
      !query || searchableText.includes(query)

    const matchesDepartment =
      !selectedDepartment.value ||
      employee.abteilung === selectedDepartment.value

    const matchesPosition =
      !selectedPosition.value ||
      employee.position === selectedPosition.value

    return (
      matchesSearch &&
      matchesDepartment &&
      matchesPosition
    )
  })
})

// =====================================================
// KPI VALUES
// =====================================================

const activeEmployees = computed(() => {
  return employees.value.filter(isActive).length
})

const departmentsCount = computed(() => {
  return departments.value.length
})

// =====================================================
// NAVIGATION
// =====================================================

const getEmployeeUid = (employee) => {
  return employee.employee_uid || employee.uid || null
}

const openDetail = (employee) => {
  const uid = getEmployeeUid(employee)

  if (!uid) {
    notify('Für diesen Mitarbeiter wurde keine UID gefunden.', 'warning')
    return
  }

  router.push(`/employees/${encodeURIComponent(uid)}`)
}

const editEmployee = (employee) => {
  const uid = employee.employee_uid || employee.uid

  if (!uid) {
    console.error('[EditEmployee] UID introuvable :', employee)
    notify('Für diesen Mitarbeiter wurde keine UID gefunden.', 'warning')
    return
  }

  const target = `/employees/edit/${encodeURIComponent(uid)}`
  console.log('[EditEmployee] Navigation vers :', target)

  router.push(target).catch((err) => {
    console.error('[EditEmployee] Erreur de navigation:', err)
    notify('Die Bearbeitungsseite konnte nicht geöffnet werden.', 'error')
  })
}

// ✅ NOUVEAU : clic sur la ligne → ouvre les détails
// Signature Vuetify 3 : (event, { item })
const onRowClick = (event, { item }) => {
  openDetail(item)
}

// =====================================================
// RESET FILTERS
// =====================================================

const resetFilters = () => {
  search.value = ''
  selectedDepartment.value = null
  selectedPosition.value = null
}

// =====================================================
// INITIALIZATION
// =====================================================

onMounted(() => {
  loadEmployees()
})
</script>

<style scoped>
.v-card {
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.v-card-title {
  letter-spacing: 0.2px;
}

.v-btn {
  text-transform: none;
  font-weight: 600;
}

.employee-table {
  border-radius: 0 0 18px 18px;
}

/* ✅ Curseur main sur les lignes cliquables */
.employee-table :deep(tbody tr) {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.employee-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.employee-table :deep(th) {
  white-space: nowrap;
}

.employee-table :deep(td) {
  vertical-align: middle;
}

@media (max-width: 600px) {
  .employee-table :deep(th),
  .employee-table :deep(td) {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}
</style>