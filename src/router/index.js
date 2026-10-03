import { createRouter, createWebHistory } from 'vue-router'
import AllTickets from '../views/AllTickets.vue'

const routes = [
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'),
  },
  { 
    path: '/products', 
    component: () => import('../views/ProductsList.vue')
  },

  {
    path: '/products/view/:id',
    name: 'ProductView',
    component: () => import('../views/stock/ProductView.vue'),
    meta: { requiresAuth: true }
  },

  {
    path: '/products/edit/:id',
    name: 'EditProduct',
    component: () => import('../components/products/EditProduct.vue')
  },

  // ---------- KUNDEN MANAGEMENT ----------
  // ⚠️ ORDRE IMPORTANT : /add et /edit AVANT /:id
  {
    path: '/customers',
    name: 'Kunden',
    component: () => import('../views/KundenView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/customers/add',
    name: 'KundeCreate',
    component: () => import('../components/kunden/KundeCreate.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/customers/edit/:id(\\d+)',        // ✅ AVANT /:id
    name: 'EditKunde',
    component: () => import('../components/kunden/EditKunde.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/customers/:id(\\d+)',             // ✅ APRÈS /edit
    name: 'KundeDetail',
    component: () => import('../views/KundeDetailView.vue'),
    meta: { requiresAuth: true }
  },

  { 
    path: '/quotes', 
    component: () => import('../views/Quotes.vue')
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
  {
    path: '/categories',
    name: 'CategoriesList',
    component: () => import('../views/CategoriesList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/article-types',
    name: 'ArticleTypesList',
    component: () => import('../views/ArticleTypesList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/brands',
    name: 'Brands',
    component: () => import('../components/products/Brands.vue'),
    meta: { requiresAuth: true }
  },

  // ---------- MITARBEITER MANAGEMENT ----------
  {
    path: '/employees/add',
    name: 'EmployeeCreate',
    component: () => import('../components/employees/EmployeeCreate.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees',
    name: 'Employees',
    component: () => import('../views/EmployeesView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/edit/:uid([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})',   // ✅ AVANT /:uid
    name: 'EditEmployee',
    component: () => import('../components/employees/EditEmployee.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/:uid([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})',       // ✅ APRÈS /edit
    name: 'EmployeeDetail',
    component: () => import('../views/EmployeeDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/roles',
    name: 'RolesManagement',
    component: () => import('../views/RolesManagement.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/positions',
    name: 'Positions',
    component: () => import('../components/employees/Positions.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/departments',
    name: 'Departments',
    component: () => import('../components/employees/Departments.vue'),
    meta: { requiresAuth: true }
  },

  // ⚠️⚠️⚠️ CATCH-ALL TOUJOURS EN DERNIER ⚠️⚠️⚠️
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router