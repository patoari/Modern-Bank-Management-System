import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)
  const userRole        = computed(() => user.value?.roles?.[0]?.name || null)
  const userName = computed(() => {
    if (!user.value) return ''
    // Staff/admin user — name comes from nested customer or staff relation
    if (user.value.customer) return `${user.value.customer.first_name} ${user.value.customer.last_name}`
    if (user.value.staff) return user.value.email
    return user.value.email
  })

  function initAuth() {
    const stored = localStorage.getItem('user')
    if (stored) user.value = JSON.parse(stored)
    if (token.value) api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    const { user: userData, token: tokenData } = data.data
    token.value = tokenData
    user.value  = userData
    localStorage.setItem('token', tokenData)
    localStorage.setItem('user', JSON.stringify(userData))
    api.defaults.headers.common['Authorization'] = `Bearer ${tokenData}`
    return data.data
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    delete api.defaults.headers.common['Authorization']
    router.push({ name: 'login' })
  }

  function hasPermission(permission) {
    return user.value?.permissions?.includes(permission) ?? false
  }

  function hasRole(role) {
    if (Array.isArray(role)) return role.includes(userRole.value)
    return userRole.value === role
  }

  return { user, token, isAuthenticated, userRole, userName, initAuth, login, logout, hasPermission, hasRole }
})
