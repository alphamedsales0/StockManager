<template>
  <v-app>
    <!-- Layout nur für eingeloggte Nutzer -->
    <template v-if="authStore.isLoggedIn">
      <Sidebar />
      <AppHeader />
    </template>

    <v-main>
      <router-view v-slot="{ Component, route: currentRoute }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="currentRoute.path" />
        </transition>
      </router-view>
    </v-main>

    <template v-if="authStore.isLoggedIn">
      <NotificationsPanel />
      <AppFooter v-if="showFooter" />
    </template>

    <!-- Dialog "Zugriff verweigert" -->
    <v-dialog
      v-model="permissionStore.showPermissionDialog"
      max-width="420"
      persistent
    >
      <v-card class="permission-dialog" rounded="lg" elevation="12">
        <v-card-item class="pa-6">
          <div class="d-flex flex-column align-center text-center">
            <v-avatar size="72" color="error" class="mb-4">
              <v-icon size="40" color="white">mdi-lock</v-icon>
            </v-avatar>

            <v-card-title class="text-h5 font-weight-bold mb-2">
              Zugriff verweigert
            </v-card-title>

            <v-card-text class="text-body-1 text-medium-emphasis">
              Sie haben keine Berechtigung, auf diese Funktion zuzugreifen.
              <br />
              <span class="text-caption text-grey">
                Bitte wenden Sie sich an Ihren Administrator.
              </span>
            </v-card-text>
          </div>
        </v-card-item>

        <v-divider />

        <v-card-actions class="pa-4 justify-center">
          <v-btn
            color="primary"
            variant="flat"
            rounded="pill"
            size="large"
            class="px-8"
            @click="permissionStore.closeDialog"
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
import { computed, onMounted, onUnmounted } from 'vue'
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

// ── Footer-Sichtbarkeit ──────────────────────────────────────────
const HIDE_FOOTER_ROUTES = new Set(['Login', 'login'])

const showFooter = computed(() => !HIDE_FOOTER_ROUTES.has(route.name))

// ── Lifecycle ────────────────────────────────────────────────────
onMounted(async () => {
  try {
    await authStore.initialize()
  } catch (error) {
    console.error('Auth-Initialisierung fehlgeschlagen:', error)
  }
})

// Sicherheitsnetz: Dialog beim Verlassen zurücksetzen
onUnmounted(() => {
  permissionStore.closeDialog?.()
})
</script>

<style>
/* ── Reset ─────────────────────────────────────────────────── */
*,
*::before,
*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body {
  font-family: 'Roboto', sans-serif;
  background-color: #f5f5f5;
}

/* Vuetify-Reset für Layout */
.v-application,
.v-app-bar {
  padding-left: 0 !important;
  margin-left: 0 !important;
}

/* ── Permission-Dialog ─────────────────────────────────────── */
.permission-dialog {
  background: #fff;
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

/* ── Route-Transition ──────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>