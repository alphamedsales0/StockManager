import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface ConfirmationOptions {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  confirmColor?: string
  cancelColor?: string
}

export const useConfirmationStore = defineStore('confirmation', () => {
  const isVisible = ref(false)
  const options = ref<ConfirmationOptions>({
    title: '',
    message: '',
    confirmText: 'OK',
    cancelText: 'Abbrechen',
    confirmColor: 'primary',
    cancelColor: 'grey'
  })
  
  let resolvePromise: ((value: boolean) => void) | null = null
  
  function confirm(opts: ConfirmationOptions): Promise<boolean> {
    return new Promise((resolve) => {
      options.value = {
        ...options.value,
        ...opts
      }
      resolvePromise = resolve
      isVisible.value = true
    })
  }
  
  function confirmAction() {
    if (resolvePromise) {
      resolvePromise(true)
      resolvePromise = null
    }
    isVisible.value = false
  }
  
  function cancelAction() {
    if (resolvePromise) {
      resolvePromise(false)
      resolvePromise = null
    }
    isVisible.value = false
  }
  
  return {
    isVisible,
    options,
    confirm,
    confirmAction,
    cancelAction
  }
})