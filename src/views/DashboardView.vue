<template>
  <v-container fluid class="pa-4 dashboard-container">
    <!-- Kopfbereich -->
    <v-row class="mb-4">
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center flex-wrap">
          <div class="mb-2">
            <div class="d-flex align-center">
              <v-icon icon="mdi-view-dashboard" class="mr-2" size="small"></v-icon>
              <h1 class="text-h5 font-weight-bold text-grey-darken-3">
                Übersicht
              </h1>
            </div>
            <p class="text-caption text-grey-darken-1 mt-1">
              Gesamtübersicht Ihrer Aktivitäten (Produkte, Kunden, Angebote)
            </p>
          </div>

          <!-- Composant Actions Rapides -->
          <QuickActions
            :loading="loading"
            @new-product="addNewProduct"
            @export-report="exportReport"
            @refresh="refreshAll"
          />
        </div>
      </v-col>
    </v-row>

    <!-- Cartes de statistiques (composant extrait) -->
    <OverviewCards />
    <StatisticsCards />

    <!-- Ligne unique pour les deux cartes : RecentTickets + MonthlySales -->
    <v-row>
      <!-- RecentTickets occupe 8/12 de la largeur -->
      <v-col  cols="12" md="8" class="tall-card">
        <RecentTickets />
      </v-col>
      <!-- MonthlySales occupe 4/12 de la largeur -->
      <v-col cols="12" md="4">
        <MonthlySales />
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '../stores/products'
import { useCustomerStore } from '../stores/customers'
import { useQuoteStore } from '../stores/quotes'
import OverviewCards from '../components/dashboard/OverviewCards.vue'
import StatisticsCards from '../components/dashboard/StatisticsCards.vue'
import QuickActions from '../components/dashboard/QuickActions.vue'
import RecentTickets from '../components/dashboard/RecentTickets.vue'
import MonthlySales from '../components/dashboard/MonthlySales.vue'

const router = useRouter()
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
    console.log('Daten erfolgreich geladen')
  } catch (error) {
    console.error('Fehler beim Laden der Daten:', error)
  } finally {
    loading.value = false
  }
}

const refreshAll = () => loadData()
const addNewProduct = () => router.push('/products/add')
const exportReport = () => console.log('Export du rapport')

onMounted(() => loadData())
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
  min-height: 350px;   /* ou height: 100% pour remplir la colonne */
}

</style>