<template>
  <v-card class="pa-4" rounded="lg" elevation="2">
    <v-card-title class="text-h5">Revenue Distribution</v-card-title>
    <canvas id="percentChart" style="height: 250px; width: 100%;"></canvas>
  </v-card>
</template>

<script setup>
import { onMounted } from 'vue'
//import { Chart } from 'chart.js'

onMounted(() => {
  const ctx = document.getElementById('percentChart')?.getContext('2d')
  if (ctx) {
    new Chart(ctx, { type: 'doughnut', data: { labels: ['Products', 'Services', 'Subscriptions'], datasets: [{ data: [55, 25, 20], backgroundColor: ['#4272d7', '#f5a623', '#0acf97'] }] } })
  }
})
</script>