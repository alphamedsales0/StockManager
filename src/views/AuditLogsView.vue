<template>
  <v-container fluid class="audit-page pa-3 pa-md-4">

    <!-- HERO -->
    <v-card class="hero-card mb-3">
      <v-card-text class="pa-5 pa-md-6">
        <div class="d-flex align-center flex-wrap ga-3">
          <v-avatar color="primary" size="52">
            <v-icon size="28" color="white">mdi-shield-search</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <h1 class="text-h5 font-weight-bold">Audit-Logs</h1>
            <p class="text-body-2 text-medium-emphasis ma-0">
              Protokoll aller sicherheitsrelevanten Aktionen
            </p>
          </div>
          <v-btn
            variant="tonal"
            prepend-icon="mdi-refresh"
            :loading="loading"
            @click="load"
          >
            Aktualisieren
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- FILTRES -->
    <v-card class="modern-card mb-3">
      <v-card-text class="pa-4">
        <v-row dense>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.action"
              :items="actionOptions"
              label="Aktion"
              density="compact"
              variant="outlined"
              clearable
              hide-details
              @update:model-value="applyFilters"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.limit"
              :items="[25, 50, 100, 200]"
              label="Pro Seite"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="applyFilters"
            />
          </v-col>
          <v-col cols="12" md="5">
            <v-text-field
              v-model="filters.search"
              label="Suche (Benutzer, IP, Aktion)"
              prepend-inner-icon="mdi-magnify"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn
              block
              variant="text"
              prepend-icon="mdi-filter-remove"
              @click="resetFilters"
            >
              Reset
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- TABLEAU -->
    <v-card class="modern-card">
      <v-data-table-server
        :headers="headers"
        :items="filteredLogs"
        :items-length="total"
        :loading="loading"
        :items-per-page="filters.limit"
        :page="currentPage"
        item-value="id"
        @update:page="onPageChange"
        class="audit-table"
        no-data-text="Keine Logs gefunden"
        loading-text="Logs werden geladen..."
      >
        <!-- DATE -->
        <template #item.created_at="{ item }">
          <div class="cell-date">
            <div class="date-main">{{ formatDate(item.created_at) }}</div>
            <div class="date-time">{{ formatTime(item.created_at) }}</div>
          </div>
        </template>

        <!-- ACTION -->
        <template #item.action="{ item }">
          <v-chip
            :color="actionColor(item.action)"
            variant="tonal"
            size="small"
            class="action-chip"
          >
            <v-icon start size="14">{{ actionIcon(item.action) }}</v-icon>
            {{ actionLabel(item.action) }}
          </v-chip>
        </template>

        <!-- ACTEUR -->
        <template #item.actor_id="{ item }">
          <div v-if="item.actor_id" class="cell-actor">
            <div class="actor-name">{{ item.username || '—' }}</div>
            <div class="actor-email">{{ item.actor_email || '' }}</div>
          </div>
          <span v-else class="text-medium-emphasis">Système</span>
        </template>

        <!-- CIBLE -->
        <template #item.target_type="{ item }">
          <div v-if="item.target_type" class="cell-target">
            <v-icon size="14" class="mr-1">{{ targetIcon(item.target_type) }}</v-icon>
            <span>{{ item.target_type }} #{{ item.target_id }}</span>
          </div>
          <span v-else class="text-medium-emphasis">—</span>
        </template>

        <!-- IP -->
        <template #item.ip="{ item }">
          <code class="ip-code">{{ item.ip || '—' }}</code>
        </template>

        <!-- DÉTAILS -->
        <template #item.meta="{ item }">
          <v-btn
            v-if="item.meta"
            size="x-small"
            variant="text"
            prepend-icon="mdi-code-json"
            @click="showMeta(item)"
          >
            Details
          </v-btn>
          <span v-else class="text-medium-emphasis">—</span>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- DIALOG META -->
    <v-dialog v-model="metaDialog" max-width="640">
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="mr-2">mdi-code-json</v-icon>
          <span>Log-Details #{{ selectedLog?.id }}</span>
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" @click="metaDialog = false" />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <div v-if="selectedLog" class="meta-grid">
            <div><strong>Zeit:</strong> {{ formatDate(selectedLog.created_at) }} {{ formatTime(selectedLog.created_at) }}</div>
            <div><strong>Aktion:</strong> {{ selectedLog.action }}</div>
            <div><strong>Akteur:</strong> {{ selectedLog.username || 'Système' }} (ID {{ selectedLog.actor_id || '—' }})</div>
            <div><strong>Ziel:</strong> {{ selectedLog.target_type || '—' }} #{{ selectedLog.target_id || '—' }}</div>
            <div><strong>IP:</strong> {{ selectedLog.ip || '—' }}</div>
            <div><strong>User-Agent:</strong> {{ selectedLog.user_agent || '—' }}</div>
          </div>
          <v-divider class="my-3" />
          <div class="text-caption text-medium-emphasis mb-1">Meta (JSON)</div>
          <pre class="meta-pre">{{ prettyMeta }}</pre>
        </v-card-text>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const logs = ref([])
const total = ref(0)
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const actionsList = ref([])

const filters = ref({
  action: null,
  limit: 50,
  search: '',
})

const metaDialog = ref(false)
const selectedLog = ref(null)

const headers = [
  { title: 'Datum',   key: 'created_at',  width: 160 },
  { title: 'Aktion',  key: 'action',      width: 220 },
  { title: 'Akteur',  key: 'actor_id',    width: 200 },
  { title: 'Ziel',    key: 'target_type', width: 180 },
  { title: 'IP',      key: 'ip',          width: 140 },
  { title: 'Details', key: 'meta',        width: 120, sortable: false },
]

const actionOptions = computed(() =>
  actionsList.value.map(a => ({
    title: `${actionLabel(a.action)} (${a.cnt})`,
    value: a.action,
  }))
)

const filteredLogs = computed(() => {
  if (!filters.value.search) return logs.value
  const q = filters.value.search.toLowerCase()
  return logs.value.filter(l =>
    (l.username || '').toLowerCase().includes(q) ||
    (l.actor_email || '').toLowerCase().includes(q) ||
    (l.ip || '').includes(q) ||
    (l.action || '').toLowerCase().includes(q)
  )
})

const prettyMeta = computed(() => {
  if (!selectedLog.value?.meta) return '—'
  try {
    const obj = typeof selectedLog.value.meta === 'string'
      ? JSON.parse(selectedLog.value.meta)
      : selectedLog.value.meta
    return JSON.stringify(obj, null, 2)
  } catch {
    return selectedLog.value.meta
  }
})

/* ---------- LOAD ---------- */
async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    params.set('limit', filters.value.limit)
    params.set('offset', (currentPage.value - 1) * filters.value.limit)
    if (filters.value.action) params.set('action', filters.value.action)

    const res = await fetch(`/api/audit_logs_list.php?${params.toString()}`, {
      credentials: 'include',
    })
    const data = await res.json()
    if (!data.success) throw new Error(data.message || 'Fehler')

    logs.value = data.logs
    total.value = data.total
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function loadActions() {
  try {
    const res = await fetch('/api/audit_logs_actions.php', { credentials: 'include' })
    const data = await res.json()
    if (data.success) actionsList.value = data.actions
  } catch { /* ignore */ }
}

function onPageChange(page) {
  currentPage.value = page
  load()
}

function applyFilters() {
  currentPage.value = 1
  load()
}

function resetFilters() {
  filters.value = { action: null, limit: 50, search: '' }
  currentPage.value = 1
  load()
}

/* ---------- HELPERS ---------- */
function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}
function formatTime(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleTimeString('de-DE', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
}

function actionColor(action) {
  if (action.includes('password')) return 'warning'
  if (action.includes('delete'))   return 'error'
  if (action.includes('create'))   return 'success'
  if (action.includes('login'))    return 'info'
  if (action.includes('update'))   return 'primary'
  return 'grey'
}
function actionIcon(action) {
  if (action.includes('password')) return 'mdi-lock-reset'
  if (action.includes('delete'))   return 'mdi-delete'
  if (action.includes('create'))   return 'mdi-plus'
  if (action.includes('login'))    return 'mdi-login'
  if (action.includes('update'))   return 'mdi-pencil'
  return 'mdi-shield-check'
}
function actionLabel(action) {
  const map = {
    password_reset_requested: 'Passwort-Reset angefordert',
    password_reset_completed: 'Passwort geändert',
    employee_deleted:         'Mitarbeiter gelöscht',
    employee_created:         'Mitarbeiter erstellt',
    employee_updated:         'Mitarbeiter bearbeitet',
    login_success:            'Login erfolgreich',
    login_failed:             'Login fehlgeschlagen',
    logout:                   'Logout',
  }
  return map[action] || action
}
function targetIcon(type) {
  return {
    user: 'mdi-account',
    employee: 'mdi-account-tie',
    document: 'mdi-file',
  }[type] || 'mdi-target'
}

function showMeta(item) {
  selectedLog.value = item
  metaDialog.value = true
}

onMounted(() => {
  load()
  loadActions()
})
</script>

<style scoped>
.audit-page {
  max-width: 1600px;
  margin: 0 auto;
  background: #f6f8fb;
  min-height: 100vh;
}

.hero-card {
  border-radius: 14px !important;
  border: 1px solid #e5e9f0 !important;
  background: linear-gradient(135deg, #ffffff 0%, #fbfcfe 55%, #f4f7fb 100%) !important;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06) !important;
}

.modern-card {
  border: 1px solid #e5e9f0 !important;
  border-radius: 14px !important;
  background: #ffffff !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
}

.audit-table {
  background: transparent !important;
}

/* Cellules */
.cell-date { display: flex; flex-direction: column; }
.date-main { font-weight: 600; color: #0f172a; font-size: .82rem; }
.date-time { color: #64748b; font-size: .74rem; font-variant-numeric: tabular-nums; }

.cell-actor { display: flex; flex-direction: column; }
.actor-name { font-weight: 600; color: #0f172a; font-size: .82rem; }
.actor-email { color: #64748b; font-size: .72rem; }

.cell-target {
  display: inline-flex;
  align-items: center;
  font-size: .78rem;
  color: #475569;
}

.action-chip {
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
}

.ip-code {
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 6px;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  font-size: .72rem;
  color: #334155;
}

/* Dialog meta */
.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 20px;
  font-size: .85rem;
  color: #334155;
}
.meta-pre {
  background: #0f172a;
  color: #e2e8f0;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: .78rem;
  max-height: 320px;
  overflow: auto;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
}

@media (max-width: 600px) {
  .meta-grid { grid-template-columns: 1fr; }
  .audit-page { padding: 8px !important; }
}
</style>