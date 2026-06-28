import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Notification {
  id: number
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  title?: string
  timeout?: number
}

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<Notification[]>([])
  const nextId = ref(1)
  
  function showNotification(message: string, type: Notification['type'] = 'info', title?: string, timeout = 4000) {
    const notification: Notification = {
      id: nextId.value++,
      message,
      type,
      title,
      timeout
    }
    
    notifications.value.push(notification)
    
    // Auto-remove after timeout
    if (timeout > 0) {
      setTimeout(() => {
        removeNotification(notification.id)
      }, timeout)
    }
    
    return notification.id
  }
  
  function showSuccess(message: string, title?: string, timeout = 4000) {
    return showNotification(message, 'success', title, timeout)
  }
  
  function showError(message: string, title?: string, timeout = 4000) {
    return showNotification(message, 'error', title, timeout)
  }
  
  function showWarning(message: string, title?: string, timeout = 4000) {
    return showNotification(message, 'warning', title, timeout)
  }
  
  function showInfo(message: string, title?: string, timeout = 4000) {
    return showNotification(message, 'info', title, timeout)
  }
  
  function removeNotification(id: number) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  function clearNotifications() {
    notifications.value = []
  }
  
  return {
    notifications,
    showNotification,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    removeNotification,
    clearNotifications
  }
})