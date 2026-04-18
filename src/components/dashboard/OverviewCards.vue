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

        <!-- Barre de progression ou placeholder pour hauteur égale -->
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
  </v-row>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '../../stores/products'
import { useCustomerStore } from '../../stores/customers'
import { useQuoteStore } from '../../stores/quotes'

const router = useRouter()
const productStore = useProductStore()
const customerStore = useCustomerStore()
const quoteStore = useQuoteStore()

// Daten aus den Stores
const totalProducts = computed(() => productStore.products?.length || 0)
const inStock = computed(() => productStore.products?.filter(p => p.stock > 0).length || 0)
const totalCustomers = computed(() => customerStore.customers?.length || 0)
const activeCustomers = computed(() => customerStore.customers?.filter(c => c.active !== false).length || 0)
const pendingQuotes = computed(() => quoteStore.quotes?.filter(q => q.status === 'pending').length || 0)
const totalQuotes = computed(() => quoteStore.quotes?.length || 0)
const totalStockValue = computed(() =>
  (productStore.products || []).reduce((sum, p) => sum + (p.purchasePrice * p.stock), 0)
)
const averageProductValue = computed(() =>
  totalProducts.value > 0 ? totalStockValue.value / totalProducts.value : 0
)

// Prozentsätze für Fortschrittsbalken
const inStockPercentage = computed(() =>
  totalProducts.value > 0 ? Math.round((inStock.value / totalProducts.value) * 100) : 0
)
const activeCustomerPercentage = computed(() =>
  totalCustomers.value > 0 ? Math.round((activeCustomers.value / totalCustomers.value) * 100) : 0
)
const pendingQuotesPercentage = computed(() =>
  totalQuotes.value > 0 ? Math.round((pendingQuotes.value / totalQuotes.value) * 100) : 0
)

const cardsData = computed(() => [
  {
    title: 'Produkte',
    value: totalProducts.value,
    subtitle: `${inStock.value} auf Lager`,
    description: 'Aktive Artikel',
    icon: 'mdi-medical-bag',
    // Vert sombre → vert clair
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
    // Bleu sombre → bleu clair
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
    title: 'Angebote',
    value: pendingQuotes.value,
    subtitle: `${totalQuotes.value} insgesamt`,
    description: 'Ausstehende Anfragen',
    icon: 'mdi-file-document',
    // Orange sombre → orange clair
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
    description: 'Gesamter Einkaufswert',
    icon: 'mdi-currency-eur',
    // Violet sombre → violet clair
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