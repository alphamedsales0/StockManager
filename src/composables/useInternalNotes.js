// composables/useInternalNotes.js
import { ref } from 'vue'
import { apiUrl } from '@/config/api'

export function useInternalNotes() {
  const internalNotes = ref([])

  const loadInternalNotes = async (ticketId) => {
    try {
      const response = await fetch(
        apiUrl(`/get_internal_notes.php?ticket_id=${ticketId}`),
        { credentials: 'include' }
      )
      const data = await response.json()
      if (data.success) internalNotes.value = data.notes
    } catch (err) {
      console.error(err)
    }
  }

  const addInternalNote = async (ticketId, note, author) => {
    const response = await fetch(apiUrl('/add_internal_note.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticketId,
        note: note,
        author: author
      })
    })
    const data = await response.json()
    if (data.success) {
      internalNotes.value.push({
        id: data.note_id || Date.now(),
        text: note,
        author: author,
        created_at: new Date().toISOString()
      })
      return true
    }
    return false
  }

  return { internalNotes, loadInternalNotes, addInternalNote }
}