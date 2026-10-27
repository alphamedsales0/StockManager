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
  const permissions = ref([])
  const roleOverride = ref(localStorage.getItem('userRole') || null)

  // ============================================================
  // GETTERS
  // ============================================================
  const userRole = computed(() => {
    // ✅ Toujours en minuscule
    const role =
      authStore.user?.role ||
      roleOverride.value ||
      localStorage.getItem('userRole') ||
      null

    return role ? String(role).toLowerCase().trim() : null
  })

  const allowedKeys = computed(() => permissions.value)

  // ✅ Comparaisons insensibles à la casse
  const isAdmin = computed(() => userRole.value === 'admin')
  const isOffice = computed(() => userRole.value === 'office')
  const isTechnician = computed(() => userRole.value === 'technician')
  const isLoggedIn = computed(() => !!userRole.value)

  // ============================================================
  // ACTIONS
  // ============================================================
  function can(key) {
    // ✅ Admin a TOUJOURS tous les droits
    if (userRole.value === 'admin') return true

    // ✅ Sinon, vérifie dans les permissions chargées
    return permissions.value.includes(key)
  }

  async function loadPermissions() {
    if (loading.value) return

    loading.value = true
    try {
      console.log('[permissions] Chargement...')

      const res = await axios.get(apiUrl('/get_user_permissions.php'), {
        withCredentials: true
      })

      console.log('[permissions] API response:', res.data)

      if (res.data?.success) {
        permissions.value = res.data.permissions || []
        permissionsLoaded.value = true

        console.log('[permissions] Loaded:', permissions.value.length, 'permissions')
        console.log('[permissions] Liste:', permissions.value)
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

  function setRole(role) {
    if (!role) {
      roleOverride.value = null
      localStorage.removeItem('userRole')
      permissions.value = []
      permissionsLoaded.value = false
      return
    }

    // ✅ Toujours en minuscule
    const roleLower = String(role).toLowerCase().trim()

    roleOverride.value = roleLower
    localStorage.setItem('userRole', roleLower)
    // ⚠️ Ne PAS appeler loadPermissions ici
    // C'est Login.vue qui doit le faire explicitement
  }

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
    // ✅ Comparaison insensible à la casse
    const requiredLower = String(requiredRole).toLowerCase().trim()
    if (userRole.value === requiredLower) {
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