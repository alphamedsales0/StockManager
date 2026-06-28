import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface AuthSuccessOptions {
  title: string
  message: string
  redirectUrl?: string
  buttonText?: string
}

export const useAuthSuccessStore = defineStore('auth-success', () => {
  const isVisible = ref(false)
  const options = ref<AuthSuccessOptions>({
    title: '',
    message: '',
    redirectUrl: '/',
    buttonText: 'OK'
  })
  
  let resolvePromise: ((value: boolean) => void) | null = null
  
  function show(opts: AuthSuccessOptions): Promise<boolean> {
    return new Promise((resolve) => {
      options.value = {
        ...options.value,
        ...opts
      }
      resolvePromise = resolve
      isVisible.value = true
    })
  }
  
  function close() {
    if (resolvePromise) {
      resolvePromise(true)
      resolvePromise = null
    }
    isVisible.value = false
  }
  
  function redirect() {
    if (options.value.redirectUrl) {
      window.location.href = options.value.redirectUrl
    }
    close()
  }
  
  return {
    isVisible,
    options,
    show,
    close,
    redirect
  }
})