<template>
  <div>
    <template v-for="(device, idx) in data.devices" :key="idx">
      <!-- Geräte-Titel über die volle Breite -->
      <v-row v-if="idx === 0 || idx > 0" class="mt-3 mb-2">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-bold" v-if="idx === 0">Gerät {{ idx+1 }}</div>
          <div class="text-subtitle-1 font-weight-bold mt-3" v-else>Gerät {{ idx+1 }}</div>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" sm="6">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-factory</v-icon> Hersteller</div>
            <div class="item-value">{{ device.manufacturer || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-cellphone</v-icon> Modell</div>
            <div class="item-value">{{ device.model || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-counter</v-icon> Seriennummer</div>
            <div class="item-value">{{ device.serial || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-alert-circle</v-icon> Fehlerbeschreibung</div>
            <div class="item-value">{{ device.errorDescription || 'Keine Angabe' }}</div>
          </div>
        </v-col>
      </v-row>
    </template>
    <!-- Zusatzinformationen -->
    <v-row>
      <v-col cols="12">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-text</v-icon> Zusatzinformationen</div>
          <div class="item-value">{{ data.additional || '-' }}</div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
defineProps(['data'])
</script>

<style scoped>
.info-item {
  background: rgba(248, 250, 252, 0.6);
  padding: 8px 12px;
  border-radius: 8px;
  margin-bottom: 6px;
}
.dark-mode .info-item {
  background: rgba(30, 41, 59, 0.5);
}
.item-label {
  font-size: 12px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 4px;
}
.dark-mode .item-label {
  color: #94a3b8;
}
.item-value {
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
}
.dark-mode .item-value {
  color: #e2e8f0;
}
</style>