<template>
  <v-container>
    <h1 class="text-h4 mb-4">Neuer Mitarbeiter</h1>
    <v-form @submit.prevent="submit" ref="form">
      <!-- Benutzerdaten -->
      <v-text-field v-model="employee.email" label="E-Mail" type="email" required />
      <v-text-field v-model="employee.password" label="Passwort" type="password" required />

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

      <!-- Adresse -->
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

      <v-btn type="submit" color="success" :loading="loading">Speichern</v-btn>
      <v-btn type="button" color="grey" @click="$router.back()">Abbrechen</v-btn>
    </v-form>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const form = ref(null)
const loading = ref(false)
const employee = ref({
  email: '',
  password: '',
  vorname: '',
  nachname: '',
  mitarbeiter_nummer: '',
  telefon: '',
  mobil: '',
  position: '',
  abteilung: '',
  einstellungsdatum: '',
  geburtsdatum: '',
  gehalt: null,
  notfall_kontakt_name: '',
  notfall_kontakt_telefon: '',
  adresse: {
    strasse: '',
    hausnummer: '',
    plz: '',
    stadt: '',
    land: 'Deutschland'
  }
})

const submit = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const response = await axios.post('/api/employees.php', employee.value)
    if (response.data.success) {
      router.push('/employees')
    } else {
      alert('Fehler beim Speichern: ' + (response.data.error || 'Unbekannter Fehler'))
    }
  } catch (error) {
    console.error(error)
    alert('Netzwerkfehler beim Speichern.')
  } finally {
    loading.value = false
  }
}
</script>