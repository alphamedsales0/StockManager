<template>
  <v-footer app color="grey-lighten-4" class="footer" v-if="showFooter">
    <v-container fluid class="px-4">
      <v-row align="center" justify="space-between">
        <v-col cols="12" md="auto" class="text-center text-md-left">
          <div class="text-caption text-grey-darken-1">
            <v-icon size="small" class="mr-1" color="primary">mdi-package-variant</v-icon>
            StockManager v1.0.0
          </div>
          <div class="text-caption text-grey">
            © {{ currentYear }} - Intelligente Lagerverwaltung
          </div>
        </v-col>
        
        <v-col cols="12" md="auto" class="text-center">
          <div class="d-flex justify-center gap-2">
            <v-btn
              variant="text"
              size="x-small"
              color="grey-darken-1"
              @click="openAbout"
              class="text-caption"
            >
              Über
            </v-btn>
            <v-btn
              variant="text"
              size="x-small"
              color="grey-darken-1"
              @click="openHelp"
              class="text-caption"
            >
              Hilfe
            </v-btn>
            <v-btn
              variant="text"
              size="x-small"
              color="grey-darken-1"
              @click="openPrivacy"
              class="text-caption"
            >
              Datenschutz
            </v-btn>
            <v-btn
              variant="text"
              size="x-small"
              color="grey-darken-1"
              @click="openTerms"
              class="text-caption"
            >
              Nutzungsbedingungen
            </v-btn>
          </div>
        </v-col>
        
        <v-col cols="12" md="auto" class="text-center text-md-right">
          <div class="d-flex justify-center justify-md-end align-center gap-2">
            <div class="text-caption text-grey">
              <v-icon size="small" color="success" class="mr-1">mdi-shield-check</v-icon>
              Sicheres System
            </div>
            <v-divider vertical class="mx-2" v-if="$vuetify.display.mdAndUp"></v-divider>
            <div class="text-caption text-grey">
              Letzte Sicherung: {{ lastBackupTime }}
            </div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </v-footer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const currentYear = ref(new Date().getFullYear())
const lastBackupTime = ref('Heute, 02:00')

// Footer auf bestimmten Seiten ausblenden
const showFooter = computed(() => {
  const hideOnRoutes = ['login']
  return !hideOnRoutes.includes(route.name)
})

const openAbout = () => {
  console.log('Über-Seite öffnen')
  // Navigation implementieren
}

const openHelp = () => {
  console.log('Hilfeseite öffnen')
  // Navigation implementieren
}

const openPrivacy = () => {
  console.log('Datenschutzrichtlinie öffnen')
  // Navigation implementieren
}

const openTerms = () => {
  console.log('Nutzungsbedingungen öffnen')
  // Navigation implementieren
}

onMounted(() => {
  // Backup-Zeit simulieren
  const now = new Date()
  lastBackupTime.value = `Heute, ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`
})
</script>

<style scoped>
.footer {
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  padding: 8px 0;
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 960px) {
  .footer {
    padding: 16px 0;
  }
  
  .footer .v-col {
    margin-bottom: 8px;
  }
  
  .footer .v-col:last-child {
    margin-bottom: 0;
  }
}
</style>