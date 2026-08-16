<template>
  <v-container>
    <!-- === KARTE: HINZUFÜGEN === -->
    <v-card class="mb-6" elevation="3">
      <v-card-title class="bg-primary text-white py-3">
        <v-icon left color="white" class="mr-2">mdi-format-list-bulleted-type</v-icon>
        Kategorienverwaltung
      </v-card-title>
      <v-card-text class="pt-4">
        <v-form ref="formRef" @submit.prevent="saveCategory">
          <v-row align="center">
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="form.name"
                label="Name"
                variant="outlined"
                prepend-inner-icon="mdi-tag"
                :rules="[v => !!v?.trim() || 'Name ist erforderlich']"
                required
              />
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="form.value"
                label="Technischer Wert"
                hint="z.B. cardio"
                persistent-hint
                variant="outlined"
                prepend-inner-icon="mdi-code-tags"
                :rules="[v => !!v?.trim() || 'Technischer Wert ist erforderlich']"
                required
              />
            </v-col>
            <v-col cols="12" sm="4" class="d-flex justify-end" style="height: 100%; align-self: flex-start; padding-top: 10px;">
              <v-btn
                type="submit"
                color="primary"
                :loading="saving"
                :disabled="saving"
                height="56"
                class="text-none"
                size="large"
              >
                <v-icon left>mdi-plus</v-icon>
                Hinzufügen
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- === KARTE: VORHANDENE KATEGORIEN === -->
    <v-card elevation="2">
      <!-- Header mit Suche & Seitenanzahl -->
      <v-data-table-server
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="categories"
        :items-length="totalItems"
        :loading="loading"
        :search="search"
        item-value="id"
        @update:options="loadItems"
        hide-default-footer
        class="rounded-lg"
      >
        <template #top>
          <div class="bg-grey-lighten-3 py-3 px-4 d-flex align-center flex-wrap" style="gap: 12px;">
            <div class="d-flex align-center">
              <v-icon left color="primary" class="mr-2">mdi-format-list-bulleted</v-icon>
              <span class="text-subtitle-1 font-weight-medium">Vorhandene Kategorien</span>
              <v-chip size="small" color="primary" class="ml-2">{{ totalItems }}</v-chip>
            </div>
            <v-spacer />
            <v-text-field
              v-model="search"
              label="Suchen..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
              style="max-width: 200px;"
            />
            <v-select
              v-model="itemsPerPage"
              :items="itemsPerPageOptions"
              label="Pro Seite"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 120px;"
            />
          </div>
        </template>

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
          <v-btn
            icon="mdi-pencil"
            size="small"
            color="primary"
            variant="tonal"
            class="mr-1"
            @click="openEditDialog(item)"
          />
          <v-btn
            icon="mdi-delete"
            size="small"
            color="error"
            variant="tonal"
            @click="openDeleteDialog(item)"
          />
        </template>

        <template #no-data>
          <div class="pa-6 text-center text-grey">
            <v-icon size="48" class="mb-2">mdi-inbox-outline</v-icon>
            <div>Keine Kategorien vorhanden</div>
          </div>
        </template>

        <template #bottom>
          <div v-if="totalItems > 0" class="d-flex justify-center pa-4">
            <v-pagination
              v-model="page"
              :length="totalPages"
              :total-visible="5"
              color="primary"
              rounded="circle"
            />
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- ADD / EDIT DIALOG -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card>
        <v-card-title class="bg-primary text-white">
          <v-icon left color="white" class="mr-2">mdi-tag-plus</v-icon>
          {{ editing ? 'Kategorie bearbeiten' : 'Neue Kategorie anlegen' }}
        </v-card-title>
        <v-card-text class="pt-4">
          <v-form ref="formRef" @submit.prevent="saveCategory">
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
              hint="z.B. cardio"
              persistent-hint
              variant="outlined"
              prepend-inner-icon="mdi-code-tags"
              :rules="[v => !!v?.trim() || 'Technischer Wert ist erforderlich']"
              required
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="dialog=false">Abbrechen</v-btn>
          <v-btn color="primary" @click="saveCategory" :loading="saving" class="text-none">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- DELETE CONFIRMATION -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 bg-error text-white">
          <v-icon left color="white">mdi-alert</v-icon>
          Löschen bestätigen
        </v-card-title>
        <v-card-text class="pt-4">
          Möchten Sie die Kategorie <strong class="text-error">{{ selectedItem?.name }}</strong> wirklich löschen?
          Diese Aktion kann nicht rückgängig gemacht werden.
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="deleteDialog=false">Abbrechen</v-btn>
          <v-btn color="error" @click="deleteCategory" :loading="deleting" class="text-none">Löschen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- SNACKBAR -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top end">
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show=false" color="white" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'

// ------------------------------------------------------
// État
// ------------------------------------------------------
const categories = ref([])
const totalItems = ref(0)
const loading = ref(false)
const itemsPerPage = ref(10)
const search = ref('')
const page = ref(1)

const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Technischer Wert', key: 'value', sortable: true },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
]

const itemsPerPageOptions = [
  { title: '5', value: 5 },
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 }
]

const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))

// Options de tableau (pour rechargement)
let currentOptions = {
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'desc' }]
}

// ------------------------------------------------------
// Chargement des données
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
    })

    const response = await fetch(`/api/get_categories_pagination.php?${params.toString()}`)
    const data = await response.json()

    if (data.success) {
      categories.value = data.items
      totalItems.value = data.total
    } else {
      showSnackbar('Fehler: ' + (data.message || 'Unbekannt'), 'error')
    }
  } catch (error) {
    showSnackbar('Netzwerkfehler beim Laden', 'error')
    console.error(error)
  } finally {
    loading.value = false
  }
}

// ------------------------------------------------------
// Montage
// ------------------------------------------------------
onMounted(() => {
  loadItems(currentOptions)
})

// ------------------------------------------------------
// Recherche & Seitenanzahl
// ------------------------------------------------------
watch(search, () => {
  currentOptions.page = 1
  page.value = 1
  loadItems(currentOptions)
})

watch(itemsPerPage, () => {
  page.value = 1
  // loadItems wird durch update:options automatisch ausgelöst
})

// ------------------------------------------------------
// Dialog Ajouter / Éditer
// ------------------------------------------------------
const dialog = ref(false)
const editing = ref(false)
const saving = ref(false)
const form = ref({ id: null, name: '', value: '' })
const formRef = ref(null)

const openAddDialog = () => {
  editing.value = false
  form.value = { id: null, name: '', value: '' }
  dialog.value = true
  formRef.value?.resetValidation()
}

const openEditDialog = (item) => {
  editing.value = true
  form.value = { id: item.id, name: item.name, value: item.value }
  dialog.value = true
  formRef.value?.resetValidation()
}

// ------------------------------------------------------
// Sauvegarder
// ------------------------------------------------------
const saveCategory = async () => {
  const { valid } = await formRef.value?.validate() || { valid: false }
  if (!valid) return

  saving.value = true
  try {
    let url, method
    if (editing.value) {
      url = '/api/update_category.php'
      method = 'put'
    } else {
      url = '/api/add_category.php'
      method = 'post'
    }

    const response = await axios({
      method,
      url,
      data: form.value,
      headers: { 'Content-Type': 'application/json' }
    })

    if (response.data.success) {
      showSnackbar('Kategorie erfolgreich gespeichert', 'success')
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
// Supprimer
// ------------------------------------------------------
const deleteDialog = ref(false)
const deleting = ref(false)
const selectedItem = ref(null)

const openDeleteDialog = (item) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const deleteCategory = async () => {
  deleting.value = true
  try {
    const response = await axios({
      method: 'post',
      url: '/api/delete_category.php',
      data: { id: selectedItem.value.id },
      headers: { 'Content-Type': 'application/json' }
    })

    if (response.data.success) {
      showSnackbar('Kategorie gelöscht', 'success')
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
// Snackbar
// ------------------------------------------------------
const snackbar = ref({ show: false, text: '', color: 'success' })

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}
</script>

<style scoped>

.v-card { transition: all .25s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-data-table-server { border-radius: 16px; overflow: hidden; }
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
  .v-data-table-server :deep(.v-table) { font-size: 0.85rem; }
  .v-select { max-width: 100px !important; }
}
</style>