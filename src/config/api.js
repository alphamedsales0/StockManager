// src/config/api.js
// ============================================================
// Configuration centralisée de l'API et des URLs externes
// ============================================================

// ✅ En DEV : passe par le proxy Vite (/api → stckmng.alpha-med-care.com)
// ✅ En PROD : même domaine (/api)
export const API_BASE =
  import.meta.env.VITE_API_BASE_URL || '/api'

export const IMAGE_BASE_URL =
  (import.meta.env.VITE_IMAGE_BASE_URL || 'https://alpha-med-care.com').replace(/\/+$/, '')

export const SHOP_BASE_URL =
  import.meta.env.VITE_SHOP_BASE_URL || 'https://alpha-med-care.com'

export const UPLOAD_BASE_URL =
  (import.meta.env.VITE_UPLOAD_BASE_URL || 'https://alpha-med-care.com').replace(/\/+$/, '')

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
  const clean = String(path).replace(/^\/+/, '')
  return `${IMAGE_BASE_URL}/${clean}`
}

export function uploadUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  const clean = String(path).replace(/^\/+/, '')
  return `${UPLOAD_BASE_URL}/${clean}`
}

export function toRelativePath(url) {
  if (!url) return ''
  if (!/^https?:\/\//i.test(url)) return url
  try {
    return new URL(url).pathname
  } catch {
    return url
  }
}