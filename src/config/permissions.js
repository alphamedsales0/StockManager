// src/config/permissions.js
// ============================================================
// CONFIGURATION CENTRALE DES PERMISSIONS
// ============================================================

// ✅ Routes publiques (accessibles à tous les rôles connectés)
export const PUBLIC_ROUTES = ['tickets', 'help']

// ============================================================
// MATRICE DES PERMISSIONS — 3 rôles
// ============================================================
export const ROLE_PERMISSIONS = {
  // ---------------------------------------------------------
  // ADMIN : accès total
  // ---------------------------------------------------------
  Admin: [
    'dashboard',
    'tickets',
    'tickets-write',
    'tickets-delete',         // ✅ NOUVEAU — suppression de tickets
    'products',
    'products-write',
    'products-categories',
    'customers',
    'customers-write',
    'employees',
    'orders',
    'stats',
    'help'
  ],

  // ---------------------------------------------------------
  // OFFICE : peut éditer mais pas supprimer les tickets
  // ---------------------------------------------------------
  Office: [
    'dashboard',
    'tickets',
    'tickets-write',
    // ❌ pas de 'tickets-delete'
    'products',
    'products-write',
    'products-categories',
    'customers',
    'customers-write',
    'orders',
    'help'
  ],

  // ---------------------------------------------------------
  // TECHNICIAN : lecture seule partout
  // ---------------------------------------------------------
  Technician: [
    'tickets',
    'products',
    'customers',
    'help'
  ]
}

// ✅ Liste des rôles disponibles
export const AVAILABLE_ROLES = ['Admin', 'Office', 'Technician']

// ============================================================
// Helper : mapping route → clé de permission
// ============================================================
export function getRouteKey(path) {
  if (!path) return null

  if (path === '/' || path === '/dashboard') return 'dashboard'

  // Produits
  if (path === '/products/add') return 'products-write'
  if (path.startsWith('/products/edit/')) return 'products-write'
  if (path.startsWith('/categories')) return 'products-categories'
  if (path.startsWith('/article-types')) return 'products-categories'
  if (path.startsWith('/brands')) return 'products-categories'
  if (path.startsWith('/products')) return 'products'

  // Kunden
  if (path === '/customers/add') return 'customers-write'
  if (path.startsWith('/customers/edit/')) return 'customers-write'
  if (path.startsWith('/customers')) return 'customers'

  // Mitarbeiter
  if (path.startsWith('/employees')) return 'employees'
  if (path.startsWith('/roles')) return 'employees'
  if (path.startsWith('/positions')) return 'employees'
  if (path.startsWith('/departments')) return 'employees'

  // Autres
  if (path.startsWith('/orders')) return 'orders'
  if (path.startsWith('/stats')) return 'stats'
  if (path.startsWith('/tickets')) return 'tickets'
  if (path.startsWith('/ticket/')) return 'tickets'
  if (path.startsWith('/help')) return 'help'

  return null
}