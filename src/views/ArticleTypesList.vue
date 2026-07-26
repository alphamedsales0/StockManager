<template>
  <v-container fluid class="pa-6">

    <!-- HEADER CARD -->
    <v-card rounded="xl" elevation="3" class="mb-6">
      <v-toolbar color="transparent" class="px-4">
        <v-toolbar-title>
          <v-icon color="primary" class="mr-2">mdi-format-list-bulleted-type</v-icon>
          <strong>Artikeltypen</strong>
        </v-toolbar-title>

        <v-spacer />

        <v-text-field
          v-model="search"
          label="Suchen..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width:300px"
        />

        <v-btn color="primary" prepend-icon="mdi-plus" class="ml-4" rounded="lg" @click="openAddDialog">
          Neu
        </v-btn>
      </v-toolbar>
    </v-card>

    <!-- DATATABLE -->
    <v-card rounded="xl" elevation="3">
      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="articleTypes"
        :items-length="totalItems"
        :loading="loading"
        :search="search"
        item-value="id"
        @update:options="loadItems"
      >
        <!-- ID -->
        <template #item.id="{ item }">
          <v-chip size="small" color="primary" variant="tonal">#{{ item.id }}</v-chip>
        </template>

        <!-- NAME -->
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <v-avatar size="36" color="primary" class="mr-3">
              <v-icon>mdi-tag</v-icon>
            </v-avatar>
            <div><strong>{{ item.name }}</strong></div>
          </div>
        </template>

        <!-- VALUE -->
        <template #item.value="{ item }">
          <v-chip color="secondary" variant="outlined">{{ item.value }}</v-chip>
        </template>

        <!-- ACTIONS -->
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="openEditDialog(item)"></v-btn>
          <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="openDeleteDialog(item)"></v-btn>
        </template>

        <!-- Leere Tabelle -->
        <template #no-data>
          <div class="pa-6 text-center text-grey">
            <v-icon size="48" class="mb-2">mdi-inbox-outline</v-icon>
            <div>Keine Artikeltypen vorhanden</div>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- ADD / EDIT DIALOG -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card rounded="xl">
        <v-card-title class="pa-6">
          <v-icon color="primary" class="mr-2">mdi-tag-plus</v-icon>
          {{ editing ? 'Artikeltyp bearbeiten' : 'Neuen Artikeltyp anlegen' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="saveType">
            <v-text-field
              v-model="form.name"
              label="Name"
              variant="outlined"
              prepend-inner-icon="mdi-tag"
              class="mb-3"
              :rules="[v => !!v?.trim() || 'Name ist erforderlich']"
              required
            />
            <v-text-field
              v-model="form.value"
              label="Technischer Wert"
              hint="z.B. treadmill"
              persistent-hint
              variant="outlined"
              prepend-inner-icon="mdi-code-tags"
              :rules="[v => !!v?.trim() || 'Technischer Wert ist erforderlich']"
              required
            />
            <v-card-actions class="pa-0 mt-4">
              <v-spacer />
              <v-btn variant="text" @click="dialog=false">Abbrechen</v-btn>
              <v-btn color="primary" type="submit" :loading="saving">Speichern</v-btn>
            </v-card-actions>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- DELETE CONFIRMATION -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title>
          <v-icon color="error" class="mr-2">mdi-alert</v-icon>
          Löschen bestätigen
        </v-card-title>
        <v-card-text>
          Möchten Sie den Artikeltyp <strong>{{ selectedItem?.name }}</strong> wirklich löschen?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog=false">Abbrechen</v-btn>
          <v-btn color="error" :loading="deleting" @click="deleteType">Löschen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- SNACKBAR -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000">
      {{ snackbar.text }}
      <template #actions>
        <v-btn icon="mdi-close" variant="text" @click="snackbar.show=false"></v-btn>
      </template>
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'


const articleTypes = ref([])
const totalItems = ref(0)
const loading = ref(false)
const itemsPerPage = ref(10)
const search = ref('')

const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Technischer Wert', key: 'value', sortable: true },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
]

// Aktuelle Tabellenoptionen (für Neuladen)
let currentOptions = {
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'desc' }] // Standard-Sortierung
}

// ------------------------------------------------------
// LOAD DATA
// ------------------------------------------------------
const loadItems = async (options) => {
  currentOptions = options
  loading.value = true

  try {
    const params = new URLSearchParams({
      page: options.page,
      itemsPerPage: options.itemsPerPage,
      search: search.value,
      sortBy: options.sortBy?.length ? options.sortBy[0].key : 'id',
      sortOrder: options.sortBy?.length ? options.sortBy[0].order : 'desc'
    });

    // Relativer Pfad – läuft über den Proxy
    const response = await fetch(`/api/article_types_pagination.php?${params.toString()}`);
    const data = await response.json();

    if (data.success) {
      articleTypes.value = data.items;
      totalItems.value = data.total;
    } else {
      showSnackbar('Fehler: ' + (data.message || 'Unbekannt'), 'error');
    }
  } catch (error) {
    showSnackbar('Netzwerkfehler beim Laden', 'error');
    console.error(error);
  } finally {
    loading.value = false;
  }
}

// ------------------------------------------------------
// BEIM MOUNTEN
// ------------------------------------------------------
onMounted(() => {
  loadItems(currentOptions)
})

// ------------------------------------------------------
// SEARCH WATCH
// ------------------------------------------------------
watch(search, () => {
  currentOptions.page = 1
  loadItems(currentOptions)
})

// ------------------------------------------------------
// DIALOG ADD / EDIT
// ------------------------------------------------------
const dialog = ref(false)
const editing = ref(false)
const saving = ref(false)
const form = ref({ id: null, name: '', value: '' })
const formRef = ref(null) // Referenz für v-form

const openAddDialog = () => {
  editing.value = false
  form.value = { id: null, name: '', value: '' }
  dialog.value = true
  // Fehler zurücksetzen, falls vorhanden
  formRef.value?.resetValidation()
}

const openEditDialog = (item) => {
  editing.value = true
  form.value = { id: item.id, name: item.name, value: item.value }
  dialog.value = true
  formRef.value?.resetValidation()
}

// ------------------------------------------------------
// SAVE
// ------------------------------------------------------
const saveType = async () => {
  // Formular-Validierung
  const { valid } = await formRef.value?.validate() || { valid: false }
  if (!valid) return

  saving.value = true
  try {
    let url, method
    if (editing.value) {
      url = `${API_BASE}/update_article_type.php`
      method = 'put'
    } else {
      url = `${API_BASE}/add_article_type.php`
      method = 'post'
    }

    const response = await axios({
      method,
      url,
      data: form.value,
      headers: { 'Content-Type': 'application/json' }
    })

    if (response.data.success) {
      showSnackbar('Artikeltyp erfolgreich gespeichert', 'success')
      dialog.value = false
      await loadItems(currentOptions)
    } else {
      showSnackbar('Fehler: ' + (response.data.message || 'Unbekannt'), 'error')
    }
  } catch (error) {
    console.error(error)
    showSnackbar(error.response?.data?.message || 'Fehler beim Speichern', 'error')
  } finally {
    saving.value = false
  }
}

// ------------------------------------------------------
// DELETE
// ------------------------------------------------------
const deleteDialog = ref(false)
const deleting = ref(false)
const selectedItem = ref(null)

const openDeleteDialog = (item) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const deleteType = async () => {
  deleting.value = true
  try {
    const response = await axios({
      method: 'post',
      url: `${API_BASE}/delete_article_type.php`,
      data: { id: selectedItem.value.id },
      headers: { 'Content-Type': 'application/json' }
    })

    if (response.data.success) {
      showSnackbar('Artikeltyp gelöscht', 'success')
      deleteDialog.value = false
      await loadItems(currentOptions)
    } else {
      showSnackbar('Fehler: ' + (response.data.message || 'Unbekannt'), 'error')
    }
  } catch (error) {
    console.error(error)
    showSnackbar('Fehler beim Löschen', 'error')
  } finally {
    deleting.value = false
  }
}

// ------------------------------------------------------
// SNACKBAR
// ------------------------------------------------------
const snackbar = ref({ show: false, text: '', color: 'success' })

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}
</script>

<style scoped>
.v-container { max-width: 1600px; }
.v-card { transition: all .25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-toolbar { min-height: 80px; }
.v-toolbar-title { font-size: 1.25rem; letter-spacing: .3px; }
:deep(.v-data-table) { border-radius: 16px; overflow: hidden; }
:deep(.v-data-table-header__content) { font-weight: 700; color: rgb(var(--v-theme-primary)); }
:deep(.v-data-table__td) { height: 64px; }
:deep(.v-data-table__tr) { transition: background .2s ease; }
:deep(.v-data-table__tr:hover) { background: rgba(var(--v-theme-primary), .05); }
.v-btn { text-transform: none; letter-spacing: .2px; }
.v-dialog .v-card { overflow: hidden; }
.v-dialog .v-card-title { font-size: 1.2rem; font-weight: 700; }
:deep(.v-field) { border-radius: 12px; }
:deep(.v-field--focused) { box-shadow: 0 0 0 2px rgba(var(--v-theme-primary), .15); }
.v-chip { font-weight: 600; }
.v-snackbar { font-weight: 500; }
@media(max-width:900px) {
  .v-toolbar { flex-wrap: wrap; height: auto; padding-bottom: 15px; }
  .v-toolbar-title { width: 100%; margin-bottom: 15px; }
  .v-text-field { width: 100% !important; max-width: none !important; }
  .v-btn { margin-top: 10px; }
}
</style>