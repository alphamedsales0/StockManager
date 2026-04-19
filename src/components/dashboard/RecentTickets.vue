<template>
  <v-card elevation="3" rounded="lg" class="recent-tickets-card h-100">
    <v-card-title class="text-h6 py-3 bg-grey-lighten-3">
      Aktuelle Tickets
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text class="pa-0">
      <v-table density="compact" class="table-earnings">
        <thead>
          <tr>
            <th>Datum</th>
            <th>Ref.-Nr.</th>
            <th>Betreff</th>
            <th>Kategorie</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in earningsItems" :key="item.refNr">
            <td>{{ item.date }}</td>
            <td>{{ item.refNr }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.kategorie }}</td>
            <td>
              <v-chip :color="getStatusColor(item.status)" size="x-small" label>
                {{ item.status }}
              </v-chip>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
const getStatusColor = (status) => {
  const colors = {
    'In Bearbeitung': 'warning',
    'In Prüfung': 'info',
    'Zurückgestellt': 'orange-darken-2',
    'Abgeschlossen': 'success',
    'Storniert': 'error',
    'Abgelehnt': 'red-darken-2',
    'In Planung': 'primary'
  }
  return colors[status] || 'grey'
}

const earningsItems = [
  { date: '2025-01-15', refNr: '100398', name: 'Sicherheitstechnische Kontrolle (STK)', kategorie: 'Dienstleistungen', status: 'In Bearbeitung' },
  { date: '2025-01-14', refNr: '100397', name: 'DGÜV-Prüfung', kategorie: 'Dienstleistungen', status: 'In Prüfung' },
  { date: '2025-01-13', refNr: '100396', name: 'Serviceanforderung', kategorie: 'Services Formular', status: 'Zurückgestellt' },
  { date: '2025-01-10', refNr: '100392', name: 'Installationsanforderung', kategorie: 'Services Formular', status: 'Abgelehnt' },
  { date: '2025-01-09', refNr: '100391', name: 'Angebot', kategorie: 'Online-Shop', status: 'In Planung' }
]
</script>

<style scoped>
.recent-tickets-card {
  background: white;
  border-left: 4px solid #1976d2;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.recent-tickets-card .v-card-text {
  flex: 1;
}
.table-earnings th,
.table-earnings td {
  padding: 8px 10px !important;
  font-size: 0.85rem;
}
</style>