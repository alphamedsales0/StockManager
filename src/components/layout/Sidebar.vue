<template>
  <v-navigation-drawer
    :rail="uiStore.rail"
    permanent
    @click="uiStore.rail = false"
    color="grey-darken-4"
    class="elevation-3"
    :width="uiStore.rail ? 56 : 280"
  >
    <!-- Grand Header personnalisé -->
    <div class="sidebar-header" :class="{ 'rail-mode': uiStore.rail }">
      <div class="d-flex align-center" :class="uiStore.rail ? 'justify-center' : 'justify-space-between'">
        <!-- Icône database + titre -->
        <div class="d-flex align-center">
          <v-icon icon="mdi-database" size="32" color="white"></v-icon>
          <span v-if="!uiStore.rail" class="text-h5 font-weight-bold text-white ml-2">
            StockManager
          </span>
        </div>
        <!-- Bouton de basculement (uniquement en mode ouvert) -->
        <v-btn
          v-if="!uiStore.rail"
          variant="text"
          icon="mdi-chevron-left"
          @click.stop="uiStore.toggleRail"
          size="small"
          color="white"
        />
      </div>
    </div>

    <v-divider class="my-2" />

    <v-list dense nav class="py-0">
      <!-- Dashboard -->
      <v-list-item
        prepend-icon="mdi-view-dashboard"
        title="Dashboard"
        :active="activeTab === 'dashboard'"
        @click="navigateTo('dashboard')"
        class="px-0"
      />

      <!-- PRODUKTE -->
      <v-list-group value="products" prepend-icon="mdi-package">
        <template v-slot:activator="{ props }">
          <v-list-item v-bind="props" title="Produkte" prepend-icon="mdi-package" />
        </template>
        <v-list-item prepend-icon="mdi-format-list-bulleted" title="Alle Produkte" @click="navigateTo('products-list')" />
        <v-list-item prepend-icon="mdi-plus-box" title="Neues Produkt" @click="navigateTo('add-product')" />
        <v-list-item prepend-icon="mdi-tag" title="Kategorien" @click="navigateTo('categories')" />
      </v-list-group>

      <!-- KUNDEN -->
      <v-list-group value="customers" prepend-icon="mdi-account-group">
        <template v-slot:activator="{ props }">
          <v-list-item v-bind="props" title="Kunden" prepend-icon="mdi-account-group" />
        </template>
        <v-list-item prepend-icon="mdi-format-list-bulleted" title="Alle Kunden" @click="navigateTo('customers-list')" />
        <v-list-item prepend-icon="mdi-account-plus" title="Neuer Kunde" @click="navigateTo('add-customer')" />
      </v-list-group>

      <!-- BESTELLUNGEN -->
      <v-list-group value="orders" prepend-icon="mdi-cart">
        <template v-slot:activator="{ props }">
          <v-list-item v-bind="props" title="Bestellungen" prepend-icon="mdi-cart" />
        </template>
        <v-list-item prepend-icon="mdi-format-list-bulleted" title="Alle Bestellungen" @click="navigateTo('orders-list')" />
        <v-list-item prepend-icon="mdi-cart-plus" title="Neue Bestellung" @click="navigateTo('add-order')" />
      </v-list-group>

      <v-list-item prepend-icon="mdi-chart-line" title="Statistiken" @click="navigateTo('stats')" class="px-0" />
      <v-divider class="my-2" />
      <v-list-item prepend-icon="mdi-help-circle" title="Hilfe" @click="navigateTo('help')" class="px-0" />
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '../../stores/uiStore'

const router = useRouter()
const uiStore = useUiStore()
const activeTab = ref('dashboard')

const navigateTo = (tab) => {
  activeTab.value = tab
  const routes = {
    dashboard: '/',
    'products-list': '/products',
    'add-product': '/products/add',
    categories: '/products/categories',
    'customers-list': '/customers',
    'add-customer': '/customers/add',
    'orders-list': '/orders',
    'add-order': '/orders/add',
    stats: '/stats',
    help: '/help'
  }
  router.push(routes[tab] || '/')
}
</script>

<style scoped>
/* Styles du sidebar header */
.sidebar-header {
  padding: 20px 16px;
  background: rgba(0, 0, 0, 0.2);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.2s ease;
}

.sidebar-header.rail-mode {
  padding: 20px 8px;
}

/* Styles existants */
.v-navigation-drawer {
  border-right: 1px solid rgba(0, 0, 0, 0.08);
}
.v-navigation-drawer .v-list-item {
  min-height: 36px !important;
  padding-top: 2px !important;
  padding-bottom: 2px !important;
}
.v-navigation-drawer .v-list-item--active {
  background-color: rgba(25, 118, 210, 0.08);
  color: #1976d2;
  font-weight: 500;
}
.v-navigation-drawer .v-list-item--active .v-icon {
  color: #1976d2;
}
.v-navigation-drawer .v-list-group__items .v-list-item {
  padding-left: 32px !important;
}
@media (max-width: 600px) {
  .v-navigation-drawer {
    width: 56px !important;
  }
  .v-navigation-drawer:not(.v-navigation-drawer--rail) {
    width: 280px !important;
  }
}
</style>