<template>
  <section class="data-table-section mt-8">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h3 class="text-h5 font-weight-bold mb-4">data table</h3>

          <!-- Barre d'outils avec selects natifs -->
          <v-row class="align-center mb-4">
            <v-col cols="12" md="6" class="d-flex ga-2">
              <select v-model="filters.property" class="custom-select">
                <option v-for="opt in propertyOptions" :key="opt.value" :value="opt.value">
                  {{ opt.text }}
                </option>
              </select>

              <select v-model="filters.time" class="custom-select">
                <option v-for="opt in timeOptions" :key="opt.value" :value="opt.value">
                  {{ opt.text }}
                </option>
              </select>

              <v-btn variant="tonal" color="grey-darken-1" prepend-icon="mdi-filter">
                filters
              </v-btn>
            </v-col>

            <v-col cols="12" md="6" class="d-flex justify-md-end ga-2">
              <v-btn color="success" prepend-icon="mdi-plus" rounded="pill" size="small">
                add item
              </v-btn>

              <select v-model="filters.exportType" class="custom-select">
                <option v-for="opt in exportOptions" :key="opt.value" :value="opt.value">
                  {{ opt.text }}
                </option>
              </select>
            </v-col>
          </v-row>

          <!-- Table (identique) -->
          <v-card rounded="lg" elevation="2" class="pa-2">
            <v-table fixed-header hover class="table-data2">
              <thead>
                <tr>
                  <th style="width: 40px">
                    <v-checkbox v-model="selectAll" hide-details density="compact" />
                  </th>
                  <th>name</th>
                  <th>email</th>
                  <th>description</th>
                  <th>date</th>
                  <th>status</th>
                  <th>price</th>
                  <th style="width: 140px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredItems" :key="item.id" class="tr-shadow">
                  <td>
                    <v-checkbox v-model="selected" :value="item.id" hide-details density="compact" />
                  </td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.email }}</td>
                  <td class="desc">{{ item.description }}</td>
                  <td>{{ formatDate(item.date) }}</td>
                  <td>
                    <v-chip :color="getStatusColor(item.status)" size="small" variant="flat" class="status-chip">
                      {{ item.status }}
                    </v-chip>
                  </td>
                  <td>{{ formatPrice(item.price) }}</td>
                  <td>
                    <div class="table-data-feature d-flex ga-1">
                      <button class="item" title="Send" @click="onSend(item)">
                        <i class="zmdi zmdi-mail-send"></i>
                      </button>
                      <button class="item" title="Edit" @click="onEdit(item)">
                        <i class="zmdi zmdi-edit"></i>
                      </button>
                      <button class="item" title="Delete" @click="onDelete(item)">
                        <i class="zmdi zmdi-delete"></i>
                      </button>
                      <button class="item" title="More" @click="onMore(item)">
                        <i class="zmdi zmdi-more"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const items = ref([
  { id: 1, name: 'Lori Lynch', email: 'lori@example.com', description: 'Samsung Galaxy S25 Ultra', date: '2025-01-15 14:32', status: 'Processed', price: 679 },
  { id: 2, name: 'Lori Lynch', email: 'john@example.com', description: 'iPhone 17 128GB Titanium', date: '2025-01-15 14:32', status: 'Processed', price: 999 },
  { id: 3, name: 'Lori Lynch', email: 'lyn@example.com', description: 'iPhone 17 Pro Max 1TB Space Black', date: '2025-01-15 14:32', status: 'Denied', price: 1199 },
  { id: 4, name: 'Lori Lynch', email: 'doe@example.com', description: 'Camera C430W 4k', date: '2025-01-15 14:32', status: 'Processed', price: 699 }
])

const filters = ref({ property: 'all', time: 'today', exportType: 'export' })

const propertyOptions = [
  { text: 'All Properties', value: 'all' },
  { text: 'Sales Data', value: 'sales' },
  { text: 'User Analytics', value: 'analytics' }
]
const timeOptions = [
  { text: 'Today', value: 'today' },
  { text: '3 Days', value: '3days' },
  { text: '1 Week', value: '1week' },
  { text: '1 Month', value: '1month' }
]
const exportOptions = [
  { text: 'Export', value: 'export' },
  { text: 'Export as CSV', value: 'csv' },
  { text: 'Export as PDF', value: 'pdf' },
  { text: 'Export as Excel', value: 'xlsx' }
]

const selected = ref([])
const selectAll = ref(false)

const filteredItems = computed(() => {
  let result = [...items.value]
  if (filters.value.property === 'sales') {
    result = result.filter(item => item.description.toLowerCase().includes('samsung') || item.description.toLowerCase().includes('iphone'))
  } else if (filters.value.property === 'analytics') {
    result = result.filter(item => item.description.toLowerCase().includes('camera'))
  }
  return result
})

watch(selectAll, (val) => {
  selected.value = val ? filteredItems.value.map(i => i.id) : []
})
watch(filteredItems, () => {
  selectAll.value = false
  selected.value = []
}, { deep: true })

const onSend = (item) => { console.log('Send', item) }
const onEdit = (item) => { console.log('Edit', item) }
const onDelete = (item) => { console.log('Delete', item) }
const onMore = (item) => { console.log('More', item) }

const formatDate = (d) => d
const formatPrice = (p) => `$${p.toFixed(2)}`
const getStatusColor = (status) => status === 'Processed' ? 'success' : status === 'Denied' ? 'error' : 'warning'
</script>

<style scoped>
/* Style pour les selects natifs (identique au v-select) */
.custom-select {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #ccc;
  background-color: white;
  font-size: 0.9rem;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
  max-width: 180px;
  width: 100%;
}
.custom-select:hover {
  border-color: #888;
}
.custom-select:focus {
  border-color: #1976d2;
}

.filter-select, .export-select { 
  max-width: 180px; 
}

.table-data2 td.desc { 
  max-width: 220px; 
  white-space: nowrap; 
  overflow: hidden; 
  text-overflow: ellipsis; 
}

.tr-shadow { transition: box-shadow 0.2s; }
.tr-shadow:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); background-color: #fafafa; }

.status-chip { font-weight: 500; text-transform: capitalize; }

/* Boutons d'action style original */
.table-data-feature .item {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: transparent;
  border: none;
  color: #666;
  font-size: 1.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}
.table-data-feature .item:hover {
  background-color: rgba(0,0,0,0.08);
}
.table-data-feature .item i {
  font-size: 1.2rem;
}
</style>