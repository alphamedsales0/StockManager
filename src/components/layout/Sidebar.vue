<template>
  <v-navigation-drawer
    :rail="uiStore.rail"
    permanent
    color="grey-darken-4"
    class="elevation-3"
    :width="uiStore.rail ? 56 : 280"
  >
    <!-- HEADER -->
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

      <!-- DASHBOARD — Admin + Office -->
      <v-list-item
        v-if="permissionStore.can('dashboard')"
        prepend-icon="mdi-view-dashboard-outline"
        title="Dashboard"
        :active="activeTab === 'dashboard'"
        @click="navigateTo('dashboard')"
      />

      <!-- TICKETS — tous les rôles -->
      <v-list-item
        prepend-icon="mdi-ticket-outline"
        title="Tickets"
        :active="activeTab === 'tickets'"
        @click="navigateTo('tickets')"
      />

      <!-- PRODUKTE — Admin + Office + Technician (lecture seule pour Technician) -->
      <v-list-group v-if="permissionStore.can('products')" value="products">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Produkte" prepend-icon="mdi-package-variant" />
        </template>

        <!-- Toujours visible -->
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Produkte"
          :active="activeTab === 'products-list'"
          @click="navigateTo('products-list')"
        />

        <!-- Admin / Office uniquement -->
        <v-list-item
          v-if="permissionStore.can('products-write')"
          prepend-icon="mdi-plus-box-outline"
          title="Neues Produkt"
          :active="activeTab === 'add-product'"
          @click="navigateTo('add-product')"
        />

        <!-- Admin / Office uniquement -->
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

      <!-- KUNDEN — Admin + Office (écriture) + Technician (lecture) -->
      <v-list-group v-if="permissionStore.can('customers')" value="customers">
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
          v-if="permissionStore.can('customers-write')"
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Kunde"
          :active="activeTab === 'add-customer'"
          @click="navigateTo('add-customer')"
        />
      </v-list-group>

      <!-- MITARBEITER — Admin uniquement -->
      <v-list-group v-if="permissionStore.can('employees')" value="employees">
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

      <!-- BESTELLUNGEN — Admin + Office -->
      <v-list-group v-if="permissionStore.can('orders')" value="orders">
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

      <v-divider v-if="permissionStore.can('stats')" class="my-2" />

      <!-- STATISTIKEN — Admin uniquement -->
      <v-list-item
        v-if="permissionStore.can('stats')"
        prepend-icon="mdi-chart-line"
        title="Statistiken"
        :active="activeTab === 'stats'"
        @click="navigateTo('stats')"
      />

      <!-- HILFE — tous les rôles -->
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

  // Routes publiques (toujours autorisées)
  const publicTabs = ['tickets', 'help']
  if (publicTabs.includes(tab)) {
    router.push(target)
    return
  }

  // Routes protégées
  const key = getParentKey(tab)
  if (permissionStore.can(key)) {
    router.push(target)
  } else {
    permissionStore.showAccessDenied()
  }
}

// ============================================================
// Retourne la clé de permission d'un onglet
// ============================================================
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