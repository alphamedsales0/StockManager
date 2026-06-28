<template>
  <div>
    <!-- Basisinformationen -->
    <v-row>
      <v-col cols="12" sm="6" md="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-floor-plan</v-icon> Fußboden</div>
          <div class="item-value">{{ data.floor || '-' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-door</v-icon> Türen</div>
          <div class="item-value">{{ data.doors || '-' }}</div>
        </div>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-elevator</v-icon> Aufzug vorhanden</div>
          <div class="item-value">{{ data.elevator ? 'Ja' : 'Nein' }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Aufzug-Details (bedingt) -->
    <template v-if="data.elevator">
      <v-row>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-storefront</v-icon> Zugang Stockwerk</div>
            <div class="item-value">{{ data.elevatorAccess || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-arrow-expand-horizontal</v-icon> Türe Maße (m)</div>
            <div class="item-value">{{ data.elevatorDoorSize || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-arrow-expand-vertical</v-icon> Innentiefe (m)</div>
            <div class="item-value">{{ data.elevatorInsideSize || '-' }}</div>
          </div>
        </v-col>
      </v-row>
    </template>

    <!-- Treppe -->
    <v-row>
      <v-col cols="12" sm="6" md="4">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-stairs</v-icon> Treppe vorhanden</div>
          <div class="item-value">{{ data.stairs ? 'Ja' : 'Nein' }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Treppen-Details (bedingt) -->
    <template v-if="data.stairs">
      <v-row>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-ruler</v-icon> Treppenbreite (cm)</div>
            <div class="item-value">{{ data.stairWidth || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-rotate-right</v-icon> Übers Eck</div>
            <div class="item-value">{{ data.cornerStair ? 'Ja' : 'Nein' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-export</v-icon> Ausstieg Stockwerk</div>
            <div class="item-value">{{ data.stairExit || '-' }}</div>
          </div>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-numeric</v-icon> Stufenanzahl</div>
            <div class="item-value">{{ data.stairSteps || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-arrow-right-bold</v-icon> Verlauf nach Stufen</div>
            <div class="item-value">{{ data.stairAfterSteps || '-' }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="info-item">
            <div class="item-label"><v-icon size="16">mdi-hand-saw</v-icon> Geländer abnehmbar</div>
            <div class="item-value">{{ data.railingRemovable ? 'Ja' : 'Nein' }}</div>
          </div>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
defineProps(['data'])
</script>