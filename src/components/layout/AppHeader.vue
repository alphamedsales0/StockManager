<template>
  <v-app-bar color="surface" elevation="1" density="comfortable" class="modern-app-bar">
    <!-- Sidebar-Toggle -->
    <v-app-bar-nav-icon
      @click.stop="uiStore.toggleRail"
      variant="text"
      aria-label="Seitenleiste umschalten"
    />

    <!-- Suchfeld -->
    <v-text-field
      v-model="searchQuery"
      density="compact"
      variant="solo-filled"
      rounded="pill"
      placeholder="Suchen…"
      prepend-inner-icon="mdi-magnify"
      hide-details
      clearable
      class="search-field"
      aria-label="Suche"
      @keyup.enter="performSearch"
    />

    <v-spacer />

    <div class="d-flex align-center ga-2">
      <!-- Nachrichten-Dropdown -->
        
      <AppBarDropdown
        v-model="menuMsg"
        :unread-count="unreadMessages"
        icon="mdi-message-text"
        title="Nachrichten"
        empty-text="Keine Nachrichten"
        :items="messages"
        item-key="id"
        action-text="Alle Nachrichten anzeigen"
        @open="loadData"
        @mark-all-read="markAllAsRead('message')"
        @item-click="openMessageDetail"
        @action-click="goTo('/messages')"
      >
        <!-- ✅ Flacher Slot, direktes Kind der Komponente -->
        <template #prepend="{ item: msg }">
          <v-avatar
            size="36"
            :color="!msg.is_read ? 'primary' : 'grey-lighten-2'"
          >
            <span
              class="text-body-2 font-weight-bold"
              :class="{ 'text-white': !msg.is_read }"
            >
              {{ msg.sender_name?.charAt(0) || '?' }}
            </span>
          </v-avatar>
        </template>

        <template #content="{ item: msg }">
          <div class="message-content">
            <div class="d-flex align-center gap-2">
              <span class="text-body-2 font-weight-medium text-truncate">
                {{ msg.subject }}
              </span>
              <v-chip v-if="!msg.is_read" color="error" size="x-small">neu</v-chip>
            </div>
            <span class="text-caption text-medium-emphasis text-truncate">
              {{ msg.preview }}
            </span>
            <span class="text-caption text-grey">{{ msg.formatted_date }}</span>
          </div>
        </template>
      </AppBarDropdown>

      <!-- Benachrichtigungen-Dropdown -->
      <AppBarDropdown
        v-model="menuNotif"
        :unread-count="unreadNotifications"
        icon="mdi-bell"
        title="Benachrichtigungen"
        empty-text="Keine Benachrichtigungen"
        :items="notifications"
        item-key="id"
        action-text="Alle Benachrichtigungen anzeigen"
        :icon-for="getTypeIcon"
        :icon-color="getTypeColor"
        @open="loadData"
        @mark-all-read="markAllAsRead('notification')"
        @item-click="openNotificationDetail"
        @action-click="goTo('/notifications')"
      >
        <template #content="{ item: n }">
          <div class="notification-content">
            <div class="d-flex align-center gap-2">
              <span class="text-body-2 font-weight-medium text-truncate">
                {{ n.title }}
              </span>
              <v-chip v-if="!n.is_read" color="error" size="x-small">neu</v-chip>
            </div>
            <span class="text-caption text-medium-emphasis">{{ n.message }}</span>
            <span class="text-caption text-grey">{{ n.formatted_date }}</span>
            <a
              v-if="n.link"
              :href="n.link"
              @click.stop
              class="text-caption text-primary"
            >
              Details
            </a>
          </div>
        </template>
      </AppBarDropdown>

      <!-- Profil-Menü -->
      <v-menu
        v-model="menuAccount"
        location="bottom end"
        :close-on-content-click="false"
        offset-y
      >
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            class="text-none"
            density="comfortable"
            aria-label="Profilmenü"
          >
            <v-avatar size="36" color="grey-darken-3">
              <span class="text-white">{{ userInitials }}</span>
            </v-avatar>
            <span class="ml-2 d-none d-sm-inline">
              {{ displayName }}
              <v-icon icon="mdi-chevron-down" size="small" />
            </span>
          </v-btn>
        </template>

        <v-card width="280" class="account-dropdown mt-3">
          <div class="d-flex pa-4 align-center">
            <v-avatar size="48" color="grey-darken-3">
              <span class="text-white text-h6">{{ userInitials }}</span>
            </v-avatar>
            <div class="ml-3">
              <div class="text-subtitle-1 font-weight-medium">{{ displayName }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ authStore.user?.email || 'Keine E-Mail' }}
              </div>
            </div>
          </div>
          <v-divider />
          <v-list density="compact" nav>
            <v-list-item
              prepend-icon="mdi-account"
              title="Profil"
              @click="goTo('/users_profile')"
            />
            <v-list-item
              prepend-icon="mdi-cog"
              title="Einstellungen"
              @click="goTo('/settings')"
            />
          </v-list>
          <v-divider />
          <div class="pa-2">
            <v-btn
              block
              variant="text"
              color="error"
              prepend-icon="mdi-logout"
              @click="handleLogout"
            >
              Abmelden
            </v-btn>
          </div>
        </v-card>
      </v-menu>
    </div>
  </v-app-bar>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/notifications'
import { logout } from '../../api/auth_stock'
import axios from 'axios'
import AppBarDropdown from './AppBarDropdown.vue'

// ── Stores & Router ──────────────────────────────────────────────
const uiStore = useUiStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()

// ── State ────────────────────────────────────────────────────────
const menuMsg = ref(false)
const menuNotif = ref(false)
const menuAccount = ref(false)

const searchQuery = ref('')
const messages = ref([])
const notifications = ref([])
const unreadMessages = ref(0)
const unreadNotifications = ref(0)
const loading = ref(false)

// ── Konstanten ───────────────────────────────────────────────────
const TYPE_ICONS = {
  info: 'mdi-information',
  warning: 'mdi-alert',
  success: 'mdi-check-circle',
  error: 'mdi-close-circle',
}
const TYPE_COLORS = {
  info: 'info',
  warning: 'warning',
  success: 'success',
  error: 'error',
}

const getTypeIcon = (type) => TYPE_ICONS[type] ?? 'mdi-bell'
const getTypeColor = (type) => TYPE_COLORS[type] ?? 'grey'

// ── Computed ─────────────────────────────────────────────────────
const displayName = computed(
  () => authStore.user?.name || authStore.user?.email || 'Gast',
)

const userInitials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  const name = (u.name || '').trim()
  if (!name) return u.email?.charAt(0).toUpperCase() || '?'

  const parts = name.split(/\s+/)
  if (parts.length > 1) {
    return (parts[0][0] + parts.at(-1)[0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

// ── Suche ────────────────────────────────────────────────────────
const performSearch = () => {
  const q = searchQuery.value.trim()
  if (!q) return
  router.push({ path: '/search', query: { q } })
}

// ── Daten laden ──────────────────────────────────────────────────
const loadData = async () => {
  if (!authStore.isLoggedIn || loading.value) return
  loading.value = true

  const [notifResult, msgResult] = await Promise.allSettled([
    axios.get('/api/get_notifications.php', { withCredentials: true }),
    axios.get('/api/get_messages.php', { withCredentials: true }),
  ])

  if (notifResult.status === 'fulfilled' && notifResult.value.data.success) {
    notifications.value = notifResult.value.data.notifications
    unreadNotifications.value = notifResult.value.data.unreadCount
  } else if (notifResult.status === 'rejected') {
    console.error('Benachrichtigungen:', notifResult.reason)
  }

  if (msgResult.status === 'fulfilled' && msgResult.value.data.success) {
    messages.value = msgResult.value.data.messages
    unreadMessages.value = msgResult.value.data.unreadCount
  } else if (msgResult.status === 'rejected') {
    console.error('Nachrichten:', msgResult.reason)
  }

  loading.value = false
}

// ── Als gelesen markieren ────────────────────────────────────────
const markAsRead = async (type, id) => {
  const list = type === 'message' ? messages.value : notifications.value
  const item = list.find((i) => i.id === id)
  if (!item || item.is_read) return

  // Optimistisches Update
  item.is_read = 1
  if (type === 'message') {
    unreadMessages.value = Math.max(0, unreadMessages.value - 1)
  } else {
    unreadNotifications.value = Math.max(0, unreadNotifications.value - 1)
  }

  try {
    await axios.post(
      '/api/mark_read.php',
      { type, id },
      { withCredentials: true },
    )
  } catch (error) {
    // Rollback bei Fehler
    item.is_read = 0
    if (type === 'message') unreadMessages.value++
    else unreadNotifications.value++
    console.error('Fehler beim Markieren:', error)
  }
}

// ── Alle als gelesen markieren ───────────────────────────────────
const markAllAsRead = async (type) => {
  const list = type === 'message' ? messages.value : notifications.value
  const unread = list.filter((i) => !i.is_read)
  if (!unread.length) return

  // Optimistisches Update
  unread.forEach((i) => (i.is_read = 1))
  const prevCount =
    type === 'message' ? unreadMessages.value : unreadNotifications.value
  if (type === 'message') unreadMessages.value = 0
  else unreadNotifications.value = 0

  // Bulk-Request falls API vorhanden, sonst einzeln
  try {
    await axios.post(
      '/api/mark_all_read.php',
      { type, ids: unread.map((i) => i.id) },
      { withCredentials: true },
    )
  } catch {
    // Fallback: einzeln
    await Promise.allSettled(
      unread.map((i) =>
        axios.post(
          '/api/mark_read.php',
          { type, id: i.id },
          { withCredentials: true },
        ),
      ),
    )
  }

  // Bei totalem Fehler zurücksetzen (hier vereinfacht)
  if (!unread.length && prevCount === 0) return
}

// ── Navigation ───────────────────────────────────────────────────
const goTo = (route) => {
  router.push(route)
  menuMsg.value = false
  menuNotif.value = false
  menuAccount.value = false
}

// ── Detail öffnen ────────────────────────────────────────────────
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

// ── Logout ───────────────────────────────────────────────────────
const handleLogout = async () => {
  try {
    const res = await logout()
    if (!res.success) throw new Error(res.message || 'Fehler beim Abmelden')

    authStore.clearUser()
    notificationStore.showSuccess('Abgemeldet', 'Auf Wiedersehen!')
    router.push('/login')
  } catch (error) {
    notificationStore.showError('Fehler beim Abmelden', error.message)
  }
}

// ── Lifecycle & Reaktivität ──────────────────────────────────────
watch(
  () => authStore.isLoggedIn,
  (loggedIn) => {
    if (loggedIn) {
      loadData()
    } else {
      messages.value = []
      notifications.value = []
      unreadMessages.value = 0
      unreadNotifications.value = 0
    }
  },
  { immediate: true },
)

// Optional: Polling alle 60 Sekunden für Live-Updates
let pollInterval = null
onMounted(() => {
  if (authStore.isLoggedIn) {
    pollInterval = setInterval(loadData, 60_000)
  }
})

// Cleanup (in `<script setup>` via `onUnmounted` ergänzen)
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

// Expose für externe Aktualisierung
defineExpose({ loadData })
</script>

<style scoped>
/* ── App-Bar ──────────────────────────────────────────────── */
.modern-app-bar {
  backdrop-filter: blur(12px);
  background-color: color-mix(in srgb, var(--v-theme-surface) 92%, transparent) !important;
}

.search-field {
  max-width: 320px;
  transition: max-width 0.2s ease;
}
.search-field:focus-within {
  max-width: 400px;
}

/* ── Dropdown-Container ───────────────────────────────────── */
.dropdown-card {
  border-radius: 12px !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
  overflow: hidden;
}

.dropdown-scroll {
  max-height: 350px;
  overflow-y: auto;
  scrollbar-gutter: stable;
}

/* ── Einträge ─────────────────────────────────────────────── */
.message-item,
.notification-item {
  cursor: pointer;
  transition: background 0.15s ease;
}
.message-item:hover,
.notification-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.message-content,
.notification-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
  width: 100%;
  min-width: 0;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Ungelesene Einträge ──────────────────────────────────── */
.unread-item {
  background-color: rgba(33, 150, 243, 0.06);
  border-left: 3px solid rgb(var(--v-theme-primary));
}
.unread-item:hover {
  background-color: rgba(33, 150, 243, 0.12);
}

/* ── Profil-Dropdown ──────────────────────────────────────── */
.account-dropdown :deep(.v-list-item__prepend) {
  margin-right: 5px;
}

/* ── Scrollbar ────────────────────────────────────────────── */
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

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 600px) {
  .search-field {
    max-width: 160px;
  }
}
</style>