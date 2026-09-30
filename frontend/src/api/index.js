import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  withCredentials: false,
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
})

// Attach token from localStorage on every request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Only set JSON content type if not sending FormData
  if (!(config.data instanceof FormData)) {
    config.headers['Content-Type'] = 'application/json'
  }

  return config
})

api.interceptors.response.use(
  res => res,
  err => {

    // const isLoginRequest = err.config?.url?.includes('/admin/login')

    // if (err.response?.status === 401 && !isLoginRequest) {
    //   localStorage.removeItem('admin_token')
    //   window.location.href = '/admin/login'
    // }

    if (err.response?.status === 401) {
      const data        = err.response.data ?? {}
      const sessionExpired = data.session_expired === true

      // Only redirects if there is stored token (logged in user)
      if (localStorage.getItem('admin_token')) {
        localStorage.removeItem('admin_token')
        localStorage.removeItem('admin_last_activity_at')

        const reason = sessionExpired ? 'inactivity' : 'unauthorized'

        // Prevents redirect loop on the login page
        if (!window.location.pathname.includes('/admin/login')) {
          window.location.href = `/admin/login?reason=${reason}`
        }
      }
    }
    return Promise.reject(err)
  }
)

export default api;
