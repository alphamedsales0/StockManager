<template>
  <div>
    <v-row>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-file-sign</v-icon> Vertragsart</div>
          <div class="item-value">{{ data.contractType === 'einzelwartung' ? 'Einzelwartung' : 'Wartungsvertrag' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-checkbox-multiple</v-icon> Ausgewählte Optionen</div>
          <div class="item-value">
            <v-chip v-for="opt in data.options" :key="opt" class="mr-2 mb-2" size="small" color="primary" variant="outlined">{{ opt }}</v-chip>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
defineProps(['data'])
</script>