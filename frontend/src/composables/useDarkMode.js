import { ref } from "vue";

const isDark = ref(false)
let initialized = false

export function useDarkMode() {

  function applyDarkMode(dark) {
    if (dark) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
    isDark.value = dark
  }

  function init() {
    if (initialized) return
    initialized = true

    //Check localStorage first
    const stored = localStorage.getItem('portfolio-dark-mode')

    if (stored !== null) {
      // Use stored preference
      applyDarkMode(stored === 'true')
    } else {
      // Default to dark mode
      applyDarkMode(true)
    }
  }

  function toggle() {
    const newValue = !isDark.value
    localStorage.setItem('portfolio-dark-mode', String(newValue))
    applyDarkMode(newValue)
  }

  function setDark(value) {
    localStorage.setItem('portfolio-dark-mode', String(value))
    applyDarkMode(value)
  }

  return { isDark, init, toggle, setDark }

}
