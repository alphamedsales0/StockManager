<template>
  <v-dialog
    :model-value="modelValue"
    max-width="900"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card class="permissions-dialog" rounded="lg">
      <!-- ============================================================ -->
      <!-- HEADER -->
      <!-- ============================================================ -->
      <v-card-title class="d-flex justify-space-between align-center bg-primary text-white px-4 py-3">
        <span class="text-h6 font-weight-semibold">
          <v-icon start color="white">mdi-shield-key</v-icon>
          Berechtigungen: {{ roleName }}
        </span>
        <v-btn
          icon="mdi-close"
          variant="text"
          color="white"
          size="small"
          @click="close"
        />
      </v-card-title>

      <!-- ============================================================ -->
      <!-- LOADING -->
      <!-- ============================================================ -->
      <v-card-text v-if="loading" class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="48" />
        <p class="text-caption mt-3">Lade Berechtigungen...</p>
      </v-card-text>

      <!-- ============================================================ -->
      <!-- ERREUR -->
      <!-- ============================================================ -->
      <v-card-text v-else-if="error" class="text-center py-8">
        <v-icon size="48" color="error">mdi-alert-circle</v-icon>
        <p class="text-body-2 text-error mt-2">{{ error }}</p>
        <v-btn size="small" color="primary" variant="tonal" @click="loadData">
          Erneut laden
        </v-btn>
      </v-card-text>

      <!-- ============================================================ -->
      <!-- CONTENT -->
      <!-- ============================================================ -->
      <v-card-text v-else class="pa-4" style="max-height: 70vh;">
        <!-- Stats + actions rapides -->
        <div class="d-flex justify-space-between align-center mb-3 flex-wrap gap-2">
          <div class="text-caption text-grey">
            <v-icon size="14">mdi-information-outline</v-icon>
            <strong>{{ selectedIds.length }}</strong> von {{ totalPermissions }} Berechtigungen aktiviert
          </div>
          <div class="d-flex gap-1">
            <v-btn
              size="small"
              variant="outlined"
              color="success"
              prepend-icon="mdi-check-all"
              @click="selectAll"
            >
              Alle
            </v-btn>
            <v-btn
              size="small"
              variant="outlined"
              color="grey"
              prepend-icon="mdi-close-box-multiple"
              @click="deselectAll"
            >
              Keine
            </v-btn>
          </div>
        </div>

        <v-divider class="mb-3" />

        <!-- Matrix par catégorie -->
        <div
          v-for="(perms, category) in groupedPermissions"
          :key="category"
          class="category-block mb-4"
        >
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="text-subtitle-1 font-weight-bold d-flex align-items-center">
              <v-icon start color="primary" size="18">
                {{ categoryIcon(category) }}
              </v-icon>
              {{ category }}
              <v-chip size="x-small" variant="tonal" color="primary" class="ml-2">
                {{ countInCategory(perms) }}/{{ perms.length }}
              </v-chip>
            </h3>
            <v-btn
              size="x-small"
              variant="text"
              color="primary"
              @click="toggleCategory(perms)"
            >
              {{ allCategorySelected(perms) ? 'Abwählen' : 'Alle wählen' }}
            </v-btn>
          </div>

          <v-row dense>
            <v-col
              v-for="perm in perms"
              :key="perm.id"
              cols="12"
              sm="6"
              md="4"
            >
              <v-checkbox
                :model-value="selectedIds.includes(perm.id)"
                color="primary"
                density="compact"
                hide-details
                class="perm-checkbox"
                @update:model-value="togglePermission(perm.id)"
              >
                <template #label>
                  <div>
                    <div class="text-body-2 font-weight-medium">{{ perm.display_name }}</div>
                    <div class="text-caption text-grey">{{ perm.key_name }}</div>
                  </div>
                </template>
              </v-checkbox>
            </v-col>
          </v-row>
        </div>

        <!-- Si aucune permission -->
        <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center py-6">
          <v-icon size="48" color="grey-lighten-2">mdi-shield-off-outline</v-icon>
          <p class="text-body-2 text-grey mt-2">Keine Berechtigungen verfügbar</p>
        </div>
      </v-card-text>

      <!-- ============================================================ -->
      <!-- FOOTER -->
      <!-- ============================================================ -->
      <v-divider />
      <v-card-actions class="pa-3 d-flex justify-space-between">
        <v-btn
          variant="text"
          prepend-icon="mdi-arrow-left"
          @click="close"
        >
          Abbrechen
        </v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-content-save"
          :loading="saving"
          :disabled="saving || loading"
          @click="save"
        >
          Speichern
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { apiUrl } from '@/config/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  roleId: { type: Number, required: true },
  roleName: { type: String, default: '—' }
})

const emit = defineEmits(['update:modelValue', 'saved'])

// ============================================================
// STATE
// ============================================================
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const groupedPermissions = ref({})
const selectedIds = ref([])
const totalPermissions = ref(0)

// ============================================================
// HELPERS
// ============================================================
const categoryIcon = (cat) => {
  const icons = {
    'Dashboard': 'mdi-view-dashboard',
    'Produkte': 'mdi-package-variant',
    'Kunden': 'mdi-account-group',
    'Mitarbeiter': 'mdi-account-tie',
    'Bestellungen': 'mdi-cart',
    'Tickets': 'mdi-ticket',
    'Statistiken': 'mdi-chart-line',
    'Allgemein': 'mdi-help-circle',
    'System': 'mdi-cog'
  }
  return icons[cat] || 'mdi-shield'
}

const countInCategory = (perms) =>
  perms.filter(p => selectedIds.value.includes(p.id)).length

const allCategorySelected = (perms) =>
  perms.every(p => selectedIds.value.includes(p.id))

// ============================================================
// ACTIONS
// ============================================================
const togglePermission = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(idx, 1)
  }
}

const toggleCategory = (perms) => {
  if (allCategorySelected(perms)) {
    perms.forEach(p => {
      const idx = selectedIds.value.indexOf(p.id)
      if (idx !== -1) selectedIds.value.splice(idx, 1)
    })
  } else {
    perms.forEach(p => {
      if (!selectedIds.value.includes(p.id)) {
        selectedIds.value.push(p.id)
      }
    })
  }
}

const selectAll = () => {
  const all = Object.values(groupedPermissions.value).flat()
  selectedIds.value = all.map(p => p.id)
}

const deselectAll = () => {
  selectedIds.value = []
}

// ============================================================
// LOAD / SAVE
// ============================================================
const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    const res = await axios.get(
      apiUrl(`/get_role_permissions.php?id=${props.roleId}`),
      { withCredentials: true }
    )

    // ✅ LOG COMPLET pour voir ce que le serveur renvoie
    console.log('🔍 [Dialog] Role ID:', props.roleId)
    console.log('🔍 [Dialog] Role Name:', props.roleName)
    console.log('🔍 [Dialog] Full response:', res.data)
    console.log('🔍 [Dialog] success:', res.data?.success)
    console.log('🔍 [Dialog] error:', res.data?.error)

    if (res.data?.success) {
      groupedPermissions.value = res.data.permissions || {}
      selectedIds.value = res.data.assigned_ids || []
      totalPermissions.value = res.data.total || 0
    } else {
      error.value = res.data?.error || `API: success = false (${JSON.stringify(res.data)})`
    }
  } catch (err) {
    console.error('❌ [Dialog] Catch:', err)
    error.value = err.response?.data?.error || err.message
  } finally {
    loading.value = false
  }
}

const save = async () => {
  saving.value = true
  error.value = null
  try {
    const res = await axios.post(
      apiUrl('/update_role_permissions.php'),
      {
        role_id: props.roleId,
        permission_ids: selectedIds.value
      },
      { withCredentials: true }
    )

    if (res.data?.success) {
      emit('saved', {
        roleId: props.roleId,
        count: selectedIds.value.length
      })
      close()
    } else {
      error.value = res.data?.error || 'Fehler beim Speichern'
    }
  } catch (err) {
    console.error('[RolePermissionsDialog] Save error:', err)
    error.value = err.response?.data?.error || 'Verbindungsfehler'
  } finally {
    saving.value = false
  }
}

const close = () => {
  emit('update:modelValue', false)
}

// ============================================================
// WATCH
// ============================================================
watch(
  () => props.modelValue,
  (val) => {
    if (val && props.roleId) {
      loadData()
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.permissions-dialog {
  border-radius: 12px !important;
}

.category-block {
  padding: 12px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e8eaf0;
}

.perm-checkbox :deep(.v-label) {
  opacity: 1 !important;
}

.perm-checkbox {
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.15s;
}

.perm-checkbox:hover {
  background: rgba(25, 118, 210, 0.05);
}
</style>