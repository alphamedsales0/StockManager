// composables/useTimeEntries.js
import { ref } from 'vue'

export function useTimeEntries() {
  const timeEntries = ref([])

  const loadTimeEntries = async (ticketId) => {
    try {
      const response = await fetch(`/api/get_time_entries.php?ticket_id=${ticketId}`)
      const data = await response.json()
      if (data.success) timeEntries.value = data.time_entries
    } catch (err) {
      console.error(err)
    }
  }

  const addTimeEntry = async (ticketId, hours, description, userName) => {
    const response = await fetch('/api/add_time_entry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticketId,
        hours: hours,
        description: description,
        user_name: userName
      })
    })
    const data = await response.json()
    if (data.success) {
      timeEntries.value.push({
        id: data.entry_id || Date.now(),
        date: new Date().toLocaleDateString('de-DE'),
        user: userName,
        hours: hours,
        description: description
      })
      return true
    }
    return false
  }

  return { timeEntries, loadTimeEntries, addTimeEntry }
}