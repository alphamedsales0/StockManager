// composables/useAttachments.js
import { ref } from 'vue'

export function useAttachments() {
  const attachments = ref([])

  const loadAttachments = async (ticketId) => {
    try {
      const response = await fetch(`/api/get_attachments.php?ticket_id=${ticketId}`)
      const data = await response.json()
      if (data.success) attachments.value = data.attachments
    } catch (err) {
      console.error(err)
    }
  }

  const uploadFiles = async (ticketId, files, uploadedBy) => {
    const formData = new FormData()
    formData.append('ticket_id', ticketId)
    formData.append('uploaded_by', uploadedBy)
    for (let file of files) {
      formData.append('files[]', file)
    }
    const response = await fetch('/api/upload_attachment.php', {
      method: 'POST',
      body: formData
    })
    const data = await response.json()
    if (data.success) {
      // Dateien zur Liste hinzufügen
      const newFiles = data.files || []
      attachments.value = [...attachments.value, ...newFiles]
      return true
    }
    return false
  }

  const downloadAttachment = (file) => {
    window.open(`/api/download_attachment.php?id=${file.id}`, '_blank')
  }

  return { attachments, loadAttachments, uploadFiles, downloadAttachment }
}