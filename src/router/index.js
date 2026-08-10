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
    path: '/employees/:id(\\d+)',   // NUR ZAHLEN
    name: 'EmployeeDetail',
    component: () => import('../views/EmployeeDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/edit/:id(\\d+)', // NUR ZAHLEN
    name: 'EditEmployee',
    component: () => import('../components/employees/EditEmployee.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router