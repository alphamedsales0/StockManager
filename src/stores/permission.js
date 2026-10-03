// src/stores/permission.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import { ROLE_PERMISSIONS, AVAILABLE_ROLES } from '../config/permissions'

export const usePermissionStore = defineStore('permission', () => {
  const authStore = useAuthStore()

  // ============================================================
  // STATE
  // ============================================================
  const showPermissionDialog = ref(false)

  // Rôle "override" (utile si l'auth store ne l'a pas encore)
  const roleOverride = ref(localStorage.getItem('userRole') || null)

  // ============================================================
  // GETTERS
  // ============================================================
  // ✅ Priorité : authStore.user.role → sinon roleOverride
  const userRole = computed(() => authStore.user?.role || roleOverride.value || null)

  const allowedKeys = computed(() => {
    if (!userRole.value) return []
    return ROLE_PERMISSIONS[userRole.value] || []
  })

  const isAdmin = computed(() => userRole.value === 'Admin')
  const isOffice = computed(() => userRole.value === 'Office')
  const isTechnician = computed(() => userRole.value === 'Technician')
  const isLoggedIn = computed(() => !!userRole.value)

  // ============================================================
  // ACTIONS
  // ============================================================

  // ✅ Vérifie si le rôle autorise une clé
  //    ⚠️ BONUS : Admin a TOUJOURS tous les droits, peu importe la clé
  function can(key) {
    // 🔒 Sécurité absolue : Admin retourne toujours true
    if (userRole.value === 'Admin') return true

    // Sinon, on vérifie la matrice classique
    return allowedKeys.value.includes(key)
  }

  // ✅ Enregistre le rôle (appelé par Login.vue)
  function setRole(role) {
    if (!role) {
      roleOverride.value = null
      localStorage.removeItem('userRole')
      return
    }
    if (!ROLE_PERMISSIONS[role]) {
      console.warn(`Rôle inconnu : ${role}`)
      return
    }
    roleOverride.value = role
    localStorage.setItem('userRole', role)
  }

  // ✅ Vide le rôle (appelé à la déconnexion)
  function clearRole() {
    roleOverride.value = null
    localStorage.removeItem('userRole')
  }

  // ✅ Affiche le dialog "Zugriff verweigert"
  function showAccessDenied() {
    showPermissionDialog.value = true
  }

  // ✅ Ferme le dialog
  function closeDialog() {
    showPermissionDialog.value = false
  }

  // ✅ Ancienne API (compat) : vérifie un rôle exact
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
    // getters
    userRole,
    allowedKeys,
    isAdmin,
    isOffice,
    isTechnician,
    isLoggedIn,
    // actions
    can,
    setRole,
    clearRole,
    showAccessDenied,
    closeDialog,
    checkPermission,
    // constantes
    AVAILABLE_ROLES
  }
})