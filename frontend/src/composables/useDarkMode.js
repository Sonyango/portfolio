import { ref, watch, onMounted } from "vue";

const isDark = ref(false)

export function useDarkMode() {
  onMounted(() => {
    const stored = localStorage.getItem('darkMode')
    isDark.value = stored ? JSON.parse(stored) : true
    applyDarkMode(isDark.value)
  })

  function toggle() {
    isDark.value = !isDark.value
    localStorage.setItem('darkMode', JSON.stringify(isDark.value))
    applyDarkMode(isDark.value)
  }

  function applyDarkMode(dark) {
    if (dark) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  watch(isDark, applyDarkMode)

  return { isDark, toggle }
}
