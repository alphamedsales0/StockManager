<template>
  <v-container fluid class="employees-page pa-3 pa-md-5">

    <!-- ================= HERO HEADER ================= -->
    <v-card class="hero-header mb-5">
      <div class="hero-header-bg"></div>
      <div class="hero-header-glow"></div>

      <div class="hero-header-content pa-5 pa-md-6">
        <div class="hero-header-left">
          <div class="hero-pill">
            <v-icon size="13">mdi-account-group-outline</v-icon>
            PERSONALVERWALTUNG
          </div>

          <h1 class="hero-title">Mitarbeiter</h1>

          <div class="hero-subtitle">
            Übersicht, Verwaltung und Pflege aller Mitarbeiterprofile
          </div>

          <div class="hero-stats">
            <div class="hero-stat">
              <v-icon size="16">mdi-account-multiple</v-icon>
              <strong>{{ employees.length }}</strong> gesamt
            </div>
            <v-divider vertical class="mx-2" />
            <div class="hero-stat hero-stat-success">
              <v-icon size="16">mdi-account-check</v-icon>
              <strong>{{ activeEmployees }}</strong> aktiv
            </div>
            <v-divider vertical class="mx-2" />
            <div class="hero-stat">
              <v-icon size="16">mdi-office-building</v-icon>
              <strong>{{ departmentsCount }}</strong> Abteilungen
            </div>
          </div>
        </div>

        <div class="hero-header-actions">
          <v-btn
            variant="tonal"
            prepend-icon="mdi-refresh"
            class="hero-action-btn"
            :loading="loading"
            :disabled="loading"
            @click="loadEmployees"
          >
            Aktualisieren
          </v-btn>

          <v-btn
            color="primary"
            variant="flat"
            size="large"
            prepend-icon="mdi-account-plus"
            class="hero-primary-btn"
            @click="router.push('/employees/add')"
          >
            Neuer Mitarbeiter
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- ================= KPI CARDS ================= -->
    <v-row class="mb-4 kpi-row">
      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-primary">
              <v-icon size="22">mdi-account-multiple</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Gesamt Mitarbeiter</div>
              <div class="kpi-value">{{ employees.length }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-primary"></div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-success">
              <v-icon size="22">mdi-account-check</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Aktiv</div>
              <div class="kpi-value">{{ activeEmployees }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-success"></div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-warning">
              <v-icon size="22">mdi-office-building</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Abteilungen</div>
              <div class="kpi-value">{{ departmentsCount }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-warning"></div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-info">
              <v-icon size="22">mdi-account-off-outline</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Inaktiv</div>
              <div class="kpi-value">{{ employees.length - activeEmployees }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-info"></div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ================= FILTER BAR ================= -->
    <v-card class="filter-card mb-5" elevation="0">
      <v-card-text class="pa-4">
        <div class="filter-grid">
          <v-text-field
            v-model="search"
            label="Mitarbeiter suchen"
            placeholder="Name, E-Mail oder Mitarbeiternummer"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            class="filter-search"
          />

          <v-select
            v-model="selectedDepartment"
            label="Abteilung"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-office-building"
            :items="departments"
            clearable
            hide-details
            class="filter-select"
          />

          <v-select
            v-model="selectedPosition"
            label="Position"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-account-tie"
            :items="positions"
            clearable
            hide-details
            class="filter-select"
          />

          <v-btn
            variant="tonal"
            color="grey-darken-2"
            prepend-icon="mdi-filter-remove-outline"
            class="filter-reset"
            @click="resetFilters"
          >
            Zurücksetzen
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- ================= ERROR ================= -->
    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      closable
      class="mb-4 modern-alert"
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

    <!-- ================= TABLE ================= -->
    <v-card class="table-card" elevation="0">
      <div class="table-header">
        <div>
          <div class="table-title">Mitarbeiterübersicht</div>
          <div class="table-subtitle">
            Alle Profile in einer Tabelle
          </div>
        </div>

        <v-chip variant="tonal" color="primary" class="table-chip">
          <v-icon start size="14">mdi-account-multiple-outline</v-icon>
          {{ filteredEmployees.length }} Mitarbeiter
        </v-chip>
      </div>

      <v-divider />

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
          <v-avatar size="44" class="employee-avatar">
            <v-img
              v-if="getPhoto(item)"
              :src="getPhoto(item)"
              :alt="`${item.vorname || ''} ${item.nachname || ''}`"
              cover
              @error="onImageError(item)"
            />
            <v-icon v-else size="20">mdi-account</v-icon>
          </v-avatar>
        </template>

        <!-- NAME AND EMAIL -->
        <template #item.name="{ item }">
          <div class="py-2">
            <div class="cell-name">
              {{ item.vorname || '' }} {{ item.nachname || '' }}
            </div>
            <div class="cell-sub">
              {{ item.email || 'Keine E-Mail hinterlegt' }}
            </div>
          </div>
        </template>

        <!-- EMPLOYEE NUMBER -->
        <template #item.mitarbeiter_nummer="{ item }">
          <span class="cell-mono">{{ item.mitarbeiter_nummer || '–' }}</span>
        </template>

        <!-- DEPARTMENT -->
        <template #item.abteilung="{ item }">
          <v-chip
            v-if="item.abteilung"
            size="small"
            variant="tonal"
            color="primary"
            class="cell-chip"
          >
            {{ item.abteilung }}
          </v-chip>
          <span v-else class="text-medium-emphasis">–</span>
        </template>

        <!-- POSITION -->
        <template #item.position="{ item }">
          <span class="cell-value">{{ item.position || '–' }}</span>
        </template>

        <!-- PHONE -->
        <template #item.telefon="{ item }">
          <span class="cell-value">{{ item.telefon || item.mobil || '–' }}</span>
        </template>

        <!-- STATUS -->
        <template #item.status="{ item }">
          <v-chip
            :color="isActive(item) ? 'success' : 'error'"
            size="small"
            variant="tonal"
            class="status-chip"
          >
            <v-icon start size="14">
              {{ isActive(item) ? 'mdi-check-circle' : 'mdi-close-circle' }}
            </v-icon>
            {{ isActive(item) ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
        </template>

        <!-- ACTIONS -->
        <template #item.actions="{ item }">
          <div class="actions-cell">
            <v-tooltip text="Details anzeigen">
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

            <v-tooltip text="Bearbeiten">
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

            <!-- ✅ Supprimer : visible pour TOUT LE MONDE.
                 Admin/Office → dialogue de confirmation
                 Autres → modal "Zugriff verweigert" directement -->
            <v-tooltip text="Mitarbeiter löschen">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-delete-outline"
                  variant="text"
                  size="small"
                  color="error"
                  aria-label="Mitarbeiter löschen"
                  @click.stop="confirmDelete(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- EMPTY STATE -->
        <template #no-data>
          <div class="empty-state">
            <div class="empty-icon">
              <v-icon size="36">mdi-account-search-outline</v-icon>
            </div>
            <div class="empty-title">Keine Mitarbeiter gefunden</div>
            <div class="empty-sub">
              Passe deine Suchbegriffe oder Filter an.
            </div>
            <v-btn
              class="mt-4"
              variant="tonal"
              color="primary"
              prepend-icon="mdi-filter-remove-outline"
              @click="resetFilters"
            >
              Filter zurücksetzen
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- ================= DELETE DIALOG ================= -->
    <v-dialog v-model="deleteDialog" max-width="480" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="delete-dialog-header">
          <v-avatar color="error" size="42" class="mr-3">
            <v-icon>mdi-alert-circle-outline</v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">Mitarbeiter löschen?</div>
            <div class="text-caption text-medium-emphasis">
              Diese Aktion kann nicht rückgängig gemacht werden.
            </div>
          </div>
        </v-card-title>

        <v-card-text class="pa-5 pt-2">
          <div v-if="employeeToDelete">
            <p class="mb-2">
              Du bist dabei, folgenden Mitarbeiter <strong>endgültig zu löschen</strong>:
            </p>
            <v-alert type="warning" variant="tonal" density="compact" class="mb-2">
              <div class="font-weight-bold">
                {{ employeeToDelete.vorname }} {{ employeeToDelete.nachname }}
              </div>
              <div class="text-caption">
                {{ employeeToDelete.mitarbeiter_nummer || '–' }}
                · {{ employeeToDelete.email || 'Keine E-Mail' }}
              </div>
            </v-alert>

            <p class="text-body-2 text-error">
              Alle zugehörigen Daten (Adressen, Bankkonten, Qualifikationen,
              Dokumente, Versicherung) sowie das Benutzerkonto und die
              hochgeladenen Dateien werden gelöscht.
            </p>
          </div>
        </v-card-text>

        <v-card-actions class="pa-5 pt-0">
          <v-spacer />
          <v-btn variant="text" :disabled="deleting" @click="deleteDialog = false">
            Abbrechen
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            prepend-icon="mdi-delete"
            :loading="deleting"
            @click="deleteEmployee"
          >
            Endgültig löschen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= SNACKBAR ================= -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="bottom right"
      :timeout="3500"
      rounded="lg"
    >
      {{ snackbarText }}

      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePermissionStore } from '@/stores/permission'

const router = useRouter()
const permissionStore = usePermissionStore()

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

// Delete state
const deleteDialog = ref(false)
const employeeToDelete = ref(null)
const deleting = ref(false)

// =====================================================
// PERMISSIONS
// =====================================================

// ✅ Rôles autorisés à supprimer un employé :
//    Admin ou Office — identique à $ALLOWED_ROLES dans employees_delete.php
const canDelete = computed(() =>
  permissionStore.isAdmin || permissionStore.isOffice
)

// =====================================================
// API CONFIGURATION
// =====================================================

const API_BASE = (
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_BASE ||
  '/api'
).replace(/\/+$/, '')

const EMPLOYEES_API = `${API_BASE}/employees_list.php`
const EMPLOYEE_DELETE_API = `${API_BASE}/employees_delete.php`

// =====================================================
// TABLE HEADERS
// =====================================================

const headers = [
  { title: 'Foto', key: 'photo', sortable: false, width: '76px' },
  { title: 'Mitarbeiter', key: 'name', sortable: false, minWidth: '220px' },
  { title: 'Nummer', key: 'mitarbeiter_nummer', minWidth: '130px' },
  { title: 'Abteilung', key: 'abteilung', minWidth: '140px' },
  { title: 'Position', key: 'position', minWidth: '140px' },
  { title: 'Telefon', key: 'telefon', minWidth: '130px' },
  { title: 'Status', key: 'status', sortable: false, width: '110px' },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'center', width: '140px' }
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
      headers: { Accept: 'application/json' },
      credentials: 'include',
      cache: 'no-store'
    })

    const contentType = response.headers.get('content-type') || ''
    const raw = await response.text()

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

    const matchesSearch = !query || searchableText.includes(query)

    const matchesDepartment =
      !selectedDepartment.value ||
      employee.abteilung === selectedDepartment.value

    const matchesPosition =
      !selectedPosition.value ||
      employee.position === selectedPosition.value

    return matchesSearch && matchesDepartment && matchesPosition
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
    notify('Für diesen Mitarbeiter wurde keine UID gefunden.', 'warning')
    return
  }

  const target = `/employees/edit/${encodeURIComponent(uid)}`

  router.push(target).catch((err) => {
    console.error('[EditEmployee] Erreur de navigation:', err)
    notify('Die Bearbeitungsseite konnte nicht geöffnet werden.', 'error')
  })
}

const onRowClick = (event, { item }) => {
  openDetail(item)
}

// =====================================================
// DELETE EMPLOYEE — Admin ou Office
// =====================================================

// ✅ Si pas Admin/Office → directement le modal "Zugriff verweigert"
//    Sinon → ouvre le dialogue de confirmation
const confirmDelete = (employee) => {
  if (!canDelete.value) {
    permissionStore.showAccessDenied()
    return
  }

  employeeToDelete.value = employee
  deleteDialog.value = true
}

// ✅ Garde finale (ceinture + bretelles) avant l'appel API
const deleteEmployee = async () => {
  if (!canDelete.value) {
    permissionStore.showAccessDenied()
    deleteDialog.value = false
    employeeToDelete.value = null
    return
  }

  const emp = employeeToDelete.value
  if (!emp) return

  const uid = emp.employee_uid || emp.uid
  const id = emp.benutzer_id || emp.user_id || emp.id

  if (!uid && !id) {
    notify('Keine UID/ID gefunden – Löschen nicht möglich.', 'error')
    return
  }

  deleting.value = true

  try {
    const params = new URLSearchParams()
    if (uid) params.append('uid', uid)
    else params.append('id', String(id))

    const url = `${EMPLOYEE_DELETE_API}?${params.toString()}`

    const response = await fetch(url, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        ...(localStorage.getItem('token')
          ? { Authorization: `Bearer ${localStorage.getItem('token')}` }
          : {})
      },
      credentials: 'include',
      cache: 'no-store'
    })

    const raw = await response.text()
    let data = null
    try {
      data = JSON.parse(raw)
    } catch {
      /* ignore */
    }

    // 401 / 403 → modal d'accès refusé
    if (response.status === 401 || response.status === 403) {
      permissionStore.showAccessDenied()
      deleteDialog.value = false
      employeeToDelete.value = null
      return
    }

    if (!response.ok || !data || data.success !== true) {
      throw new Error(data?.error || `HTTP ${response.status}`)
    }

    employees.value = employees.value.filter(e => {
      const eUid = e.employee_uid || e.uid
      const eId = e.benutzer_id || e.user_id || e.id
      return !(uid ? eUid === uid : eId === id)
    })

    notify('Mitarbeiter wurde gelöscht.', 'success')
    deleteDialog.value = false
    employeeToDelete.value = null
  } catch (err) {
    console.error('[DeleteEmployee]', err)
    notify(`Löschen fehlgeschlagen: ${err.message}`, 'error')
  } finally {
    deleting.value = false
  }
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
/* ============================================================
   VARIABLES
============================================================ */
.employees-page {
  --text-main: #0f172a;
  --text-soft: #334155;
  --text-muted: #64748b;
  --text-faint: #94a3b8;
  --border: #e5e9f0;
  --border-strong: #d5dbe4;
  --surface: #ffffff;
  --background: #f6f8fb;
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
  --shadow-md: 0 4px 14px rgba(15, 23, 42, 0.06);
  max-width: 1600px;
  margin: 0 auto;
  background: var(--background);
  min-height: 100vh;
}

/* ============================================================
   HERO
============================================================ */
.hero-header {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-lg) !important;
  background: linear-gradient(135deg, #ffffff 0%, #fbfcfe 55%, #f4f7fb 100%);
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-md) !important;
}
.hero-header-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background-image:
    radial-gradient(circle at 88% 18%, rgba(15, 23, 42, 0.05) 0, transparent 38%),
    radial-gradient(circle at 8% 100%, rgba(15, 23, 42, 0.03) 0, transparent 34%);
}
.hero-header-glow {
  position: absolute;
  top: -60%;
  right: -20%;
  width: 60%;
  height: 220%;
  z-index: 0;
  pointer-events: none;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 60%);
  filter: blur(40px);
}
.hero-header-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.hero-header-left { min-width: 0; }
.hero-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.hero-title {
  margin: 10px 0 0;
  font-size: clamp(1.5rem, 2.6vw, 1.9rem);
  font-weight: 750;
  letter-spacing: -0.03em;
  color: var(--text-main);
  line-height: 1.15;
}
.hero-subtitle {
  margin-top: 6px;
  font-size: 0.88rem;
  color: var(--text-muted);
}
.hero-stats {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
  color: var(--text-soft);
  font-size: 0.82rem;
}
.hero-stat {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: #ffffff;
  border: 1px solid var(--border);
  font-weight: 500;
}
.hero-stat strong { color: var(--text-main); font-weight: 750; }
.hero-stat-success { border-color: #bbf7d0; background: #f0fdf4; }
.hero-stat-success strong { color: #166534; }
.hero-stat-success .v-icon { color: #16a34a !important; }
.hero-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.hero-action-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
  height: 44px !important;
}
.hero-primary-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
  height: 44px !important;
  background: #0f172a !important;
  color: #ffffff !important;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.hero-primary-btn:hover {
  transform: translateY(-1px);
  background: #020617 !important;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.3) !important;
}

/* ============================================================
   KPI
============================================================ */
.kpi-row { margin-left: 0 !important; margin-right: 0 !important; }
.kpi-card {
  position: relative;
  border-radius: var(--radius-lg) !important;
  background: var(--surface) !important;
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-sm) !important;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md) !important;
  border-color: var(--border-strong) !important;
}
.kpi-content {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 16px 22px !important;
}
.kpi-icon {
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  color: #ffffff !important;
}
.kpi-icon-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.kpi-icon-success { background: linear-gradient(135deg, #22c55e, #16a34a); }
.kpi-icon-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.kpi-icon-info    { background: linear-gradient(135deg, #94a3b8, #64748b); }
.kpi-text { min-width: 0; }
.kpi-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.kpi-value {
  margin-top: 2px;
  font-size: 1.6rem;
  font-weight: 750;
  color: var(--text-main);
  letter-spacing: -0.02em;
  line-height: 1;
}
.kpi-bar {
  position: absolute;
  left: 0; right: 0; bottom: 0;
  height: 3px;
}
.kpi-bar-primary { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.kpi-bar-success { background: linear-gradient(90deg, #22c55e, #16a34a); }
.kpi-bar-warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.kpi-bar-info    { background: linear-gradient(90deg, #94a3b8, #64748b); }

/* ============================================================
   FILTER
============================================================ */
.filter-card {
  border-radius: var(--radius-lg) !important;
  background: var(--surface) !important;
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-sm) !important;
}
.filter-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto;
  gap: 12px;
  align-items: center;
}
.filter-search, .filter-select { min-width: 0; }
.filter-reset {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
  height: 44px !important;
  white-space: nowrap;
}

/* ============================================================
   TABLE
============================================================ */
.table-card {
  border-radius: var(--radius-lg) !important;
  background: var(--surface) !important;
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-sm) !important;
  overflow: hidden;
}
.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 20px 24px;
}
.table-title {
  font-size: 1.05rem;
  font-weight: 750;
  color: var(--text-main);
  letter-spacing: -0.01em;
}
.table-subtitle {
  margin-top: 2px;
  font-size: 0.82rem;
  color: var(--text-muted);
}
.table-chip { font-weight: 700; letter-spacing: 0.02em; }

.employee-table { background: transparent !important; }
.employee-table :deep(tbody tr) {
  cursor: pointer;
  transition: background-color 0.18s ease;
}
.employee-table :deep(tbody tr:hover) { background-color: #f8fafc !important; }
.employee-table :deep(th) {
  white-space: nowrap;
  background: #f8fafc !important;
  color: var(--text-muted) !important;
  font-size: 0.72rem !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid var(--border) !important;
}
.employee-table :deep(td) {
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9 !important;
}
.employee-avatar {
  background: #f1f5f9 !important;
  color: #64748b !important;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 1px var(--border);
}
.cell-name { font-weight: 700; color: var(--text-main); font-size: 0.9rem; }
.cell-sub { margin-top: 2px; font-size: 0.76rem; color: var(--text-muted); }
.cell-mono {
  font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
  font-size: 0.82rem;
  color: var(--text-soft);
  letter-spacing: 0.02em;
}
.cell-value { color: var(--text-soft); font-size: 0.86rem; font-weight: 500; }
.cell-chip { font-weight: 700; }
.status-chip { font-weight: 700; letter-spacing: 0.02em; }
.actions-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

/* ============================================================
   EMPTY
============================================================ */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}
.empty-icon {
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #f1f5f9;
  color: #94a3b8 !important;
  margin-bottom: 14px;
}
.empty-title { font-size: 1.05rem; font-weight: 700; color: var(--text-main); }
.empty-sub { margin-top: 4px; font-size: 0.85rem; color: var(--text-muted); }

/* ============================================================
   ALERT / DIALOG
============================================================ */
.modern-alert {
  border-radius: var(--radius-md) !important;
  border: 1px solid #fecaca !important;
}
.delete-dialog-header {
  display: flex;
  align-items: center;
  padding: 20px 20px 8px !important;
}

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 1100px) {
  .filter-grid { grid-template-columns: 1fr 1fr; }
  .filter-reset { grid-column: span 2; }
}
@media (max-width: 960px) {
  .hero-header-content { flex-direction: column; align-items: stretch; }
  .hero-header-actions { width: 100%; }
  .hero-header-actions > * { flex: 1; }
}
@media (max-width: 600px) {
  .employees-page { padding: 8px !important; }
  .hero-header-content { padding: 20px 16px !important; }
  .hero-stats { gap: 6px; font-size: 0.76rem; }
  .hero-stat { padding: 3px 8px; }
  .kpi-content { padding: 14px 12px 18px !important; gap: 12px; }
  .kpi-value { font-size: 1.4rem; }
  .kpi-label { font-size: 0.66rem; }
  .filter-grid { grid-template-columns: 1fr; }
  .filter-reset { grid-column: auto; width: 100%; }
  .table-header { padding: 16px; flex-direction: column; align-items: flex-start; }
  .employee-table :deep(th),
  .employee-table :deep(td) {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}
</style>