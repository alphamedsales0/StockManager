<template>
  <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-account-group</v-icon>
      Kundendaten
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-row>
        <v-col v-for="(item, idx) in fields" :key="idx" cols="12" sm="6" lg="12">
          <div class="info-item">
            <div class="item-label">
              <v-icon size="16" class="mr-1">{{ item.icon }}</v-icon>
              {{ item.label }}
            </div>
            <div class="item-value">{{ item.value }}</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
defineProps({
  fields: Array,
  darkMode: Boolean,
})
</script>

<style scoped>
.info-item {
  background: rgba(248, 250, 252, 0.6);
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 8px;
}
.dark-mode .info-item {
  background: rgba(30, 41, 59, 0.5);
}
.item-label {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 4px;
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
</style>