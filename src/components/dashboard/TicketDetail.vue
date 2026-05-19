<template>
  <v-container fluid class="ticket-container">
    <v-card v-if="loading" class="pa-4 text-center">
      <v-progress-circular indeterminate color="primary" />
      <div class="mt-2">Lade Ticket-Details...</div>
    </v-card>

    <v-card v-else-if="ticket" class="pa-3">
      <v-card-title class="text-h5 d-flex align-center py-1">
        Ticket: {{ ticket.reference_number }}
        <v-chip :color="statusColor" size="small" class="ml-2">{{ translateStatus(ticket.status) }}</v-chip>
      </v-card-title>
      <v-divider class="my-2" />

      <v-row dense no-gutters class="mt-2">
        <!-- Linke Spalte: Kundendaten -->
        <v-col cols="6" class="pr-md-4 left-col">
          <h4 class="text-subtitle-1 font-weight-bold mb-2">Kundendaten</h4>
          <v-sheet variant="outlined" rounded="md" class="pa-3">
            <v-list density="compact" class="py-0">
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-account-badge</v-icon></template>
                <strong>Anrede:</strong> {{ ticket.customer.anrede }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-card-account-details</v-icon></template>
                <strong>Name:</strong> {{ ticket.customer.firstname }} {{ ticket.customer.lastname }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-domain</v-icon></template>
                <strong>Firma:</strong> {{ ticket.customer.company || '-' }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-email</v-icon></template>
                <strong>E-Mail:</strong> {{ ticket.customer.email }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-phone</v-icon></template>
                <strong>Telefon:</strong> {{ ticket.customer.phone }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-home</v-icon></template>
                <strong>Straße:</strong> {{ ticket.customer.address }} {{ ticket.customer.hausnummer }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-post</v-icon></template>
                <strong>PLZ:</strong> {{ ticket.customer.plz }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-map-marker</v-icon></template>
                <strong>Ort:</strong> {{ ticket.customer.ort }}
              </v-list-item>
              <v-list-item class="py-1">
                <template v-slot:prepend><v-icon>mdi-flag</v-icon></template>
                <strong>Land:</strong> {{ ticket.customer.land }}
              </v-list-item>
            </v-list>
          </v-sheet>
        </v-col>

        <!-- Rechte Spalte: Formulardaten -->
        <v-col cols="6" class="pl-md-4">
          <h4 class="text-subtitle-1 font-weight-bold mb-2">Formulardaten</h4>
          <v-sheet variant="outlined" rounded="md" class="pa-3">
            <!-- SERVICE REQUEST -->
            <template v-if="ticket.form_type === 'service_request'">
              <v-list density="compact" class="py-0">
                <template v-for="(device, idx) in ticket.form_data.devices" :key="idx">
                  <v-list-item class="mt-2 py-1">
                    <template v-slot:prepend><v-icon>mdi-wrench</v-icon></template>
                    <strong>Gerät {{ idx+1 }}</strong>
                  </v-list-item>
                  <v-list-item class="pl-4 py-1">
                    <template v-slot:prepend><v-icon>mdi-factory</v-icon></template>
                    <strong>Hersteller:</strong> {{ device.manufacturer || '-' }}
                  </v-list-item>
                  <v-list-item class="pl-4 py-1">
                    <template v-slot:prepend><v-icon>mdi-chip</v-icon></template>
                    <strong>Modell:</strong> {{ device.model || '-' }}
                  </v-list-item>
                  <v-list-item class="pl-4 py-1">
                    <template v-slot:prepend><v-icon>mdi-numeric</v-icon></template>
                    <strong>Seriennummer:</strong> {{ device.serial || '-' }}
                  </v-list-item>
                  <v-list-item class="pl-4 py-1">
                    <template v-slot:prepend><v-icon>mdi-alert-circle</v-icon></template>
                    <strong>Fehlerbeschreibung:</strong> {{ device.errorDescription || 'Keine Angabe' }}
                  </v-list-item>
                </template>
                <v-list-item class="mt-2 py-1">
                  <template v-slot:prepend><v-icon>mdi-text-box</v-icon></template>
                  <strong>Zusatzinformationen:</strong> {{ ticket.form_data.additional || '-' }}
                </v-list-item>
              </v-list>
            </template>

            <!-- MAINTENANCE -->
            <template v-else-if="ticket.form_type === 'maintenance'">
              <v-list density="compact" class="py-0">
                <v-list-item class="py-1">
                  <template v-slot:prepend><v-icon>mdi-file-sign</v-icon></template>
                  <strong>Vertragsart:</strong> {{ ticket.form_data.contractType === 'einzelwartung' ? 'Einzelwartung' : 'Wartungsvertrag' }}
                </v-list-item>
                <v-list-item class="mt-2 py-1">
                  <template v-slot:prepend><v-icon>mdi-list-box</v-icon></template>
                  <strong>Ausgewählte Optionen:</strong>
                </v-list-item>
                <v-list-item v-for="(opt, idx) in ticket.form_data.options" :key="idx" class="pl-4 py-1">
                  <template v-slot:prepend><v-icon>mdi-check</v-icon></template>
                  {{ opt }}
                </v-list-item>
              </v-list>
            </template>

            <!-- INSTALLATION -->
            <template v-else-if="ticket.form_type === 'installation'">
              <v-list density="compact" class="py-0">
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-floor-plan</v-icon></template><strong>Fußboden:</strong> {{ ticket.form_data.floor || '-' }}</v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-door</v-icon></template><strong>Türen:</strong> {{ ticket.form_data.doors || '-' }}</v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-elevator</v-icon></template><strong>Aufzug vorhanden:</strong> {{ ticket.form_data.elevator ? 'Ja' : 'Nein' }}</v-list-item>
                <template v-if="ticket.form_data.elevator">
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-arrow-up-bold</v-icon></template><strong>Zugang Stockwerk:</strong> {{ ticket.form_data.elevatorAccess || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-arrow-expand-horizontal</v-icon></template><strong>Türe Maße (m):</strong> {{ ticket.form_data.elevatorDoorSize || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-arrow-expand-vertical</v-icon></template><strong>Innentiefe (m):</strong> {{ ticket.form_data.elevatorInsideSize || '-' }}</v-list-item>
                </template>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-stairs</v-icon></template><strong>Treppe vorhanden:</strong> {{ ticket.form_data.stairs ? 'Ja' : 'Nein' }}</v-list-item>
                <template v-if="ticket.form_data.stairs">
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-ruler</v-icon></template><strong>Treppenbreite (cm):</strong> {{ ticket.form_data.stairWidth || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-angle-right</v-icon></template><strong>Übers Eck:</strong> {{ ticket.form_data.cornerStair ? 'Ja' : 'Nein' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-arrow-up</v-icon></template><strong>Ausstieg Stockwerk:</strong> {{ ticket.form_data.stairExit || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-numeric</v-icon></template><strong>Stufenanzahl:</strong> {{ ticket.form_data.stairSteps || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-arrow-right-bold</v-icon></template><strong>Verlauf nach Stufen:</strong> {{ ticket.form_data.stairAfterSteps || '-' }}</v-list-item>
                  <v-list-item class="pl-4 py-1"><template v-slot:prepend><v-icon>mdi-hand-extended</v-icon></template><strong>Geländer abnehmbar:</strong> {{ ticket.form_data.railingRemovable ? 'Ja' : 'Nein' }}</v-list-item>
                </template>
              </v-list>
            </template>

            <!-- REPARATUR -->
            <template v-else-if="ticket.form_type === 'reparatur'">
              <v-list density="compact" class="py-0">
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-devices</v-icon></template><strong>Gerät / Produkt:</strong> {{ ticket.form_data.device || '-' }}</v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-alert-outline</v-icon></template><strong>Problembeschreibung:</strong> {{ ticket.form_data.problem || '-' }}</v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-cog</v-icon></template><strong>Service-Typ:</strong> {{ ticket.form_data.service || '-' }}</v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-speedometer</v-icon></template><strong>Dringlichkeit:</strong> 
                  <v-chip :color="getUrgencyColor(ticket.form_data.urgency)" size="small" label class="ml-2">{{ translateUrgency(ticket.form_data.urgency) }}</v-chip>
                </v-list-item>
                <v-list-item class="py-1"><template v-slot:prepend><v-icon>mdi-calendar-clock</v-icon></template><strong>Eingangsdatum:</strong> {{ formatDate(ticket.form_data.submissionDate) }}</v-list-item>
              </v-list>
            </template>

            <!-- FALLBACK -->
            <template v-else>
              <pre class="text-caption">{{ JSON.stringify(ticket.form_data, null, 2) }}</pre>
            </template>
          </v-sheet>
        </v-col>
      </v-row>

      <!-- Notizen -->
      <v-row v-if="ticket.notes" dense class="mt-3">
        <v-col cols="12" class="py-0">
          <h4 class="text-subtitle-1 font-weight-bold mb-2">Notizen</h4>
          <v-sheet variant="outlined" rounded="md" class="pa-3">
            {{ ticket.notes }}
          </v-sheet>
        </v-col>
      </v-row>

      <v-card-actions class="justify-end mt-3 px-3 pb-2">
        <v-btn variant="outlined" @click="goBack">Zurück</v-btn>
      </v-card-actions>
    </v-card>

    <v-alert v-else type="error" class="ma-4">Ticket nicht gefunden.</v-alert>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const ticket = ref(null)
const loading = ref(true)

const statusColor = computed(() => {
  const map = {
    pending: 'warning',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'error'
  }
  return map[ticket.value?.status] || 'grey'
})

const translateStatus = (status) => {
  const map = {
    pending: 'In Bearbeitung',
    in_progress: 'In Prüfung',
    completed: 'Abgeschlossen',
    cancelled: 'Storniert'
  }
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

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadDetails = async () => {
  const id = route.params.id
  if (!id) {
    loading.value = false
    return
  }
  try {
    const response = await fetch(`/api/get_ticket_details.php?id=${id}`)
    const data = await response.json()
    if (data.success) {
      ticket.value = data.ticket
    } else {
      console.error('API error:', data.error)
    }
  } catch (error) {
    console.error('Failed to load ticket details:', error)
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/dashboard')
}

onMounted(() => loadDetails())
</script>

<style scoped>
.ticket-container {
  max-width: 1600px;
  margin: 0 auto;
}

/* Horizontale Spalten – immer nebeneinander */
.v-row {
  flex-wrap: nowrap !important;
}
.v-col {
  flex: 1 1 50% !important;
  max-width: 50% !important;
}

/* Vertikale Trennlinie (rechter Rand der linken Spalte) */
@media (min-width: 960px) {
  .left-col {
    border-right: 1px solid #e0e0e0;
  }
}

/* Leicht vergrößerte, aber noch kompakte Listen-Einträge */
.v-list-item {
  min-height: 36px !important;
  padding: 4px 0 !important;
}
.v-list-item__prepend {
  margin-right: 12px !important;
}
.v-card-text {
  padding: 12px !important;
}
.v-card-title {
  font-size: 1.25rem;
}
</style>