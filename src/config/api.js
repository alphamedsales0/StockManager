// src/config/api.js
// ============================================================
// Configuration centralisée de l'API et des URLs externes
// ============================================================

// ✅ API PHP (dev : proxy Vite, prod : URL absolue)
export const API_BASE =
  import.meta.env.VITE_API_BASE_URL || '/api'

// ✅ URL pour les images produits
export const IMAGE_BASE_URL =
  import.meta.env.VITE_IMAGE_BASE_URL || 'https://alpha-med-care.com'

// ✅ URL de la boutique en ligne
export const SHOP_BASE_URL =
  import.meta.env.VITE_SHOP_BASE_URL || 'https://alpha-med-care.com'

// ✅ URL pour les uploads
export const UPLOAD_BASE_URL =
  import.meta.env.VITE_UPLOAD_BASE_URL || 'https://alpha-med-care.com'

// ✅ Nom de l'app
export const APP_NAME =
  import.meta.env.VITE_APP_NAME || 'StockManager'

// ============================================================
// Helpers
// ============================================================
export function apiUrl(path) {
  const cleanPath = String(path).replace(/^\/+/, '')
  return `${API_BASE}/${cleanPath}`
}

export function imageUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  return `${IMAGE_BASE_URL}/${String(path).replace(/^\/+/, '')}`
}

export function uploadUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  return `${UPLOAD_BASE_URL}/${String(path).replace(/^\/+/, '')}`
}