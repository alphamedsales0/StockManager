<template>
  <v-container fluid class="all-customers-page pa-3 pa-md-5">

    <!-- ================= HEADER ================= -->
    <v-card class="header-card mb-4" elevation="0">
      <div class="header-content pa-5">
        <div class="header-left">
          <div class="header-icon">
            <v-icon size="24" color="white">mdi-account-multiple</v-icon>
          </div>
          <div>
            <div class="header-pill">
              <v-icon size="12">mdi-account-group-outline</v-icon>
              KUNDEN
            </div>
            <h1 class="header-title">Alle Kunden</h1>
            <div class="header-subtitle">
              {{ filteredKunden.length }} von {{ kunden.length }} Kunden
            </div>
          </div>
        </div>

        <div class="header-actions">
          <v-btn
            variant="text"
            prepend-icon="mdi-arrow-left"
            @click="goBack"
          >
            Zurück
          </v-btn>

          <v-btn
            variant="flat"
            color="white"
            prepend-icon="mdi-refresh"
            class="refresh-btn"
            :loading="loading"
            :disabled="loading"
            @click="loadKunden"
          >
            Aktualisieren
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- ================= SEARCH + FILTERS ================= -->
    <v-card class="filter-card mb-4" elevation="0">
      <v-card-text class="pa-4">
        <div class="filter-grid">
          <v-text-field
            v-model="search"
            label="Kunden suchen"
            placeholder="Name, Firma, E-Mail oder Kundennummer"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            class="filter-search"
          />

          <v-select
            v-model="selectedType"
            label="Kundentyp"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-account-switch"
            :items="['Privatkunde', 'Gewerbekunde']"
            clearable
            hide-details
            class="filter-select"
          />

          <v-select
            v-model="selectedGroup"
            label="Kundengruppe"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-tag-multiple"
            :items="groups"
            clearable
            hide-details
            class="filter-select"
          />

          <v-btn
            variant="tonal"
            class="filter-reset"
            prepend-icon="mdi-filter-remove-outline"
            @click="resetFilters"
          >
            Zurücksetzen
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- ================= TABLE ================= -->
    <v-card class="table-card" elevation="0">
      <v-data-table
        :headers="headers"
        :items="filteredKunden"
        :loading="loading"
        :items-per-page="-1"
        :hide-default-footer="true"
        hover
        class="customer-table"
        loading-text="Kunden werden geladen..."
        no-data-text="Keine Kunden vorhanden"
      >
        <!-- PHOTO -->
        <template #item.photo="{ item }">
          <v-avatar size="40" class="customer-avatar">
            <img
              v-if="item.profilbild"
              :src="item.profilbild"
              :alt="displayName(item)"
              class="customer-photo"
            />
            <span v-else class="avatar-initials">{{ initials(item) }}</span>
          </v-avatar>
        </template>

        <!-- NAME -->
        <template #item.name="{ item }">
          <div class="py-1">
            <div class="cell-name">{{ displayName(item) }}</div>
            <div class="cell-sub">{{ item.email || 'Keine E-Mail' }}</div>
          </div>
        </template>

        <!-- NUMBER -->
        <template #item.kundennummer="{ item }">
          <span class="cell-mono">{{ item.kundennummer || '–' }}</span>
        </template>

        <!-- TYPE -->
        <template #item.type="{ item }">
          <span
            class="type-badge"
            :class="item.person_type === 'Gewerbekunde' ? 'type-business' : 'type-private'"
          >
            <v-icon size="11">
              {{ item.person_type === 'Gewerbekunde' ? 'mdi-domain' : 'mdi-account' }}
            </v-icon>
            {{ item.person_type }}
          </span>
        </template>

        <!-- EMAIL -->
        <template #item.email="{ item }">
          <span class="cell-value">{{ item.email || '–' }}</span>
        </template>

        <!-- GROUP -->
        <template #item.kundengruppe="{ item }">
          <span v-if="item.kundengruppe" class="group-badge">
            <v-icon size="11">mdi-tag</v-icon>
            {{ item.kundengruppe }}
          </span>
          <span v-else class="text-medium-emphasis">–</span>
        </template>

        <!-- STATUS -->
        <template #item.status="{ item }">
          <span
            class="status-badge"
            :class="item.email_verifiziert ? 'status-success' : 'status-pending'"
          >
            <v-icon size="11">
              {{ item.email_verifiziert ? 'mdi-check-circle' : 'mdi-clock-outline' }}
            </v-icon>
            {{ item.email_verifiziert ? 'Verifiziert' : 'Offen' }}
          </span>
        </template>

        <!-- ACTIONS -->
        <template #item.actions="{ item }">
          <div class="actions-cell">
            <v-tooltip text="Ansehen">
              <template #activator="{ props }">
                <button
                  v-bind="props"
                  class="action-btn action-view"
                  @click.stop="openDetail(item)"
                >
                  <v-icon size="16">mdi-eye-outline</v-icon>
                </button>
              </template>
            </v-tooltip>

            <v-tooltip
              v-if="permissionStore.can('customers-create')"
              text="Bearbeiten"
            >
              <template #activator="{ props }">
                <button
                  v-bind="props"
                  class="action-btn action-edit"
                  @click.stop="editKunde(item)"
                >
                  <v-icon size="16">mdi-pencil-outline</v-icon>
                </button>
              </template>
            </v-tooltip>

            <v-tooltip text="Löschen">
              <template #activator="{ props }">
                <button
                  v-bind="props"
                  class="action-btn action-delete"
                  @click.stop="deleteKunde(item)"
                >
                  <v-icon size="16">mdi-delete-outline</v-icon>
                </button>
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- EMPTY STATE -->
        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="52" color="grey-lighten-1" class="mb-3">
              mdi-account-search-outline
            </v-icon>
            <div class="text-h6 mb-1">Keine Kunden gefunden</div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              Passe deine Suchbegriffe oder Filter an.
            </div>
            <v-btn
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
        <v-card-title class="d-flex align-center pa-5">
          <v-avatar color="error" size="42" class="mr-3">
            <v-icon>mdi-alert-circle-outline</v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">Kunden löschen?</div>
            <div class="text-caption text-medium-emphasis">
              Diese Aktion kann nicht rückgängig gemacht werden.
            </div>
          </div>
        </v-card-title>

        <v-card-text class="pa-5 pt-2">
          <div v-if="selectedItem">
            <p class="mb-2">
              Du bist dabei, folgenden Kunden <strong>endgültig zu löschen</strong>:
            </p>
            <v-alert type="warning" variant="tonal" density="compact">
              <div class="font-weight-bold">{{ displayName(selectedItem) }}</div>
              <div class="text-caption">
                {{ selectedItem.kundennummer || '–' }}
                · {{ selectedItem.email || 'Keine E-Mail' }}
              </div>
            </v-alert>
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
            @click="confirmDelete"
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
import axios from 'axios'
import { usePermissionStore } from '@/stores/permission'

const router = useRouter()
const permissionStore = usePermissionStore()

const API_BASE = (
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_BASE ||
  '/api'
).replace(/\/+$/, '')

// =====================================================
// STATE
// =====================================================
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

// =====================================================
// PERMISSIONS
// =====================================================
const canDelete = computed(() =>
  permissionStore.isAdmin || permissionStore.isOffice
)

// =====================================================
// TABLE HEADERS
// =====================================================
const headers = [
  { title: 'Foto',         key: 'photo',        sortable: false, width: '70px' },
  { title: 'Kunde',        key: 'name',         minWidth: '220px' },
  { title: 'Nummer',       key: 'kundennummer', minWidth: '140px' },
  { title: 'Typ',          key: 'type',         minWidth: '140px' },
  { title: 'E-Mail',       key: 'email',        minWidth: '200px' },
  { title: 'Kundengruppe', key: 'kundengruppe', minWidth: '140px' },
  { title: 'Status',       key: 'status',       width: '130px' },
  { title: 'Aktionen',     key: 'actions',      sortable: false, align: 'center', width: '140px' }
]

// =====================================================
// HELPERS
// =====================================================
const displayName = (item) => {
  if (item.person_type === 'Gewerbekunde') return item.firma || 'Gewerbekunde'
  return `${item.vorname || ''} ${item.nachname || ''}`.trim() || '—'
}

const initials = (item) => {
  if (item.person_type === 'Gewerbekunde') {
    return (item.firma || 'G').charAt(0).toUpperCase()
  }
  return `${(item.vorname || '').charAt(0)}${(item.nachname || '').charAt(0)}`
    .toUpperCase() || '—'
}

// =====================================================
// COMPUTED
// =====================================================
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

// =====================================================
// NAVIGATION
// =====================================================
const goBack = () => router.push('/customers')

const openDetail = (item) => router.push(`/customers/${item.id}`)

const editKunde = (item) => {
  if (!permissionStore.can('customers-create')) {
    permissionStore.showAccessDenied()
    return
  }
  router.push(`/customers/edit/${item.id}`)
}

// =====================================================
// FILTERS
// =====================================================
const resetFilters = () => {
  search.value = ''
  selectedType.value = null
  selectedGroup.value = null
}

// =====================================================
// LOAD
// =====================================================
const loadKunden = async () => {
  if (loading.value) return

  loading.value = true
  try {
    const response = await axios.get(
      `${API_BASE}/kunden_list.php`,
      { withCredentials: true }
    )
    if (!response.data.success) {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
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

// =====================================================
// DELETE
// =====================================================
const deleteKunde = (item) => {
  if (!canDelete.value) {
    permissionStore.showAccessDenied()
    return
  }
  selectedItem.value = item
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!canDelete.value) {
    permissionStore.showAccessDenied()
    deleteDialog.value = false
    selectedItem.value = null
    return
  }

  deleting.value = true

  try {
    const token = localStorage.getItem('token')

    const response = await axios.delete(
      `${API_BASE}/kunden_delete.php?id=${selectedItem.value.id}`,
      {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        withCredentials: true
      }
    )

    if (response.data.success) {
      snackbarText.value = 'Kunde gelöscht'
      snackbarColor.value = 'success'
      snackbar.value = true
      deleteDialog.value = false
      selectedItem.value = null
      await loadKunden()
    } else {
      throw new Error(response.data.error || 'Unbekannter Fehler')
    }
  } catch (error) {
    if (error.response?.status === 401 || error.response?.status === 403) {
      permissionStore.showAccessDenied()
      deleteDialog.value = false
      selectedItem.value = null
      return
    }

    snackbarText.value = error.response?.data?.error || 'Fehler beim Löschen'
    snackbarColor.value = 'error'
    snackbar.value = true
  } finally {
    deleting.value = false
  }
}

// =====================================================
// INIT
// =====================================================
onMounted(loadKunden)
</script>

<style scoped>
/* ============================================================
   PALETTE — BLEU MÉDICAL + GRIS
============================================================ */
.all-customers-page {
  /* Bleu médical */
  --medical-900: #0c4a6e;
  --medical-800: #075985;
  --medical-700: #0369a1;
  --medical-600: #0284c7;
  --medical-500: #0ea5e9;
  --medical-100: #e0f2fe;
  --medical-50:  #f0f9ff;

  /* Gris neutres */
  --grey-900: #0f172a;
  --grey-700: #334155;
  --grey-600: #475569;
  --grey-500: #64748b;
  --grey-400: #94a3b8;
  --grey-200: #e2e8f0;
  --grey-100: #f1f5f9;
  --grey-50:  #f8fafc;

  --text-main: #0f172a;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --background: #f8fafc;

  max-width: 1600px;
  margin: 0 auto;
  background: var(--background);
  min-height: 100vh;
}

/* ============================================================
   HEADER — bleu médical profond
============================================================ */
.header-card {
  border-radius: 16px !important;
  background: linear-gradient(135deg, #207dac 0%, #3781a7 45%, #1d6688 100%) !important;
  border: none !important;
  box-shadow: 0 8px 24px rgba(2, 132, 199, 0.25) !important;
  overflow: hidden;
  position: relative;
}
.header-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 40%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 60%);
  pointer-events: none;
}
.header-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}
.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(4px);
}
.header-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  backdrop-filter: blur(4px);
}
.header-title {
  margin: 6px 0 0;
  font-size: 1.4rem;
  font-weight: 750;
  color: #ffffff;
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.header-subtitle {
  margin-top: 3px;
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.85);
}
.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.header-actions .v-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: 10px !important;
}
.header-actions .v-btn[variant="text"] {
  color: #ffffff !important;
}
.header-actions .v-btn[variant="text"]:hover {
  background: rgba(255, 255, 255, 0.15) !important;
}
.refresh-btn {
  color: var(--medical-700) !important;
  font-weight: 700 !important;
}

/* ============================================================
   FILTERS
============================================================ */
.filter-card {
  border-radius: 16px !important;
  background: #ffffff !important;
  border: 1px solid var(--border) !important;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.05) !important;
}
.filter-card :deep(.v-field) {
  border-radius: 10px !important;
}
.filter-card :deep(.v-field--focused) {
  box-shadow: 0 0 0 2px var(--medical-500) inset !important;
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
  border-radius: 10px !important;
  height: 44px !important;
  white-space: nowrap;
  background: var(--medical-50) !important;
  color: var(--medical-700) !important;
  border: 1px solid var(--medical-100) !important;
}
.filter-reset:hover {
  background: var(--medical-100) !important;
}

/* ============================================================
   TABLE
============================================================ */
.table-card {
  border-radius: 16px !important;
  background: #ffffff !important;
  border: 1px solid var(--border) !important;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.05) !important;
  overflow: hidden;
}

.customer-table :deep(tbody tr) {
  transition: background-color 0.18s ease;
}
.customer-table :deep(tbody tr:hover) {
  background-color: var(--medical-50) !important;
}
.customer-table :deep(th) {
  white-space: nowrap;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
  color: var(--grey-700) !important;
  font-size: 0.7rem !important;
  font-weight: 800 !important;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  border-bottom: 2px solid var(--grey-200) !important;
}
.customer-table :deep(td) {
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9 !important;
}

/* Avatar */
.customer-avatar {
  background: var(--medical-100) !important;
  color: var(--medical-700) !important;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 1px var(--grey-200);
  overflow: hidden;
}
.customer-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.avatar-initials {
  font-size: 0.85rem;
  font-weight: 800;
  color: var(--medical-700);
}

/* Cellules */
.cell-name {
  font-weight: 700;
  color: var(--text-main);
  font-size: 0.88rem;
}
.cell-sub {
  margin-top: 2px;
  font-size: 0.74rem;
  color: var(--text-muted);
}
.cell-mono {
  font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
  font-size: 0.78rem;
  color: var(--medical-700);
  letter-spacing: 0.02em;
  font-weight: 600;
}
.cell-value {
  color: var(--grey-700);
  font-size: 0.84rem;
  font-weight: 500;
}

/* ============================================================
   BADGES
============================================================ */
.type-badge,
.group-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

/* Type */
.type-private {
  background: var(--medical-50);
  color: var(--medical-700);
  border: 1px solid var(--medical-100);
}
.type-business {
  background: var(--grey-100);
  color: var(--grey-700);
  border: 1px solid var(--grey-200);
}

/* Groupe */
.group-badge {
  background: #eef2ff;
  color: #4338ca;
  border: 1px solid #c7d2fe;
}

/* Status */
.status-success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.status-pending {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

/* ============================================================
   ACTIONS
============================================================ */
.actions-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}
.action-btn:hover { transform: scale(1.08); }

.action-view {
  color: var(--medical-600);
}
.action-view:hover {
  background: var(--medical-50);
  border-color: var(--medical-100);
}

.action-edit {
  color: #d97706;
}
.action-edit:hover {
  background: #fffbeb;
  border-color: #fde68a;
}

.action-delete {
  color: #dc2626;
}
.action-delete:hover {
  background: #fef2f2;
  border-color: #fecaca;
}

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 1100px) {
  .filter-grid { grid-template-columns: 1fr 1fr; }
  .filter-reset { grid-column: span 2; }
}
@media (max-width: 600px) {
  .all-customers-page { padding: 8px !important; }
  .header-content { padding: 16px !important; }
  .header-title { font-size: 1.15rem; }
  .header-actions { width: 100%; }
  .header-actions .v-btn { flex: 1; }
  .filter-grid { grid-template-columns: 1fr; }
  .filter-reset { grid-column: auto; width: 100%; }
  .customer-table :deep(th),
  .customer-table :deep(td) {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}
</style>