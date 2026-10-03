// src/api/auth_stock.js
import axios from 'axios'
import { apiUrl } from '@/config/api'

const AUTH_URL = apiUrl('/auth_stock.php')

export const getCurrentUser = async () => {
  try {
    const response = await axios.get(`${AUTH_URL}?action=user`, {
      withCredentials: true
    })
    return response.data
  } catch (error) {
    console.error('Fehler beim Abrufen des Benutzers:', error)
    return { success: false }
  }
}

export const login = async (email, password) => {
  try {
    const response = await axios.post(
      `${AUTH_URL}?action=login`,
      { email, password },
      { withCredentials: true }
    )
    return response.data
  } catch (error) {
    console.error('Login error:', error)
    return { success: false, message: error.message }
  }
}

export const logout = async () => {
  try {
    const response = await axios.post(
      `${AUTH_URL}?action=logout`,
      {},
      { withCredentials: true }
    )
    return response.data
  } catch (error) {
    console.error('Logout error:', error)
    return { success: false }
  }
}