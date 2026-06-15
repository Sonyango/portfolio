import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/index.js'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function login(email, password) {
    loading.value = true
    try {
      const { data } = await api.post('/admin/login', { email, password })

      // Save token to localStorage
      localStorage.setItem('admin_token', data.token)
      user.value = data.user

      return { success: true }
    } catch (error) {
      const message =  error.response?.data?.message || 'Login failed.'
      return { success: false, message }
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

  return { user, loading, isAuthenticated, isAdmin, login, logout, fetchMe }
})
