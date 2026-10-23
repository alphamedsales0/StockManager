<template>
  <v-container fluid class="customers-page pa-3 pa-md-5">

    <!-- ================= HERO HEADER ================= -->
    <v-card class="hero-header mb-5">
      <div class="hero-header-bg"></div>
      <div class="hero-header-glow"></div>

      <div class="hero-header-content pa-5 pa-md-6">
        <div class="hero-header-left">
          <div class="hero-pill">
            <v-icon size="13">mdi-account-group-outline</v-icon>
            KUNDENVERWALTUNG
          </div>

          <h1 class="hero-title">Kunden</h1>

          <div class="hero-subtitle">
            Übersicht und Pflege aller Kundenprofile
          </div>

          <div class="hero-stats">
            <div class="hero-stat">
              <v-icon size="16">mdi-account-multiple</v-icon>
              <strong>{{ kunden.length }}</strong> gesamt
            </div>
            <v-divider vertical class="mx-2" />
            <div class="hero-stat hero-stat-success">
              <v-icon size="16">mdi-account-check</v-icon>
              <strong>{{ verifiedCount }}</strong> verifiziert
            </div>
            <v-divider vertical class="mx-2" />
            <div class="hero-stat hero-stat-warning">
              <v-icon size="16">mdi-domain</v-icon>
              <strong>{{ gewerbeCount }}</strong> Gewerbe
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
            @click="loadKunden"
          >
            Aktualisieren
          </v-btn>

          <v-btn
            v-if="permissionStore.can('customers-create')"
            color="primary"
            variant="flat"
            size="large"
            prepend-icon="mdi-account-plus"
            class="hero-primary-btn"
            @click="router.push('/customers/add')"
          >
            Neuer Kunde
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
              <div class="kpi-label">Gesamt Kunden</div>
              <div class="kpi-value">{{ kunden.length }}</div>
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
              <div class="kpi-label">Verifiziert</div>
              <div class="kpi-value">{{ verifiedCount }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-success"></div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-warning">
              <v-icon size="22">mdi-domain</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Gewerbekunden</div>
              <div class="kpi-value">{{ gewerbeCount }}</div>
            </div>
          </v-card-text>
          <div class="kpi-bar kpi-bar-warning"></div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card" elevation="0">
          <v-card-text class="kpi-content">
            <div class="kpi-icon kpi-icon-info">
              <v-icon size="22">mdi-account-outline</v-icon>
            </div>
            <div class="kpi-text">
              <div class="kpi-label">Privatkunden</div>
              <div class="kpi-value">{{ kunden.length - gewerbeCount }}</div>
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

    <!-- ================= TABLE ================= -->
    <v-card class="table-card" elevation="0">
     <div class="table-header">
  <div>
    <div class="table-title">Kundenübersicht</div>
    <div class="table-subtitle">
  Erste 10 Kunden – klicken Sie auf „Alle ansehen" für die vollständige Liste
</div>
  </div>

  <div class="table-header-actions">
    <v-chip variant="tonal" color="primary" class="table-chip">
      <v-icon start size="14">mdi-account-multiple-outline</v-icon>
      {{ filteredKunden.length }} Kunden
    </v-chip>

    <v-btn
  variant="tonal"
  color="primary"
  size="small"
  prepend-icon="mdi-format-list-bulleted"
  append-icon="mdi-arrow-right"
  class="show-all-btn"
  @click="goToAllKunden"
>
  Alle ansehen
</v-btn>
  </div>
</div>

      <v-divider />

     <v-data-table
  :headers="headers"
  :items="filteredKunden"
  :loading="loading"
  :items-per-page="10"
  hover
  class="customer-table"
  loading-text="Kunden werden geladen..."
  no-data-text="Keine Kunden vorhanden"
  items-per-page-text="Einträge pro Seite"
>
        <template #item="{ item, columns }">
          <tr class="clickable-row" @click="openDetail(item)">
            <td
              v-for="col in columns"
              :key="col.key"
              :class="{ 'text-center': col.key === 'actions' }"
            >
              <!-- PHOTO -->
              <template v-if="col.key === 'photo'">
                <v-avatar size="44" class="customer-avatar">
                  <img
                    v-if="item.profilbild"
                    :src="item.profilbild"
                    class="customer-photo"
                    :alt="displayName(item)"
                  />
                  <v-icon v-else size="20">mdi-account</v-icon>
                </v-avatar>
              </template>

              <!-- NAME -->
              <template v-else-if="col.key === 'name'">
                <div class="py-2">
                  <div class="cell-name">{{ displayName(item) }}</div>
                  <div class="cell-sub">{{ item.email || 'Keine E-Mail' }}</div>
                </div>
              </template>

              <!-- NUMBER -->
              <template v-else-if="col.key === 'kundennummer'">
                <span class="cell-mono">{{ item.kundennummer || '–' }}</span>
              </template>

              <!-- TYPE -->
              <template v-else-if="col.key === 'type'">
                <v-chip
                  :color="item.person_type === 'Gewerbekunde' ? 'warning' : 'primary'"
                  size="small"
                  variant="tonal"
                  class="cell-chip"
                >
                  <v-icon start size="14">
                    {{ item.person_type === 'Gewerbekunde' ? 'mdi-domain' : 'mdi-account' }}
                  </v-icon>
                  {{ item.person_type }}
                </v-chip>
              </template>

              <!-- EMAIL -->
              <template v-else-if="col.key === 'email'">
                <span class="cell-value">{{ item.email || '–' }}</span>
              </template>

              <!-- GROUP -->
              <template v-else-if="col.key === 'kundengruppe'">
                <v-chip
                  v-if="item.kundengruppe"
                  size="small"
                  variant="tonal"
                  color="primary"
                  class="cell-chip"
                >
                  {{ item.kundengruppe }}
                </v-chip>
                <span v-else class="text-medium-emphasis">–</span>
              </template>

              <!-- STATUS -->
              <template v-else-if="col.key === 'status'">
                <v-chip
                  :color="item.email_verifiziert ? 'success' : 'grey'"
                  size="small"
                  variant="tonal"
                  class="status-chip"
                >
                  <v-icon start size="14">
                    {{ item.email_verifiziert ? 'mdi-check-circle' : 'mdi-clock-outline' }}
                  </v-icon>
                  {{ item.email_verifiziert ? 'Verifiziert' : 'Offen' }}
                </v-chip>
              </template>

              <!-- ACTIONS -->
              <template v-else-if="col.key === 'actions'">
                <div class="actions-cell">
                  <v-tooltip text="Ansehen">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-eye-outline"
                        variant="text"
                        size="small"
                        color="primary"
                        @click.stop="openDetail(item)"
                      />
                    </template>
                  </v-tooltip>

                  <v-tooltip
                    v-if="permissionStore.can('customers-create')"
                    text="Bearbeiten"
                  >
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-pencil-outline"
                        variant="text"
                        size="small"
                        color="warning"
                        @click.stop="editKunde(item)"
                      />
                    </template>
                  </v-tooltip>

                  <!-- ✅ Supprimer : visible pour TOUT LE MONDE.
                       Admin/Office → dialogue de confirmation
                       Autres → modal "Zugriff verweigert" directement -->
                  <v-tooltip text="Löschen">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-delete-outline"
                        variant="text"
                        size="small"
                        color="error"
                        @click.stop="deleteKunde(item)"
                      />
                    </template>
                  </v-tooltip>
                </div>
              </template>

              <!-- DEFAULT -->
              <template v-else>
                <span class="cell-value">{{ item[col.key] || '–' }}</span>
              </template>
            </td>
          </tr>
        </template>

        <template #no-data>
          <div class="empty-state">
            <div class="empty-icon">
              <v-icon size="36">mdi-account-search-outline</v-icon>
            </div>
            <div class="empty-title">Keine Kunden gefunden</div>
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
            <v-alert type="warning" variant="tonal" density="compact" class="mb-2">
              <div class="font-weight-bold">{{ displayName(selectedItem) }}</div>
              <div class="text-caption">
                {{ selectedItem.kundennummer || '–' }}
                · {{ selectedItem.email || 'Keine E-Mail' }}
              </div>
            </v-alert>

            <p class="text-body-2 text-error">
              Alle zugehörigen Daten werden gelöscht.
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



const goToAllKunden = () => {
  router.push('/customers/all')
}

// =====================================================
// PERMISSIONS
// =====================================================

// ✅ Rôles autorisés à supprimer un kunde :
//    Admin ou Office — identique à $ALLOWED_ROLES dans kunden_delete.php
const canDelete = computed(() =>
  permissionStore.isAdmin || permissionStore.isOffice
)

// =====================================================
// TABLE HEADERS
// =====================================================
const headers = [
  { title: 'Foto',         key: 'photo',        sortable: false, width: '76px' },
  { title: 'Kunde',        key: 'name',         minWidth: '220px' },
  { title: 'Nummer',       key: 'kundennummer', minWidth: '140px' },
  { title: 'Typ',          key: 'type',         minWidth: '150px' },
  { title: 'E-Mail',       key: 'email',        minWidth: '200px' },
  { title: 'Kundengruppe', key: 'kundengruppe', minWidth: '150px' },
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

const verifiedCount = computed(() => kunden.value.filter(k => k.email_verifiziert).length)
const gewerbeCount = computed(() => kunden.value.filter(k => k.person_type === 'Gewerbekunde').length)

// =====================================================
// ACTIONS
// =====================================================
const resetFilters = () => {
  search.value = ''
  selectedType.value = null
  selectedGroup.value = null
}

const openDetail = (item) => router.push(`/customers/${item.id}`)

const editKunde = (item) => {
  if (!permissionStore.can('customers-create')) {
    permissionStore.showAccessDenied()
    return
  }
  router.push(`/customers/edit/${item.id}`)
}

// ✅ Si pas Admin/Office → directement le modal "Zugriff verweigert"
//    Sinon → ouvre le dialogue de confirmation
const deleteKunde = (item) => {
  if (!canDelete.value) {
    permissionStore.showAccessDenied()
    return
  }

  selectedItem.value = item
  deleteDialog.value = true
}

// ✅ Garde finale (ceinture + bretelles) avant l'appel API
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
    // 401 / 403 → même modal d'accès refusé
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
// INITIALIZATION
// =====================================================
onMounted(loadKunden)
</script>

<style scoped>
/* ============================================================
   VARIABLES
============================================================ */
.customers-page {
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

.table-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.show-all-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
  height: 36px !important;
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
.hero-stat-warning { border-color: #fde68a; background: #fffbeb; }
.hero-stat-warning strong { color: #92400e; }
.hero-stat-warning .v-icon { color: #d97706 !important; }
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

.customer-table { background: transparent !important; }
.customer-table :deep(tbody tr) { transition: background-color 0.18s ease; }
.customer-table :deep(tbody tr:hover) { background-color: #f8fafc !important; }
.customer-table :deep(th) {
  white-space: nowrap;
  background: #f8fafc !important;
  color: var(--text-muted) !important;
  font-size: 0.72rem !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid var(--border) !important;
}
.customer-table :deep(td) {
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9 !important;
}
.clickable-row { cursor: pointer; }
.customer-avatar {
  background: #f1f5f9 !important;
  color: #64748b !important;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 1px var(--border);
  overflow: hidden;
}
.customer-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
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
   DIALOG
============================================================ */
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

  .table-header-actions {
  width: 100%;
  justify-content: space-between;
}
  .customers-page { padding: 8px !important; }
  .hero-header-content { padding: 20px 16px !important; }
  .hero-stats { gap: 6px; font-size: 0.76rem; }
  .hero-stat { padding: 3px 8px; }
  .kpi-content { padding: 14px 12px 18px !important; gap: 12px; }
  .kpi-value { font-size: 1.4rem; }
  .kpi-label { font-size: 0.66rem; }
  .filter-grid { grid-template-columns: 1fr; }
  .filter-reset { grid-column: auto; width: 100%; }
  .table-header { padding: 16px; flex-direction: column; align-items: flex-start; }
  .customer-table :deep(th),
  .customer-table :deep(td) {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}
</style>