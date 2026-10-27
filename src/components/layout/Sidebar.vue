<template>
  <v-navigation-drawer
    :rail="uiStore.rail"
    permanent
    class="elevation-3 sidebar-blue"
    :width="uiStore.rail ? 56 : 280"
  >
    <!-- ============================================================ -->
    <!-- HEADER FIXE (slot prepend) -->
    <!-- ============================================================ -->
    <template #prepend>
      <div class="sidebar-header" :class="{ 'rail-mode': uiStore.rail }">
        <div
          class="d-flex align-center"
          :class="uiStore.rail ? 'justify-center' : 'justify-space-between'"
        >
          <div class="d-flex align-center">
            <v-icon size="32" color="white">mdi-database</v-icon>
            <span v-if="!uiStore.rail" class="text-h6 font-weight-bold text-white ml-2">
              StockManager
            </span>
          </div>
          <v-btn
            v-if="!uiStore.rail"
            variant="text"
            icon="mdi-chevron-left"
            size="small"
            color="white"
            @click.stop="uiStore.toggleRail"
          >
            <v-icon>mdi-chevron-left</v-icon>
            <v-tooltip activator="parent" location="bottom">Einklappen</v-tooltip>
          </v-btn>
        </div>
      </div>
      <v-divider class="sidebar-divider" />
    </template>

    <!-- ============================================================ -->
    <!-- MENU SCROLLABLE (slot par défaut) -->
    <!-- ============================================================ -->
    <v-list nav density="compact" class="pa-1">

      <!-- ==================== HAUPTMENÜ ==================== -->
      <v-list-subheader v-if="!uiStore.rail" class="sidebar-subheader">
        Hauptmenü
      </v-list-subheader>

      <!-- DASHBOARD -->
      <v-list-item
        v-if="permissionStore.can('dashboard')"
        prepend-icon="mdi-view-dashboard-outline"
        :active="activeTab === 'dashboard'"
        @click="navigateTo('dashboard')"
      >
        <v-list-item-title>Dashboard</v-list-item-title>
        <template #append>
          <span v-if="!uiStore.rail" class="kbd-hint">G D</span>
        </template>
      </v-list-item>

      <!-- TICKETS avec badge -->
      <v-list-item
        prepend-icon="mdi-ticket-outline"
        :active="activeTab === 'tickets'"
        @click="navigateTo('tickets')"
      >
        <v-list-item-title>Tickets</v-list-item-title>
        <template #append>
          <v-chip
            v-if="openTicketsCount > 0"
            size="x-small"
            color="error"
            variant="flat"
            class="badge-chip"
          >
            {{ openTicketsCount }}
          </v-chip>
        </template>
      </v-list-item>

      <!-- ==================== VERWALTUNG ==================== -->
      <v-list-subheader
        v-if="!uiStore.rail && (permissionStore.can('products') || permissionStore.can('customers') || permissionStore.can('employees') || permissionStore.can('orders'))"
        class="sidebar-subheader"
      >
        Verwaltung
      </v-list-subheader>

      <!-- PRODUKTE -->
      <v-list-group
        v-if="permissionStore.can('products')"
        value="products"
      >
        <template #activator="{ props }">
          <v-list-item v-bind="props" prepend-icon="mdi-package-variant">
            <v-list-item-title>Produkte</v-list-item-title>
          </v-list-item>
        </template>

        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Produkte"
          :active="activeTab === 'products-list'"
          @click="navigateTo('products-list')"
        />

        <v-list-item
          v-if="permissionStore.can('products-write')"
          prepend-icon="mdi-plus-box-outline"
          title="Neues Produkt"
          :active="activeTab === 'add-product'"
          @click="navigateTo('add-product')"
        />

        <v-list-item
          v-if="permissionStore.can('products-categories')"
          prepend-icon="mdi-tag-outline"
          title="Kategorien"
          :active="activeTab === 'categories'"
          @click="navigateTo('categories')"
        />
        <v-list-item
          v-if="permissionStore.can('products-categories')"
          prepend-icon="mdi-format-list-checks"
          title="Artikeltypen"
          :active="activeTab === 'article-types'"
          @click="navigateTo('article-types')"
        />
        <v-list-item
          v-if="permissionStore.can('products-categories')"
          prepend-icon="mdi-tag"
          title="Marken"
          :active="activeTab === 'brands'"
          @click="navigateTo('brands')"
        />
      </v-list-group>

      <!-- KUNDEN -->
      <v-list-group
        v-if="permissionStore.can('customers')"
        value="customers"
      >
        <template #activator="{ props }">
          <v-list-item v-bind="props" prepend-icon="mdi-account-group-outline">
            <v-list-item-title>Kunden</v-list-item-title>
          </v-list-item>
        </template>

        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Kunden"
          :active="activeTab === 'customers-list'"
          @click="navigateTo('customers-list')"
        />

        <v-list-item
          v-if="permissionStore.can('customers-write')"
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Kunde"
          :active="activeTab === 'add-customer'"
          @click="navigateTo('add-customer')"
        />
      </v-list-group>

      <!-- MITARBEITER -->
      <v-list-group
        v-if="permissionStore.can('employees')"
        value="employees"
      >
        <template #activator="{ props }">
          <v-list-item v-bind="props" prepend-icon="mdi-account-tie-outline">
            <v-list-item-title>Mitarbeiter</v-list-item-title>
          </v-list-item>
        </template>
        <v-list-item
          prepend-icon="mdi-account-multiple-outline"
          title="Alle Mitarbeiter"
          :active="activeTab === 'employees-list'"
          @click="navigateTo('employees-list')"
        />
        <v-list-item
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Mitarbeiter"
          :active="activeTab === 'add-employee'"
          @click="navigateTo('add-employee')"
        />
        <v-list-item
          prepend-icon="mdi-account-cog"
          title="Rollen"
          :active="activeTab === 'roles'"
          @click="navigateTo('roles')"
        />
        <v-list-item
          prepend-icon="mdi-briefcase-outline"
          title="Position"
          :active="activeTab === 'positions'"
          @click="navigateTo('positions')"
        />
        <v-list-item
          prepend-icon="mdi-office-building-outline"
          title="Abteilung"
          :active="activeTab === 'departments'"
          @click="navigateTo('departments')"
        />
      </v-list-group>

      <!-- BESTELLUNGEN -->
      <v-list-group
        v-if="permissionStore.can('orders')"
        value="orders"
      >
        <template #activator="{ props }">
          <v-list-item v-bind="props" prepend-icon="mdi-cart-outline">
            <v-list-item-title>Bestellungen</v-list-item-title>
          </v-list-item>
        </template>
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Bestellungen"
          :active="activeTab === 'orders-list'"
          @click="navigateTo('orders-list')"
        />
        <v-list-item
          prepend-icon="mdi-cart-plus"
          title="Neue Bestellung"
          :active="activeTab === 'add-order'"
          @click="navigateTo('add-order')"
        />
      </v-list-group>

      <!-- ==================== SYSTEM ==================== -->
      <v-list-subheader v-if="!uiStore.rail" class="sidebar-subheader">
        System
      </v-list-subheader>

      <!-- STATISTIKEN -->
      <v-list-item
        v-if="permissionStore.can('stats')"
        prepend-icon="mdi-chart-line"
        title="Statistiken"
        :active="activeTab === 'stats'"
        @click="navigateTo('stats')"
      />

      <!-- HILFE -->
      <v-list-item
        prepend-icon="mdi-help-circle-outline"
        title="Hilfe"
        :active="activeTab === 'help'"
        @click="navigateTo('help')"
      />
    </v-list>

    <!-- ============================================================ -->
    <!-- FOOTER FIXE (slot append) -->
    <!-- ============================================================ -->
    <template #append>
      <div class="sidebar-footer-fixed">
        <v-divider class="sidebar-divider" />

        <!-- STATUT SERVEUR + VERSION -->
        <div v-if="!uiStore.rail" class="footer-info">
          <div class="server-status" :class="serverStatus">
            <span class="status-dot"></span>
            <span>{{ serverStatus === 'online' ? 'Server online' : 'Hors ligne' }}</span>
          </div>
          <div class="app-version">
            v{{ appVersion }} · {{ buildDate }}
          </div>
        </div>

        <!-- PROFIL UTILISATEUR -->
        <div class="user-section" :class="{ 'rail-mode': uiStore.rail }">
          <v-divider class="mb-2 sidebar-divider" />

          <div class="user-card" :class="{ 'rail-mode': uiStore.rail }">
            <div class="user-avatar">
              <v-icon size="20" color="white">mdi-account-circle</v-icon>
            </div>
            <div v-if="!uiStore.rail" class="user-info">
              <div class="user-name">{{ displayName }}</div>
              <div class="user-role">{{ displayRole }}</div>
            </div>
            <v-btn
              v-if="!uiStore.rail"
              icon="mdi-logout"
              size="small"
              variant="text"
              color="white"
              class="ml-auto"
              @click="handleLogout"
            >
              <v-icon size="18">mdi-logout</v-icon>
              <v-tooltip activator="parent" location="top">Abmelden</v-tooltip>
            </v-btn>
          </div>
        </div>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useUiStore } from '../../stores/uiStore'
import { usePermissionStore } from '../../stores/permission'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/notifications'

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()
const permissionStore = usePermissionStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

// ============================================================
// CONFIG
// ============================================================
const appVersion = '1.0.0'
const buildDate = new Date().toLocaleDateString('de-DE')

// ============================================================
// USER DISPLAY
// ============================================================
const displayName = computed(() => {
  const u = authStore.user
  if (!u) return 'Benutzer'
  if (u.name) return u.name
  if (u.vorname && u.nachname) return `${u.vorname} ${u.nachname}`
  if (u.email) return u.email.split('@')[0]
  return 'Benutzer'
})

const displayRole = computed(() => {
  // ✅ Récupère le rôle depuis plusieurs sources (fallback)
  const role =
    authStore.user?.role ||
    permissionStore.userRole ||
    localStorage.getItem('userRole') ||
    null

  // ✅ Si connecté mais pas de rôle → '—'
  if (!role) {
    return authStore.isLoggedIn ? '—' : 'Gast'
  }

  // ✅ Normalise en minuscule
  const r = String(role).toLowerCase().trim()

  // ✅ Map des rôles → labels affichés
  const map = {
    admin: 'Administrator',
    administrator: 'Administrator',
    office: 'Büro',
    büro: 'Büro',
    technician: 'Techniker',
    techniker: 'Techniker',
    employee: 'Mitarbeiter',
    mitarbeiter: 'Mitarbeiter',
    manager: 'Manager',
    hr: 'Personal',
    personal: 'Personal',
    finance: 'Finanzen',
    finanzen: 'Finanzen',
    sales: 'Vertrieb',
    vertrieb: 'Vertrieb',
    warehouse: 'Lager',
    lager: 'Lager',
    purchasing: 'Einkauf',
    einkauf: 'Einkauf',
    support: 'Support',
    teamlead: 'Teamleiter',
    teamleiter: 'Teamleiter',
    accountant: 'Buchhalter',
    buchhalter: 'Buchhalter',
    developer: 'Entwickler',
    entwickler: 'Entwickler',
    manu: 'Manu'
  }

  // ✅ Retourne le label ou le rôle capitalisé
  return map[r] || (r.charAt(0).toUpperCase() + r.slice(1))
})

// ============================================================
// TICKETS COUNT (polling 60s)
// ============================================================
const openTicketsCount = ref(0)
let ticketsInterval = null

const loadTicketsCount = async () => {
  try {
    const res = await axios.get('/api/get_open_tickets_count.php', {
      withCredentials: true
    })
    openTicketsCount.value = res.data?.count || 0
  } catch {
    // silencieux
  }
}

// ============================================================
// SERVER STATUS (ping 30s)
// ============================================================
const serverStatus = ref('online')
let serverInterval = null

const checkServer = async () => {
  try {
    await axios.get('/api/ping.php', {
      withCredentials: true,
      timeout: 3000
    })
    serverStatus.value = 'online'
  } catch {
    serverStatus.value = 'offline'
  }
}

// ============================================================
// ONGLET ACTIF
// ============================================================
const activeTab = computed(() => {
  const path = route.path
  if (path === '/' || path === '/dashboard') return 'dashboard'
  if (path === '/tickets') return 'tickets'
  if (path === '/products') return 'products-list'
  if (path === '/products/add') return 'add-product'
  if (path.startsWith('/products/view/')) return 'products-list'
  if (path.startsWith('/products/edit/')) return 'products-list'
  if (path === '/categories') return 'categories'
  if (path === '/article-types') return 'article-types'
  if (path === '/brands') return 'brands'
  if (path === '/customers') return 'customers-list'
  if (path === '/customers/add') return 'add-customer'
  if (path.startsWith('/customers/edit/')) return 'customers-list'
  if (path.match(/^\/customers\/\d+$/)) return 'customers-list'
  if (path === '/employees') return 'employees-list'
  if (path === '/employees/add') return 'add-employee'
  if (path.startsWith('/employees/')) return 'employees-list'
  if (path === '/roles') return 'roles'
  if (path === '/positions') return 'positions'
  if (path === '/departments') return 'departments'
  if (path === '/orders') return 'orders-list'
  if (path === '/orders/add') return 'add-order'
  if (path === '/stats') return 'stats'
  if (path === '/help') return 'help'
  return 'dashboard'
})

// ============================================================
// NAVIGATION
// ============================================================
const navigateTo = (tab) => {
  const routes = {
    dashboard: '/',
    tickets: '/tickets',
    'products-list': '/products',
    'add-product': '/products/add',
    categories: '/categories',
    'article-types': '/article-types',
    brands: '/brands',
    'customers-list': '/customers',
    'add-customer': '/customers/add',
    'employees-list': '/employees',
    'add-employee': '/employees/add',
    roles: '/roles',
    positions: '/positions',
    departments: '/departments',
    'orders-list': '/orders',
    'add-order': '/orders/add',
    stats: '/stats',
    help: '/help'
  }

  const target = routes[tab] || '/'
  const publicTabs = ['tickets', 'help']
  if (publicTabs.includes(tab)) {
    router.push(target)
    return
  }

  const key = getParentKey(tab)
  if (permissionStore.can(key)) {
    router.push(target)
  } else {
    permissionStore.showAccessDenied()
  }
}

const getParentKey = (tab) => {
  if (tab === 'dashboard') return 'dashboard'
  if (tab === 'products-list') return 'products'
  if (tab === 'add-product') return 'products-write'
  if (tab === 'categories') return 'products-categories'
  if (tab === 'article-types') return 'products-categories'
  if (tab === 'brands') return 'products-categories'
  if (tab === 'customers-list') return 'customers'
  if (tab === 'add-customer') return 'customers-write'
  if (['employees-list', 'add-employee', 'roles', 'positions', 'departments'].includes(tab)) {
    return 'employees'
  }
  if (tab === 'orders-list' || tab === 'add-order') return 'orders'
  if (tab === 'stats') return 'stats'
  if (tab === 'help') return 'help'
  return tab
}

// ============================================================
// LOGOUT
// ============================================================
const handleLogout = async () => {
  try {
    await axios.post('/api/auth_stock.php?action=logout', {}, { withCredentials: true })
  } catch (err) {
    console.warn('Logout API error:', err)
  } finally {
    authStore.logout?.()
    if (authStore.setUser) authStore.setUser(null)
    if (authStore.setLoggedIn) authStore.setLoggedIn(false)
    notificationStore.showInfo?.('Abgemeldet', 'Bis bald!')
    router.push('/login')
  }
}

// ============================================================
// RACCOURCIS CLAVIER
// ============================================================
const handleKeydown = (e) => {
  const tag = document.activeElement?.tagName
  if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return

  if (e.altKey) {
    const map = {
      d: 'dashboard',
      t: 'tickets',
      p: 'products-list',
      k: 'customers-list',
      o: 'orders-list'
    }
    const target = map[e.key.toLowerCase()]
    if (target) {
      e.preventDefault()
      navigateTo(target)
    }
  }
}

// ============================================================
// AUTO-SCROLL VERS L'ITEM ACTIF
// ============================================================
watch(
  () => route.path,
  async () => {
    await nextTick()
    const activeItem = document.querySelector('.v-navigation-drawer__content .v-list-item--active')
    if (activeItem) {
      activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  }
)

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
  loadTicketsCount()
  checkServer()

  ticketsInterval = setInterval(loadTicketsCount, 60000)
  serverInterval = setInterval(checkServer, 30000)

  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  if (ticketsInterval) clearInterval(ticketsInterval)
  if (serverInterval) clearInterval(serverInterval)
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* ============================================================
   SIDEBAR BLEU SOMBRE UNI
   ============================================================ */
.sidebar-blue {
  background: #0e1b2c !important;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3) !important;
}

/* ============================================================
   ✅ CONTENT SCROLLABLE (Vuetify gère ça automatiquement)
   ============================================================ */
.sidebar-blue :deep(.v-navigation-drawer__content) {
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.sidebar-blue :deep(.v-navigation-drawer__content)::-webkit-scrollbar {
  width: 5px;
}

.sidebar-blue :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.sidebar-blue :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.35);
}

/* ============================================================
   ✅ HEADER (dans slot prepend)
   ============================================================ */
.sidebar-header {
  padding: 16px;
  background: #0e1925;
  flex-shrink: 0;
}

.sidebar-header.rail-mode {
  padding: 16px 8px;
}

/* ============================================================
   DIVIDERS
   ============================================================ */
.sidebar-divider {
  border-color: rgba(255, 255, 255, 0.12) !important;
  opacity: 1 !important;
}

/* ============================================================
   SUBHEADERS (sections)
   ============================================================ */
.sidebar-subheader {
  color: rgba(255, 255, 255, 0.45) !important;
  font-size: 0.65rem !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 12px 16px 4px !important;
  min-height: auto !important;
}

/* ============================================================
   LIST — Base
   ============================================================ */
.sidebar-blue :deep(.v-list) {
  background: transparent !important;
  color: #ffffff;
}

.sidebar-blue :deep(.v-list-item) {
  color: #ffffff;
  border-radius: 6px;
  margin-bottom: 2px;
  transition: all 0.2s ease;
  position: relative;
}

.sidebar-blue :deep(.v-list-item .v-icon) {
  color: #ffffff !important;
  opacity: 1 !important;
}

.sidebar-blue :deep(.v-list-item .v-list-item-title) {
  color: #ffffff !important;
  font-size: 0.9rem;
  font-weight: 500;
  white-space: nowrap;
}

.sidebar-blue :deep(.v-list-item:hover) {
  background: rgba(255, 255, 255, 0.08) !important;
}

.sidebar-blue :deep(.v-list-item--active) {
  background: rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
  font-weight: 700;
}

.sidebar-blue :deep(.v-list-item--active .v-icon) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-list-item--active .v-list-item-title) {
  color: #ffffff !important;
  font-weight: 700;
}

.sidebar-blue :deep(.v-list-item--active)::before {
  content: '';
  position: absolute;
  left: 0;
  top: 6px;
  bottom: 6px;
  width: 3px;
  background: #ffffff;
  border-radius: 0 2px 2px 0;
}

/* ============================================================
   GROUPES
   ============================================================ */
.sidebar-blue :deep(.v-list-group__header) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-list-group__header .v-icon) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-list-group__header .v-list-item-title) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-list-group__items .v-list-item) {
  padding-inline-start: 40px !important;
  color: #ffffff;
}

.sidebar-blue :deep(.v-list-group__items .v-list-item .v-list-item-title) {
  color: #ffffff !important;
  font-size: 0.85rem;
}

.sidebar-blue :deep(.v-list-group__items .v-list-item .v-icon) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-list-group__items .v-list-item--active) {
  background: rgba(255, 255, 255, 0.18) !important;
}

.sidebar-blue :deep(.v-list-group__items .v-list-item--active .v-list-item-title) {
  font-weight: 700;
}

.sidebar-blue :deep(.v-list-group__header .v-list-group__header__append-icon .v-icon) {
  color: #ffffff !important;
  opacity: 0.9 !important;
}

/* ============================================================
   BADGE TICKETS
   ============================================================ */
.badge-chip {
  font-weight: 700 !important;
  font-size: 0.65rem !important;
  min-width: 18px !important;
  height: 18px !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
}

/* ============================================================
   KBD HINT
   ============================================================ */
.kbd-hint {
  font-size: 0.6rem;
  color: rgba(255, 255, 255, 0.35);
  font-family: 'Courier New', monospace;
  padding: 2px 5px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.05);
  letter-spacing: 0.5px;
  font-weight: 600;
}

/* ============================================================
   ✅ FOOTER FIXE (dans slot append)
   ============================================================ */
.sidebar-footer-fixed {
  background: #0e1b2c;
  flex-shrink: 0;
}

.footer-info {
  padding: 8px 16px;
}

.server-status {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 0;
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.6);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #4caf50;
  box-shadow: 0 0 6px rgba(76, 175, 80, 0.6);
  animation: pulse 2s ease-in-out infinite;
}

.server-status.offline .status-dot {
  background: #f44336;
  box-shadow: 0 0 6px rgba(244, 67, 54, 0.6);
  animation: none;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.app-version {
  text-align: center;
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.35);
  padding: 4px 0;
  letter-spacing: 0.3px;
}

/* ============================================================
   USER SECTION
   ============================================================ */
.user-section {
  padding: 4px 12px 12px;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.06);
  transition: background 0.2s;
}

.user-card:hover {
  background: rgba(255, 255, 255, 0.1);
}

.user-card.rail-mode {
  justify-content: center;
  padding: 8px 4px;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 0.68rem;
  color: rgba(255, 255, 255, 0.55);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

/* ============================================================
   BOUTON CHEVRON
   ============================================================ */
.sidebar-blue :deep(.v-btn) {
  color: #ffffff !important;
}

.sidebar-blue :deep(.v-btn:hover) {
  background: rgba(255, 255, 255, 0.12);
}

/* ============================================================
   RAIL MODE
   ============================================================ */
.sidebar-blue :deep(.v-navigation-drawer--rail .v-list-item) {
  padding-inline: 8px !important;
}

.sidebar-blue :deep(.v-navigation-drawer--rail .v-list-item__prepend) {
  margin-inline-end: 0 !important;
}
</style>