import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'), // Chemin vers votre Dashboard.vue
  },
  { path: '/products', component: () => import('../views/Products.vue') },
  { path: '/customers', component: () => import('../views/Customers.vue') },
  { path: '/quotes', component: () => import('../views/Quotes.vue') },
  // Redirection pour les routes inexistantes (optionnel)
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router