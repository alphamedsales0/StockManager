// composables/useAttachments.js
import { ref } from 'vue'
import { apiUrl } from '@/config/api'

export function useAttachments() {
  const attachments = ref([])

  const loadAttachments = async (ticketId) => {
    try {
      const response = await fetch(
        apiUrl(`/get_attachments.php?ticket_id=${ticketId}`),
        { credentials: 'include' }
      )
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
    const response = await fetch(apiUrl('/upload_attachment.php'), {
      method: 'POST',
      credentials: 'include',
      body: formData
    })
    const data = await response.json()
    if (data.success) {
      const newFiles = data.files || []
      attachments.value = [...attachments.value, ...newFiles]
      return true
    }
    return false
  }

  const downloadAttachment = (file) => {
    window.open(apiUrl(`/download_attachment.php?id=${file.id}`), '_blank')
  }

  return { attachments, loadAttachments, uploadFiles, downloadAttachment }
}