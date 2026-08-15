// stores/userStore.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUserStore = defineStore('user', () => {
  const user = ref({
    id: null,
    name: '',
    email: '',
    role: 'employee'
  })

  // Rolle aus localStorage laden (beim Initialisieren)
  function loadFromStorage() {
    const stored = localStorage.getItem('userData')
    if (stored) {
      try {
        const parsed = JSON.parse(stored)
        user.value = { ...user.value, ...parsed }
      } catch (e) {
        console.warn('Fehler beim Laden der Benutzerdaten:', e)
      }
    }
    // Fallback: nur Rolle aus localStorage
    const role = localStorage.getItem('userRole')
    if (role) {
      user.value.role = role
    }
  }

  // Rolle setzen und in localStorage speichern
  function setRole(role: string) {
    user.value.role = role
    localStorage.setItem('userRole', role)
  }

  // Komplette Benutzerdaten setzen
  function setUser(data: any) {
    user.value = { ...user.value, ...data }
    localStorage.setItem('userData', JSON.stringify(user.value))
  }

  // Rolle als Computed (für einfache Prüfungen)
  const isAdmin = computed(() => user.value.role === 'admin')
  const hasRole = (roles: string | string[]) => {
    if (!roles) return true
    if (Array.isArray(roles)) {
      return roles.includes(user.value.role) || user.value.role === 'admin'
    }
    return user.value.role === roles || user.value.role === 'admin'
  }

  // Beim Laden initialisieren
  loadFromStorage()

  return { user, setRole, setUser, isAdmin, hasRole, loadFromStorage }
})