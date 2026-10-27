// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'
import AllTickets from '../views/AllTickets.vue'
import { usePermissionStore } from '../stores/permission'
import { useAuthStore } from '../stores/auth'
import { getRouteKey } from '../config/permissions'
import ActivitiesView from '@/views/ActivitiesView.vue'

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
  // ACCESS DENIED
  // ============================================================
  {
    path: '/access-denied',
    name: 'AccessDenied',
    component: () => import('../views/AccessDeniedView.vue'),
    meta: { requiresAuth: true }
  },

  // ============================================================
  // DASHBOARD
  // ============================================================
  {
    path: '/',
    name: 'DashboardView',
    component: () => import('../views/DashboardView.vue'),
  },

  {
    path: '/activities',
    name: 'Activities',
    component: ActivitiesView,
    meta: { requiresAuth: true }
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
    path: '/customers/all',
    name: 'KundenAll',
    component: () => import('../views/KundenAllView.vue'),
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
    path: '/employees/edit/:uid',
    name: 'EditEmployee',
    component: () => import('../components/employees/EditEmployee.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees/:uid',
    name: 'EmployeeDetailView',
    component: () => import('../views/EmployeeDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/employees',
    name: 'Employees',
    component: () => import('../views/EmployeesView.vue'),
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
// GUARD GLOBAL — avec chargement automatique des permissions
// ============================================================
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const permissionStore = usePermissionStore()

  // ✅ Log de diagnostic
  console.log('[Router Guard]', {
    from: from.fullPath,
    to: to.fullPath,
    routeName: to.name,
    matched: to.matched.map(r => r.path),
    loggedIn: authStore.isLoggedIn
  })

  // ✅ 1. Route publique (login, access-denied)
  if (to.meta?.public || to.name === 'Login' || to.name === 'AccessDenied') {
    // Si déjà connecté et va sur /login → redirection auto
    if (authStore.isLoggedIn && to.name === 'Login') {
      if (!permissionStore.permissionsLoaded) {
        await permissionStore.loadPermissions()
      }
      const landing = permissionStore.can('dashboard') ? '/' : '/tickets'
      return next(landing)
    }
    return next()
  }

  // ✅ 2. Restaurer la session si nécessaire
  if (!authStore.isLoggedIn) {
    try {
      const res = await axios.get('/api/auth_stock.php?action=user', {
        withCredentials: true
      })
      if (res.data?.success) {
        authStore.setUser(res.data.user)
        authStore.setLoggedIn(true)
        permissionStore.setRole(res.data.user.role)
        if (!permissionStore.permissionsLoaded) {
          await permissionStore.loadPermissions()
        }
      } else {
        return next({ name: 'Login' })
      }
    } catch {
      return next({ name: 'Login' })
    }
  }

  // ✅ 3. Charger les permissions si pas encore fait
  if (!permissionStore.permissionsLoaded) {
    await permissionStore.loadPermissions()
  }

  // ✅ 4. Routes employés : pas de vérification spécifique
  const skipPermissionCheck = [
    'EditEmployee',
    'EmployeeDetailView',
    'EmployeeCreate'
  ]
  if (skipPermissionCheck.includes(to.name)) {
    return next()
  }

  // ✅ 5. Vérifier la permission selon la route
  const key = getRouteKey(to.path)
  console.log('[Router Guard] permission check', {
    to: to.fullPath,
    permissionKey: key,
    allowed: key ? permissionStore.can(key) : null
  })

  if (!key) return next()

  if (permissionStore.can(key)) {
    return next()
  }

  // ❌ Permission refusée
  permissionStore.showAccessDenied()

  // Si navigation "from" existe → on reste sur la page actuelle
  if (from.path && from.path !== to.path) {
    return next(false)
  }

  // Sinon, redirige vers la première page accessible
  const priority = [
    { path: '/', perm: 'dashboard' },
    { path: '/tickets', perm: 'tickets' },
    { path: '/products', perm: 'products' },
    { path: '/customers', perm: 'customers' },
    { path: '/employees', perm: 'employees' },
    { path: '/quotes', perm: 'orders' },
    { path: '/help', perm: 'help' },
  ]
  for (const item of priority) {
    if (permissionStore.can(item.perm)) {
      return next(item.path)
    }
  }
  return next({ name: 'Login' })
})

export default router