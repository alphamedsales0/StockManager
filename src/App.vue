<template>
  <v-app>
    <Sidebar v-if="authStore.isLoggedIn" />
    <AppHeader v-if="authStore.isLoggedIn" />
    
    <v-main>
      <router-view />
    </v-main>
    
    <NotificationsPanel v-if="authStore.isLoggedIn" />
    <AppFooter v-if="authStore.isLoggedIn && showFooter" />

    <!-- Globales Berechtigungs-Modal - modernisiert -->
    <v-dialog v-model="permissionStore.showPermissionDialog" max-width="420" persistent>
      <v-card class="permission-dialog" rounded="lg" elevation="12">
        <v-card-item class="pa-6">
          <div class="d-flex flex-column align-center text-center">
            <!-- Icon mit Kreis-Hintergrund -->
            <v-avatar size="72" color="error" class="mb-4">
              <v-icon size="40" color="white">mdi-lock</v-icon>
            </v-avatar>
            
            <v-card-title class="text-h5 font-weight-bold mb-2">
              Zugriff verweigert
            </v-card-title>
            
            <v-card-text class="text-body-1 text-medium-emphasis">
              Sie haben keine Berechtigung, auf diese Funktion zuzugreifen.
              <br>
              <span class="text-caption text-grey">Bitte wenden Sie sich an Ihren Administrator.</span>
            </v-card-text>
          </div>
        </v-card-item>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4 justify-center">
          <v-btn
            color="primary"
            variant="flat"
            rounded="pill"
            size="large"
            @click="permissionStore.closeDialog"
            class="px-8"
          >
            Verstanden
            <v-icon end>mdi-check</v-icon>
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { usePermissionStore } from './stores/permission'
import Sidebar from './components/layout/Sidebar.vue'
import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import NotificationsPanel from './components/common/NotificationsPanel.vue'

const route = useRoute()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()

const showFooter = computed(() => {
  const hideOnRoutes = ['login']
  return !hideOnRoutes.includes(route.name)
})

onMounted(async () => {
  await authStore.initialize()
})
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
  font-family: 'Roboto', sans-serif;
  background-color: #f5f5f5;
}
.v-application, .v-app-bar {
  padding-left: 0 !important;
  margin-left: 0 !important;
}

/* Modernes Dialog-Design */
.permission-dialog {
  background: white;
  border-radius: 16px !important;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
}
.permission-dialog .v-card-item {
  padding-bottom: 8px !important;
}
.permission-dialog .v-card-actions {
  background: #f8f9fa;
}
</style>