// composables/useTicket.js
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { apiUrl } from '@/config/api'

export function useTicket() {
  const route = useRoute()
  const ticket = ref(null)
  const loading = ref(true)
  const selectedStatus = ref('')
  const currentSource = ref('form')
  const notifyCustomerOnStatus = ref(false)

  const loadTicket = async () => {
    loading.value = true
    try {
      const sourceFromQuery = route.query.source || 'form'
      const response = await fetch(
        apiUrl(`/get_ticket_details.php?id=${route.params.id}&source=${sourceFromQuery}`),
        { credentials: 'include' }
      )
      const data = await response.json()
      if (data.success) {
        ticket.value = data.ticket
        currentSource.value = ticket.value.source || 'form'
        selectedStatus.value = ticket.value.status
        return data.ticket
      }
    } catch (err) {
      console.error('Fehler beim Laden des Tickets:', err)
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (newStatus, source, notifyCustomer) => {
    const response = await fetch(apiUrl('/update_ticket_status.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        status: newStatus,
        notify_customer: notifyCustomer,
        source: source
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.status = newStatus
      ticket.value.updated_at = new Date().toISOString()
      return true
    } else {
      console.error('Status update failed:', data.error)
      throw new Error(data.error || 'Update fehlgeschlagen')
    }
  }

  const updateAssignee = async (assigneeName, source) => {
    const response = await fetch(apiUrl('/update_assignee.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        assignee_name: assigneeName,
        source: source
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.assigned_to = data.assignee_name
      ticket.value.updated_at = new Date().toISOString()
      return true
    } else {
      console.error('Assignee update failed:', data.error)
      throw new Error(data.error || 'Update fehlgeschlagen')
    }
  }

  const saveDueDate = async (dueDate) => {
    const response = await fetch(apiUrl('/update_due_date.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        due_date: dueDate,
        source: currentSource.value
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.due_date = dueDate
      return true
    } else {
      console.error('Due date update failed:', data.error)
      throw new Error(data.error || 'Update fehlgeschlagen')
    }
  }

  const deleteTicket = async (ticketId, source = 'form') => {
    const response = await fetch(apiUrl('/delete_ticket.php'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticketId,
        source: source
      })
    })
    const data = await response.json()
    if (data.success) {
      return true
    } else {
      console.error('Delete ticket failed:', data.error)
      throw new Error(data.error || 'Löschen fehlgeschlagen')
    }
  }

  return {
    ticket,
    loading,
    selectedStatus,
    currentSource,
    notifyCustomerOnStatus,
    loadTicket,
    updateStatus,
    updateAssignee,
    saveDueDate,
    deleteTicket
  }
}