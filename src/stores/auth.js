// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCurrentUser } from '../api/auth_stock'

export const useAuthStore = defineStore('auth', () => {
  // ============================================================
  // STATE
  // ============================================================
  const user = ref(null)
  const isLoggedIn = ref(false)
  const initialized = ref(false)
  let initPromise = null

  // ============================================================
  // RÔLES AUTORISÉS (tous en minuscules)
  // ============================================================
  const ALLOWED_ROLES = [
    'admin',
    'office',
    'technician',
    'manager',
    'hr',
    'finance',
    'sales',
    'warehouse',
    'purchasing',
    'support',
    'teamlead',
    'employee',
    'accountant',
    'developer',
    'manu'
  ]

  // ============================================================
  // GETTERS
  // ============================================================
  const userName = computed(() => user.value?.name || '')
  const userRole = computed(() => user.value?.role || null)
  const userEmail = computed(() => user.value?.email || '')

  // ============================================================
  // ACTIONS
  // ============================================================
  function setUser(userData) {
    if (!userData) {
      clearUser()
      return
    }

    // ✅ Normalise le rôle en minuscule
    const roleLower = String(userData.role || '').toLowerCase().trim()

    // ✅ Vérifie que le rôle est autorisé (insensible à la casse)
    if (!ALLOWED_ROLES.includes(roleLower)) {
      console.warn(`⚠️ Rôle refusé : ${userData.role} (normalisé: ${roleLower})`)
      console.warn('Rôles autorisés:', ALLOWED_ROLES)
      clearUser()
      return
    }

    // ✅ Stocke l'utilisateur avec le rôle en minuscule
    user.value = {
      ...userData,
      role: roleLower
    }
    isLoggedIn.value = true

    console.log('[auth] User set:', user.value)
  }

  function clearUser() {
    user.value = null
    isLoggedIn.value = false
  }

  function setLoggedIn(value) {
    isLoggedIn.value = !!value
  }

  async function initialize() {
    if (initialized.value) return
    if (!initPromise) {
      initPromise = (async () => {
        try {
          const result = await getCurrentUser()
          if (result.success && result.user) {
            setUser(result.user)
          }
        } catch (error) {
          console.error('Session restoration failed', error)
        } finally {
          initialized.value = true
        }
      })()
    }
    return initPromise
  }

  return {
    // state
    user,
    isLoggedIn,
    initialized,
    // getters
    userName,
    userRole,
    userEmail,
    // actions
    setUser,
    clearUser,
    initialize,
    setLoggedIn
  }
})