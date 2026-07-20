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

    <v-list nav dense>
      <!-- DASHBOARD -->
      <v-list-item
        prepend-icon="mdi-view-dashboard-outline"
        title="Dashboard"
        :active="activeTab === 'dashboard'"
        @click="navigateTo('dashboard')"
      />

      <!-- TICKETS -->
      <v-list-item
        prepend-icon="mdi-ticket-outline"
        title="Tickets"
        :active="activeTab === 'tickets'"
        @click="navigateTo('tickets')"
      />

      <!-- PRODUKTE (Hauptgruppe) -->
      <v-list-group value="products">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Produkte" prepend-icon="mdi-package-variant" />
        </template>

        <!-- Alle Produkte -->
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Produkte"
          @click="navigateTo('products-list')"
        />

        <!-- Neues Produkt -->
        <v-list-item
          prepend-icon="mdi-plus-box-outline"
          title="Neues Produkt"
          @click="navigateTo('add-product')"
        />

        <!-- Kategorien (als Untergruppe mit eigenen Unterpunkten) -->
        <v-list-group value="categories">
          <template #activator="{ props }">
            <v-list-item v-bind="props" title="Kategorien" prepend-icon="mdi-tag-outline" />
          </template>

          <!-- Artikeltypen -->
          <v-list-item
            prepend-icon="mdi-format-list-checks"
            title="Artikeltypen"
            @click="navigateTo('article-types')"
          />

          <!-- Hersteller -->
          <v-list-item
            prepend-icon="mdi-domain"
            title="Hersteller"
            @click="navigateTo('manufacturers')"
          />
        </v-list-group>
      </v-list-group>

      <!-- KUNDEN -->
      <v-list-group value="customers">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Kunden" prepend-icon="mdi-account-group-outline" />
        </template>
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Kunden"
          @click="navigateTo('customers-list')"
        />
        <v-list-item
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Kunde"
          @click="navigateTo('add-customer')"
        />
      </v-list-group>

      <!-- MITARBEITER -->
      <v-list-group value="employees">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Mitarbeiter" prepend-icon="mdi-account-tie-outline" />
        </template>
        <v-list-item
          prepend-icon="mdi-account-multiple-outline"
          title="Mitarbeiter Übersicht"
          @click="navigateTo('employees-list')"
        >
          <template #append>
            <v-chip size="x-small" color="primary">NEW</v-chip>
          </template>
        </v-list-item>
        <v-list-item
          prepend-icon="mdi-account-plus-outline"
          title="Neuer Mitarbeiter"
          @click="navigateTo('add-employee')"
        />
        <v-list-item
          prepend-icon="mdi-card-account-details-outline"
          title="Profile"
          @click="navigateTo('employee-profile')"
        />
      </v-list-group>

      <!-- BESTELLUNGEN (Dropdown) -->
      <v-list-group value="orders">
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="Bestellungen" prepend-icon="mdi-cart-outline" />
        </template>
        <v-list-item
          prepend-icon="mdi-format-list-bulleted"
          title="Alle Bestellungen"
          @click="navigateTo('orders-list')"
        />
        <v-list-item
          prepend-icon="mdi-cart-plus"
          title="Neue Bestellung"
          @click="navigateTo('add-order')"
        />
      </v-list-group>

      <v-divider class="my-2" />

      <v-list-item
        prepend-icon="mdi-chart-line"
        title="Statistiken"
        @click="navigateTo('stats')"
      />
      <v-list-item
        prepend-icon="mdi-help-circle-outline"
        title="Hilfe"
        @click="navigateTo('help')"
      />
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()

// Aktiven Menüpunkt anhand des Pfades ermitteln
const activeTab = computed(() => {
  const path = route.path

  if (path === '/') return 'dashboard'
  if (path === '/tickets') return 'tickets'

  // Produkt‑Unterpunkte
  if (path === '/products') return 'products-list'
  if (path === '/products/add') return 'add-product'

  // Kategorien‑Unterpunkte (neu)
  if (path === '/products/categories/types') return 'article-types'
  if (path === '/products/categories/manufacturers') return 'manufacturers'
  // Falls Sie eine eigene Übersichtsseite für Kategorien haben:
  // if (path === '/products/categories') return 'categories'

  // Kunden
  if (path === '/customers') return 'customers-list'
  if (path === '/customers/add') return 'add-customer'

  // Mitarbeiter
  if (path === '/employees') return 'employees-list'
  if (path === '/employees/add') return 'add-employee'
  if (path.startsWith('/employees/')) return 'employee-profile'

  // Bestellungen
  if (path === '/orders') return 'orders-list'
  if (path === '/orders/add') return 'add-order'

  if (path === '/stats') return 'stats'
  if (path === '/help') return 'help'

  return 'dashboard'
})

// Navigation zu den definierten Routen
const navigateTo = (tab) => {
  const routes = {
    dashboard: '/',
    tickets: '/tickets',

    'products-list': '/products',
    'add-product': '/products/add',

    // Kategorien‑Unterpunkte (neue Pfade)
    'article-types': '/products/categories/types',
    manufacturers: '/products/categories/manufacturers',

    'customers-list': '/customers',
    'add-customer': '/customers/add',

    'employees-list': '/employees',
    'add-employee': '/employees/add',
    'employee-profile': '/employees/1',

    'orders-list': '/orders',
    'add-order': '/orders/add',

    stats: '/stats',
    help: '/help'
  }

  router.push(routes[tab] || '/')
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
.v-list-item {
  min-height: 38px !important;
}
.v-list-item--active {
  background: rgba(25, 118, 210, 0.15);
  color: #1976d2;
  font-weight: 600;
}
.v-list-item--active .v-icon {
  color: #1976d2;
}
/* Erhöhte Einrückung für die Unter‑Unterpunkte (Artikeltypen & Hersteller) */
.v-list-group__items .v-list-group__items .v-list-item {
  padding-left: 48px !important;
}
</style>