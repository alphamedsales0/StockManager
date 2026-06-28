// stores/auth.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getCurrentUser } from '../api/auth_stock'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const isLoggedIn = ref(false)
  const initialized = ref(false)
  let initPromise = null

  function setUser(userData) {
    user.value = userData
    isLoggedIn.value = true
  }

  function clearUser() {
    user.value = null
    isLoggedIn.value = false
  }

  function setLoggedIn(value) {
    isLoggedIn.value = value
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
    user,
    isLoggedIn,
    initialized,
    setUser,
    clearUser,
    initialize,
    setLoggedIn
  }
})