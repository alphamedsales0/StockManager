// composables/useAssignees.js
import { ref } from 'vue'

export function useAssignees() {
  const assigneeOptions = ref([])
  const loadingAssignees = ref(false)

  const loadAssignees = async () => {
    loadingAssignees.value = true
    try {
      const response = await fetch('https://alpha-med-care.com/api/get_users.php')
      const data = await response.json()
      if (data.success) {
        assigneeOptions.value = data.users
      } else {
        console.error('API ERROR:', data.error)
      }
    } catch (err) {
      console.error('LOAD ASSIGNEES ERROR:', err)
    } finally {
      loadingAssignees.value = false
    }
  }

  return { assigneeOptions, loadingAssignees, loadAssignees }
}