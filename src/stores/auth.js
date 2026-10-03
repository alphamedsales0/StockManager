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
  // GETTERS
  // ============================================================
  const userName = computed(() => user.value?.name || '')
  const userRole = computed(() => user.value?.role || null)
  const userEmail = computed(() => user.value?.email || '')

  // ============================================================
  // ACTIONS
  // ============================================================
  function setUser(userData) {
    // ❌ Bloque les rôles invalides (Gast, Guest, etc.)
    if (userData && !['Admin', 'Office', 'Technician'].includes(userData.role)) {
      console.warn(`Rôle refusé : ${userData.role}`)
      clearUser()
      return
    }
    user.value = userData
    isLoggedIn.value = !!userData
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