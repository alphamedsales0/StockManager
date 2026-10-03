// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import AllTickets from '../views/AllTickets.vue'
import { usePermissionStore } from '../stores/permission'
import { useAuthStore } from '../stores/auth'
import { getRouteKey } from '../config/permissions'

const routes = [
  // ============================================================
  // LOGIN (public)
  // ============================================================
  {
    path: '/login',
    name: 'Login',
    component: () => import('../components/auth/Login.vue'),
    meta: { public: true }
  },

  // ============================================================
  // DASHBOARD
  // ============================================================
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'),
  },

  // ============================================================
  // PRODUKTE
  // ============================================================
  {
    path: '/products',
    name: 'ProductsList',
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
    component: () => import('../components/products/EditProduct.vue'),
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

  // ============================================================
  // KUNDEN
  // ============================================================
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
    path: '/customers/edit/:id(\\d+)',
    name: 'EditKunde',
    component: () => import('../components/kunden/EditKunde.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/customers/:id(\\d+)',
    name: 'KundeDetail',
    component: () => import('../views/KundeDetailView.vue'),
    meta: { requiresAuth: true }
  },

  // ============================================================
  // QUOTES
  // ============================================================
  {
    path: '/quotes',
    name: 'Quotes',
    component: () => import('../views/Quotes.vue'),
    meta: { requiresAuth: true }
  },

  // ============================================================
  // TICKETS
  // ============================================================
  {
    path: '/tickets',
    name: 'AllTickets',
    component: AllTickets,
    meta: { requiresAuth: true }
  },
  {
    path: '/ticket/:id',
    name: 'TicketDetail',
    component: () => import('../views/TicketDetail.vue'),
    meta: { requiresAuth: true }
  },

  // ============================================================
  // MITARBEITER
  // ============================================================
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
    path: '/employees/edit/:uid([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})',
    name: 'EditEmployee',
    component: () => import('../components/employees/EditEmployee.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/:uid([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})',
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

  // ============================================================
  // CATCH-ALL
  // ============================================================
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// ============================================================
// GUARD GLOBAL
// ============================================================
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const permissionStore = usePermissionStore()

  // ✅ 1. Si déjà connecté et va sur /login → redirection auto
  if (authStore.isLoggedIn && (to.name === 'Login' || to.path === '/login')) {
    const landing = permissionStore.can('dashboard') ? '/' : '/tickets'
    return next(landing)
  }

  // ✅ 2. Page login : accessible si pas connecté
  if (to.meta?.public || to.name === 'Login') {
    return next()
  }

  // ✅ 3. Pas connecté → login
  if (!authStore.isLoggedIn) {
    return next({ name: 'Login' })
  }

  // ✅ 4. Vérifier la permission selon la route
  const key = getRouteKey(to.path)
  if (!key) return next()

  if (permissionStore.can(key)) {
    return next()
  }

  // ❌ Permission refusée
  permissionStore.showAccessDenied()

  // Reste sur la page actuelle (évite les boucles infinies)
  if (from.path && from.path !== to.path) {
    return next(false)
  }

  // Sinon, fallback selon le rôle
  const fallback = permissionStore.can('dashboard') ? '/' : '/tickets'
  return next(fallback)
})

export default router