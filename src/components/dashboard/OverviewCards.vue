<template>
  <v-row>
    <v-col
      v-for="(card, index) in cardsData"
      :key="index"
      cols="12"
      sm="6"
      lg="3"
    >
      <v-card
        :style="{ background: card.cardGradient }"
        class="pa-4 cursor-pointer"
        elevation="3"
        rounded="lg"
        @click="card.action"
      >
        <div class="d-flex justify-space-between align-start mb-3">
          <div
            class="d-flex align-center justify-center"
            :style="{
              backgroundColor: card.iconBgColor,
              width: '56px',
              height: '56px',
              borderRadius: '16px',
            }"
          >
            <v-icon :color="card.iconColor" size="32">
              {{ card.icon }}
            </v-icon>
          </div>
          <div class="text-right">
            <div
              class="text-h4 font-weight-bold"
              :style="{ color: card.textColor }"
            >
              {{ card.value }}
            </div>
            <div class="text-subtitle-2 font-weight-medium" :style="{ color: card.subtitleColor }">
              {{ card.subtitle }}
            </div>
          </div>
        </div>

        <div class="mb-3">
          <div class="text-h6 font-weight-medium" :style="{ color: card.textColor }">
            {{ card.title }}
          </div>
          <div class="text-caption" :style="{ color: card.subtitleColor }">
            {{ card.description }}
          </div>
        </div>

        <!-- Fortschrittsbalken -->
        <div class="mt-2" style="min-height: 32px;">
          <div v-if="card.progress !== undefined">
            <v-progress-linear
              :model-value="card.progress"
              :color="card.progressColor"
              height="6"
              rounded
            />
            <div class="d-flex justify-space-between mt-1">
              <span class="text-caption" :style="{ color: card.subtitleColor }">
                {{ card.progressLabel }}
              </span>
              <span class="text-caption font-weight-bold" :style="{ color: card.textColor }">
                {{ card.progressValue }}%
              </span>
            </div>
          </div>
          <div v-else style="height: 0;"></div>
        </div>

        <v-divider class="my-3" :style="{ borderColor: card.dividerColor }" />

        <div class="d-flex align-center justify-end">
          <v-chip
            :color="card.footerColor"
            :text-color="card.footerTextColor"
            size="small"
            variant="flat"
            class="px-3"
            :ripple="false"
          >
            <span class="text-caption font-weight-medium">{{ card.actionText }}</span>
            <v-icon size="16" class="ml-1">mdi-arrow-right</v-icon>
          </v-chip>
        </div>
      </v-card>
    </v-col>

    <!-- Lade-Overlay (optional) -->
    <v-overlay v-model="loading" class="align-center justify-center" persistent>
      <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
    </v-overlay>
  </v-row>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Dashboard-Daten
const dashboard = ref({
  totalProducts: 0,
  inStockCount: 0,
  totalStockValue: 0,
  averageProductValue: 0,
  totalCustomers: 0,
  activeCustomers: 0,
  totalQuotes: 0,
  pendingQuotes: 0,
  totalOrders: 0,
  pendingOrders: 0,
  totalRevenue: 0
})

const loading = ref(true)

// Daten von der API laden
const fetchDashboardData = async () => {
  try {
    const response = await fetch('https://alpha-med-care.com/api/overviewcards.php')
    const json = await response.json()
    if (json.success) {
      dashboard.value = json.data
    } else {
      console.error('API-Fehler:', json.error)
    }
  } catch (err) {
    console.error('Netzwerkfehler:', err)
  } finally {
    loading.value = false
  }
}

// --- Computed Properties für die Karten ---
const totalProducts = computed(() => dashboard.value.totalProducts)
const inStockCount = computed(() => dashboard.value.inStockCount)
const totalStockValue = computed(() => dashboard.value.totalStockValue)
const averageProductValue = computed(() => dashboard.value.averageProductValue)

const totalCustomers = computed(() => dashboard.value.totalCustomers)
const activeCustomers = computed(() => dashboard.value.activeCustomers)

const totalQuotes = computed(() => dashboard.value.totalQuotes)
const pendingQuotes = computed(() => dashboard.value.pendingQuotes)

const totalOrders = computed(() => dashboard.value.totalOrders)
const pendingOrders = computed(() => dashboard.value.pendingOrders)
const totalRevenue = computed(() => dashboard.value.totalRevenue)

// Prozentwerte
const inStockPercentage = computed(() =>
  totalProducts.value > 0 ? Math.round((inStockCount.value / totalProducts.value) * 100) : 0
)
const activeCustomerPercentage = computed(() =>
  totalCustomers.value > 0 ? Math.round((activeCustomers.value / totalCustomers.value) * 100) : 0
)
const pendingQuotesPercentage = computed(() =>
  totalQuotes.value > 0 ? Math.round((pendingQuotes.value / totalQuotes.value) * 100) : 0
)
const pendingOrdersPercentage = computed(() =>
  totalOrders.value > 0 ? Math.round((pendingOrders.value / totalOrders.value) * 100) : 0
)

// Karten-Daten
const cardsData = computed(() => [
  {
    title: 'Produkte',
    value: totalProducts.value,
    subtitle: `${inStockCount.value} auf Lager`,
    description: 'Aktive Artikel',
    icon: 'mdi-medical-bag',
    cardGradient: 'linear-gradient(135deg, #1B5E20 0%, #81C784 100%)',
    footerColor: '#C8E6C9',
    textColor: '#FFFFFF',
    subtitleColor: '#E8F5E9',
    footerTextColor: '#1B5E20',
    iconColor: '#FFD54F',
    iconBgColor: 'rgba(255, 213, 79, 0.25)',
    dividerColor: '#E8F5E980',
    action: () => router.push('/products'),
    actionText: 'Produkte anzeigen',
    progress: inStockPercentage.value,
    progressColor: '#FFD54F',
    progressLabel: 'Verfügbarkeit',
    progressValue: inStockPercentage.value
  },
  {
    title: 'Kunden',
    value: totalCustomers.value,
    subtitle: `${activeCustomers.value} aktiv`,
    description: 'Gesamte Kundenbasis',
    icon: 'mdi-account-group',
    cardGradient: 'linear-gradient(135deg, #0D47A1 0%, #64B5F6 100%)',
    footerColor: '#BBDEFB',
    textColor: '#FFFFFF',
    subtitleColor: '#E3F2FD',
    footerTextColor: '#0D47A1',
    iconColor: '#FFD54F',
    iconBgColor: 'rgba(255, 213, 79, 0.25)',
    dividerColor: '#E3F2FD80',
    action: () => router.push('/customers'),
    actionText: 'Kunden verwalten',
    progress: activeCustomerPercentage.value,
    progressColor: '#FFD54F',
    progressLabel: 'Aktivitätsrate',
    progressValue: activeCustomerPercentage.value
  },
  {
    title: 'Bestellungen',
    value: totalOrders.value,
    subtitle: `${pendingOrders.value} ausstehend`,
    description: `Umsatz: ${totalRevenue.value.toLocaleString()} €`,
    icon: 'mdi-truck-delivery',
    cardGradient: 'linear-gradient(135deg, #E65100 0%, #FFB74D 100%)',
    footerColor: '#FFE0B2',
    textColor: '#FFFFFF',
    subtitleColor: '#FFF3E0',
    footerTextColor: '#E65100',
    iconColor: '#FFD54F',
    iconBgColor: 'rgba(255, 213, 79, 0.25)',
    dividerColor: '#FFF3E080',
    action: () => router.push('/orders'),
    actionText: 'Bestellungen verwalten',
    progress: pendingOrdersPercentage.value,
    progressColor: '#FFD54F',
    progressLabel: 'Offene Bestellungen',
    progressValue: pendingOrdersPercentage.value
  },
  {
    title: 'Angebote',
    value: pendingQuotes.value,
    subtitle: `${totalQuotes.value} insgesamt`,
    description: 'Ausstehende Anfragen',
    icon: 'mdi-file-document',
    cardGradient: 'linear-gradient(135deg, #BF360C 0%, #FFB74D 100%)',
    footerColor: '#FFE0B2',
    textColor: '#FFFFFF',
    subtitleColor: '#FFF3E0',
    footerTextColor: '#BF360C',
    iconColor: '#FFD54F',
    iconBgColor: 'rgba(255, 213, 79, 0.25)',
    dividerColor: '#FFF3E080',
    action: () => router.push('/quotes'),
    actionText: 'Angebote einsehen',
    progress: pendingQuotesPercentage.value,
    progressColor: '#FFD54F',
    progressLabel: 'In Bearbeitung',
    progressValue: pendingQuotesPercentage.value
  },
  {
    title: 'Lagerwert',
    value: `${totalStockValue.value.toLocaleString()} €`,
    subtitle: `${Math.round(averageProductValue.value).toLocaleString()} € / Produkt`,
    description: 'Gesamter Einkaufswert (Verkaufspreis)',
    icon: 'mdi-currency-eur',
    cardGradient: 'linear-gradient(135deg, #4A148C 0%, #CE93D8 100%)',
    footerColor: '#E1BEE7',
    textColor: '#FFFFFF',
    subtitleColor: '#F3E5F5',
    footerTextColor: '#4A148C',
    iconColor: '#FFD54F',
    iconBgColor: 'rgba(255, 213, 79, 0.25)',
    dividerColor: '#F3E5F580',
    action: () => router.push('/products'),
    actionText: 'Lagerdetails',
    progress: undefined,
    progressLabel: '',
    progressValue: 0
  },
])

onMounted(() => {
  fetchDashboardData()
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: all 0.25s ease;
}
.cursor-pointer:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 28px rgba(0, 0, 0, 0.25) !important;
}

:deep(.v-chip) {
  transition: transform 0.2s ease, background-color 0.2s ease;
  font-weight: 500;
}

.cursor-pointer:hover :deep(.v-chip) {
  transform: translateX(4px);
}

:deep(.v-progress-linear__background) {
  opacity: 0.3;
}
</style>