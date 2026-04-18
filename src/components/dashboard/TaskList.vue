<template>
  <v-card rounded="lg" elevation="2">
    <v-card-title class="d-flex justify-space-between align-center">
      <div><v-icon icon="mdi-calendar-account" class="mr-2"></v-icon> 23 January, 2025</div>
      <v-btn icon="mdi-plus" variant="text"></v-btn>
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text>
      <div class="font-weight-bold mb-3">Tasks for John Doe</div>
      <v-list density="compact">
        <v-list-item v-for="task in visibleTasks" :key="task.name" :class="`border-l-4 border-${task.color}`" class="mb-2">
          <v-list-item-title>{{ task.name }}</v-list-item-title>
          <template v-slot:append><span class="text-caption text-grey">{{ task.time }}</span></template>
        </v-list-item>
      </v-list>
      <div class="text-center mt-3" v-if="tasks.length > visibleCount">
        <v-btn variant="tonal" rounded="pill" @click="loadMore">load more</v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'
const tasks = ref([
  { name: "Meeting about plan for Admin Template 2025", time: "10:00 AM", color: "error" },
  { name: "Create new task for Dashboard", time: "11:00 AM", color: "warning" },
  { name: "Review UI concepts", time: "02:00 PM", color: "primary" },
  { name: "Deploy to staging", time: "05:00 PM", color: "success" }
])
const visibleCount = ref(3)
const visibleTasks = computed(() => tasks.value.slice(0, visibleCount.value))
const loadMore = () => { visibleCount.value += 2 }
</script>

<style scoped>
.border-l-4 {
  border-left-width: 4px;
  border-left-style: solid;
  padding-left: 12px;
}
.border-error { border-left-color: rgb(245, 54, 92); }
.border-warning { border-left-color: rgb(245, 166, 35); }
.border-primary { border-left-color: rgb(66, 114, 215); }
.border-success { border-left-color: rgb(10, 207, 151); }
</style>