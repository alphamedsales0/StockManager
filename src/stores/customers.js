import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCustomerStore = defineStore('customers', () => {
  const customers = ref([
    { id: 1, name: 'Customer A', active: true },
    { id: 2, name: 'Customer B', active: false },
    { id: 3, name: 'Customer C', active: true },
  ])

  const totalCustomers = computed(() => customers.value.length)
  const activeCustomers = computed(() => customers.value.filter(c => c.active !== false).length)

  return { customers, totalCustomers, activeCustomers }
})