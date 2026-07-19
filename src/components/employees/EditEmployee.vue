<template>
  <v-container>
    <h1 class="text-h4 mb-4">Mitarbeiter bearbeiten</h1>
    <v-form @submit.prevent="submit" ref="form" v-if="employee">
      <!-- Benutzerdaten -->
      <v-text-field v-model="employee.email" label="E-Mail" type="email" required />
      <v-text-field v-model="employee.password" label="Neues Passwort (leer lassen, um nicht zu ändern)" type="password" />

      <!-- Persönliche Daten -->
      <v-row>
        <v-col cols="6">
          <v-text-field v-model="employee.vorname" label="Vorname" required />
        </v-col>
        <v-col cols="6">
          <v-text-field v-model="employee.nachname" label="Nachname" required />
        </v-col>
      </v-row>
      <v-text-field v-model="employee.mitarbeiter_nummer" label="Mitarbeiter-Nummer" />
      <v-row>
        <v-col cols="6">
          <v-text-field v-model="employee.telefon" label="Telefon" />
        </v-col>
        <v-col cols="6">
          <v-text-field v-model="employee.mobil" label="Mobil" />
        </v-col>
      </v-row>
      <v-text-field v-model="employee.position" label="Position" />
      <v-text-field v-model="employee.abteilung" label="Abteilung" />
      <v-row>
        <v-col cols="6">
          <v-text-field v-model="employee.einstellungsdatum" label="Einstellungsdatum" type="date" />
        </v-col>
        <v-col cols="6">
          <v-text-field v-model="employee.geburtsdatum" label="Geburtsdatum" type="date" />
        </v-col>
      </v-row>
      <v-text-field v-model="employee.gehalt" label="Gehalt" type="number" step="0.01" prefix="€" />
      <v-text-field v-model="employee.notfall_kontakt_name" label="Notfallkontakt (Name)" />
      <v-text-field v-model="employee.notfall_kontakt_telefon" label="Notfallkontakt (Telefon)" />

      <!-- Adresse (une seule primaire) -->
      <v-expansion-panels>
        <v-expansion-panel>
          <v-expansion-panel-title>Adresse (primär)</v-expansion-panel-title>
          <v-expansion-panel-text>
            <v-text-field v-model="employee.adresse.strasse" label="Straße" required />
            <v-text-field v-model="employee.adresse.hausnummer" label="Hausnummer" required />
            <v-row>
              <v-col cols="4">
                <v-text-field v-model="employee.adresse.plz" label="PLZ" required />
              </v-col>
              <v-col cols="8">
                <v-text-field v-model="employee.adresse.stadt" label="Stadt" required />
              </v-col>
            </v-row>
            <v-text-field v-model="employee.adresse.land" label="Land" />
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <v-btn type="submit" color="primary" :loading="loading">Aktualisieren</v-btn>
      <v-btn type="button" color="grey" @click="$router.back()">Abbrechen</v-btn>
    </v-form>
    <v-progress-circular v-else indeterminate color="primary" />
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
const employee = ref(null)

const fetchEmployee = async () => {
  const id = route.params.id
  try {
    const response = await axios.get(`/api/employees.php?id=${id}`)
    if (response.data.success) {
      const data = response.data.employee
      // Extraire la première adresse active (primär)
      const addresses = data.addresses || []
      const primary = addresses.find(a => a.adresstyp === 'primär') || {}
      data.adresse = {
        strasse: primary.strasse || '',
        hausnummer: primary.hausnummer || '',
        plz: primary.plz || '',
        stadt: primary.stadt || '',
        land: primary.land || 'Deutschland'
      }
      employee.value = data
    } else {
      alert('Fehler beim Laden: ' + (response.data.error || 'Unbekannter Fehler'))
      router.push('/employees')
    }
  } catch (error) {
    console.error('Fehler beim Laden:', error)
    alert('Netzwerkfehler beim Laden des Mitarbeiters.')
    router.push('/employees')
  }
}

const submit = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const payload = { ...employee.value }
    if (!payload.password) delete payload.password
    const response = await axios.put(`/api/employees.php?id=${payload.benutzer_id}`, payload)
    if (response.data.success) {
      router.push('/employees')
    } else {
      alert('Fehler beim Aktualisieren: ' + (response.data.error || 'Unbekannter Fehler'))
    }
  } catch (error) {
    console.error(error)
    alert('Netzwerkfehler beim Aktualisieren.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchEmployee)
</script>