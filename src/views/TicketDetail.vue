<template>
  <v-container fluid :class="['ticket-container', { 'dark-mode': darkMode }]" class="pa-4">
    <!-- Skeleton -->
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
        <TicketHeader
          :ticket="ticket"
          :dark-mode="darkMode"
          :entry-date-label="entryDateLabel"
          :entry-date-formatted="entryDateFormatted"
          @toggle-dark-mode="toggleDarkMode"
          @go-back="goBack"
        />

        <v-container fluid class="pa-5">
          <v-row>
            <!-- Linke Spalte -->
            <v-col cols="12" lg="5">
              <CustomerInfo :fields="customerFields" :dark-mode="darkMode" />
              <Statistics :ticket="ticket" :activities="activities" :dark-mode="darkMode" />
              <TicketActions
                :ticket="ticket"
                :dark-mode="darkMode"
                :assignee-options="assigneeOptions"
                :loading-assignees="loadingAssignees"
                v-model:selected-status="selectedStatus"
                :status-options="statusOptions"
                :saving-due-date="savingDueDate"
                :updating-assignee="updatingAssignee"
                :updating-status="updatingStatus"
                @save-due-date="handleSaveDueDate"
                @update-assignee="handleUpdateAssignee"
                @update-status="handleUpdateStatus"
              />
              <TimeTracking
                :ticket="ticket"
                :time-entries="timeEntries"
                :dark-mode="darkMode"
                @add-time="handleAddTime"
              />
            </v-col>

            <!-- Rechte Spalte -->
            <v-col cols="12" lg="7">
              <FormDataRenderer :ticket="ticket" :dark-mode="darkMode" />
              <TicketInfo :ticket="ticket" :dark-mode="darkMode" />
              <Attachments
                :ticket="ticket"
                :attachments="attachments"
                :dark-mode="darkMode"
                @upload="handleUploadFiles"
                @download="downloadAttachment"
              />
              <InternalNotes
                :ticket="ticket"
                :notes="internalNotes"
                :dark-mode="darkMode"
                @add-note="handleAddInternalNote"
              />
              <CommentsAndActivities
                :ticket="ticket"
                :activities="activities"
                :dark-mode="darkMode"
                @add-comment="handleAddComment"
              />
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

    <!-- GLOBALE SNACKBAR -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top end">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

// Composables
import { useDarkMode } from '../composables/useDarkMode'
import { useTicket } from '../composables/useTicket'
import { useComments } from '../composables/useComments'
import { useAttachments } from '../composables/useAttachments'
import { useInternalNotes } from '../composables/useInternalNotes'
import { useTimeEntries } from '../composables/useTimeEntries'
import { useAssignees } from '../composables/useAssignees'

// Komponenten
import TicketHeader from '../components/ticket/TicketHeader.vue'
import CustomerInfo from '../components/ticket/CustomerInfo.vue'
import Statistics from '../components/ticket/Statistics.vue'
import TicketActions from '../components/ticket/TicketActions.vue'
import TimeTracking from '../components/ticket/TimeTracking.vue'
import FormDataRenderer from '../components/ticket/FormData/FormDataRenderer.vue'
import TicketInfo from '../components/ticket/TicketInfo.vue'
import Attachments from '../components/ticket/Attachments.vue'
import InternalNotes from '../components/ticket/InternalNotes.vue'
import CommentsAndActivities from '../components/ticket/CommentsAndActivities.vue'

const router = useRouter()

// ----- Composables -----
const { darkMode, toggleDarkMode } = useDarkMode()
const { ticket, loading, selectedStatus, currentSource,
        loadTicket, updateStatus, updateAssignee, saveDueDate } = useTicket()
const { activities, loadComments, addComment } = useComments()
const { attachments, loadAttachments, uploadFiles, downloadAttachment } = useAttachments()
const { internalNotes, loadInternalNotes, addInternalNote } = useInternalNotes()
const { timeEntries, loadTimeEntries, addTimeEntry } = useTimeEntries()
const { assigneeOptions, loadingAssignees, loadAssignees } = useAssignees()

// Lokale UI‑Ladeindikatoren (für Snackbar und Button‑Loading)
const savingDueDate = ref(false)
const updatingAssignee = ref(false)
const updatingStatus = ref(false)
const uploading = ref(false)
const addingNote = ref(false)
const addingComment = ref(false)

// Zentrale Snackbar
const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Status‑Optionen
const statusOptions = [
  { label: 'In Bearbeitung', value: 'pending' },
  { label: 'In Prüfung', value: 'in_progress' },
  { label: 'Abgeschlossen', value: 'completed' },
  { label: 'Storniert', value: 'cancelled' }
]

// ----- Computed für Kundendaten und Header -----
const customerFields = computed(() => {
  if (!ticket.value) return []
  const c = ticket.value.customer || {}
  if (ticket.value.source === 'angebot') {
    return [
      { label: 'Vorname', value: c.firstname || '-', icon: 'mdi-account' },
      { label: 'Nachname', value: c.lastname || '-', icon: 'mdi-card-account-details' },
      { label: 'Firma', value: c.company || '-', icon: 'mdi-domain' },
      { label: 'E-Mail', value: c.email || '-', icon: 'mdi-email' },
      { label: 'Telefon', value: c.phone || '-', icon: 'mdi-phone' }
    ]
  } else {
    return [
      { label: 'Anrede', value: c.anrede || '-', icon: 'mdi-account' },
      { label: 'Name', value: `${c.firstname || ''} ${c.lastname || ''}`.trim() || '-', icon: 'mdi-card-account-details' },
      { label: 'Firma', value: c.company || '-', icon: 'mdi-domain' },
      { label: 'E-Mail', value: c.email || '-', icon: 'mdi-email' },
      { label: 'Telefon', value: c.phone || '-', icon: 'mdi-phone' },
      { label: 'Adresse', value: `${c.address || ''} ${c.hausnummer || ''}`.trim() || '-', icon: 'mdi-home' },
      { label: 'Ort', value: `${c.plz || ''} ${c.ort || ''}`.trim() || '-', icon: 'mdi-map-marker' }
    ]
  }
})

const entryDateFormatted = computed(() => {
  if (!ticket.value) return ''
  const submissionDate = ticket.value.form_data?.submissionDate
  if (submissionDate) return new Date(submissionDate).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
  return new Date(ticket.value.created_at).toLocaleString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
})
const entryDateLabel = computed(() => {
  return ticket.value?.form_data?.submissionDate ? 'Eingangsdatum' : 'Erstellt am'
})

// ----- Polling (Kommentare automatisch aktualisieren) -----
const POLLING_INTERVAL_MS = 30000  // 30 Sekunden
const refreshInterval = ref(null)

const startPolling = () => {
  if (refreshInterval.value) clearInterval(refreshInterval.value)
  refreshInterval.value = setInterval(() => {
    if (ticket.value) {
      loadComments(ticket.value.id)
      // Optional: Weitere Daten nur bei Bedarf aktivieren
      // loadAttachments(ticket.value.id)
      // loadInternalNotes(ticket.value.id)
      // loadTimeEntries(ticket.value.id)
    }
  }, POLLING_INTERVAL_MS)
}

const stopPolling = () => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
}

// Visibility‑API – Polling pausieren bei Tab‑Inaktivität
const handleVisibilityChange = () => {
  if (document.hidden) {
    // Tab nicht sichtbar → Polling stoppen
    stopPolling()
  } else {
    // Tab wieder sichtbar → Polling neu starten und sofort aktualisieren
    if (ticket.value) {
      startPolling()
      loadComments(ticket.value.id) // Sofort neueste Daten holen
    }
  }
}

// ----- Event‑Handler (mit Snackbar und Loading‑States) -----
const goBack = () => router.push('/dashboard')

const handleSaveDueDate = async (dueDate) => {
  savingDueDate.value = true
  try {
    await saveDueDate(dueDate)
    snackbar.value = { show: true, text: '✅ Fälligkeitsdatum aktualisiert', color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  } finally {
    savingDueDate.value = false
  }
}

const handleUpdateAssignee = async (assigneeId) => {
  updatingAssignee.value = true
  try {
    const selectedUser = assigneeOptions.value.find(u => u.id === assigneeId)
    if (!selectedUser) throw new Error('Mitarbeiter nicht gefunden')
    await updateAssignee(selectedUser.name, currentSource.value, 'Admin')
    snackbar.value = { show: true, text: `✅ Bearbeiter zugewiesen: ${selectedUser.name}`, color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  } finally {
    updatingAssignee.value = false
  }
}

const handleUpdateStatus = async ({ status, notify }) => {
  updatingStatus.value = true
  try {
    await updateStatus(status, currentSource.value, 'Admin', notify)
    const statusLabel = statusOptions.find(s => s.value === status)?.label || status
    snackbar.value = { show: true, text: `✅ Status geändert zu: ${statusLabel}`, color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  } finally {
    updatingStatus.value = false
  }
}

const handleAddTime = async ({ hours, description }) => {
  try {
    await addTimeEntry(ticket.value.id, hours, description, 'Admin')
    await loadTimeEntries(ticket.value.id)
    snackbar.value = { show: true, text: '✅ Zeiteintrag hinzugefügt', color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  }
}

const handleUploadFiles = async (files) => {
  uploading.value = true
  try {
    await uploadFiles(ticket.value.id, files, 'Admin')
    await loadAttachments(ticket.value.id)
    snackbar.value = { show: true, text: `✅ ${files.length} Datei(en) hochgeladen`, color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Upload fehlgeschlagen: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  } finally {
    uploading.value = false
  }
}

const handleAddInternalNote = async (note) => {
  addingNote.value = true
  try {
    await addInternalNote(ticket.value.id, note, 'Admin')
    await loadInternalNotes(ticket.value.id)
    snackbar.value = { show: true, text: '✅ Interne Notiz gespeichert', color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message || 'Unbekannter Fehler'}`, color: 'error' }
  } finally {
    addingNote.value = false
  }
}

const handleAddComment = async (text) => {
  addingComment.value = true
  try {
    const success = await addComment(ticket.value.id, text, 'Admin', currentSource.value)
    if (success) {
      snackbar.value = { show: true, text: '✅ Kommentar hinzugefügt', color: 'success' }
      // Kein explizites loadComments nötig, da addComment bereits optimistisch einfügt
    } else {
      throw new Error('Kommentar konnte nicht gespeichert werden')
    }
  } catch (error) {
    snackbar.value = { show: true, text: `❌ Fehler: ${error.message}`, color: 'error' }
    // Bei Fehler können wir zur Sicherheit neu laden, um konsistent zu bleiben
    loadComments(ticket.value.id)
  } finally {
    addingComment.value = false
  }
}

// ----- Initialisierung -----
onMounted(async () => {
  await loadTicket()
  if (ticket.value) {
    await Promise.all([
      loadComments(ticket.value.id),
      loadAttachments(ticket.value.id),
      loadInternalNotes(ticket.value.id),
      loadTimeEntries(ticket.value.id),
      loadAssignees()
    ])

    // Polling starten (nur für Kommentare)
    startPolling()
    // Visibility‑Listener für Tab‑Inaktivität
    document.addEventListener('visibilitychange', handleVisibilityChange)
  }
})

// ----- Aufräumen beim Verlassen -----
onBeforeUnmount(() => {
  stopPolling()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>

<style scoped>
/* Alle Stile aus dem Original – unverändert übernehmen */
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

.info-item {
  background: rgba(248, 250, 252, 0.6);
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 8px;
  height: 100%;
}
.dark-mode .info-item {
  background: rgba(30, 41, 59, 0.5);
}
.info-item .item-label {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 4px;
}
.dark-mode .info-item .item-label {
  color: #94a3b8;
}
.info-item .item-value {
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
}
.dark-mode .info-item .item-value {
  color: #e2e8f0;
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
.v-list-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.v-list-item:last-child {
  border-bottom: none;
}
.dark-mode .v-list-item {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.transparent-list {
  background: transparent;
}
.item-label {
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
}
.item-value {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.dark-mode .item-label {
  color: #94a3b8;
}
.dark-mode .item-value {
  color: #e2e8f0;
}
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
.overdue-field input {
  border-color: #ff5252 !important;
}
.priority-chip,
.status-chip,
.priority-chip .v-icon,
.status-chip .v-icon {
  color: white !important;
}
@media (max-width: 960px) {
  .ticket-container {
    padding: 0 !important;
  }
  .ticket-card {
    border-radius: 0;
  }
}
</style>