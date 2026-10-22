<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title class="d-flex align-center">
      <v-icon color="primary" class="mr-2">mdi-briefcase-outline</v-icon>
      Berufliche Daten

      <!-- ✅ Badge "Ausgetreten" -->
      <v-chip
        v-if="employee.austrittsdatum"
        color="warning"
        size="small"
        class="ml-auto"
        prepend-icon="mdi-exit-to-app"
      >
        Ausgetreten am {{ formatDate(employee.austrittsdatum) }}
      </v-chip>
    </v-card-title>

    <v-card-text>
      <!-- Position (Combobox dynamique depuis la BDD) -->
      <v-combobox
        v-model="employee.position"
        label="Position"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        autocomplete="job-title"
        :items="positions"
        item-title="name"
        item-value="name"
        :loading="loadingPositions"
        :rules="[rules.required]"
        clearable
        no-filter
        @update:model-value="onPositionChange"
      >
        <template #no-data>
          <v-list-item>
            <span class="text-caption">
              Keine Position gefunden. Drücken Sie <strong>Enter</strong>, um eine neue anzulegen.
            </span>
          </v-list-item>
        </template>
      </v-combobox>

      <!-- Abteilung (Combobox dynamique depuis la BDD) -->
      <v-combobox
        v-model="employee.abteilung"
        label="Abteilung"
        variant="outlined"
        prepend-inner-icon="mdi-office-building"
        autocomplete="organization"
        :items="departments"
        item-title="name"
        item-value="name"
        :loading="loadingDepartments"
        :rules="[rules.required]"
        clearable
        no-filter
        @update:model-value="onDepartmentChange"
      >
        <template #no-data>
          <v-list-item>
            <span class="text-caption">
              Keine Abteilung gefunden. Drücken Sie <strong>Enter</strong>, um eine neue anzulegen.
            </span>
          </v-list-item>
        </template>
      </v-combobox>

      <!-- Rolle : sélection stricte depuis la BDD, aucune valeur par défaut -->
      <v-select
        v-model="employee.role"
        label="Rolle"
        placeholder="Rolle auswählen"
        variant="outlined"
        prepend-inner-icon="mdi-account-tie"
        :items="roles"
        :loading="loadingRoles"
        :rules="[rules.required]"
        item-title="title"
        item-value="value"
        clearable
        hide-no-data
        no-data-text="Keine Rollen verfügbar"
      />

      <!-- Vorgesetzte/r -->
      <div class="mb-2">
        <span class="text-caption text-grey">
          Wählen Sie eine Person aus der Liste
        </span>
        <v-select
          v-model="employee.vorgesetzter"
          :items="users"
          label="Vorgesetzte/r"
          variant="outlined"
          prepend-inner-icon="mdi-account-supervisor"
          item-title="name"
          item-value="name"
          :loading="loadingUsers"
          :items-per-page="20"
          clearable
          persistent-hint
          no-data-text="Keine Benutzer gefunden"
        >
          <template #prepend-item>
            <v-list-item
              v-if="!loadingUsers"
              title="Keine Auswahl (leer lassen)"
              value=""
              @click="employee.vorgesetzter = ''"
            />
          </template>
          <template #item="{ item, props }">
            <v-list-item v-bind="props">
              <template #title>
                <span>{{ item.raw.name }}</span>
                <span class="text-caption text-grey ml-2">({{ item.raw.email }})</span>
              </template>
            </v-list-item>
          </template>
        </v-select>
      </div>

      <!-- ✅ Einstellungsdatum / Austrittsdatum / Geburtsdatum -->
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.einstellungsdatum"
            label="Einstellungsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-start"
          />
        </v-col>

        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.austrittsdatum"
            label="Austrittsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-end"
            :rules="austrittRules"
            :min="employee.einstellungsdatum || undefined"
            clearable
            persistent-hint
            hint="Leer lassen, wenn der Mitarbeiter noch im Unternehmen ist"
          />
        </v-col>

        <v-col cols="12" sm="6">
          <v-text-field
            v-model="employee.geburtsdatum"
            label="Geburtsdatum"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-account"
          />
        </v-col>
      </v-row>

      <!-- Gehalt -->
      <v-text-field
        v-model="employee.gehalt"
        label="Gehalt"
        prefix="€"
        type="number"
        variant="outlined"
        prepend-inner-icon="mdi-cash"
        :rules="[rules.number]"
        autocomplete="salary"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useEmployeeStore } from '../../stores/employeeStore'
import { useValidationRules } from '../../composables/useValidationRules'
import axios from 'axios'

const store = useEmployeeStore()
const employee = store.employee
const rules = useValidationRules()

// ============================================================
// ✅ RÈGLE RÉACTIVE : se revalide dès que einstellungsdatum change
// ============================================================
const austrittRules = computed(() => [
  rules.austrittNachEintritt(employee.einstellungsdatum)
])

// ============================================================
// ✅ Helper d'affichage de date (badge "Ausgetreten")
// ============================================================
const formatDate = (val) => {
  if (!val) return ''
  try {
    return new Date(val).toLocaleDateString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return val
  }
}

// ============================================================
// UTILISATEURS (Vorgesetzte/r)
// ============================================================
const users = ref([])
const loadingUsers = ref(false)

const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await axios.get('/api/get_users.php')
    if (response.data.success) {
      users.value = response.data.users
    } else {
      console.error('Erreur chargement utilisateurs:', response.data.error)
      users.value = []
    }
  } catch (error) {
    console.error('Network error:', error)
    users.value = []
  } finally {
    loadingUsers.value = false
  }
}

// ============================================================
// RÔLES (uniquement depuis la BDD, aucun fallback hardcodé)
// ============================================================
const roles = ref([])
const loadingRoles = ref(false)

const fetchRoles = async () => {
  loadingRoles.value = true
  try {
    const response = await axios.get('https://alpha-med-care.com/api/get_roles.php')
    if (response.data.success) {
      roles.value = response.data.roles.map(r => ({
        title: r.display_name || r.name || `Rolle #${r.id}`,
        value: r.name || String(r.id)
      }))
    } else {
      console.warn('Rollen konnten nicht geladen werden')
      roles.value = []
    }
  } catch (error) {
    console.error('Fehler beim Laden der Rollen:', error)
    roles.value = [] // ✅ Aucun fallback → l'utilisateur doit choisir en BDD
  } finally {
    loadingRoles.value = false
  }
}

// ============================================================
// POSITIONS (chargement + création à la volée)
// ============================================================
const positions = ref([])
const loadingPositions = ref(false)

const loadPositions = async () => {
  loadingPositions.value = true
  try {
    const res = await axios.get('/api/get_positions_stock.php')
    if (res.data.success) {
      positions.value = res.data.items // [{ id, name }]
    } else {
      console.warn('Positions konnten nicht geladen werden')
      positions.value = []
    }
  } catch (error) {
    console.error('Fehler beim Laden der Positions:', error)
    positions.value = []
  } finally {
    loadingPositions.value = false
  }
}

const onPositionChange = async (val) => {
  let name = val
  if (typeof val === 'object' && val !== null) {
    name = val.name ?? val.title ?? ''
  }
  if (typeof name !== 'string') return

  const trimmed = name.trim()
  if (trimmed === '') return

  const exists = positions.value.some(
    p => p.name?.toLowerCase() === trimmed.toLowerCase()
  )
  if (exists) {
    const existing = positions.value.find(
      p => p.name?.toLowerCase() === trimmed.toLowerCase()
    )
    if (existing) employee.position = existing.name
    return
  }

  try {
    const res = await axios.post('/api/add_position_stock.php', { name: trimmed })
    if (res.data.success) {
      positions.value.push(res.data.item)
      employee.position = res.data.item.name
      console.log('✅ Position angelegt:', res.data.item.name)
    } else {
      console.warn('Position konnte nicht angelegt werden:', res.data.message)
    }
  } catch (error) {
    if (error.response?.status === 409) {
      console.warn('Position existiert bereits')
      await loadPositions()
      employee.position = trimmed
    } else {
      console.error('Fehler beim Anlegen der Position:', error)
    }
  }
}

// ============================================================
// ABTEILUNGEN (chargement + création à la volée)
// ============================================================
const departments = ref([])
const loadingDepartments = ref(false)

const loadDepartments = async () => {
  loadingDepartments.value = true
  try {
    const res = await axios.get('/api/get_departments_stock.php')
    if (res.data.success) {
      departments.value = res.data.items // [{ id, name }]
    } else {
      console.warn('Abteilungen konnten nicht geladen werden')
      departments.value = []
    }
  } catch (error) {
    console.error('Fehler beim Laden der Abteilungen:', error)
    departments.value = []
  } finally {
    loadingDepartments.value = false
  }
}

const onDepartmentChange = async (val) => {
  let name = val
  if (typeof val === 'object' && val !== null) {
    name = val.name ?? val.title ?? ''
  }
  if (typeof name !== 'string') return

  const trimmed = name.trim()
  if (trimmed === '') return

  const exists = departments.value.some(
    d => d.name?.toLowerCase() === trimmed.toLowerCase()
  )
  if (exists) {
    const existing = departments.value.find(
      d => d.name?.toLowerCase() === trimmed.toLowerCase()
    )
    if (existing) employee.abteilung = existing.name
    return
  }

  try {
    const res = await axios.post('/api/add_department_stock.php', { name: trimmed })
    if (res.data.success) {
      departments.value.push(res.data.item)
      employee.abteilung = res.data.item.name
      console.log('✅ Abteilung angelegt:', res.data.item.name)
    } else {
      console.warn('Abteilung konnte nicht angelegt werden:', res.data.message)
    }
  } catch (error) {
    if (error.response?.status === 409) {
      console.warn('Abteilung existiert bereits')
      await loadDepartments()
      employee.abteilung = trimmed
    } else {
      console.error('Fehler beim Anlegen der Abteilung:', error)
    }
  }
}

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
  loadUsers()
  fetchRoles()
  loadPositions()
  loadDepartments()
})
</script>