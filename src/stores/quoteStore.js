import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useQuoteStore = defineStore('quotes', () => {
  const quotes = ref([])
  const loading = ref(false)

  async function fetchQuotes() {
    loading.value = true
    try {
     const response = await fetch('/api/quotes.php')
      const json = await response.json()
      if (json.success) {
        quotes.value = json.data
      } else {
        console.error('API-Fehler:', json.error)
      }
    } catch (err) {
      console.error('Fehler beim Laden der Angebote:', err)
    } finally {
      loading.value = false
    }
  }

  return { quotes, loading, fetchQuotes }
})