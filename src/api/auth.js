// src/api/auth.js
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_BASE || '/api'

const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json' }
})

export const getCurrentUser = async () => {
  try {
    const { data } = await api.get('/auth.php?action=user')
    return data
  } catch (error) {
    // 401/403 = pas connecté, pas une vraie erreur
    if (error.response?.status === 401 || error.response?.status === 403) {
      return { success: false }
    }
    console.error('getCurrentUser error:', error)
    return { success: false }
  }
}

export const register = async (payload) => {
  const { data } = await api.post('/auth.php?action=register', payload)
  return data
}

export const login = async (email, password) => {
  const { data } = await api.post('/auth.php?action=login', { email, password })
  return data
}

export const logout = async () => {
  const { data } = await api.post('/auth.php?action=logout')
  return data
}