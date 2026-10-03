// composables/useTimeEntries.js
import { ref } from 'vue'
import { apiUrl } from '@/config/api'

export function useTimeEntries() {
  const timeEntries = ref([])

  const loadTimeEntries = async (ticketId) => {
    try {
      const response = await fetch(
        apiUrl(`/get_time_entries.php?ticket_id=${ticketId}`),
        { credentials: 'include' }
      )
      const data = await response.json()
      if (data.success) timeEntries.value = data.time_entries
    } catch (err) {
      console.error(err)
    }
  }

  const addTimeEntry = async (ticketId, hours, description) => {
    const response = await fetch(apiUrl('/add_time_entry.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticketId,
        hours: hours,
        description: description
      })
    })
    const data = await response.json()
    if (data.success) {
      timeEntries.value.push({
        id: data.entry_id || Date.now(),
        date: new Date().toLocaleDateString('de-DE'),
        user: data.user_name || '',
        hours: hours,
        description: description
      })
      return true
    }
    return false
  }

  return { timeEntries, loadTimeEntries, addTimeEntry }
}