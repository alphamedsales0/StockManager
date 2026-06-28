<!-- components/ticket/FormData/FormDataRenderer.vue -->
<template>
  <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-file-document-outline</v-icon>
      Formulardaten
    </v-card-title>
    <v-divider />
    <v-card-text>
      <component :is="component" :data="ticket.form_data" :ticket="ticket" />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'

const props = defineProps({
  ticket: Object,
  darkMode: Boolean,
})

const component = computed(() => {
  const type = props.ticket?.form_type
  switch (type) {
    case 'service_request': return defineAsyncComponent(() => import('./ServiceRequestForm.vue'))
    case 'maintenance': return defineAsyncComponent(() => import('./MaintenanceForm.vue'))
    case 'ersatzteile': return defineAsyncComponent(() => import('./ErsatzteileForm.vue'))
    case 'installation': return defineAsyncComponent(() => import('./InstallationForm.vue'))
    case 'reparatur': return defineAsyncComponent(() => import('./ReparaturForm.vue'))
    case 'angebot': return defineAsyncComponent(() => import('./AngebotForm.vue'))
    default: return defineAsyncComponent(() => import('./FallbackForm.vue'))
  }
})
</script>