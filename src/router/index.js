import { createRouter, createWebHistory } from 'vue-router'


const routes = [
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'),
  },
  
  { 
    path: '/products', component: () => import('../views/Products.vue')
  },
  
  {
    path: '/products/edit/:id',
    name: 'EditProduct',
    component: () => import('../components/products/EditProduct.vue')
  },

  { 
    path: '/customers', component: () => import('../views/Customers.vue')
  },
  
  { 
    path: '/quotes', component: () => import('../views/Quotes.vue')
  },
  
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },

  {
    path: '/products/add',
    name: 'AddProduct',
    component: () => import('../views/AddProduct.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router