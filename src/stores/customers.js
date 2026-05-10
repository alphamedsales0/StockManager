import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCustomerStore = defineStore('customers', () => {
  const customers = ref([])
  const loading = ref(false)

  const totalCustomers = computed(() => customers.value.length)
  const activeCustomers = computed(() => customers.value.filter(c => c.active).length)

  async function fetchCustomers() {
    loading.value = true
    try {
     const response = await fetch('/api/customers.php')
      const json = await response.json()
      if (json.success) {
        customers.value = json.data
      } else {
        console.error('API-Fehler:', json.error)
      }
    } catch (err) {
      console.error('Fehler beim Laden der Kunden:', err)
    } finally {
      loading.value = false
    }
  }

  return { customers, loading, totalCustomers, activeCustomers, fetchCustomers }
})