import { ref } from 'vue'
import api from '@/api/index.js'
import { useUiStore } from '@/stores/uiStore'

export function useApi() {
  const uiStore = useUiStore()
  const loading = ref(false)
  const errors  = ref({})

  async function request(method, url, data = null, options = {}) {
    loading.value = true
    errors.value  = {}
    try {
      const config = { method, url, ...options }

      if (data) {
        config.data = data

        // If formdata, let browser set Content-Type with boundary automatically
        // Do not set Content-Type manually for multipart uploads
        if (data instanceof FormData) {
          config.headers = {
            ...config.headers,
            'Content-Type': 'multipart/form-data',
          }
        }
      }

      const response = await api(config)
      return { data: response.data, success: true }

    } catch (err) {
      const status  = err.response?.status
      const message = err.response?.data?.message || 'Something went wrong.'

      if (status === 422) {
        errors.value = err.response.data.errors || {}
      }

      uiStore.error(message)
      return { data: null, success: false }
      
    } finally {
      loading.value = false
    }
  }

  const get   = (url, params = {}) => request('GET', url, null, { params })
  const post  = (url, data)        => request('POST', url, data)
  const put   = (url, data)        => request('PUT', url, data)
  const patch = (url, data)        => request('PATCH', url, data)
  const del   = (url)              => request('DELETE', url)

  return { loading, errors, get, post, put, patch, del }
}
