import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const toasts = ref([])
  const loading = ref(false)

  function addToast(message, type = 'success') {
    const id = Date.now()
    toasts.value.push({ id, message, type })
    setTimeout(() => removeToast(id), 4000)
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  function success(message) { addToast(message, 'success') }
  function error(message) { addToast(message, 'error') }
  function info(message)  { addToast(message, 'info')}

  return { toasts, loading, success, error, info, removeToast }
})
