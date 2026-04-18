// src/stores/uiStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const rail = ref(false)   // false = mode large, true = mode icônes (rail)

  function toggleRail() {
    rail.value = !rail.value
  }

  const drawerWidth = computed(() => rail.value ? 56 : 280)

  return { rail, drawerWidth, toggleRail }
})