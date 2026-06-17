<template>
  <v-container fluid :class="['ticket-container', { 'dark-mode': darkMode }]" class="pa-4">
    <!-- Skeleton Loader -->
    <template v-if="loading">
      <v-card class="skeleton-card">
        <v-skeleton-loader type="heading" />
        <v-skeleton-loader type="image" class="mt-4" />
        <v-skeleton-loader type="list-item-three-line" class="mt-4" />
        <v-skeleton-loader type="article" class="mt-4" />
      </v-card>
    </template>

    <!-- Ticket -->
    <template v-else-if="ticket">
      <v-card class="ticket-card glass-effect" :class="{ 'dark-glass': darkMode }">
        <!-- HEADER mit verbessertem Zurück-Button -->
        <v-toolbar :color="darkMode ? '#0a0f1a' : '#0f172a'" dark flat class="toolbar-header px-4">
          <!-- Runder Zurück-Button mit weißem Rand -->
          

          <div class="d-flex align-center">
            <v-avatar color="primary" size="42" class="mr-4 floating-avatar">
              <v-icon size="24">mdi-ticket-confirmation</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold">Ticket {{ ticket.reference_number }}</div>
              <div class="text-caption text-grey-lighten-1">
                {{ entryDateLabel }}: {{ entryDateFormatted }}
              </div>
            </div>
          </div>
          <v-spacer />

          <v-btn 
            icon 
            variant="outlined" 
            color="white" 
            rounded="circle" 
            @click="goBack" 
            class="mr-3" 
            size="small"
          >
            <v-icon>mdi-home</v-icon>
          </v-btn>

          <v-chip :color="priorityColor" class="priority-chip mr-2" size="small" >
            <v-icon start size="14">mdi-alert</v-icon>
            {{ priorityLabel }}
          </v-chip>
          <v-chip :color="statusColor" class="status-chip mr-4" size="large">
            <v-icon start size="18">{{ statusIcon }}</v-icon>
            {{ translateStatus(ticket.status) }}
          </v-chip>
          <!-- PDF-Export Button -->
          <v-btn icon variant="text" @click="exportToPDF" class="mr-2" title="PDF exportieren">
            <v-icon>mdi-file-pdf-box</v-icon>
          </v-btn>
          <!-- Dark Mode Button -->
          <v-btn icon variant="text" @click="darkMode = !darkMode" class="mr-2">
            <v-icon>{{ darkMode ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
          </v-btn>
        </v-toolbar>

        <!-- Rest des Templates (unverändert) -->
        <v-container fluid class="pa-5">
          <v-row>
            <!-- LINKER BEREICH (mit kombinierter Karte) -->
            <v-col cols="12" lg="5">
              
              <!-- 1. Kundendaten -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-account-group</v-icon>
                  Kundendaten
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item v-for="(item, idx) in customerFields" :key="idx">
                      <template #prepend><v-icon>{{ item.icon }}</v-icon></template>
                      <div class="d-flex align-center">
                        <span class="item-label mr-2">{{ item.label }}:</span>
                        <span class="item-value">{{ item.value }}</span>
                      </div>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- 2. Statistiken & Metriken -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-chart-box</v-icon>
                  Statistiken & Metriken
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-row>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Ticket Alter</div>
                        <div class="stat-value">{{ ticketAge }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Kommentare</div>
                        <div class="stat-value">{{ ticket.comments?.length || 0 }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Aktivitäten</div>
                        <div class="stat-value">{{ activities.length }}</div>
                      </div>
                    </v-col>
                    <v-col cols="6" sm="3" lg="6">
                      <div class="stat-card" :class="{ 'dark-stat': darkMode }">
                        <div class="stat-title">Letzte Aktion</div>
                        <div class="stat-value">{{ lastActionTime }}</div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- 3. KOMBINIERTE KARTE: Ticket Aktionen -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-ticket-cog-outline</v-icon>
                  Ticket Aktionen
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <!-- Fälligkeitsdatum -->
                  <div class="mb-6">
                    <div class="d-flex align-center mb-3">
                      <v-icon color="primary" class="mr-2">mdi-calendar-clock</v-icon>
                      <span class="text-subtitle-1 font-weight-bold">Fälligkeitsdatum</span>
                    </div>
                    <v-row align="center">
                      <v-col cols="12" md="7">
                        <v-text-field
                          v-model="dueDate"
                          type="date"
                          label="Fällig am"
                          variant="outlined"
                          density="comfortable"
                          hide-details
                          :class="{ 'overdue-field': isOverdue }"
                        />
                      </v-col>
                      <v-col cols="12" md="5">
                        <v-btn color="primary" block @click="saveDueDate" :loading="savingDueDate">
                          Speichern
                        </v-btn>
                      </v-col>
                    </v-row>
                    <div v-if="isOverdue" class="text-error mt-2">
                      <v-icon small>mdi-alert-circle</v-icon> Dieses Ticket ist überfällig!
                    </div>
                  </div>

                  <v-divider class="my-4" />

                  <!-- Bearbeiter zuweisen -->
                  <div class="mb-6">
                    <div class="d-flex align-center mb-3">
                      <v-icon color="primary" class="mr-2">mdi-account-multiple</v-icon>
                      <span class="text-subtitle-1 font-weight-bold">Bearbeiter zuweisen</span>
                    </div>
                    <v-row align="center">
                      <v-col cols="12" md="8">
                        <v-select
                          v-model="selectedAssignee"
                          :items="assigneeOptions"
                          item-title="name"
                          item-value="id"
                          label="Mitarbeiter auswählen"
                          variant="outlined"
                          density="comfortable"
                          :loading="loadingAssignees"
                          no-data-text="Keine Mitarbeiter gefunden"
                        />
                      </v-col>
                      <v-col cols="12" md="4">
                        <v-btn color="primary" block @click="updateAssignee" :loading="updatingAssignee">
                          Zuweisen
                        </v-btn>
                      </v-col>
                    </v-row>
                    <div class="mt-2 text-caption">Aktuell: {{ ticket.assigned_to || 'Niemand' }}</div>
                  </div>

                  <v-divider class="my-4" />

                  <!-- Status ändern + Kunden-Benachrichtigung -->
                  <div>
                    <div class="d-flex align-center mb-3">
                      <v-icon color="primary" class="mr-2">mdi-sync</v-icon>
                      <span class="text-subtitle-1 font-weight-bold">Status ändern</span>
                    </div>
                    <v-row align="center">
                      <v-col cols="12" md="8">
                        <v-select
                          v-model="selectedStatus"
                          :items="statusOptions"
                          item-title="label"
                          item-value="value"
                          label="Neuen Status wählen"
                          variant="outlined"
                          density="comfortable"
                          hide-details
                        />
                      </v-col>
                      <v-col cols="12" md="4">
                        <v-btn color="primary" block @click="updateStatus" :loading="updatingStatus">
                          Speichern
                        </v-btn>
                      </v-col>
                    </v-row>
                    <v-switch v-model="notifyCustomerOnStatus" label="Kunden per E-Mail benachrichtigen" class="mt-3" hide-details />
                  </div>
                </v-card-text>
              </v-card>

              <!-- 4. Zeitaufwand -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-clock-outline</v-icon>
                  Zeitaufwand ({{ totalHours }} h)
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item v-for="entry in timeEntries" :key="entry.id">
                      <template #prepend><v-icon>mdi-timer</v-icon></template>
                      <div>
                        <div class="item-label">{{ entry.date }} – {{ entry.user }}</div>
                        <div class="item-value">{{ entry.hours }} h – {{ entry.description }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item v-if="!timeEntries.length">
                      <div class="text-grey text-center py-2">Keine Zeit erfasst</div>
                    </v-list-item>
                  </v-list>
                  <v-divider class="my-3" />
                  <v-row>
                    <v-col cols="5">
                      <v-text-field v-model="newTimeHours" type="number" label="Stunden" step="0.5" variant="outlined" density="compact" />
                    </v-col>
                    <v-col cols="7">
                      <v-text-field v-model="newTimeDesc" label="Beschreibung" variant="outlined" density="compact" />
                    </v-col>
                    <v-col cols="12">
                      <v-btn color="primary" block @click="addTimeEntry" :disabled="!newTimeHours">Zeit hinzufügen</v-btn>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- RECHTEN BEREICH (unverändert) -->
            <v-col cols="12" lg="7">
              <!-- FORMULARDATEN (je nach Ticket-Typ) -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-file-document-outline</v-icon>
                  Formulardaten
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <!-- SERVICE REQUEST -->
                    <template v-if="ticket.form_type === 'service_request'">
                      <template v-for="(device, idx) in ticket.form_data.devices" :key="idx">
                        <v-list-item v-if="idx > 0" class="section-divider">
                          <div class="text-subtitle-2 font-weight-bold mt-2 mb-2">Gerät {{ idx+1 }}</div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-factory</v-icon></template>
                          <div>
                            <div class="item-label">Hersteller</div>
                            <div class="item-value">{{ device.manufacturer || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-cellphone</v-icon></template>
                          <div>
                            <div class="item-label">Modell</div>
                            <div class="item-value">{{ device.model || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-counter</v-icon></template>
                          <div>
                            <div class="item-label">Seriennummer</div>
                            <div class="item-value">{{ device.serial || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-alert-circle</v-icon></template>
                          <div>
                            <div class="item-label">Fehlerbeschreibung</div>
                            <div class="item-value">{{ device.errorDescription || 'Keine Angabe' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-text</v-icon></template>
                        <div>
                          <div class="item-label">Zusatzinformationen</div>
                          <div class="item-value">{{ ticket.form_data.additional || '-' }}</div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- MAINTENANCE -->
                    <template v-else-if="ticket.form_type === 'maintenance'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-file-sign</v-icon></template>
                        <div>
                          <div class="item-label">Vertragsart</div>
                          <div class="item-value">{{ ticket.form_data.contractType === 'einzelwartung' ? 'Einzelwartung' : 'Wartungsvertrag' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-checkbox-multiple</v-icon></template>
                        <div>
                          <div class="item-label">Ausgewählte Optionen</div>
                          <div class="item-value">
                            <v-chip v-for="opt in ticket.form_data.options" :key="opt" class="mr-2 mb-2" size="small" color="primary" variant="outlined">{{ opt }}</v-chip>
                          </div>
                        </div>
                      </v-list-item>
                    </template>


                                        <!-- ERSATZTEILE -->
                    <template v-else-if="ticket.form_type === 'ersatzteile'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-cog</v-icon></template>
                        <div>
                          <div class="item-label">Gerät / Produkt</div>
                          <div class="item-value">{{ ticket.form_data.device || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-package-variant</v-icon></template>
                        <div>
                          <div class="item-label">Ersatzteile</div>
                          <div class="item-value" style="white-space: pre-wrap;">{{ ticket.form_data.parts || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-wrench</v-icon></template>
                        <div>
                          <div class="item-label">Service-Typ</div>
                          <div class="item-value">{{ translateServiceType(ticket.form_data.service) }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-speedometer</v-icon></template>
                        <div>
                          <div class="item-label">Dringlichkeit</div>
                          <div class="item-value">
                            <v-chip :color="getUrgencyColor(ticket.form_data.urgency)" size="small">{{ translateUrgency(ticket.form_data.urgency) }}</v-chip>
                          </div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-message-text</v-icon></template>
                        <div>
                          <div class="item-label">Nachricht / Anmerkung</div>
                          <div class="item-value">{{ ticket.form_data.message || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-calendar</v-icon></template>
                        <div>
                          <div class="item-label">Eingangsdatum</div>
                          <div class="item-value">{{ formatDate(ticket.form_data.submissionDate) }}</div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- INSTALLATION -->
                    <template v-else-if="ticket.form_type === 'installation'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-floor-plan</v-icon></template>
                        <div>
                          <div class="item-label">Fußboden</div>
                          <div class="item-value">{{ ticket.form_data.floor || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-door</v-icon></template>
                        <div>
                          <div class="item-label">Türen</div>
                          <div class="item-value">{{ ticket.form_data.doors || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-elevator</v-icon></template>
                        <div>
                          <div class="item-label">Aufzug vorhanden</div>
                          <div class="item-value">{{ ticket.form_data.elevator ? 'Ja' : 'Nein' }}</div>
                        </div>
                      </v-list-item>
                      <template v-if="ticket.form_data.elevator">
                        <v-list-item>
                          <template #prepend><v-icon>mdi-storefront</v-icon></template>
                          <div>
                            <div class="item-label">Zugang Stockwerk</div>
                            <div class="item-value">{{ ticket.form_data.elevatorAccess || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-expand-horizontal</v-icon></template>
                          <div>
                            <div class="item-label">Türe Maße (m)</div>
                            <div class="item-value">{{ ticket.form_data.elevatorDoorSize || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-expand-vertical</v-icon></template>
                          <div>
                            <div class="item-label">Innentiefe (m)</div>
                            <div class="item-value">{{ ticket.form_data.elevatorInsideSize || '-' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-stairs</v-icon></template>
                        <div>
                          <div class="item-label">Treppe vorhanden</div>
                          <div class="item-value">{{ ticket.form_data.stairs ? 'Ja' : 'Nein' }}</div>
                        </div>
                      </v-list-item>
                      <template v-if="ticket.form_data.stairs">
                        <v-list-item>
                          <template #prepend><v-icon>mdi-ruler</v-icon></template>
                          <div>
                            <div class="item-label">Treppenbreite (cm)</div>
                            <div class="item-value">{{ ticket.form_data.stairWidth || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-rotate-right</v-icon></template>
                          <div>
                            <div class="item-label">Übers Eck</div>
                            <div class="item-value">{{ ticket.form_data.cornerStair ? 'Ja' : 'Nein' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-export</v-icon></template>
                          <div>
                            <div class="item-label">Ausstieg Stockwerk</div>
                            <div class="item-value">{{ ticket.form_data.stairExit || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-numeric</v-icon></template>
                          <div>
                            <div class="item-label">Stufenanzahl</div>
                            <div class="item-value">{{ ticket.form_data.stairSteps || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-arrow-right-bold</v-icon></template>
                          <div>
                            <div class="item-label">Verlauf nach Stufen</div>
                            <div class="item-value">{{ ticket.form_data.stairAfterSteps || '-' }}</div>
                          </div>
                        </v-list-item>
                        <v-list-item>
                          <template #prepend><v-icon>mdi-hand-saw</v-icon></template>
                          <div>
                            <div class="item-label">Geländer abnehmbar</div>
                            <div class="item-value">{{ ticket.form_data.railingRemovable ? 'Ja' : 'Nein' }}</div>
                          </div>
                        </v-list-item>
                      </template>
                    </template>

                    <!-- REPARATUR -->
                    <template v-else-if="ticket.form_type === 'reparatur'">
                      <v-list-item>
                        <template #prepend><v-icon>mdi-tools</v-icon></template>
                        <div>
                          <div class="item-label">Gerät / Produkt</div>
                          <div class="item-value">{{ ticket.form_data.device || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-wrench</v-icon></template>
                        <div>
                          <div class="item-label">Service-Typ</div>
                          <div class="item-value">{{ ticket.form_data.service || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-message-alert</v-icon></template>
                        <div>
                          <div class="item-label">Problembeschreibung</div>
                          <div class="item-value">{{ ticket.form_data.problem || '-' }}</div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-speedometer</v-icon></template>
                        <div>
                          <div class="item-label">Dringlichkeit</div>
                          <div class="item-value">
                            <v-chip :color="getUrgencyColor(ticket.form_data.urgency)" size="small">{{ translateUrgency(ticket.form_data.urgency) }}</v-chip>
                          </div>
                        </div>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-calendar</v-icon></template>
                        <div>
                          <div class="item-label">Eingangsdatum</div>
                          <div class="item-value">{{ formatDate(ticket.form_data.submissionDate) }}</div>
                        </div>
                      </v-list-item>
                    </template>

                    <!-- FALLBACK JSON -->
                    <template v-else>
                      <v-list-item>
                        <template #prepend><v-icon>mdi-code-json</v-icon></template>
                        <div>
                          <div class="item-label">Rohdaten</div>
                          <div class="item-value">
                            <pre class="json-preview">{{ JSON.stringify(ticket.form_data, null, 2) }}</pre>
                          </div>
                        </div>
                      </v-list-item>
                    </template>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- TICKET INFORMATIONEN -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-information-outline</v-icon>
                  Ticket Informationen
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item>
                      <template #prepend><v-icon>mdi-calendar-plus</v-icon></template>
                      <div>
                        <div class="item-label">System-Erstelldatum</div>
                        <div class="item-value">{{ formatDateTime(ticket.created_at) }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-calendar-edit</v-icon></template>
                      <div>
                        <div class="item-label">Aktualisiert</div>
                        <div class="item-value">{{ formatDateTime(ticket.updated_at) }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-account-tie</v-icon></template>
                      <div>
                        <div class="item-label">Bearbeiter</div>
                        <div class="item-value">{{ ticket.assigned_to || '-' }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item>
                      <template #prepend><v-icon>mdi-account-clock</v-icon></template>
                      <div>
                        <div class="item-label">Letzte Änderung</div>
                        <div class="item-value">{{ ticket.last_updated_by || '-' }}</div>
                      </div>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>

              <!-- OPTION 1: Dateianhänge -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-paperclip</v-icon>
                  Anhänge ({{ attachments.length }})
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item v-for="file in attachments" :key="file.id">
                      <template #prepend><v-icon>mdi-file</v-icon></template>
                      <div>
                        <div class="item-label">{{ file.name }}</div>
                        <div class="item-value">{{ formatFileSize(file.size) }}</div>
                      </div>
                      <template #append>
                        <v-btn icon variant="text" @click="downloadAttachment(file)">
                          <v-icon>mdi-download</v-icon>
                        </v-btn>
                      </template>
                    </v-list-item>
                    <v-list-item v-if="!attachments.length">
                      <div class="text-grey text-center py-4">Keine Anhänge vorhanden</div>
                    </v-list-item>
                  </v-list>
                  <v-file-input
                    v-model="newFiles"
                    label="Dateien anhängen"
                    multiple
                    variant="outlined"
                    density="comfortable"
                    class="mt-4"
                  />
                  <v-btn color="primary" block @click="uploadFiles" :loading="uploadingFiles" :disabled="!newFiles?.length">
                    Hochladen
                  </v-btn>
                </v-card-text>
              </v-card>

              <!-- OPTION 2: Interne Notizen -->
              <v-card class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-lock-outline</v-icon>
                  Interne Notizen (nur für Mitarbeiter)
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <v-list class="transparent-list">
                    <v-list-item v-for="note in internalNotes" :key="note.id">
                      <template #prepend><v-icon>mdi-note-text</v-icon></template>
                      <div>
                        <div class="item-label">{{ note.author }} – {{ formatDateTime(note.created_at) }}</div>
                        <div class="item-value">{{ note.text }}</div>
                      </div>
                    </v-list-item>
                    <v-list-item v-if="!internalNotes.length">
                      <div class="text-grey text-center py-2">Keine internen Notizen</div>
                    </v-list-item>
                  </v-list>
                  <v-textarea v-model="newInternalNote" label="Neue interne Notiz" rows="3" variant="outlined" class="mt-3" />
                  <v-btn color="secondary" block @click="addInternalNote" :disabled="!newInternalNote.trim()" class="mt-2">
                    Notiz speichern
                  </v-btn>
                </v-card-text>
              </v-card>

              <!-- Aktivitäten & Kommentare (Timeline) -->
              <v-card class="comment-card glass-effect" :class="{ 'dark-glass': darkMode }">
                <v-card-title class="section-header gradient-bg">
                  <v-icon start color="white">mdi-history</v-icon>
                  Aktivitäten & Kommentare
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <div class="timeline">
                    <transition-group name="timeline-item">
                      <div v-for="(activity, idx) in activities" :key="activity.id" class="timeline-item">
                        <div class="timeline-dot" :style="{ backgroundColor: activity.color }"></div>
                        <div class="timeline-content">
                          <div class="d-flex align-center mb-2">
                            <v-avatar :color="activity.avatarColor" size="36" class="mr-3">
                              <span class="text-caption font-weight-bold">{{ getInitials(activity.author) }}</span>
                            </v-avatar>
                            <div>
                              <div class="font-weight-bold">{{ activity.author }}</div>
                              <div class="text-caption text-grey">{{ formatDateTime(activity.created_at) }}</div>
                            </div>
                          </div>
                          <div class="activity-text">
                            <v-icon v-if="activity.type === 'status'" size="16" class="mr-1">mdi-tune</v-icon>
                            <v-icon v-else-if="activity.type === 'comment'" size="16" class="mr-1">mdi-comment</v-icon>
                            {{ activity.text }}
                          </div>
                        </div>
                      </div>
                    </transition-group>
                  </div>

                  <v-divider class="my-6" />
                  <div class="text-subtitle-1 font-weight-bold mb-4">Neuen Kommentar hinzufügen</div>
                  <v-textarea v-model="newCommentText" label="Kommentar eingeben..." rows="4" variant="outlined" auto-grow />
                  <div class="d-flex justify-end mt-4">
                    <v-btn color="primary" size="large" :disabled="!newCommentText.trim()" @click="addComment">
                      <v-icon start>mdi-send</v-icon> Kommentar speichern
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-container>

        <v-divider />
        <v-card-actions class="pa-5 justify-end">
          <v-btn variant="outlined" size="large" @click="goBack">Zurück</v-btn>
        </v-card-actions>
      </v-card>
    </template>

    <v-alert v-else type="error" variant="tonal">Ticket nicht gefunden.</v-alert>
  </v-container>
</template>



<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// --- Basis Refs ---
const ticket = ref(null)
const loading = ref(true)
const selectedStatus = ref('')
const updatingStatus = ref(false)
const newCommentText = ref('')
const darkMode = ref(false)
const activities = ref([])

// --- Option 1: Dateianhänge ---
const attachments = ref([])
const newFiles = ref([])
const uploadingFiles = ref(false)

// --- Option 2: Interne Notizen ---
const internalNotes = ref([])
const newInternalNote = ref('')

// --- Option 3: Bearbeiter-Zuweisung ---
const selectedAssignee = ref(null)
const assigneeOptions = ref([])
const loadingAssignees = ref(false)
const updatingAssignee = ref(false)

// --- Option 4: Fälligkeitsdatum ---
const dueDate = ref('')
const savingDueDate = ref(false)

// --- Option 6: Kunden-Benachrichtigung ---
const notifyCustomerOnStatus = ref(false)

// --- Option 7: Zeitaufwand ---
const timeEntries = ref([])
const newTimeHours = ref('')
const newTimeDesc = ref('')
const totalHours = computed(() => {
  return timeEntries.value.reduce((sum, e) => sum + parseFloat(e.hours), 0).toFixed(1)
})


const translateServiceType = (service) => {
  const map = { single: 'Einzelbestellung', contract: 'Vertragsbestellung' };
  return map[service] || service || '-';
}

// --- Status Optionen ---
const statusOptions = [
  { label: 'In Bearbeitung', value: 'pending' },
  { label: 'In Prüfung', value: 'in_progress' },
  { label: 'Abgeschlossen', value: 'completed' },
  { label: 'Storniert', value: 'cancelled' }
]

// ========== Hilfsfunktionen & Computeds ==========

// Eingangsdatum / Erstelldatum Header
const entryDateFormatted = computed(() => {
  if (!ticket.value) return ''
  const submissionDate = ticket.value.form_data?.submissionDate
  if (submissionDate) return formatDate(submissionDate)
  return formatDateTime(ticket.value.created_at)
})
const entryDateLabel = computed(() => {
  return ticket.value?.form_data?.submissionDate ? 'Eingangsdatum' : 'Erstellt am'
})

// Priorität
const priorityLabel = computed(() => {
  if (!ticket.value) return ''
  const urgency = ticket.value.form_data?.urgency
  if (urgency === 'hoch') return 'Höchste Priorität'
  if (urgency === 'mittel') return 'Mittlere Priorität'
  return 'Normale Priorität'
})
const priorityColor = computed(() => {
  if (!ticket.value) return 'grey'
  const urgency = ticket.value.form_data?.urgency
  if (urgency === 'hoch') return 'error'
  if (urgency === 'mittel') return 'warning'
  return 'success'
})
const statusColor = computed(() => {
  const map = { pending: 'warning', in_progress: 'info', completed: 'success', cancelled: 'error' }
  return map[ticket.value?.status] || 'grey'
})
const statusIcon = computed(() => {
  const map = { pending: 'mdi-clock-outline', in_progress: 'mdi-progress-clock', completed: 'mdi-check-circle', cancelled: 'mdi-cancel' }
  return map[ticket.value?.status]
})

// Statistiken
const ticketAge = computed(() => {
  if (!ticket.value?.created_at) return '-'
  const created = new Date(ticket.value.created_at)
  const now = new Date()
  const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24))
  if (diffDays === 0) return 'Heute'
  if (diffDays === 1) return '1 Tag'
  return `${diffDays} Tage`
})
const lastActionTime = computed(() => {
  if (!activities.value.length) return '-'
  return formatDateTime(activities.value[0].created_at)
})

// Kundendaten
const customerFields = computed(() => {
  if (!ticket.value?.customer) return []
  const c = ticket.value.customer
  return [
    { label: 'Anrede', value: c.anrede, icon: 'mdi-account' },
    { label: 'Name', value: `${c.firstname} ${c.lastname}`, icon: 'mdi-card-account-details' },
    { label: 'Firma', value: c.company || '-', icon: 'mdi-domain' },
    { label: 'E-Mail', value: c.email, icon: 'mdi-email' },
    { label: 'Telefon', value: c.phone, icon: 'mdi-phone' },
    { label: 'Adresse', value: `${c.address} ${c.hausnummer}`, icon: 'mdi-home' },
    { label: 'Ort', value: `${c.plz} ${c.ort}`, icon: 'mdi-map-marker' }
  ]
})

// Datumsformatierung
const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const translateStatus = (status) => {
  const map = { pending: 'In Bearbeitung', in_progress: 'In Prüfung', completed: 'Abgeschlossen', cancelled: 'Storniert' }
  return map[status] || status
}
const translateUrgency = (urgency) => {
  const map = { hoch: 'Hoch', mittel: 'Mittel', niedrig: 'Niedrig' }
  return map[urgency] || urgency || '-'
}
const getUrgencyColor = (urgency) => {
  const map = { hoch: 'error', mittel: 'warning', niedrig: 'success' }
  return map[urgency] || 'grey'
}
const getInitials = (name) => {
  return name?.split(' ').map(word => word.charAt(0)).join('').slice(0, 2).toUpperCase()
}

// Activity hinzufügen
function addActivity(type, author, text, extra = {}) {
  const newActivity = {
    id: Date.now() + Math.random(),
    type,
    author,
    text,
    created_at: new Date().toISOString(),
    color: type === 'status' ? '#1976d2' : '#2e7d32',
    avatarColor: type === 'status' ? '#1565c0' : '#2c3e50'
  }
  activities.value.unshift(newActivity)
}

// Kommentar
const addComment = async () => {
  if (!newCommentText.value.trim()) return;
  try {
    const response = await fetch('/api/add_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        text: newCommentText.value,
        author: 'Admin'
      })
    });
    const data = await response.json();
    if (data.success) {
      addActivity('comment', 'Admin', newCommentText.value);
      newCommentText.value = '';
    }
  } catch (err) {
    console.error(err);
  }
};

// Status aktualisieren
const updateStatus = async () => {
  if (selectedStatus.value === ticket.value.status) return
  updatingStatus.value = true
  try {
    const response = await fetch('/api/update_ticket_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        status: selectedStatus.value,
        notify_customer: notifyCustomerOnStatus.value
      })
    })
    const data = await response.json()
    if (data.success) {
      const oldStatus = ticket.value.status
      ticket.value.status = selectedStatus.value
      addActivity('status', 'Admin', `Status geändert von "${translateStatus(oldStatus)}" zu "${translateStatus(selectedStatus.value)}"`)
      if (data.notification_sent) {
        addActivity('status', 'System', 'Benachrichtigung an Kunden gesendet')
      }
    } else {
      console.error('Status update failed:', data.error)
    }
  } catch (err) {
    console.error(err)
  } finally {
    updatingStatus.value = false
  }
}

// --- Option 1: Dateianhänge ---
const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
const downloadAttachment = (file) => {
  window.open(`/api/download_attachment.php?id=${file.id}`, '_blank')
}
const uploadFiles = async () => {
  if (!newFiles.value.length) return
  uploadingFiles.value = true
  try {
    const formData = new FormData()
    formData.append('ticket_id', ticket.value.id)
    formData.append('uploaded_by', 'Admin')
    for (let file of newFiles.value) {
      formData.append('files[]', file)
    }
    const response = await fetch('/api/upload_attachment.php', {
      method: 'POST',
      body: formData
    })
    const data = await response.json()
    if (data.success) {
      await loadAttachments()
      addActivity('comment', 'Admin', `${newFiles.value.length} Datei(en) hochgeladen`)
      newFiles.value = []
    } else {
      console.error('Upload failed:', data.error)
    }
  } catch (err) {
    console.error(err)
  } finally {
    uploadingFiles.value = false
  }
}

const loadAttachments = async () => {
  try {
    const response = await fetch(`/api/get_attachments.php?ticket_id=${ticket.value.id}`)
    const data = await response.json()
    if (data.success) attachments.value = data.attachments
  } catch (err) {
    console.error(err)
  }
}

// --- Option 2: Interne Notizen ---
const addInternalNote = async () => {
  if (!newInternalNote.value.trim()) return
  try {
    const response = await fetch('/api/add_internal_note.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        note: newInternalNote.value,
        author: 'Admin'
      })
    })
    const data = await response.json()
    if (data.success) {
      await loadInternalNotes()
      addActivity('status', 'Admin', 'Interne Notiz hinzugefügt')
      newInternalNote.value = ''
    }
  } catch (err) {
    console.error(err)
  }
}
const loadInternalNotes = async () => {
  try {
    const response = await fetch(`/api/get_internal_notes.php?ticket_id=${ticket.value.id}`)
    const data = await response.json()
    if (data.success) internalNotes.value = data.notes
  } catch (err) {
    console.error(err)
  }
}

// --- Option 3: Bearbeiter (Mock, da API funktioniert nun aber wir lassen den Mock) ---
const loadAssignees = async () => {
  loadingAssignees.value = true;
  try {
    const response = await fetch('https://alpha-med-care.com/api/get_users.php');
    const text = await response.text();
    console.log("RAW RESPONSE:", text);
    const data = JSON.parse(text);
    if (data.success) {
      assigneeOptions.value = data.users;
    } else {
      console.error("API ERROR:", data.error);
      assigneeOptions.value = [];
    }
  } catch (err) {
    console.error("LOAD ASSIGNEES ERROR:", err);
    assigneeOptions.value = [];
  } finally {
    loadingAssignees.value = false;
  }
};

const updateAssignee = async () => {
  if (!selectedAssignee.value) {
    alert("Bitte wählen Sie einen Bearbeiter aus.");
    return;
  }
  updatingAssignee.value = true;
  try {
    const user = assigneeOptions.value.find(u => u.id === selectedAssignee.value);
    if (user) {
      ticket.value.assigned_to = user.name;
      addActivity('status', 'Admin', `Bearbeiter geändert zu ${user.name}`);
    } else {
      alert("Benutzer nicht gefunden");
    }
  } catch (err) {
    console.error(err);
    alert("Fehler bei der Zuweisung");
  } finally {
    updatingAssignee.value = false;
  }
};

// --- Option 4: Fälligkeitsdatum ---
const saveDueDate = async () => {
  savingDueDate.value = true
  try {
    const response = await fetch('/api/update_due_date.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        due_date: dueDate.value
      })
    })
    const data = await response.json()
    if (data.success) {
      addActivity('status', 'Admin', `Fälligkeitsdatum gesetzt auf ${formatDate(dueDate.value)}`)
    }
  } catch (err) {
    console.error(err)
  } finally {
    savingDueDate.value = false
  }
}
const isOverdue = computed(() => {
  if (!dueDate.value) return false
  const today = new Date().toISOString().slice(0,10)
  return dueDate.value < today && ticket.value?.status !== 'completed'
})

// --- Option 5: PDF-Export ---
const exportToPDF = () => {
  window.print()
}

// --- Option 7: Zeitaufwand ---
const addTimeEntry = async () => {
  if (!newTimeHours.value) return
  try {
    const response = await fetch('/api/add_time_entry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ticket_id: ticket.value.id,
        hours: parseFloat(newTimeHours.value),
        description: newTimeDesc.value,
        user_name: 'Admin'
      })
    })
    const data = await response.json()
    if (data.success) {
      await loadTimeEntries()
      addActivity('comment', 'Admin', `${newTimeHours.value} h Arbeitszeit erfasst: ${newTimeDesc.value || 'keine Beschreibung'}`)
      newTimeHours.value = ''
      newTimeDesc.value = ''
    }
  } catch (err) {
    console.error(err)
  }
}

const loadTimeEntries = async () => {
  try {
    const response = await fetch(`/api/get_time_entries.php?ticket_id=${ticket.value.id}`)
    const data = await response.json()
    if (data.success) timeEntries.value = data.time_entries
  } catch (err) {
    console.error(err)
  }
}

// --- Laden aller Zusatzdaten ---
const loadExtraData = async () => {
  if (!ticket.value) return;
  await Promise.all([
    loadAttachments(),
    loadInternalNotes(),
    loadTimeEntries(),
    loadAssignees()
  ]);
  if (ticket.value.due_date) dueDate.value = ticket.value.due_date;
  if (ticket.value.assigned_to) {
    const found = assigneeOptions.value.find(a => a.name === ticket.value.assigned_to);
    if (found) selectedAssignee.value = found.id;
  }
};

// --- Hauptladefunktion für Ticket ---
const loadDetails = async () => {
  try {
    const response = await fetch(`/api/get_ticket_details.php?id=${route.params.id}`)
    const data = await response.json()
    if (data.success) {
      ticket.value = data.ticket
      selectedStatus.value = ticket.value.status
      activities.value = []
      if (ticket.value.comments) {
        ticket.value.comments.forEach(comment => {
          addActivity('comment', comment.author, comment.text, { created_at: comment.created_at })
        })
      }
      addActivity('status', 'System', `Ticket erstellt am ${formatDateTime(ticket.value.created_at)}`)
      await loadExtraData()
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// --- Dark Mode ---
const initDarkMode = () => {
  const saved = localStorage.getItem('darkMode')
  if (saved !== null) {
    darkMode.value = saved === 'true'
  } else {
    darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
}
watch(darkMode, (val) => {
  localStorage.setItem('darkMode', val)
})

const goBack = () => {
  router.push('/dashboard')
}

onMounted(() => {
  initDarkMode();
  loadDetails();
  loadAssignees();
});
</script>

<style scoped>
/* === gleiche Styles wie im Original === */
.ticket-container {
  max-width: 1700px;
  margin: auto;
  background: #f5f7fb;
  min-height: 100vh;
  transition: background 0.3s ease;
}
.priority-chip,
.status-chip,
.priority-chip .v-icon,
.status-chip .v-icon {
  color: white !important;
}

.ticket-container.dark-mode {
  background: #0a0f1a;
}
.glass-effect {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.2s;
}
.dark-glass {
  background: rgba(18, 25, 45, 0.85) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.05) !important;
}
.ticket-card {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
}
.toolbar-header {
  min-height: 90px;
}
.gradient-bg {
  background: linear-gradient(135deg, #1e3c72, #0f172a) !important;
  color: white !important;
}
.dark-mode .gradient-bg {
  background: linear-gradient(135deg, #0a1a2f, #03070f) !important;
}
.section-header {
  min-height: 70px;
  font-weight: 700;
}
.info-card, .comment-card {
  border-radius: 10px;
  transition: transform 0.2s ease, box-shadow 0.2s;
}
.info-card:hover, .comment-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
}
.stat-card {
  background: rgba(248, 250, 252, 0.7);
  border-radius: 20px;
  padding: 16px;
  text-align: center;
  backdrop-filter: blur(4px);
}
.dark-stat {
  background: rgba(30, 40, 60, 0.6) !important;
  color: #e2e8f0;
}
.stat-title {
  font-size: 12px;
  color: #64748b;
}
.dark-mode .stat-title {
  color: #94a3b8;
}
.stat-value {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}
.dark-mode .stat-value {
  color: white;
}
.v-list-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.v-list-item:last-child {
  border-bottom: none;
}
.dark-mode .v-list-item {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.transparent-list {
  background: transparent;
}

.item-label {
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
}
.item-value {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.dark-mode .item-label {
  color: #94a3b8;
}
.dark-mode .item-value {
  color: #e2e8f0;
}
.timeline {
  position: relative;
}
.timeline-item {
  position: relative;
  padding-left: 40px;
  margin-bottom: 32px;
  transition: all 0.3s;
}
.timeline-item::before {
  content: '';
  position: absolute;
  left: 16px;
  top: 28px;
  bottom: -32px;
  width: 2px;
  background: linear-gradient(to bottom, #1976d2, #b0bec5);
}
.timeline-item:last-child::before {
  display: none;
}
.timeline-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  position: absolute;
  left: 10px;
  top: 18px;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2);
}
.activity-text {
  background: rgba(248, 250, 252, 0.8);
  border-radius: 18px;
  padding: 14px 18px;
  border-left: 4px solid #1976d2;
}
.dark-mode .activity-text {
  background: rgba(30, 41, 59, 0.7);
  color: #e2e8f0;
}
.timeline-item-enter-active,
.timeline-item-leave-active {
  transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}
.timeline-item-enter-from {
  opacity: 0;
  transform: translateX(40px);
}
.timeline-item-leave-to {
  opacity: 0;
  transform: translateX(-40px);
}
.skeleton-card {
  border-radius: 28px;
  padding: 24px;
}
.floating-avatar {
  transition: transform 0.2s;
}
.floating-avatar:hover {
  transform: scale(1.05);
}
.json-preview {
  background: #0f172a;
  color: #f8fafc;
  padding: 20px;
  border-radius: 16px;
  overflow: auto;
  font-size: 12px;
}
.overdue-field input {
  border-color: #ff5252 !important;
}
@media (max-width: 960px) {
  .ticket-container {
    padding: 0 !important;
  }
  .ticket-card {
    border-radius: 0;
  }
}
</style>