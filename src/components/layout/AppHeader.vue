<template>
  <v-app-bar color="white" elevation="1" density="comfortable">
    <v-app-bar-nav-icon @click.stop="uiStore.toggleRail" variant="text" />

    <v-text-field
      density="compact"
      variant="outlined"
      rounded="pill"
      placeholder="Search..."
      prepend-inner-icon="mdi-magnify"
      hide-details
      class="search-field"
    />

    <v-spacer />

    <div class="d-flex align-center ga-3">
      <!-- Messages / Notifications (simplifié) -->
      <v-menu v-model="menuMsg" location="bottom end">
        <template v-slot:activator="{ props }">
          <v-badge color="error" content="1" overlap>
            <v-btn icon="mdi-message-text" v-bind="props" variant="text" density="comfortable" />
          </v-badge>
        </template>
        <v-card width="300"><v-card-title>Nachrichten</v-card-title></v-card>
      </v-menu>

      <v-menu v-model="menuNotif" location="bottom end">
        <template v-slot:activator="{ props }">
          <v-badge color="error" content="3" overlap>
            <v-btn icon="mdi-bell" v-bind="props" variant="text" density="comfortable" />
          </v-badge>
        </template>
        <v-card width="250"><v-card-title>Benachrichtigungen</v-card-title></v-card>
      </v-menu>

      <!-- Profil -->
      <v-menu v-model="menuAccount" location="bottom end" :close-on-content-click="false">
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" variant="text" class="text-none" density="comfortable">
            <v-avatar size="36" color="grey-darken-3">
              <span class="text-white">{{ userInitials }}</span>
            </v-avatar>
            <span class="ml-2">{{ displayName }} <v-icon icon="mdi-chevron-down" size="small" /></span>
          </v-btn>
        </template>

        <v-card width="280" class="account-dropdown">
          <div class="d-flex pa-4 align-center">
            <v-avatar size="48" color="grey-darken-3">
              <span class="text-white text-h6">{{ userInitials }}</span>
            </v-avatar>
            <div class="ml-3">
              <div class="text-subtitle-1 font-weight-medium">{{ displayName }}</div>
              <div class="text-caption text-medium-emphasis">{{ authStore.user?.email || 'Keine E-Mail' }}</div>
            </div>
          </div>
          <v-divider />
          <v-list density="compact" nav>
            <v-list-item prepend-icon="mdi-account" title="Profil" @click="$router.push('/profile'); menuAccount = false" />
            <v-list-item prepend-icon="mdi-cog" title="Einstellungen" @click="$router.push('/settings'); menuAccount = false" />
          </v-list>
          <v-divider />
          <div class="pa-2">
            <v-btn block variant="text" color="error" prepend-icon="mdi-logout" @click="handleLogout">
              Abmelden
            </v-btn>
          </div>
        </v-card>
      </v-menu>
    </div>
  </v-app-bar>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/notifications'
import { logout } from '../../api/auth_stock'

const uiStore = useUiStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()

const menuMsg = ref(false)
const menuNotif = ref(false)
const menuAccount = ref(false)

const displayName = computed(() => {
  const u = authStore.user
  return u?.name || u?.email || 'Gast'
})

const userInitials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  const name = u.name || ''
  const parts = name.trim().split(/\s+/)
  if (parts.length > 1) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase() || u.email?.charAt(0).toUpperCase() || '?'
})

const handleLogout = async () => {
  try {
    const res = await logout()
    if (res.success) {
      authStore.clearUser()
      notificationStore.showSuccess('Abgemeldet', 'Auf Wiedersehen!')
      router.push('/login')
    } else {
      throw new Error(res.message || 'Fehler')
    }
  } catch (error) {
    notificationStore.showError('Fehler beim Abmelden', error.message)
  }
}
</script>

<style scoped>
.search-field { max-width: 260px; }
.account-dropdown :deep(.v-list-item__prepend) { margin-right: 12px; }
</style>