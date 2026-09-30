import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/index.js'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  function setAuthenticatedSession(token, authenticatedUser) {
    localStorage.setItem('admin_token', token)
    localStorage.setItem('admin_last_activity_at', String(Date.now()))
    user.value = authenticatedUser
  }

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
          method:       data.method,
          message:      data.message,
          emailHint:    data.email_hint,
        }
      }

      // Fallback: if somehow token is returned (shouldn't happen)
      if (data.token) {
        setAuthenticatedSession(data.token, data.user)
        return { success: true }
      }

      return { success: false, message: 'Unexpected response.' }

    } catch (err) {
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
  async function verifyMfa(mfaToken, code) {
    loading.value = true
    try {
      const { data } = await api.post('/admin/mfa/verify', {
        mfa_token: mfaToken,
        code,
      })

      setAuthenticatedSession(data.token, data.user)
      return { success: true }

    } catch (err) {
      const response = err.response
      return {
        success: false,
        message: response?.data?.message || 'Verification failed.',
        attemptsLeft: response?.data?.attempts_left,
        expired: response?.data?.expired || false,
        status:  response?.status,
      }
    } finally {
      loading.value = false
    }
  }

  // Resend email code
  async function resendEmailCode(mfaToken) {
    try {
      const { data } = await api.post('/admin/mfa/resend', {
        mfa_token: mfaToken,
      })
      return { success: true, message: data.message }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Could not resend code.',
      }
    }
  }

  async function logout() {
    try {
      await api.post('/admin/logout')
    } finally {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_last_activity_at')
      user.value = null
    }
  }

  // Fetch current user
  async function fetchMe() {
    const token = localStorage.getItem('admin_token')
    if (!token) return

    try {
      const { data } = await api.get('/admin/me')
      user.value = data.user
    } catch (error) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_last_activity_at')
      user.value = null
    }
  }

  return {
    user, loading,
    isAuthenticated, isAdmin,
    login, verifyMfa, resendEmailCode,
    logout, fetchMe,
   }
})
