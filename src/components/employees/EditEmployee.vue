<template>
  <v-container>
    <h1 class="text-h4 mb-4">Mitarbeiter bearbeiten</h1>
    <v-form @submit.prevent="submit" ref="form">
      <v-text-field
        v-model="employee.name"
        label="Name"
        required
        :rules="[v => !!v || 'Name ist erforderlich']"
      />
      <v-text-field
        v-model="employee.email"
        label="E-Mail"
        type="email"
        required
        :rules="[v => !!v || 'E-Mail ist erforderlich', v => /.+@.+\..+/.test(v) || 'Ungültige E-Mail']"
      />
      <v-text-field
        v-model="employee.password"
        label="Neues Passwort (leer lassen, um nicht zu ändern)"
        type="password"
        hint="Nur ausfüllen, wenn Sie das Passwort zurücksetzen möchten"
      />
      <v-btn type="submit" color="primary" :loading="loading">Aktualisieren</v-btn>
      <v-btn type="button" color="grey" @click="$router.back()">Abbrechen</v-btn>
    </v-form>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const form = ref(null)
const loading = ref(false)
const employee = ref({
  id: null,
  name: '',
  email: '',
  password: '' // optional
})

const fetchEmployee = async () => {
  const id = route.params.id
  try {
    const response = await axios.get(`/api/employees/${id}`)
    employee.value = response.data.user
  } catch (error) {
    console.error('Fehler beim Laden:', error)
    router.push('/employees')
  }
}

const submit = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const payload = { ...employee.value }
    if (!payload.password) delete payload.password // nur senden, wenn gesetzt
    await axios.put(`/api/employees/${employee.value.id}`, payload)
    router.push('/employees')
  } catch (error) {
    console.error('Fehler beim Aktualisieren:', error)
    alert('Fehler beim Speichern.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchEmployee)
</script>