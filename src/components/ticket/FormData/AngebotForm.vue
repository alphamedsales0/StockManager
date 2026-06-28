<template>
  <div>
    <!-- Zeile 1: Datum und Menge -->
    <v-row>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-calendar</v-icon> Gewünschtes Datum</div>
          <div class="item-value">{{ data.desired_date ? formatDate(data.desired_date) : '-' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="6">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-cart</v-icon> Anzahl Positionen</div>
          <div class="item-value">{{ data.total_quantity || 0 }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Zeile 2: Nachricht -->
    <v-row>
      <v-col cols="12">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-message-text</v-icon> Nachricht</div>
          <div class="item-value">{{ data.message || '-' }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Zeile 3: Optionen -->
    <v-row>
      <v-col cols="12" sm="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-truck</v-icon> Versandkosten im Angebot</div>
          <div class="item-value">{{ data.include_shipping ? 'Ja' : 'Nein' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-percent</v-icon> MwSt. ausweisen</div>
          <div class="item-value">{{ data.include_vat ? 'Ja' : 'Nein' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-email-newsletter</v-icon> Newsletter abonniert</div>
          <div class="item-value">{{ data.newsletter ? 'Ja' : 'Nein' }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Zeile 4: Produkte-Tabelle -->
    <v-row v-if="data.cart_items && data.cart_items.length">
      <v-col cols="12">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-format-list-bulleted</v-icon> Produkte</div>
          <div class="item-value">
            <v-table density="compact" class="mt-2">
              <thead>
                <tr>
                  <th>Produkt</th>
                  <th>Menge</th>
                  <th>Preis</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in data.cart_items" :key="idx">
                  <td>{{ item.name }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ item.price }} €</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Zeile 5: Summen -->
    <v-row>
      <v-col cols="6" sm="3">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-currency-eur</v-icon> Zwischensumme</div>
          <div class="item-value">{{ data.cart_subtotal }} €</div>
        </div>
      </v-col>
      <v-col cols="6" sm="3">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-sale</v-icon> Rabatt</div>
          <div class="item-value">{{ data.cart_discount || 0 }} €</div>
        </div>
      </v-col>
      <v-col cols="6" sm="3">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-truck-fast</v-icon> Versandkosten</div>
          <div class="item-value">{{ data.cart_shipping || 0 }} €</div>
        </div>
      </v-col>
      <v-col cols="6" sm="3">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-cash</v-icon> Gesamtsumme</div>
          <div class="item-value font-weight-bold">{{ data.cart_total }} €</div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
const props = defineProps(['data'])

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>