import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useProductStore = defineStore('products', () => {
  const products = ref([
    { id: 1, name: 'Product A', stock: 15, purchasePrice: 10 },
    { id: 2, name: 'Product B', stock: 8, purchasePrice: 25 },
    { id: 3, name: 'Product C', stock: 0, purchasePrice: 5 },
  ])

  const totalProducts = computed(() => products.value.length)
  const inStock = computed(() => products.value.filter(p => p.stock > 0).length)
  const totalStockValue = computed(() =>
    products.value.reduce((sum, p) => sum + (p.purchasePrice * p.stock), 0)
  )

  return { products, totalProducts, inStock, totalStockValue }
})