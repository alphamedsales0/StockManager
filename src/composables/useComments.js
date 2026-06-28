// composables/useComments.js
import { ref } from 'vue'

export function useComments() {
  const activities = ref([])

  const loadComments = async (ticketId) => {
    if (!ticketId) return
    try {
      const response = await fetch(`/api/get_comments.php?ticket_id=${ticketId}`)
      const data = await response.json()
      if (data.success) {
        activities.value = data.comments.map(c => ({
          id: c.id,
          type: c.type === 'status' ? 'status' : 'comment',
          author: c.author,
          text: c.text,
          created_at: c.created_at,
          color: c.type === 'status' ? '#1976d2' : '#2e7d32',
          avatarColor: c.type === 'status' ? '#1565c0' : '#2c3e50'
        }))
      }
    } catch (err) {
      console.error('Fehler beim Laden der Kommentare:', err)
    }
  }

  const addComment = async (ticketId, text, author, source) => {
    try {
      const response = await fetch('/api/add_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ticket_id: ticketId,
          text: text,
          author: author,
          source: source,
          type: 'comment'
        })
      })
      const data = await response.json()
      if (data.success) {
        // Neuen Kommentar in activities einfügen (neuesten zuerst)
        activities.value.unshift({
          id: data.comment_id || Date.now(),
          type: 'comment',
          author: author,
          text: text,
          created_at: new Date().toISOString(),
          color: '#2e7d32',
          avatarColor: '#2c3e50'
        })
        return true
      }
      return false
    } catch (err) {
      console.error('Fehler beim Hinzufügen des Kommentars:', err)
      return false
    }
  }

  return { activities, loadComments, addComment }
}