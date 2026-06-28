<template>
  <v-card v-if="ticket" class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-ticket-cog-outline</v-icon>
      Ticket Aktionen
    </v-card-title>
    <v-divider />
    <v-card-text>
      <!-- Fälligkeitsdatum -->
      <div class="mb-4">
        <div class="d-flex align-center mb-2">
          <v-icon color="primary" class="mr-2">mdi-calendar-clock</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Fälligkeitsdatum</span>
        </div>
        <v-row align="center">
          <v-col cols="12" md="7">
            <v-text-field
              v-model="localDueDate"
              type="date"
              label="Fällig am"
              variant="outlined"
              density="comfortable"
              hide-details
              :class="{ 'overdue-field': isOverdue }"
            />
          </v-col>
          <v-col cols="12" md="5">
            <v-btn color="primary" block @click="handleSaveDueDate" :loading="savingDueDate">Speichern</v-btn>
          </v-col>
        </v-row>
        <div v-if="isOverdue" class="text-error mt-2">
          <v-icon small>mdi-alert-circle</v-icon> Dieses Ticket ist überfällig!
        </div>
      </div>

      <v-divider class="my-4" />

      <!-- Bearbeiter zuweisen -->
      <div class="mb-4">
        <div class="d-flex align-center mb-2">
          <v-icon color="primary" class="mr-2">mdi-account-multiple</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Bearbeiter zuweisen</span>
        </div>
        <v-row align="center">
          <v-col cols="12" md="8">
            <v-select
              v-model="selectedAssignee"
              :items="assigneeOptions"
              item-title="name"
              item-value="id"
              label="Mitarbeiter auswählen"
              variant="outlined"
              density="comfortable"
              :loading="loadingAssignees"
              no-data-text="Keine Mitarbeiter gefunden"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-btn color="primary" block @click="handleUpdateAssignee" :loading="updatingAssignee">Zuweisen</v-btn>
          </v-col>
        </v-row>
        <div class="mt-2 text-caption">Aktuell: {{ ticket.assigned_to || 'Niemand' }}</div>
      </div>

      <v-divider class="my-4" />

      <!-- Status ändern + Kunden-Benachrichtigung -->
      <div>
        <div class="d-flex align-center mb-2">
          <v-icon color="primary" class="mr-2">mdi-sync</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Status ändern</span>
        </div>
        <v-row align="center">
          <v-col cols="12" md="8">
            <v-select
              v-model="localSelectedStatus"
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
            <v-btn color="primary" block @click="handleUpdateStatus" :loading="updatingStatus">Speichern</v-btn>
          </v-col>
        </v-row>
        <v-switch
          v-model="notifyCustomer"
          label="Kunden per E-Mail benachrichtigen"
          class="mt-3"
          hide-details
          color="success"
        />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  ticket: Object,
  darkMode: Boolean,
  assigneeOptions: Array,
  loadingAssignees: Boolean,
  selectedStatus: String,
  statusOptions: Array,
  savingDueDate: Boolean,
  updatingAssignee: Boolean,
  updatingStatus: Boolean,
})

const emit = defineEmits([
  'update:selectedStatus',
  'saveDueDate',
  'updateAssignee',
  'updateStatus',
])

const localSelectedStatus = computed({
  get: () => props.selectedStatus,
  set: (val) => emit('update:selectedStatus', val),
})

const localDueDate = ref(props.ticket?.due_date || '')
const selectedAssignee = ref(null)
const notifyCustomer = ref(false)

watch(() => props.ticket, (newTicket) => {
  if (newTicket) localDueDate.value = newTicket.due_date || ''
}, { immediate: true })

const isOverdue = computed(() => {
  if (!localDueDate.value) return false
  const today = new Date().toISOString().slice(0,10)
  return localDueDate.value < today && props.ticket?.status !== 'completed'
})

const handleSaveDueDate = () => emit('saveDueDate', localDueDate.value)
const handleUpdateAssignee = () => {
  if (!selectedAssignee.value) return alert('Bitte wählen Sie einen Bearbeiter aus.')
  emit('updateAssignee', selectedAssignee.value)
}
const handleUpdateStatus = () => {
  if (localSelectedStatus.value === props.ticket?.status) return
  emit('updateStatus', { status: localSelectedStatus.value, notify: notifyCustomer.value })
}
</script>

<style scoped>
.overdue-field input {
  border-color: #ff5252 !important;
}
</style>