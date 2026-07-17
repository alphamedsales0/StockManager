import { createRouter, createWebHistory } from 'vue-router'
import AllTickets from '../views/AllTickets.vue'   // Pfad anpassen

const routes = [
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'),
  },
  
  { 
    path: '/products', 
    component: () => import('../views/Products.vue')
  },
  
  {
    path: '/products/edit/:id',
    name: 'EditProduct',
    component: () => import('../components/products/EditProduct.vue')
  },

  { 
    path: '/customers', 
    component: () => import('../views/Customers.vue')
  },
  
  { 
    path: '/quotes', 
    component: () => import('../views/Quotes.vue')
  },
  
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },

  {
    path: '/ticket/:id',
    name: 'TicketDetail',
    component: () => import('../views/TicketDetail.vue')
  },

  {
    path: '/tickets',
    name: 'AllTickets',
    component: AllTickets,
    meta: { requiresAuth: true }
  },

  {
    path: '/products/add',
    name: 'AddProduct',
    component: () => import('../views/AddProduct.vue'),
    meta: { requiresAuth: true }
  },

  // ---------- NEUE ROUTES FÜR MITARBEITER ----------
  {
    path: '/employees',
    name: 'Employees',
    component: () => import('../views/Employees.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/add',
    name: 'AddEmployee',
    component: () => import('../views/AddEmployee.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/edit/:id',
    name: 'EditEmployee',
    component: () => import('../components/employees/EditEmployee.vue'), // oder views/EditEmployee.vue
    meta: { requiresAuth: true }
  }
  // ------------------------------------------------
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router