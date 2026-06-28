<template>
  <div>
    <v-row>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-tools</v-icon> Gerät / Produkt</div>
          <div class="item-value">{{ data.device || '-' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-wrench</v-icon> Service-Typ</div>
          <div class="item-value">{{ data.service || '-' }}</div>
        </div>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-speedometer</v-icon> Dringlichkeit</div>
          <div class="item-value">
            <v-chip :color="getUrgencyColor(data.urgency)" size="small">{{ translateUrgency(data.urgency) }}</v-chip>
          </div>
        </div>
      </v-col>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-calendar</v-icon> Eingangsdatum</div>
          <div class="item-value">{{ formatDate(data.submissionDate) }}</div>
        </div>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-message-alert</v-icon> Problembeschreibung</div>
          <div class="item-value">{{ data.problem || '-' }}</div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
const props = defineProps(['data'])

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
  return new Date(dateString).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>