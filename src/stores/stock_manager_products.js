// src/stores/stock_manager_products.js
import { defineStore } from 'pinia'

export const useProductStore = defineStore('products', {
  state: () => ({
    products: [],
    loading: false,
    error: null
  }),

  actions: {
    // Récupérer tous les produits
    async fetchProducts() {
    this.loading = true;
    this.error = null;
    try {
        const response = await fetch('https://alpha-med-care.com/api/stock_manager_products.php');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();

        // Ihr API-Endpunkt sendet: { success: true, data: [...] }
        if (data && data.success === true && Array.isArray(data.data)) {
            this.products = data.data;
        } else if (Array.isArray(data)) {
            this.products = data;
        } else {
            console.warn('Unbekanntes API-Format:', data);
            this.products = [];
        }

        console.log(`${this.products.length} Produkte geladen`);
    } catch (error) {
        console.error('Erreur fetchProducts:', error);
        this.error = error.message;
        this.products = [];
    } finally {
        this.loading = false;
    }
},

    // Créer un nouveau produit (avec toutes ses spécificités)
    async createProduct(productData) {
      this.loading = true
      this.error = null
      try {
        const response = await fetch('/api/products.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(productData)
        })
        const result = await response.json()
        if (!result.success) throw new Error(result.error || 'Erreur inconnue')
        
        // Ajouter le produit créé à la liste locale
        // L'API devrait renvoyer l'article complet (ou au moins son ID)
        if (result.article) {
          this.products.push(result.article)
        } else if (result.article_id) {
          // Option : recharger la liste pour être sûr d'avoir les données à jour
          await this.fetchProducts()
        }
        return result
      } catch (error) {
        console.error('Erreur createProduct:', error)
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // Option : supprimer un produit
    async deleteProduct(id) {
      // À implémenter si nécessaire
    },

    // Option : mettre à jour un produit
    async updateProduct(id, data) {
      // À implémenter
    }
  },

  getters: {
    // Nombre total de produits
    totalCount: (state) => state.products.length,
    
    // Produits en stock (in_stock = true)
    inStockProducts: (state) => state.products.filter(p => p.in_stock === true || p.in_stock === 1),
    
    // Produits en rupture (in_stock = false)
    outOfStockProducts: (state) => state.products.filter(p => p.in_stock === false || p.in_stock === 0),
    
    // Valeur totale du stock (basée sur le prix de vente)
    totalStockValue: (state) => {
      return state.products
        .filter(p => p.in_stock)
        .reduce((sum, p) => sum + (Number(p.price) || 0), 0)
    }
  }
})