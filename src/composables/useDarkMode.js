// composables/useDarkMode.js
import { ref, watch } from 'vue'

export function useDarkMode() {
  const darkMode = ref(false)

  const initDarkMode = () => {
    const saved = localStorage.getItem('darkMode')
    if (saved !== null) {
      darkMode.value = saved === 'true'
    } else {
      darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
  }

  const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
  }

  watch(darkMode, (val) => {
    localStorage.setItem('darkMode', val)
  })

  initDarkMode()

  return { darkMode, toggleDarkMode }
}