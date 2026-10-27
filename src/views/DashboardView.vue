<template>
  <div class="dashboard-container">
    <v-container fluid class="pa-4">

      <!-- HEADER -->
      <v-row class="mb-4">
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center flex-wrap">
            <div class="mb-2">
              <div class="d-flex align-center">
                <v-icon icon="mdi-view-dashboard" class="mr-2" size="small"></v-icon>
                <h1 class="text-h5 font-weight-bold text-grey-darken-3">Übersicht</h1>
              </div>
              <p class="text-caption text-grey-darken-1 mt-1">
                Gesamtübersicht Ihrer Aktivitäten (Produkte, Kunden, Angebote)
              </p>
            </div>
            <QuickActions
              :loading="loading"
              :can-write-products="permissionStore.can('products-write')"
              :can-export="permissionStore.can('stats')"
              @new-product="addNewProduct"
              @export-report="exportReport"
              @refresh="refreshAll"
            />
          </div>
        </v-col>
      </v-row>

      <!-- OVERVIEW CARDS -->
      <OverviewCards
        v-if="permissionStore.can('dashboard')"
        class="mb-4"
      />

      <!-- STATISTICS CARDS -->
      <StatisticsCards
        v-if="permissionStore.can('products') || permissionStore.can('stats')"
        class="mb-4"
      />

      <!-- RECENT TICKETS + MONTHLY SALES -->
      <v-row>
        <v-col
          v-if="permissionStore.can('tickets')"
          cols="12"
          :md="showMonthlySales ? 8 : 12"
          class="tall-card"
        >
          <RecentTickets />
        </v-col>

        <v-col
          v-if="showMonthlySales"
          cols="12"
          :md="permissionStore.can('tickets') ? 4 : 12"
        >
          <MonthlySales />
        </v-col>
      </v-row>

      <!-- FALLBACK -->
      <v-row v-if="!hasAnyDashboardContent" justify="center" class="mt-8">
        <v-col cols="12" md="6" class="text-center">
          <v-icon size="80" color="grey-lighten-1" class="mb-3">
            mdi-view-dashboard-outline
          </v-icon>
          <h2 class="text-h6 font-weight-medium mb-2">
            Kein Zugriff auf Dashboard-Inhalte
          </h2>
          <p class="text-body-2 text-grey-darken-1 mb-4">
            Sie haben keine Berechtigung, Dashboard-Inhalte anzuzeigen.
          </p>
          <v-btn
            color="primary"
            variant="tonal"
            prepend-icon="mdi-arrow-right"
            @click="goToFirstAccessible"
          >
            Zum ersten verfügbaren Bereich
          </v-btn>
        </v-col>
      </v-row>

    </v-container>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { usePermissionStore } from '../stores/permission'
import { useProductStore } from '../stores/stock_manager_products'
import { useCustomerStore } from '../stores/customers'
import { useQuoteStore } from '../stores/quoteStore'

import OverviewCards from '../components/dashboard/OverviewCards.vue'
import StatisticsCards from '../components/dashboard/StatisticsCards.vue'
import QuickActions from '../components/dashboard/QuickActions.vue'
import RecentTickets from '../components/dashboard/RecentTickets.vue'
import MonthlySales from '../components/dashboard/MonthlySales.vue'

const router = useRouter()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()
const productStore = useProductStore()
const customerStore = useCustomerStore()
const quoteStore = useQuoteStore()

const loading = ref(false)

const showMonthlySales = computed(() => {
  return permissionStore.can('stats') || permissionStore.can('orders')
})

const hasAnyDashboardContent = computed(() => {
  return (
    permissionStore.can('dashboard') ||
    permissionStore.can('tickets') ||
    permissionStore.can('products') ||
    permissionStore.can('stats') ||
    permissionStore.can('orders')
  )
})

const loadData = async () => {
  loading.value = true
  try {
    const promises = []
    if (permissionStore.can('products')) promises.push(productStore.fetchProducts())
    if (permissionStore.can('customers')) promises.push(customerStore.fetchCustomers())
    if (permissionStore.can('orders')) promises.push(quoteStore.fetchQuotes())
    await Promise.all(promises)
  } catch (error) {
    console.error('Fehler beim Laden der Daten:', error)
  } finally {
    loading.value = false
  }
}

const refreshAll = () => loadData()

const addNewProduct = () => {
  if (!permissionStore.can('products-write')) {
    permissionStore.showAccessDenied()
    return
  }
  router.push('/products/add')
}

const exportReport = () => {
  if (!permissionStore.can('stats')) {
    permissionStore.showAccessDenied()
    return
  }
  console.log('Export')
}

const goToFirstAccessible = () => {
  const priority = [
    { path: '/', perm: 'dashboard' },
    { path: '/tickets', perm: 'tickets' },
    { path: '/products', perm: 'products' },
    { path: '/customers', perm: 'customers' },
    { path: '/employees', perm: 'employees' },
    { path: '/quotes', perm: 'orders' },
    { path: '/help', perm: 'help' },
  ]
  for (const item of priority) {
    if (permissionStore.can(item.perm)) {
      router.push(item.path)
      return
    }
  }
  router.push('/login')
}

onMounted(async () => {
  await authStore.initialize()
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }
  if (!permissionStore.permissionsLoaded) {
    await permissionStore.loadPermissions()
  }
  await loadData()
})
</script>

<style scoped>
.dashboard-container {
  max-width: 1600px;
  margin: 0 auto;
}
.mb-4 {
  margin-bottom: 1.5rem !important;
}
.tall-card :deep(.v-card) {
  min-height: 350px;
}
</style>