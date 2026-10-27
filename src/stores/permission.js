// src/stores/permission.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from './auth'
import { apiUrl } from '@/config/api'

export const usePermissionStore = defineStore('permission', () => {
  const authStore = useAuthStore()

  // ============================================================
  // STATE
  // ============================================================
  const showPermissionDialog = ref(false)
  const loading = ref(false)
  const permissionsLoaded = ref(false)
  const permissions = ref([])   // ← chargées depuis la DB
  const roleOverride = ref(localStorage.getItem('userRole') || null)

  // ============================================================
  // GETTERS
  // ============================================================
  const userRole = computed(() =>
    authStore.user?.role || roleOverride.value || null
  )

  const allowedKeys = computed(() => permissions.value)

  const isAdmin = computed(() => userRole.value === 'Admin')
  const isOffice = computed(() => userRole.value === 'Office')
  const isTechnician = computed(() => userRole.value === 'Technician')
  const isLoggedIn = computed(() => !!userRole.value)

  // ============================================================
  // ACTIONS
  // ============================================================

  /**
   * ✅ Vérifie si l'utilisateur a une permission
   * Admin a TOUJOURS tout
   */
  function can(key) {
    if (userRole.value === 'Admin') return true
    return permissions.value.includes(key)
  }

  /**
   * ✅ Charge les permissions depuis l'API
   */
  async function loadPermissions() {
    if (loading.value) return

    loading.value = true
    try {
      const res = await axios.get(apiUrl('/get_user_permissions.php'), {
        withCredentials: true
      })

      if (res.data?.success) {
        permissions.value = res.data.permissions || []
        permissionsLoaded.value = true

        if (import.meta.env.DEV) {
          console.log('[permissions] Loaded:', permissions.value)
        }
      } else {
        console.warn('[permissions] API error:', res.data?.error)
        permissions.value = []
      }
    } catch (err) {
      console.error('[permissions] Load error:', err)
      permissions.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Enregistre le rôle (appelé par Login)
   */
  function setRole(role) {
    if (!role) {
      roleOverride.value = null
      localStorage.removeItem('userRole')
      permissions.value = []
      permissionsLoaded.value = false
      return
    }
    roleOverride.value = role
    localStorage.setItem('userRole', role)
    // Charge les permissions depuis la DB
    loadPermissions()
  }

  /**
   * ✅ Vide le rôle
   */
  function clearRole() {
    roleOverride.value = null
    permissions.value = []
    permissionsLoaded.value = false
    localStorage.removeItem('userRole')
  }

  function showAccessDenied() {
    showPermissionDialog.value = true
  }

  function closeDialog() {
    showPermissionDialog.value = false
  }

  function checkPermission(requiredRole, callback) {
    if (userRole.value === requiredRole) {
      if (callback) callback()
      return true
    }
    showPermissionDialog.value = true
    return false
  }

  return {
    // state
    showPermissionDialog,
    loading,
    permissions,
    permissionsLoaded,

    // getters
    userRole,
    allowedKeys,
    isAdmin,
    isOffice,
    isTechnician,
    isLoggedIn,

    // actions
    can,
    loadPermissions,
    setRole,
    clearRole,
    showAccessDenied,
    closeDialog,
    checkPermission
  }
})