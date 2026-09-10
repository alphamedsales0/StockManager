<template>
  <v-app-bar color="white" elevation="1" density="comfortable">
    <!-- Sidebar-Toggle -->
    <v-app-bar-nav-icon @click.stop="uiStore.toggleRail" variant="text" />

    <!-- Suchfeld -->
    <v-text-field
      density="compact"
      variant="outlined"
      rounded="pill"
      placeholder="Suchen…"
      prepend-inner-icon="mdi-magnify"
      hide-details
      class="search-field"
    />

    <v-spacer />

    <div class="d-flex align-center ga-3">
      <!-- Nachrichten-Dropdown -->
      <v-menu
        v-model="menuMsg"
        location="bottom end"
        :close-on-content-click="false"
        offset-y
        transition="slide-y-transition"
        max-width="380"
      >
        <template v-slot:activator="{ props }">
          <v-badge
            :color="unreadMessages > 0 ? 'error' : 'grey'"
            :content="unreadMessages > 0 ? unreadMessages : ''"
            overlap
          >
            <v-btn icon="mdi-message-text" v-bind="props" variant="text" density="comfortable" @click="loadData" />
          </v-badge>
        </template>

        <v-card min-width="350" max-width="400" class="dropdown-card">
          <v-card-title class="d-flex align-center pa-3">
            <span class="text-subtitle-1 font-weight-bold">Nachrichten</span>
            <v-spacer />
            <v-btn
              variant="text"
              color="primary"
              size="small"
              @click="markAllAsRead('message')"
              v-if="unreadMessages > 0"
            >
              Alle gelesen
            </v-btn>
          </v-card-title>
          <v-divider />

          <div class="dropdown-scroll">
            <v-list density="compact" v-if="messages.length">
              <v-list-item
                v-for="msg in messages"
                :key="msg.id"
                @click="openMessageDetail(msg)"
                :class="{ 'unread-item': !msg.is_read }"
                class="message-item"
              >
                <template #prepend>
                  <v-avatar size="36" :color="!msg.is_read ? 'primary' : 'grey-lighten-2'">
                    <span class="text-body-2 font-weight-bold" :class="!msg.is_read ? 'text-white' : ''">
                      {{ msg.sender_name?.charAt(0) || '?' }}
                    </span>
                  </v-avatar>
                </template>
                <div class="message-content">
                  <div class="d-flex align-center">
                    <span class="text-body-2 font-weight-medium">{{ msg.subject }}</span>
                    <v-chip v-if="!msg.is_read" color="error" size="x-small" class="ml-2">neu</v-chip>
                  </div>
                  <span class="text-caption text-medium-emphasis">{{ msg.preview }}</span>
                  <span class="text-caption text-grey">{{ msg.formatted_date }}</span>
                </div>
              </v-list-item>
            </v-list>
            <div v-else class="text-center pa-4 text-grey">Keine Nachrichten</div>
          </div>

          <v-divider />
          <v-card-actions class="pa-2">
            <v-btn variant="text" color="primary" block size="small" @click="goTo('/messages')">
              Alle Nachrichten anzeigen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-menu>

      <!-- Benachrichtigungen-Dropdown -->
      <v-menu
        v-model="menuNotif"
        location="bottom end"
        :close-on-content-click="false"
        offset-y
        transition="slide-y-transition"
        max-width="380"
      >
        <template v-slot:activator="{ props }">
          <v-badge
            :color="unreadNotifications > 0 ? 'error' : 'grey'"
            :content="unreadNotifications > 0 ? unreadNotifications : ''"
            overlap
          >
            <v-btn icon="mdi-bell" v-bind="props" variant="text" density="comfortable" @click="loadData" />
          </v-badge>
        </template>

        <v-card min-width="350" max-width="400" class="dropdown-card">
          <v-card-title class="d-flex align-center pa-3">
            <span class="text-subtitle-1 font-weight-bold">Benachrichtigungen</span>
            <v-spacer />
            <v-btn
              variant="text"
              color="primary"
              size="small"
              @click="markAllAsRead('notification')"
              v-if="unreadNotifications > 0"
            >
              Alle gelesen
            </v-btn>
          </v-card-title>
          <v-divider />

          <div class="dropdown-scroll">
            <v-list density="compact" v-if="notifications.length">
              <v-list-item
                v-for="n in notifications"
                :key="n.id"
                @click="openNotificationDetail(n)"
                :class="{ 'unread-item': !n.is_read }"
                class="notification-item"
              >
                <template #prepend>
                  <v-icon :color="getTypeColor(n.type)" size="28">{{ getTypeIcon(n.type) }}</v-icon>
                </template>
                <div class="notification-content">
                  <div class="d-flex align-center">
                    <span class="text-body-2 font-weight-medium">{{ n.title }}</span>
                    <v-chip v-if="!n.is_read" color="error" size="x-small" class="ml-2">neu</v-chip>
                  </div>
                  <span class="text-caption text-medium-emphasis">{{ n.message }}</span>
                  <span class="text-caption text-grey">{{ n.formatted_date }}</span>
                  <a v-if="n.link" :href="n.link" @click.stop class="text-caption primary--text">Details</a>
                </div>
              </v-list-item>
            </v-list>
            <div v-else class="text-center pa-4 text-grey">Keine Benachrichtigungen</div>
          </div>

          <v-divider />
          <v-card-actions class="pa-2">
            <v-btn variant="text" color="primary" block size="small" @click="goTo('/notifications')">
              Alle Benachrichtigungen anzeigen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-menu>

      <!-- Profil-Menü (bleibt) -->
      <v-menu v-model="menuAccount" location="bottom end" :close-on-content-click="false" offset-y>
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
            <v-list-item prepend-icon="mdi-account" title="Profil" @click="goTo('/users_profile')" />
            <v-list-item prepend-icon="mdi-cog" title="Einstellungen" @click="goTo('/settings')" />
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/notifications'
import { logout } from '../../api/auth_stock'
import axios from 'axios'

const uiStore = useUiStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()

// Dropdown-Steuerung
const menuMsg = ref(false)
const menuNotif = ref(false)
const menuAccount = ref(false)

// Daten
const messages = ref([])
const notifications = ref([])
const unreadMessages = ref(0)
const unreadNotifications = ref(0)
const loading = ref(false)

// Profil
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

// Hilfsfunktionen für Benachrichtigungstypen
const getTypeIcon = (type) => {
  const map = {
    info: 'mdi-information',
    warning: 'mdi-alert',
    success: 'mdi-check-circle',
    error: 'mdi-close-circle'
  }
  return map[type] || 'mdi-bell'
}

const getTypeColor = (type) => {
  const map = {
    info: 'info',
    warning: 'warning',
    success: 'success',
    error: 'error'
  }
  return map[type] || 'grey'
}

// Daten laden
const loadData = async () => {
  if (!authStore.isLoggedIn || loading.value) return
  loading.value = true
  try {
    const [notifRes, msgRes] = await Promise.all([
      axios.get('/api/get_notifications.php', { withCredentials: true }),
      axios.get('/api/get_messages.php', { withCredentials: true })
    ])
    if (notifRes.data.success) {
      notifications.value = notifRes.data.notifications
      unreadNotifications.value = notifRes.data.unreadCount
    }
    if (msgRes.data.success) {
      messages.value = msgRes.data.messages
      unreadMessages.value = msgRes.data.unreadCount
    }
  } catch (error) {
    console.error('Fehler beim Laden:', error)
  } finally {
    loading.value = false
  }
}

// Einzelnes als gelesen markieren
const markAsRead = async (type, id) => {
  try {
    await axios.post('/api/mark_read.php', { type, id }, { withCredentials: true })
    // Lokal aktualisieren ohne API-Call
    if (type === 'message') {
      const msg = messages.value.find(m => m.id === id)
      if (msg) msg.is_read = 1
      unreadMessages.value = messages.value.filter(m => !m.is_read).length
    } else {
      const notif = notifications.value.find(n => n.id === id)
      if (notif) notif.is_read = 1
      unreadNotifications.value = notifications.value.filter(n => !n.is_read).length
    }
  } catch (error) {
    console.error('Fehler beim Markieren:', error)
  }
}

// Alle als gelesen markieren
const markAllAsRead = async (type) => {
  const items = type === 'message' ? messages.value : notifications.value
  const unread = items.filter(item => !item.is_read)
  
  for (const item of unread) {
    try {
      await axios.post('/api/mark_read.php', { type, id: item.id }, { withCredentials: true })
    } catch (error) {
      console.error('Fehler beim Markieren:', error)
    }
  }
  
  // Lokal aktualisieren
  if (type === 'message') {
    unreadMessages.value = 0
    messages.value.forEach(m => m.is_read = 1)
  } else {
    unreadNotifications.value = 0
    notifications.value.forEach(n => n.is_read = 1)
  }
}

// Navigation
const goTo = (route) => {
  router.push(route)
  menuMsg.value = false
  menuNotif.value = false
  menuAccount.value = false
}

// Detail öffnen
const openMessageDetail = (msg) => {
  if (!msg.is_read) markAsRead('message', msg.id)
  router.push(`/message/${msg.id}`)
  menuMsg.value = false
}

const openNotificationDetail = (n) => {
  if (!n.is_read) markAsRead('notification', n.id)
  if (n.link) {
    router.push(n.link)
  } else {
    notificationStore.showInfo('Benachrichtigung', n.message)
  }
  menuNotif.value = false
}

// Logout
const handleLogout = async () => {
  try {
    const res = await logout()
    if (res.success) {
      authStore.clearUser()
      notificationStore.showSuccess('Abgemeldet', 'Auf Wiedersehen!')
      router.push('/login')
    } else {
      throw new Error(res.message || 'Fehler beim Abmelden')
    }
  } catch (error) {
    notificationStore.showError('Fehler beim Abmelden', error.message)
  }
}

onMounted(() => {
  if (authStore.isLoggedIn) loadData()
})

// Expose für Aktualisierung von außen
defineExpose({ loadData })
</script>

<style scoped>
.search-field {
  max-width: 260px;
}

/* Dropdown-Styling */
.dropdown-card {
  border-radius: 12px !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.dropdown-scroll {
  max-height: 350px;
  overflow-y: auto;
}

/* Nachrichten-Einträge */
.message-item {
  cursor: pointer;
  transition: background 0.2s;
}
.message-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.message-item .message-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
  width: 100%;
}

/* Benachrichtigungen-Einträge */
.notification-item {
  cursor: pointer;
  transition: background 0.2s;
}
.notification-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.notification-item .notification-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
  width: 100%;
}

/* Ungelesene Einträge */
.unread-item {
  background-color: rgba(33, 150, 243, 0.06);
  border-left: 3px solid #1976d2;
}
.unread-item:hover {
  background-color: rgba(33, 150, 243, 0.12);
}

/* Profil-Dropdown */
.account-dropdown :deep(.v-list-item__prepend) {
  margin-right: 12px;
}

/* Scrollbar-Stil */
.dropdown-scroll::-webkit-scrollbar {
  width: 4px;
}
.dropdown-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.dropdown-scroll::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 4px;
}
.dropdown-scroll::-webkit-scrollbar-thumb:hover {
  background: #aaa;
}
</style>