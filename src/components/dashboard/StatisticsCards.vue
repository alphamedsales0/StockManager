<template>
  <v-row>
    <v-col cols="12" md="4" class="tall-card">
      <ProductCategory />
    </v-col>

    <v-col cols="12" md="4">
      <RecentActivities />
    </v-col>

    <v-col cols="12" md="4">
      <LowStockAlert />
    </v-col>
  </v-row>
</template>

<script setup>
import RecentActivities from './RecentActivities.vue'
import LowStockAlert from './LowStockAlert.vue'
import ProductCategory from './ProductCategory.vue'
</script>

<style scoped>
.tall-card :deep(.v-card) {
  min-height: 350px;
}
</style>