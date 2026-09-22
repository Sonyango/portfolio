import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/index.js'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  // Login
  async function login(email, password) {
    loading.value = true
    try {
      const { data } = await api.post('/admin/login', { email, password })

      // MFA required
      if (data.mfa_required) {
        return {
          success:      false,
          mfaRequired:  true,
          mfaToken:     data.mfa_token,
          message:      data.message,
        }
      }

      // Save token to localStorage
      localStorage.setItem('admin_token', data.token)
      user.value = data.user
      return { success: true }

    } catch (err) {
      //const message =  error.response?.data?.message || 'Login failed.'
      const response = err.response
      return {
        success: false,
        message: response?.data?.message || 'Login failed.',
        warning: response?.data?.warning || '',
        status:  response?.status,
        retryAfter: response?.data?.retry_after,
        lockoutType: response?.data?.lockout_type,
      }
    } finally {
      loading.value = false
    }
  }

  // MFA Verify
  async function verifyMfa(mfaToken, totpCode) {
    loading.value = true
    try {
      const { data } = await api.post('/admin/mfa/verify', {
        mfa_token: mfaToken,
        totp_code: totpCode,
      })

      localStorage.setItem('admin_token', data.token)
      user.value = data.user
      return { success: true }

    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Verification failed.',
      }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/admin/logout')
    } finally {
      localStorage.removeItem('admin_token')
      user.value = null
    }
  }

  async function fetchMe() {
    const token = localStorage.getItem('admin_token')
    if (!token) return

    try {
      const { data } = await api.get('/admin/me')
      user.value = data.user
    } catch (error) {
      localStorage.removeItem('admin_token')
      user.value = null
    }
  }

  // Inactivity check
  async function pingActivity() {
    if (!user.value) return

    try {
      const { data } = await api.get('/admin/activity-check')
      if (data.session_expired) {
        localStorage.removeItem('admin_token')
        user.value = null
        window.location.href = '/admin/login?reason=inactivity'
      }
    } catch (error) {
      if (error.response?.status === 401) {
        localStorage.removeItem('admin_token')
        user.value = null
        window.location.href = '/admin/login?reason=inactivity'
      }
    }
  }

  return {
    user, loading,
    isAuthenticated, isAdmin,
    login, verifyMfa, logout,
    fetchMe, pingActivity,
   }
})
