<template>
  <div class="d-flex align-center gap-1 flex-wrap action-buttons">
    <v-btn
      color="primary"
      @click="$emit('new-product')"
      size="small"
      variant="tonal"
    >
      <template v-slot:prepend>
        <v-icon size="small">mdi-plus</v-icon>
      </template>
      <span class="d-none d-sm-inline">Neu</span>
    </v-btn>

    <v-btn
      variant="outlined"
      @click="$emit('export-report')"
      size="small"
    >
      <template v-slot:prepend>
        <v-icon size="small">mdi-export</v-icon>
      </template>
      <span class="d-none d-sm-inline">Rapport</span>
    </v-btn>

    <div class="refresh-icon" @click="$emit('refresh')">
      <v-icon :class="{ spinning: loading }">mdi-refresh</v-icon>
    </div>
  </div>
</template>

<script setup>
defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['new-product', 'export-report', 'refresh'])
</script>

<style scoped>
.gap-1 {
  gap: 4px;
}

/* Boutons d'action */
.action-buttons .v-btn {
  min-width: 110px !important;
  height: 36px;
  padding: 0 12px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.2) !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease;
}
.action-buttons .v-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
.action-buttons .v-btn--variant-outlined {
  border: 1.5px solid rgba(0, 0, 0, 0.3) !important;
}

/* Refresh icon */
.refresh-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: 1.5px solid #9e9e9e;
  cursor: pointer;
  transition: all 0.2s ease;
}
.refresh-icon:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-color: #616161;
}
.spinning {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>