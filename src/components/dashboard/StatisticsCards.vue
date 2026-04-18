<!-- src/components/Statistics.vue -->
<template>
  <v-row>
      <!-- Carte 1 : Graphique en pourcentage -->
    <v-col cols="12" md="4" class="tall-card">
      <PercentChart />
    </v-col>

    <!-- Carte 2 : Dernières activités -->
    <v-col cols="12" md="4">
      <RecentActivities />
    </v-col>

    <!-- Carte 3 : Alertes stock bas -->
    <v-col cols="12" md="4">
      <LowStockAlert />
    </v-col>
  </v-row>
</template>

<script setup>
import { computed } from 'vue'
import { useProductStore } from '../../stores/products'
import RecentActivities from './RecentActivities.vue'
import LowStockAlert from './LowStockAlert.vue'
import PercentChart from './PercentChart.vue'

const productStore = useProductStore()
const totalProducts = computed(() => productStore.products?.length || 0)
</script>

<style scoped>

.statistic-card {
  overflow: hidden;
  position: relative;
  transition: transform 0.2s, box-shadow 0.2s;
}
.statistic-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15) !important;
}
.card-content {
  padding: 24px 20px;
  position: relative;
  z-index: 1;
}

.tall-card :deep(.v-card) {
  min-height: 350px;   /* ou height: 100% pour remplir la colonne */
}

.number {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 8px;
  line-height: 1.2;
}
.desc {
  font-size: 0.9rem;
  opacity: 0.9;
  color: rgba(0,0,0,0.7);
}
.card-icon {
  position: absolute;
  bottom: 16px;
  right: 16px;
  opacity: 0.2;
}
.green-card {
  background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
}
.green-card .number {
  color: #1B5E20;
}
@media (max-width: 600px) {
  .number {
    font-size: 1.6rem;
  }
  .desc {
    font-size: 0.8rem;
  }
  .card-icon {
    font-size: 36px !important;
  }
}
</style>