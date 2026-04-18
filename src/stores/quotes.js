import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useQuoteStore = defineStore('quotes', () => {
  const quotes = ref([
    { id: 1, status: 'pending' },
    { id: 2, status: 'approved' },
    { id: 3, status: 'pending' },
  ])

  const pendingQuotes = computed(() => quotes.value.filter(q => q.status === 'pending').length)

  return { quotes, pendingQuotes }
})