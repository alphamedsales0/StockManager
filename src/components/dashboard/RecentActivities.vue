<template>
  <v-card rounded="lg" elevation="3" class="pa-0" style="height: 100%;">
    
    <!-- Header modernisé -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3">
      <span class="text-h6 font-weight-semibold">
        <v-icon start size="20">mdi-history</v-icon>
        Letzte Aktivitäten
      </span>

      <div class="d-flex align-center ga-1">
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-cog-outline</v-icon>
        </v-btn>
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-fullscreen</v-icon>
        </v-btn>
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-refresh</v-icon>
        </v-btn>
        <v-btn icon variant="text" size="small" color="grey-darken-1">
          <v-icon size="20">mdi-delete-outline</v-icon>
        </v-btn>
      </div>
    </v-card-title>

    <!-- Timeline améliorée -->
    <div class="timeline-container pa-4">
      <div
        v-for="(activity, idx) in recentActivities"
        :key="idx"
        class="timeline-item"
      >
        <!-- Ligne verticale (sauf pour le dernier) -->
        <div v-if="idx !== recentActivities.length - 1" class="timeline-line"></div>

        <!-- Cercle avec icône agrandi -->
        <div class="timeline-dot" :class="activity.color">
          <v-icon size="24" color="white">{{ activity.icon }}</v-icon>
        </div>

        <!-- Contenu avec carte -->
        <div class="timeline-content">
          <div class="activity-card">
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center">
                <span class="text-subtitle-2 font-weight-medium">
                  {{ activity.title }}
                </span>
                <v-chip
                  v-if="activity.new"
                  size="x-small"
                  color="red"
                  class="ml-2"
                  label
                  variant="flat"
                >
                  NEW
                </v-chip>
              </div>
              <v-icon size="18" color="grey-lighten-1" class="ml-2">mdi-dots-vertical</v-icon>
            </div>
            <div class="d-flex align-center mt-1">
              <v-icon size="14" color="grey" class="mr-1">mdi-clock-outline</v-icon>
              <span class="text-caption text-grey">{{ activity.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </v-card>
</template>

<script setup>
import { ref } from 'vue'

const recentActivities = ref([
  { icon: 'mdi-plus-box', title: 'Neues Produkt hinzugefügt', time: 'Vor 2 Stunden', color: 'success', new: true },
  { icon: 'mdi-file-document', title: 'Angebot #REF-001 erstellt', time: 'Vor 5 Stunden', color: 'info', new: false },
  { icon: 'mdi-account-plus', title: 'Neuer Kunde registriert', time: 'Gestern, 14:30', color: 'primary', new: false },
  { icon: 'mdi-package-variant', title: 'Bestand aktualisiert', time: 'Gestern, 09:15', color: 'warning', new: false }
])
</script>

<style scoped>
.timeline-container {
  position: relative;
  max-height: 400px;
  overflow-y: auto;
}

.timeline-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  margin-bottom: 28px;
}

/* Ligne verticale améliorée */
.timeline-line {
  position: absolute;
  left: 18px; /* centré sur le dot (36px/2 = 18) */
  top: 36px;
  bottom: -28px;
  width: 2px;
  background: linear-gradient(to bottom, #bdbdbd, #e0e0e0);
  border-radius: 2px;
}

/* Cercle agrandi (36px) */
.timeline-dot {
  position: relative;
  z-index: 2;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.timeline-dot:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* Couleurs modernisées */
.timeline-dot.success { background: linear-gradient(135deg, #43a047, #2e7d32); }
.timeline-dot.info { background: linear-gradient(135deg, #1e88e5, #1565c0); }
.timeline-dot.primary { background: linear-gradient(135deg, #5c6bc0, #3949ab); }
.timeline-dot.warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }

/* Carte de contenu */
.timeline-content {
  margin-left: 16px;
  flex: 1;
}

.activity-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 10px 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
  border: 1px solid #f0f0f0;
}

.activity-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateX(4px);
  border-color: #e0e0e0;
}

.text-grey {
  color: #6b6b6b;
}

/* Scrollbar personnalisée */
.timeline-container::-webkit-scrollbar {
  width: 5px;
}

.timeline-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>