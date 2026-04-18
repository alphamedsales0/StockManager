<template>
  <v-container fluid class="pa-4 pa-md-6">
    <v-row>
      <v-col cols="12" lg="9">
        <v-card elevation="4" rounded="lg">
          <v-card-title class="text-h5 py-3 bg-grey-lighten-3">
            Aktuelle Tickets
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-0">
            <v-table density="comfortable" class="table-earnings">
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
                    <v-chip :color="getStatusColor(item.status)" size="small" label>
                      {{ item.status }}
                    </v-chip>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="3">
        <v-card elevation="4" rounded="lg" class="top-countries-card" color="primary" dark>
          <v-card-title class="text-h5 py-3">
            Top-Länder
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-0">
            <v-table density="comfortable" class="table-top-countries">
              <tbody>
                <tr v-for="country in topCountries" :key="country.name">
                  <td class="font-weight-medium">{{ country.name }}</td>
                  <td class="text-end">{{ country.earnings }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
// Mapping des couleurs selon le statut
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
  { date: '2025-01-15 14:32', refNr: '100398', name: 'Sicherheitstechnische Kontrolle (STK)', kategorie: 'Dienstleistungen', status: 'In Bearbeitung' },
  { date: '2025-01-14 10:18', refNr: '100397', name: 'DGÜV-Prüfung', kategorie: 'Dienstleistungen', status: 'In Prüfung' },
  { date: '2025-01-13 16:45', refNr: '100396', name: 'Serviceanforderung', kategorie: 'Services Formular', status: 'Zurückgestellt' },
  { date: '2025-01-12 09:23', refNr: '100395', name: 'Gerätereparaturen', kategorie: 'Dienstleistungen', status: 'Abgeschlossen' },
  { date: '2025-01-11 13:17', refNr: '100393', name: 'Kalibrierung', kategorie: 'Dienstleistungen', status: 'Storniert' },
  { date: '2025-01-10 11:55', refNr: '100392', name: 'Installationsanforderung', kategorie: 'Services Formular', status: 'Abgelehnt' },
  { date: '2025-01-09 15:42', refNr: '100391', name: 'Angebot', kategorie: 'Online-Shop', status: 'In Planung' }
]

const topCountries = [
  { name: 'Vereinigte Staaten', earnings: '$119,366.96' },
  { name: 'Australien', earnings: '$70,261.65' },
  { name: 'Vereinigtes Königreich', earnings: '$46,399.22' },
  { name: 'Türkei', earnings: '$35,364.90' },
  { name: 'Deutschland', earnings: '$20,366.96' },
  { name: 'Frankreich', earnings: '$10,366.96' },
  { name: 'Australien', earnings: '$5,366.96' },
  { name: 'Italien', earnings: '$1639.32' }
]
</script>

<style scoped>
.top-countries-card {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
  color: white;
}

.top-countries-card .v-table,
.top-countries-card .v-table__wrapper table {
  background-color: transparent;
  color: white;
}

.top-countries-card .v-table tr {
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.top-countries-card .v-table td,
.top-countries-card .v-table th {
  color: white;
}

.top-countries-card .v-table th {
  font-weight: 500;
  opacity: 0.9;
}
</style>