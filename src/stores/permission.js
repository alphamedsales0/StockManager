// stores/permission.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const usePermissionStore = defineStore('permission', () => {
  const authStore = useAuthStore()
  const showPermissionDialog = ref(false)

  // Prüft, ob der Benutzer die erforderliche Rolle hat.
  // Wenn ja, wird der Callback ausgeführt (z.B. Navigation).
  // Wenn nein, wird das globale Modal geöffnet.
  function checkPermission(requiredRole, callback) {
    const userRole = authStore.user?.role
    if (userRole === requiredRole) {
      if (callback) callback()
      return true
    } else {
      showPermissionDialog.value = true
      return false
    }
  }

  function closeDialog() {
    showPermissionDialog.value = false
  }

  return {
    showPermissionDialog,
    checkPermission,
    closeDialog
  }
})