<template>
  <!-- Dashboard (nur wenn eingeloggt) -->
  <div v-if="authStore.isLoggedIn" class="dashboard-container">
    <v-container fluid class="pa-4">
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
              @new-product="addNewProduct"
              @export-report="exportReport"
              @refresh="refreshAll"
            />
          </div>
        </v-col>
      </v-row>

      <OverviewCards />
      <StatisticsCards />

      <v-row>
        <v-col cols="12" md="8" class="tall-card">
          <RecentTickets />
        </v-col>
        <v-col cols="12" md="4">
          <MonthlySales />
        </v-col>
      </v-row>
    </v-container>
  </div>

  <!-- Login (wenn nicht eingeloggt) -->
  <Login v-else />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useProductStore } from '../stores/stock_manager_products'
import { useCustomerStore } from '../stores/customers'
import { useQuoteStore } from '../stores/quoteStore'

import OverviewCards from '../components/dashboard/OverviewCards.vue'
import StatisticsCards from '../components/dashboard/StatisticsCards.vue'
import QuickActions from '../components/dashboard/QuickActions.vue'
import RecentTickets from '../components/dashboard/RecentTickets.vue'
import MonthlySales from '../components/dashboard/MonthlySales.vue'
import Login from '../components/auth/Login.vue'

const router = useRouter()
const authStore = useAuthStore()
const productStore = useProductStore()
const customerStore = useCustomerStore()
const quoteStore = useQuoteStore()

const loading = ref(false)

const loadData = async () => {
  loading.value = true
  try {
    await Promise.all([
      productStore.fetchProducts(),
      customerStore.fetchCustomers(),
      quoteStore.fetchQuotes()
    ])
  } catch (error) {
    console.error('Fehler beim Laden der Daten:', error)
  } finally {
    loading.value = false
  }
}

const refreshAll = () => loadData()
const addNewProduct = () => router.push('/products/add')
const exportReport = () => console.log('Export')

onMounted(async () => {
  await authStore.initialize()
  if (authStore.isLoggedIn) {
    await loadData()
  }
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