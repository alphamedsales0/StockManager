<template>
  <v-navigation-drawer
    :rail="uiStore.rail"
    permanent
    color="grey-darken-4"
    class="elevation-3"
    :width="uiStore.rail ? 56 : 280"
  >
    <!-- HEADER (unverändert) -->
    <div class="sidebar-header" :class="{ 'rail-mode': uiStore.rail }">
      <div
        class="d-flex align-center"
        :class="uiStore.rail ? 'justify-center' : 'justify-space-between'"
      >
        <div class="d-flex align-center">
          <v-icon size="32" color="white">mdi-database</v-icon>
          <span v-if="!uiStore.rail" class="text-h5 font-weight-bold text-white ml-2">
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
        />
      </div>
    </div>

    <v-divider class="my-2" />

    <v-list nav density="compact" class="pa-1">
      <!-- DASHBOARD – immer erlaubt -->
      <v-list-item
        prepend-icon="mdi-view-dashboard-outline"
        title="Dashboard"
        :active="activeTab === 'dashboard'"
        @click="navigateTo('dashboard')"
      />

      <!-- TICKETS – immer erlaubt -->
      <v-list-item
        prepend-icon="mdi-ticket-outline"
        title="Tickets"
        :active="activeTab === 'tickets'"
        @click="navigateTo('tickets')"
      />

      <!-- PRODUKTE – für alle sichtbar, aber nur Admin darf navigieren -->
      <v-list-group value="products">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Produkte" prepend-icon="mdi-package-variant" />
        </template>
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Produkte"
          :active="activeTab === 'products-list'"
          @click="navigateTo('products-list')"
        />
        <v-list-item
          prepend-icon="mdi-plus-box-outline"
          title="Neues Produkt"
          :active="activeTab === 'add-product'"
          @click="navigateTo('add-product')"
        />
        <v-list-item
          prepend-icon="mdi-tag-outline"
          title="Kategorien"
          :active="activeTab === 'categories'"
          @click="navigateTo('categories')"
        />
        <v-list-item
          prepend-icon="mdi-format-list-checks"
          title="Artikeltypen"
          :active="activeTab === 'article-types'"
          @click="navigateTo('article-types')"
        />
        <v-list-item
          prepend-icon="mdi-tag"
          title="Marken"
          :active="activeTab === 'brands'"
          @click="navigateTo('brands')"
        />
      </v-list-group>

      <!-- KUNDEN – für alle sichtbar -->
      <v-list-group value="customers">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Kunden" prepend-icon="mdi-account-group-outline" />
        </template>
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Kunden"
          :active="activeTab === 'customers-list'"
          @click="navigateTo('customers-list')"
        />
        <v-list-item
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Kunde"
          :active="activeTab === 'add-customer'"
          @click="navigateTo('add-customer')"
        />
      </v-list-group>

      <!-- MITARBEITER – für alle sichtbar -->
      <v-list-group value="employees">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Mitarbeiter" prepend-icon="mdi-account-tie-outline" />
        </template>
        <v-list-item
          prepend-icon="mdi-account-multiple-outline"
          :active="activeTab === 'employees-list'"
          @click="navigateTo('employees-list')"
        >
          <template #title>
            <span style="display: flex; align-items: center; gap: 6px; white-space: nowrap;">
              <span style="font-size: 0.9rem;">Alle Mitarbeiter</span>
            </span>
          </template>
        </v-list-item>
        <v-list-item
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Mitarbeiter"
          :active="activeTab === 'add-employee'"
          @click="navigateTo('add-employee')"
        />
        <v-list-item
          prepend-icon="mdi-card-account-details-outline"
          title="Profile"
          :active="activeTab === 'employee-profile'"
          @click="navigateTo('employee-profile')"
        />
        <v-list-item
          prepend-icon="mdi-account-cog"
          title="Rollen"
          :active="activeTab === 'roles'"
          @click="navigateTo('roles')"
        />
      </v-list-group>

      <!-- BESTELLUNGEN – für alle sichtbar -->
      <v-list-group value="orders">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Bestellungen" prepend-icon="mdi-cart-outline" />
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

      <v-divider class="my-2" />

      <!-- STATISTIKEN – für alle sichtbar -->
      <v-list-item
        prepend-icon="mdi-chart-line"
        title="Statistiken"
        :active="activeTab === 'stats'"
        @click="navigateTo('stats')"
      />

      <!-- HILFE – immer erlaubt -->
      <v-list-item
        prepend-icon="mdi-help-circle-outline"
        title="Hilfe"
        :active="activeTab === 'help'"
        @click="navigateTo('help')"
      />
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'
import { usePermissionStore } from '../../stores/permission'

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()
const permissionStore = usePermissionStore()

// Liste der geschützten Tabs (nur Admin)
const protectedTabs = [
  'products-list', 'add-product', 'categories', 'article-types', 'brands',
  'customers-list', 'add-customer',
  'employees-list', 'add-employee', 'employee-profile', 'roles',
  'orders-list', 'add-order',
  'stats'
]

const activeTab = computed(() => {
  const path = route.path
  if (path === '/') return 'dashboard'
  if (path === '/tickets') return 'tickets'
  if (path === '/products') return 'products-list'
  if (path === '/products/add') return 'add-product'
  if (path === '/categories') return 'categories'
  if (path === '/article-types') return 'article-types'
  if (path === '/brands') return 'brands'
  if (path === '/customers') return 'customers-list'
  if (path === '/customers/add') return 'add-customer'
  if (path === '/employees') return 'employees-list'
  if (path === '/employees/add') return 'add-employee'
  if (path.startsWith('/employees/')) return 'employee-profile'
  if (path === '/roles') return 'roles'
  if (path === '/orders') return 'orders-list'
  if (path === '/orders/add') return 'add-order'
  if (path === '/stats') return 'stats'
  if (path === '/help') return 'help'
  return 'dashboard'
})

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
    'employee-profile': '/employees/1',
    roles: '/roles',
    'orders-list': '/orders',
    'add-order': '/orders/add',
    stats: '/stats',
    help: '/help'
  }

  const target = routes[tab] || '/'

  if (protectedTabs.includes(tab)) {
    // Geschützter Tab – prüfe Berechtigung
    permissionStore.checkPermission('Admin', () => {
      router.push(target)
    })
  } else {
    // Ungeschützte Tabs (dashboard, tickets, help) – immer erlaubt
    router.push(target)
  }
}
</script>

<style scoped>
.sidebar-header {
  padding: 20px 16px;
  background: rgba(0, 0, 0, 0.25);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.sidebar-header.rail-mode {
  padding: 20px 8px;
}
.v-navigation-drawer {
  border-right: 1px solid rgba(0, 0, 0, 0.08);
}
.v-list-item .v-list-item__prepend {
  margin-inline-end: 6px !important;
  min-width: 30px !important;
}
.v-list-item .v-list-item__content {
  overflow: visible !important;
}
.v-list-item .v-list-item__title {
  font-size: 0.9rem;
  white-space: nowrap;
}
.v-list-item .v-list-item__title .v-chip {
  flex-shrink: 0;
}
.v-list-item--active {
  background: rgba(25, 118, 210, 0.15);
  color: #1976d2;
  font-weight: 600;
}
.v-list-item--active .v-icon {
  color: #1976d2;
}
</style>