// composables/useTicket.js
import { ref } from 'vue'
import { useRoute } from 'vue-router'

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
    // Falls ein Query-Parameter existiert, übergeben wir ihn als Fallback – aber die API ermittelt die Quelle selbst.
    const sourceFromQuery = route.query.source || 'form'
    const response = await fetch(`/api/get_ticket_details.php?id=${route.params.id}&source=${sourceFromQuery}`)
    const data = await response.json()
    if (data.success) {
      ticket.value = data.ticket
      // WICHTIG: Die Quelle aus dem Ticket setzen (die API hat sie korrekt ermittelt)
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

  const updateStatus = async (newStatus, source, changedBy, notifyCustomer) => {
    const response = await fetch('/api/update_ticket_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        status: newStatus,
        notify_customer: notifyCustomer,
        source: source,
        changed_by: changedBy
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.status = newStatus
      ticket.value.last_updated_by = changedBy
      ticket.value.updated_at = new Date().toISOString()
      return true
    } else {
      console.error('Status update failed:', data.error)
      return false
    }
  }

  const updateAssignee = async (assigneeName, source, changedBy) => {
    const response = await fetch('/api/update_assignee.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        assignee_name: assigneeName,
        source: source,
        changed_by: changedBy
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.assigned_to = data.assignee_name
      ticket.value.last_updated_by = changedBy
      ticket.value.updated_at = new Date().toISOString()
      return true
    } else {
      console.error('Assignee update failed:', data.error)
      return false
    }
  }

  const saveDueDate = async (dueDate) => {
    const response = await fetch('/api/update_due_date.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        due_date: dueDate
      })
    })
    const data = await response.json()
    if (data.success) {
      ticket.value.due_date = dueDate
      return true
    } else {
      console.error('Due date update failed:', data.error)
      return false
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
    saveDueDate
  }
}