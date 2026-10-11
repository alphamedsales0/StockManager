// src/api/profile.js
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_BASE || '/api'

// Instance dédiée (hérite de la config globale axios)
const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,   // 🔑 envoie le cookie de session
  headers: { 'Content-Type': 'application/json' }
})

/* =====================================================
   PROFIL
===================================================== */

export async function getProfile() {
  const { data } = await api.get('/auth.php?action=profile')
  return data
}

export async function updateProfile(payload) {
  const { data } = await api.post('/auth.php?action=updateProfile', payload)
  return data
}

/* =====================================================
   ADRESSES / KOMMUNIKATION
===================================================== */

export async function deleteAddress(id) {
  const { data } = await api.post('/auth.php?action=deleteAddress', { id })
  return data
}

export async function deleteCommunication(id) {
  const { data } = await api.post('/auth.php?action=deleteCommunication', { id })
  return data
}

/* =====================================================
   MOT DE PASSE
===================================================== */

export async function changePassword(payload) {
  const { data } = await api.post('/change_password.php', payload)
  return data
}

/* =====================================================
   PROFILBILD — UPLOAD
   ➜ Passe par updateProfile (multipart détecté côté PHP)
===================================================== */

/**
 * Upload du profilbild
 * @param {FormData} formData - contient le champ "image"
 * @returns {Promise<{success: boolean, data?: {url: string}, message?: string}>}
 */
export async function uploadProfileImage(formData) {
  try {
    const { data } = await api.post(
      '/auth.php?action=updateProfile',
      formData,
      {
        headers: {
          // ⚠️ On écrase le Content-Type global pour laisser
          // le navigateur gérer le boundary multipart automatiquement
          'Content-Type': 'multipart/form-data'
        }
      }
    )
    return data
  } catch (err) {
    console.error('[uploadProfileImage]', err)
    return {
      success: false,
      message: err.response?.data?.message || err.message || 'Upload fehlgeschlagen'
    }
  }
}

/* =====================================================
   PROFILBILD — SUPPRESSION
   ➜ Passe par updateProfile avec un flag JSON
===================================================== */

/**
 * Suppression du profilbild
 * @returns {Promise<{success: boolean, message?: string}>}
 */
export async function deleteProfileImage() {
  try {
    const { data } = await api.post(
      '/auth.php?action=updateProfile',
      { delete_profile_image: true }
    )
    return data
  } catch (err) {
    console.error('[deleteProfileImage]', err)
    return {
      success: false,
      message: err.response?.data?.message || err.message || 'Löschen fehlgeschlagen'
    }
  }
}