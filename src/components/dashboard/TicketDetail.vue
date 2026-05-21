<template>
  <v-container fluid :class="['ticket-container', { 'dark-mode': darkMode }]" class="pa-4">
    <!-- Skeleton Loader -->
    <template v-if="loading">
      <v-card class="skeleton-card">
        <v-skeleton-loader type="heading" />
        <v-skeleton-loader type="image" class="mt-4" />
        <v-skeleton-loader type="list-item-three-line" class="mt-4" />
        <v-skeleton-loader type="article" class="mt-4" />
      </v-card>
    </template>

    <!-- Ticket -->
    <template v-else-if="ticket">
      <v-card class="ticket-card glass-effect" :class="{ 'dark-glass': darkMode }">
        <!-- HEADER avec toggle dark mode -->
        <v-toolbar :color="darkMode ? '#0a0f1a' : '#0f172a'" dark flat class="toolbar-header px-4">
          <div class="d-flex align-center">
            <v-avatar color="primary" size="42" class="mr-4 floating-avatar">
              <v-icon size="24">mdi-ticket-confirmation</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold">Ticket {{ ticket.reference_number }}</div>
              <div class="text-caption text-grey-lighten-1">Erstellt am {{ formatDateTime(ticket.created_at) }}</div>
            </div>
          </div>
          <v-spacer />
          <v-chip :color="priorityColor" class="priority-chip mr-2" size="small">
            <v-icon start size="14">mdi-alert</v-icon>
            {{ priorityLabel }}
          </v-chip>
          <v-chip :color="statusColor" class="status-chip mr-4" size="large">
            <v-icon start size="18">{{ statusIcon }}</v-icon>
            {{ translateStatus(ticket.status) }}
          </v-chip>
          <v-btn icon variant="text" @click="darkMode = !darkMode" class="mr-2">
            <v-icon>{{ darkMode ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
          </v-btn>
          <v-btn icon variant="text" @click="goBack"><v-icon>mdi-close</v-icon></v-btn>
        </v-toolbar>

        <!-- CONTENT -->
        <v-container fluid class="pa-5">
          <v-row>
            <!-- LEFT COLUMN -->
            <v-col cols="12" lg="5">
              <!-- Statistiques Ticket -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-chart-box</v-icon>
                  Statistiken & Metriken
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-row>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Ticket Alter</div>
                        <div class="stat-value">{{ ticketAge }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Kommentare</div>
                        <div class="stat-value">{{ ticket.comments?.length || 0 }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Aktivitäten</div>
                        <div class="stat-value">{{ activities.length }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Letzte Aktion</div>
                        <div class="stat-value">{{ lastActionTime }}</div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Kundendaten (Liste avec lignes transparentes) -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-account-group</v-icon>
                  Kundendaten
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item v-for="(item, idx) in customerFields" :key="idx">
                      <template #prepend><v-icon>{{ item.icon }}</v-icon></template>
                      <div>
                        <div class="item-label">{{ item.label }}</div>
                        <div class="item-value">{{ item.value }}</div>
                      </div>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- Ticket Info (Erstellt, Aktualisiert, Bearbeiter, Letzte Änderung) -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-information-outline</v-icon>
                  Ticket Informationen
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item>
                      <template #prepend><v-icon>mdi-calendar-plus</v-icon></template>
                      <div>
                        <div class="item-label">Erstellt</div>
                        <div class="item-value">{{ formatDateTime(ticket.created_at) }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-calendar-edit</v-icon></template>
                      <div>
                        <div class="item-label">Aktualisiert</div>
                        <div class="item-value">{{ formatDateTime(ticket.updated_at) }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-account-tie</v-icon></template>
                      <div>
                        <div class="item-label">Bearbeiter</div>
                        <div class="item-value">{{ ticket.assigned_to || '-' }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-account-clock</v-icon></template>
                      <div>
                        <div class="item-label">Letzte Änderung</div>
                        <div class="item-value">{{ ticket.last_updated_by || '-' }}</div>
                      </div>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- Status ändern -->
              <v-card class="info-card glass-effect" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-sync</v-icon>
                  Status ändern
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="8">
                      <v-select
                        v-model="selectedStatus"
                        :items="statusOptions"
                        item-title="label"
                        item-value="value"
                        label="Neuen Status wählen"
                        variant="outlined"
                        density="comfortable"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-btn color="primary" block size="large" @click="updateStatus" :loading="updatingStatus">
                        Speichern
                      </v-btn>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- RIGHT COLUMN -->
            <v-col cols="12" lg="7">
              <!-- FORMULARDATEN - Style liste avec icônes (comme Kundendaten) -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-file-document-outline</v-icon>
                  Formulardaten
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <!-- SERVICE REQUEST -->
                    <template v-if="ticket.form_type === 'service_request'">
                      <template v-for="(device, idx) in ticket.form_data.devices" :key="idx">
                        <v-list-item v-if="idx > 0" class="section-divider">
                          <div class="text-subtitle-2 font-weight-bold mt-2 mb-2">Gerät {{ idx+1 }}</div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-factory</v-icon></template>
                          <div>
                            <div class="item-label">Hersteller</div>
                            <div class="item-value">{{ device.manufacturer || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-cellphone</v-icon></template>
                          <div>
                            <div class="item-label">Modell</div>
                            <div class="item-value">{{ device.model || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-counter</v-icon></template>
                          <div>
                            <div class="item-label">Seriennummer</div>
                            <div class="item-value">{{ device.serial || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-alert-circle</v-icon></template>
                          <div>
                            <div class="item-label">Fehlerbeschreibung</div>
                            <div class="item-value">{{ device.errorDescription || 'Keine Angabe' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-text</v-icon></template>
                        <div>
                          <div class="item-label">Zusatzinformationen</div>
                          <div class="item-value">{{ ticket.form_data.additional || '-' }}</div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- MAINTENANCE -->
                    <template v-else-if="ticket.form_type === 'maintenance'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-file-sign</v-icon></template>
                        <div>
                          <div class="item-label">Vertragsart</div>
                          <div class="item-value">{{ ticket.form_data.contractType === 'einzelwartung' ? 'Einzelwartung' : 'Wartungsvertrag' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-checkbox-multiple</v-icon></template>
                        <div>
                          <div class="item-label">Ausgewählte Optionen</div>
                          <div class="item-value">
                            <v-chip v-for="opt in ticket.form_data.options" :key="opt" class="mr-2 mb-2" size="small" color="primary" variant="outlined">{{ opt }}</v-chip>
                          </div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- INSTALLATION -->
                    <template v-else-if="ticket.form_type === 'installation'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-floor-plan</v-icon></template>
                        <div>
                          <div class="item-label">Fußboden</div>
                          <div class="item-value">{{ ticket.form_data.floor || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-door</v-icon></template>
                        <div>
                          <div class="item-label">Türen</div>
                          <div class="item-value">{{ ticket.form_data.doors || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-elevator</v-icon></template>
                        <div>
                          <div class="item-label">Aufzug vorhanden</div>
                          <div class="item-value">{{ ticket.form_data.elevator ? 'Ja' : 'Nein' }}</div>
                        </div>
                      </v-list-item>
                      <template v-if="ticket.form_data.elevator">
                        <v-list-item>
                          <template #prepend><v-icon>mdi-storefront</v-icon></template>
                          <div>
                            <div class="item-label">Zugang Stockwerk</div>
                            <div class="item-value">{{ ticket.form_data.elevatorAccess || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-expand-horizontal</v-icon></template>
                          <div>
                            <div class="item-label">Türe Maße (m)</div>
                            <div class="item-value">{{ ticket.form_data.elevatorDoorSize || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-expand-vertical</v-icon></template>
                          <div>
                            <div class="item-label">Innentiefe (m)</div>
                            <div class="item-value">{{ ticket.form_data.elevatorInsideSize || '-' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-stairs</v-icon></template>
                        <div>
                          <div class="item-label">Treppe vorhanden</div>
                          <div class="item-value">{{ ticket.form_data.stairs ? 'Ja' : 'Nein' }}</div>
                        </div>
                      </v-list-item>
                      <template v-if="ticket.form_data.stairs">
                        <v-list-item>
                          <template #prepend><v-icon>mdi-ruler</v-icon></template>
                          <div>
                            <div class="item-label">Treppenbreite (cm)</div>
                            <div class="item-value">{{ ticket.form_data.stairWidth || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-rotate-right</v-icon></template>
                          <div>
                            <div class="item-label">Übers Eck</div>
                            <div class="item-value">{{ ticket.form_data.cornerStair ? 'Ja' : 'Nein' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-export</v-icon></template>
                          <div>
                            <div class="item-label">Ausstieg Stockwerk</div>
                            <div class="item-value">{{ ticket.form_data.stairExit || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-numeric</v-icon></template>
                          <div>
                            <div class="item-label">Stufenanzahl</div>
                            <div class="item-value">{{ ticket.form_data.stairSteps || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-right-bold</v-icon></template>
                          <div>
                            <div class="item-label">Verlauf nach Stufen</div>
                            <div class="item-value">{{ ticket.form_data.stairAfterSteps || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-hand-saw</v-icon></template>
                          <div>
                            <div class="item-label">Geländer abnehmbar</div>
                            <div class="item-value">{{ ticket.form_data.railingRemovable ? 'Ja' : 'Nein' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                    </template>

                    <!-- REPARATUR -->
                    <template v-else-if="ticket.form_type === 'reparatur'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-tools</v-icon></template>
                        <div>
                          <div class="item-label">Gerät / Produkt</div>
                          <div class="item-value">{{ ticket.form_data.device || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-wrench</v-icon></template>
                        <div>
                          <div class="item-label">Service-Typ</div>
                          <div class="item-value">{{ ticket.form_data.service || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-message-alert</v-icon></template>
                        <div>
                          <div class="item-label">Problembeschreibung</div>
                          <div class="item-value">{{ ticket.form_data.problem || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-speedometer</v-icon></template>
                        <div>
                          <div class="item-label">Dringlichkeit</div>
                          <div class="item-value">
                            <v-chip :color="getUrgencyColor(ticket.form_data.urgency)" size="small">{{ translateUrgency(ticket.form_data.urgency) }}</v-chip>
                          </div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-calendar</v-icon></template>
                        <div>
                          <div class="item-label">Eingangsdatum</div>
                          <div class="item-value">{{ formatDate(ticket.form_data.submissionDate) }}</div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- FALLBACK JSON -->
                    <template v-else>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-code-json</v-icon></template>
                        <div>
                          <div class="item-label">Rohdaten</div>
                          <div class="item-value">
                            <pre class="json-preview">{{ JSON.stringify(ticket.form_data, null, 2) }}</pre>
                          </div>
                        </div>
                      </v-list-item>
                    </template>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- ACTIVITY LOG & COMMENTAIRES (timeline unifiée) -->
              <v-card class="comment-card glass-effect" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-history</v-icon>
                  Aktivitäten & Kommentare
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="timeline">
                    <transition-group name="timeline-item">
                      <div v-for="(activity, idx) in activities" :key="activity.id" class="timeline-item">
                        <div class="timeline-dot" :style="{ backgroundColor: activity.color }"></div>
                        <div class="timeline-content">
                          <div class="d-flex align-center mb-2">
                            <v-avatar :color="activity.avatarColor" size="36" class="mr-3">
                              <span class="text-caption font-weight-bold">{{ getInitials(activity.author) }}</span>
                            </v-avatar>
                            <div>
                              <div class="font-weight-bold">{{ activity.author }}</div>
                              <div class="text-caption text-grey">{{ formatDateTime(activity.created_at) }}</div>
                            </div>
                          </div>
                          <div class="activity-text">
                            <v-icon v-if="activity.type === 'status'" size="16" class="mr-1">mdi-tune</v-icon>
                            <v-icon v-else-if="activity.type === 'comment'" size="16" class="mr-1">mdi-comment</v-icon>
                            {{ activity.text }}
                          </div>
                        </div>
                      </div>
                    </transition-group>
                  </div>

                  <v-divider class="my-6" />
                  <div class="text-subtitle-1 font-weight-bold mb-4">Neuen Kommentar hinzufügen</div>
                  <v-textarea v-model="newCommentText" label="Kommentar eingeben..." rows="4" variant="outlined" auto-grow />
                  <div class="d-flex justify-end mt-4">
                    <v-btn color="primary" size="large" :disabled="!newCommentText.trim()" @click="addComment">
                      <v-icon start>mdi-send</v-icon> Kommentar speichern
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-container>

        <v-divider />
        <v-card-actions class="pa-5 justify-end">
          <v-btn variant="outlined" size="large" @click="goBack">Zurück</v-btn>
        </v-card-actions>
      </v-card>
    </template>

    <v-alert v-else type="error" variant="tonal">Ticket nicht gefunden.</v-alert>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// --- Réactivité ---
const ticket = ref(null)
const loading = ref(true)
const selectedStatus = ref('')
const updatingStatus = ref(false)
const newCommentText = ref('')
const darkMode = ref(false)
const activities = ref([])

// --- Options statut ---
const statusOptions = [
  { label: 'In Bearbeitung', value: 'pending' },
  { label: 'In Prüfung', value: 'in_progress' },
  { label: 'Abgeschlossen', value: 'completed' },
  { label: 'Storniert', value: 'cancelled' }
]

// --- Priorité ---
const priorityLabel = computed(() => {
  if (!ticket.value) return ''
  const urgency = ticket.value.form_data?.urgency
  if (urgency === 'hoch') return 'Höchste Priorität'
  if (urgency === 'mittel') return 'Mittlere Priorität'
  return 'Normale Priorität'
})

const priorityColor = computed(() => {
  if (!ticket.value) return 'grey'
  const urgency = ticket.value.form_data?.urgency
  if (urgency === 'hoch') return 'error'
  if (urgency === 'mittel') return 'warning'
  return 'success'
})

const statusColor = computed(() => {
  const map = { pending: 'warning', in_progress: 'info', completed: 'success', cancelled: 'error' }
  return map[ticket.value?.status] || 'grey'
})

const statusIcon = computed(() => {
  const map = { pending: 'mdi-clock-outline', in_progress: 'mdi-progress-clock', completed: 'mdi-check-circle', cancelled: 'mdi-cancel' }
  return map[ticket.value?.status]
})

// --- Statistiques ---
const ticketAge = computed(() => {
  if (!ticket.value?.created_at) return '-'
  const created = new Date(ticket.value.created_at)
  const now = new Date()
  const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24))
  if (diffDays === 0) return 'Heute'
  if (diffDays === 1) return '1 Tag'
  return `${diffDays} Tage`
})

const lastActionTime = computed(() => {
  if (!activities.value.length) return '-'
  const last = activities.value[0]
  return formatDateTime(last.created_at)
})

// --- Kundendaten formatiert ---
const customerFields = computed(() => {
  if (!ticket.value?.customer) return []
  const c = ticket.value.customer
  return [
    { label: 'Anrede', value: c.anrede, icon: 'mdi-account' },
    { label: 'Name', value: `${c.firstname} ${c.lastname}`, icon: 'mdi-card-account-details' },
    { label: 'Firma', value: c.company || '-', icon: 'mdi-domain' },
    { label: 'E-Mail', value: c.email, icon: 'mdi-email' },
    { label: 'Telefon', value: c.phone, icon: 'mdi-phone' },
    { label: 'Adresse', value: `${c.address} ${c.hausnummer}`, icon: 'mdi-home' },
    { label: 'Ort', value: `${c.plz} ${c.ort}`, icon: 'mdi-map-marker' }
  ]
})

// --- Utilitaires ---
const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const translateStatus = (status) => {
  const map = { pending: 'In Bearbeitung', in_progress: 'In Prüfung', completed: 'Abgeschlossen', cancelled: 'Storniert' }
  return map[status] || status
}

const translateUrgency = (urgency) => {
  const map = { hoch: 'Hoch', mittel: 'Mittel', niedrig: 'Niedrig' }
  return map[urgency] || urgency || '-'
}

const getUrgencyColor = (urgency) => {
  const map = { hoch: 'error', mittel: 'warning', niedrig: 'success' }
  return map[urgency] || 'grey'
}

const getInitials = (name) => {
  return name?.split(' ').map(word => word.charAt(0)).join('').slice(0, 2).toUpperCase()
}

// --- Activity Log ---
function addActivity(type, author, text, extra = {}) {
  const newActivity = {
    id: Date.now() + Math.random(),
    type,
    author,
    text,
    created_at: new Date().toISOString(),
    color: type === 'status' ? '#1976d2' : '#2e7d32',
    avatarColor: type === 'status' ? '#1565c0' : '#2c3e50'
  }
  activities.value.unshift(newActivity)
}

// --- Actions ---
const updateStatus = () => {
  if (selectedStatus.value === ticket.value.status) return
  updatingStatus.value = true
  setTimeout(() => {
    const oldStatus = ticket.value.status
    ticket.value.status = selectedStatus.value
    addActivity('status', 'Admin', `Status geändert von "${translateStatus(oldStatus)}" zu "${translateStatus(selectedStatus.value)}"`)
    updatingStatus.value = false
  }, 800)
}

const addComment = () => {
  if (!newCommentText.value.trim()) return
  const commentText = newCommentText.value
  addActivity('comment', 'Admin', commentText)
  if (!ticket.value.comments) ticket.value.comments = []
  ticket.value.comments.unshift({
    id: Date.now(),
    author: 'Admin',
    text: commentText,
    created_at: new Date().toISOString()
  })
  newCommentText.value = ''
}

// --- Chargement ---
const loadDetails = async () => {
  try {
    const response = await fetch(`/api/get_ticket_details.php?id=${route.params.id}`)
    const data = await response.json()
    if (data.success) {
      ticket.value = data.ticket
      selectedStatus.value = ticket.value.status
      activities.value = []
      if (ticket.value.comments) {
        ticket.value.comments.forEach(comment => {
          addActivity('comment', comment.author, comment.text, { created_at: comment.created_at })
        })
      }
      addActivity('status', 'System', `Ticket erstellt am ${formatDateTime(ticket.value.created_at)}`)
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// --- Dark mode ---
const initDarkMode = () => {
  const saved = localStorage.getItem('darkMode')
  if (saved !== null) {
    darkMode.value = saved === 'true'
  } else {
    darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
}
watch(darkMode, (val) => {
  localStorage.setItem('darkMode', val)
})

const goBack = () => {
  router.push('/dashboard')
}

onMounted(() => {
  initDarkMode()
  loadDetails()
})
</script>

<style scoped>
/* === Base & Dark Mode === */
.ticket-container {
  max-width: 1700px;
  margin: auto;
  background: #f5f7fb;
  min-height: 100vh;
  transition: background 0.3s ease;
}
.ticket-container.dark-mode {
  background: #0a0f1a;
}
.glass-effect {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.2s;
}
.dark-glass {
  background: rgba(18, 25, 45, 0.85) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.05) !important;
}
.ticket-card {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
}
.toolbar-header {
  min-height: 90px;
}
.gradient-bg {
  background: linear-gradient(135deg, #1e3c72, #0f172a) !important;
  color: white !important;
}
.dark-mode .gradient-bg {
  background: linear-gradient(135deg, #0a1a2f, #03070f) !important;
}
.section-header {
  min-height: 70px;
  font-weight: 700;
}
.info-card, .comment-card {
  border-radius: 10px;
  transition: transform 0.2s ease, box-shadow 0.2s;
}
.info-card:hover, .comment-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
}
.stat-card {
  background: rgba(248, 250, 252, 0.7);
  border-radius: 20px;
  padding: 16px;
  text-align: center;
  backdrop-filter: blur(4px);
}
.dark-stat {
  background: rgba(30, 40, 60, 0.6) !important;
  color: #e2e8f0;
}
.stat-title {
  font-size: 12px;
  color: #64748b;
}
.dark-mode .stat-title {
  color: #94a3b8;
}
.stat-value {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}
.dark-mode .stat-value {
  color: white;
}
/* === LIGNES TRANSPARENTES pour tous les v-list-item === */
.v-list-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);  /* ligne très légère, transparente */
}
.v-list-item:last-child {
  border-bottom: none;
}
.dark-mode .v-list-item {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
/* === Fin lignes transparentes === */
.transparent-list {
  background: transparent;
}
.item-label {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}
.dark-mode .item-label {
  color: #94a3b8;
}
.item-value {
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
}
.dark-mode .item-value {
  color: #e2e8f0;
}
/* Timeline */
.timeline {
  position: relative;
}
.timeline-item {
  position: relative;
  padding-left: 40px;
  margin-bottom: 32px;
  transition: all 0.3s;
}
.timeline-item::before {
  content: '';
  position: absolute;
  left: 16px;
  top: 28px;
  bottom: -32px;
  width: 2px;
  background: linear-gradient(to bottom, #1976d2, #b0bec5);
}
.timeline-item:last-child::before {
  display: none;
}
.timeline-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  position: absolute;
  left: 10px;
  top: 18px;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2);
}
.activity-text {
  background: rgba(248, 250, 252, 0.8);
  border-radius: 18px;
  padding: 14px 18px;
  border-left: 4px solid #1976d2;
}
.dark-mode .activity-text {
  background: rgba(30, 41, 59, 0.7);
  color: #e2e8f0;
}
/* Animations */
.timeline-item-enter-active,
.timeline-item-leave-active {
  transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}
.timeline-item-enter-from {
  opacity: 0;
  transform: translateX(40px);
}
.timeline-item-leave-to {
  opacity: 0;
  transform: translateX(-40px);
}
/* Skeleton */
.skeleton-card {
  border-radius: 28px;
  padding: 24px;
}
.floating-avatar {
  transition: transform 0.2s;
}
.floating-avatar:hover {
  transform: scale(1.05);
}
.json-preview {
  background: #0f172a;
  color: #f8fafc;
  padding: 20px;
  border-radius: 16px;
  overflow: auto;
  font-size: 12px;
}
/* Responsive */
@media (max-width: 960px) {
  .ticket-container {
    padding: 0 !important;
  }
  .ticket-card {
    border-radius: 0;
  }
}
</style>