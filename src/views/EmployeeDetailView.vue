<template>
  <v-container fluid class="employee-page pa-3 pa-md-4">

    <!-- LOADING -->
    <v-card v-if="loading" class="modern-card loading-card">
      <v-card-text class="text-center pa-10">
        <v-progress-circular indeterminate color="primary" size="48" width="4" />
        <div class="mt-4 text-subtitle-1 font-weight-medium">
          Mitarbeiter wird geladen...
        </div>
        <div class="text-body-2 text-medium-emphasis mt-1">
          Bitte einen Moment warten
        </div>
      </v-card-text>
    </v-card>

    <!-- ERROR -->
    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      class="modern-card"
      prominent
    >
      <template #prepend>
        <v-icon size="26">mdi-alert-circle-outline</v-icon>
      </template>
      <div class="font-weight-bold mb-1">
        Mitarbeiter konnte nicht geladen werden
      </div>
      <div>{{ error }}</div>
    </v-alert>

    <!-- CONTENT -->
    <template v-else-if="employee">

      <!-- HERO -->
      <v-card class="hero-card mb-3">
        <div class="hero-background"></div>
        <div class="hero-glow"></div>

        <v-card-text class="hero-content pa-5 pa-md-7">

          <div class="hero-header">
            <div>
              <div class="hero-pill">
                <v-icon size="13">mdi-account-badge-outline</v-icon>
                MITARBEITER
              </div>
              <h1 class="hero-title">Mitarbeiterprofil</h1>
              <div class="hero-subtitle">
                Übersicht und Stammdaten des Mitarbeiters
              </div>
            </div>

            <div class="hero-actions">
  <v-btn
    variant="text"
    prepend-icon="mdi-arrow-left"
    class="hero-back-btn"
    @click="router.back()"
  >
    Zurück
  </v-btn>

  <v-btn
    v-if="employee.email"
    variant="tonal"
    prepend-icon="mdi-email-outline"
    class="hero-action-btn"
    @click="mailEmployee"
  >
    E-Mail
  </v-btn>

  <v-btn
  v-if="permissionStore.isAdmin"
  variant="tonal"
  color="error"
  prepend-icon="mdi-delete-outline"
  class="hero-action-btn"
  @click="confirmDelete"
>
  Löschen
</v-btn>

  <v-btn
    size="large"
    variant="flat"
    prepend-icon="mdi-pencil-outline"
    class="edit-button-hero"
    @click="goToEdit"
  >
    Bearbeiten
  </v-btn>
</div>
          </div>

          <v-divider class="hero-divider" />

          <v-row align="center" class="ma-0 hero-body">

            <!-- PHOTO / HIERARCHY -->
            <v-col cols="12" md="auto" class="d-flex justify-center justify-md-start">
              <div class="employee-hierarchy">
                <div class="person-block">
                  <div class="person-label">Mitarbeiter</div>
                  <div class="avatar-wrapper">
                    <v-avatar size="160" class="employee-avatar">
                      <img
                        v-if="employee.photo && !photoError"
                        :src="employee.photo"
                        :alt="fullName"
                        class="employee-photo"
                        @error="photoError = true"
                      />
                      <span v-else class="avatar-initials">{{ initials }}</span>
                    </v-avatar>
                  </div>
                </div>

                <template v-if="employee.vorgesetzter">
                  <div class="hierarchy-line"></div>
                  <div class="person-block supervisor-block">
                    <div class="person-label">Vorgesetzter</div>
                    <v-avatar size="84" class="supervisor-avatar">
                      <img
                        v-if="employee.vorgesetzter_foto && !supervisorPhotoError"
                        :src="employee.vorgesetzter_foto"
                        :alt="employee.vorgesetzter"
                        class="employee-photo"
                        @error="supervisorPhotoError = true"
                      />
                      <v-icon v-else size="40" color="grey-darken-2">mdi-account-tie-outline</v-icon>
                    </v-avatar>
                    <div class="supervisor-name">{{ employee.vorgesetzter }}</div>
                  </div>
                </template>
              </div>
            </v-col>

            <!-- ✅ BLOC INFOS — ALIGNEMENT EN COLONNES -->
            <v-col cols="12" md class="employee-info-column">
              <div class="employee-info-block">

                <!-- Ligne 1 : Statut -->
                <div class="info-row info-row-single">
                  <div class="info-col">
                    <div class="info-col-label">Status</div>
                    <div class="info-col-value">
                      <v-chip
                        :color="employee.is_active ? 'success' : 'error'"
                        variant="flat"
                        size="small"
                        class="status-chip"
                      >
                        <v-icon start size="14">
                          {{ employee.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                        </v-icon>
                        {{ employee.is_active ? 'Aktiv' : 'Inaktiv' }}
                      </v-chip>
                    </div>
                  </div>
                </div>

                <v-divider class="employee-divider" />

                <!-- Ligne 2 : Vorname / Position / Benutzername -->
                <div class="info-row">
                  <div class="info-col">
                    <div class="info-col-label">Vorname</div>
                    <div class="info-col-value">{{ employee.vorname || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Position</div>
                    <div class="info-col-value">{{ employee.position || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Benutzername</div>
                    <div class="info-col-value">{{ employee.username || '—' }}</div>
                  </div>
                </div>

                <v-divider class="employee-divider" />

                <!-- Ligne 3 : Nachname / Vorname / Mitarbeiter-Nr. -->
                <div class="info-row">
                  <div class="info-col">
                    <div class="info-col-label">Nachname</div>
                    <div class="info-col-value">{{ employee.nachname || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Vorname</div>
                    <div class="info-col-value">{{ employee.vorname || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Mitarbeiter-Nr.</div>
                    <div class="info-col-value info-col-mono">
                      {{ employee.mitarbeiter_nummer || '—' }}
                    </div>
                  </div>
                </div>

                <v-divider class="employee-divider" />

                <!-- Ligne 4 : Position / Abteilung / Benutzername -->
                <div class="info-row">
                  <div class="info-col">
                    <div class="info-col-label">Position</div>
                    <div class="info-col-value">{{ employee.position || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Abteilung</div>
                    <div class="info-col-value">{{ employee.abteilung || '—' }}</div>
                  </div>

                  <div class="info-col">
                    <div class="info-col-label">Benutzername</div>
                    <div class="info-col-value">{{ employee.username || '—' }}</div>
                  </div>
                </div>

              </div>
            </v-col>

            <!-- QUICK INFO + KPI -->
            <v-col cols="12" md="4">
              <div class="hero-info-grid">
                <div v-for="info in heroInfo" :key="info.label" class="hero-info-item">
                  <div class="hero-info-icon">
                    <v-icon size="17">{{ info.icon }}</v-icon>
                  </div>
                  <div>
                    <div class="hero-info-label">{{ info.label }}</div>
                    <div class="hero-info-value">{{ info.value }}</div>
                  </div>
                </div>

                <!-- KPI -->
                <div class="kpi-mini-row">
                  <div v-for="kpi in kpis" :key="kpi.label" class="kpi-mini">
                    <div class="kpi-mini-icon">
                      <v-icon size="22">{{ kpi.icon }}</v-icon>
                    </div>
                    <div class="kpi-mini-value">{{ kpi.value }}</div>
                    <div class="kpi-mini-label">{{ kpi.label }}</div>
                  </div>
                </div>
              </div>
            </v-col>

          </v-row>
        </v-card-text>
      </v-card>

      <!-- TABS -->
      <v-tabs v-model="activeTab" class="profile-tabs mb-3" density="comfortable">
        <v-tab value="personal" prepend-icon="mdi-account-details-outline">Persönlich</v-tab>
        <v-tab value="contact" prepend-icon="mdi-home-outline">Kontakt</v-tab>
        <v-tab value="finance" prepend-icon="mdi-bank-outline">Finanzen</v-tab>
        <v-tab value="career" prepend-icon="mdi-school-outline">Karriere</v-tab>
      </v-tabs>

      <!-- TAB CONTENT -->
      <v-window v-model="activeTab">

        <!-- PERSÖNLICH -->
        <v-window-item value="personal">
          <v-row class="ma-0 section-row">

            <v-col cols="12" md="6">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-account-details-outline"
                  color="grey"
                  title="Persönliche Daten"
                  subtitle="Allgemeine Informationen"
                />
                <v-card-text class="pa-3">
                  <div class="contact-fields">
                    <template v-for="(field, i) in personalFields" :key="field.label">
                      <div class="contact-row">
                        <div class="contact-icon"><v-icon size="16">{{ field.icon }}</v-icon></div>
                        <div class="contact-label">{{ field.label }}</div>
                        <div class="contact-value">{{ field.value }}</div>
                      </div>
                      <v-divider v-if="i < personalFields.length - 1" class="item-divider" />
                    </template>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6" v-if="employee.versicherung_gesellschaft">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-shield-check-outline"
                  color="grey"
                  title="Versicherung"
                  subtitle="Versicherungsinformationen"
                />
                <v-card-text class="pa-3">
                  <div class="contact-fields">
                    <template v-for="(field, i) in insuranceFields" :key="field.label">
                      <div class="contact-row">
                        <div class="contact-icon"><v-icon size="16">{{ field.icon }}</v-icon></div>
                        <div class="contact-label">{{ field.label }}</div>
                        <div class="contact-value">{{ field.value || '—' }}</div>
                      </div>
                      <v-divider v-if="i < insuranceFields.length - 1" class="item-divider" />
                    </template>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

          </v-row>
        </v-window-item>

        <!-- KONTAKT -->
        <v-window-item value="contact">
          <v-row class="ma-0 section-row">

            <v-col cols="12" md="6" lg="5">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-card-account-phone-outline"
                  color="grey"
                  title="Kontaktdaten"
                  subtitle="Erreichbarkeit"
                />
                <v-card-text class="pa-3">
                  <div class="contact-fields">
                    <template v-for="(field, i) in contactFields" :key="field.label">
                      <div class="contact-row">
                        <div class="contact-icon"><v-icon size="16">{{ field.icon }}</v-icon></div>
                        <div class="contact-label">{{ field.label }}</div>
                        <div class="contact-value">{{ field.value || '—' }}</div>
                      </div>
                      <v-divider v-if="i < contactFields.length - 1" class="item-divider" />
                    </template>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="7">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-map-marker-outline"
                  color="grey"
                  title="Anschriften"
                  :subtitle="employee.addresses?.length ? `${employee.addresses.length} hinterlegt` : 'Keine Adresse hinterlegt'"
                />
                <v-card-text class="pa-3">
                  <div v-if="employee.addresses?.length" class="address-list">
                    <template v-for="(addr, i) in employee.addresses" :key="addr.id">
                      <div class="address-item">
                        <div class="d-flex align-center mb-2">
                          <v-chip size="x-small" variant="flat" class="chip-grey">
                            {{ addr.adresstyp }}
                          </v-chip>
                        </div>
                        <div class="address-main">{{ addr.strasse }} {{ addr.hausnummer }}</div>
                        <div class="address-city">{{ addr.plz }} {{ addr.stadt }}</div>
                        <div class="address-country">{{ addr.land }}</div>
                      </div>
                      <v-divider v-if="i < employee.addresses.length - 1" class="item-divider" />
                    </template>
                  </div>
                  <div v-else class="empty-state">
                    <v-icon size="36">mdi-map-marker-off-outline</v-icon>
                    <div class="mt-2">Keine Adresse hinterlegt</div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

          </v-row>
        </v-window-item>

        <!-- FINANZEN -->
        <v-window-item value="finance">
          <v-row class="ma-0 section-row">

            <v-col cols="12" md="6" lg="5">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-cash-multiple"
                  color="grey"
                  title="Vergütung"
                  subtitle="Steuer & Soziales"
                />
                <v-card-text class="pa-3">
                  <div class="contact-fields">
                    <div class="contact-row">
                      <div class="contact-icon"><v-icon size="16">mdi-percent-outline</v-icon></div>
                      <div class="contact-label">Steuerklasse</div>
                      <div class="contact-value">{{ employee.steuerklasse || '—' }}</div>
                    </div>
                    <v-divider class="item-divider" />
                    <div class="contact-row">
                      <div class="contact-icon"><v-icon size="16">mdi-church</v-icon></div>
                      <div class="contact-label">Konfession</div>
                      <div class="contact-value">{{ employee.konfession || '—' }}</div>
                    </div>
                    <v-divider class="item-divider" />
                    <div class="contact-row">
                      <div class="contact-icon"><v-icon size="16">mdi-shield-outline</v-icon></div>
                      <div class="contact-label">Sozialversicherung</div>
                      <div class="contact-value">{{ employee.versicherung_nummer || '—' }}</div>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="7">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-bank-outline"
                  color="grey"
                  title="Bankverbindungen"
                  :subtitle="employee.bank_accounts?.length ? `${employee.bank_accounts.length} Konto(en)` : 'Keine Bankverbindung'"
                />
                <v-card-text class="pa-3">
                  <div v-if="employee.bank_accounts?.length" class="bank-list">
                    <template v-for="(bank, i) in employee.bank_accounts" :key="bank.id">
                      <div class="bank-card">
                        <div class="bank-top">
                          <div class="bank-logo"><v-icon size="22">mdi-bank</v-icon></div>
                          <div class="flex-grow-1 ml-2">
                            <div class="bank-owner">{{ bank.kontoinhaber }}</div>
                            <div class="bank-name">{{ bank.bankname || 'Bankverbindung' }}</div>
                          </div>
                          <v-chip size="x-small" variant="flat" class="chip-grey">
                            {{ bank.ist_aktiv ? 'Aktiv' : 'Inaktiv' }}
                          </v-chip>
                        </div>
                        <v-divider class="my-3" />
                        <div class="contact-fields">
                          <div class="contact-row">
                            <div class="contact-icon"><v-icon size="16">mdi-account-outline</v-icon></div>
                            <div class="contact-label">Kontoinhaber</div>
                            <div class="contact-value">{{ bank.kontoinhaber || '—' }}</div>
                          </div>
                          <v-divider class="item-divider" />
                          <div class="contact-row">
                            <div class="contact-icon"><v-icon size="16">mdi-identifier</v-icon></div>
                            <div class="contact-label">IBAN</div>
                            <div class="contact-value">{{ bank.iban }}</div>
                          </div>
                          <template v-if="bank.bic">
                            <v-divider class="item-divider" />
                            <div class="contact-row">
                              <div class="contact-icon"><v-icon size="16">mdi-bank-outline</v-icon></div>
                              <div class="contact-label">BIC</div>
                              <div class="contact-value">{{ bank.bic }}</div>
                            </div>
                          </template>
                        </div>
                      </div>
                      <v-divider v-if="i < employee.bank_accounts.length - 1" class="item-divider" />
                    </template>
                  </div>
                  <div v-else class="empty-state">
                    <v-icon size="36">mdi-bank-off-outline</v-icon>
                    <div class="mt-2">Keine Bankverbindung hinterlegt</div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

          </v-row>
        </v-window-item>

        <!-- KARRIERE -->
        <v-window-item value="career">
          <v-row class="ma-0 section-row">

            <v-col cols="12" md="6" v-if="employee.qualifications?.length">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-school-outline"
                  color="grey"
                  title="Qualifikationen"
                  :subtitle="`${employee.qualifications.length} Einträge`"
                />
                <v-card-text class="pa-3">
                  <div class="qualification-list">
                    <template v-for="(q, i) in employee.qualifications" :key="q.id">
                      <div class="qualification-card">
                        <div class="qualification-icon"><v-icon size="20">mdi-certificate-outline</v-icon></div>
                        <div class="qualification-content">
                          <div class="qualification-title">{{ q.bezeichnung }}</div>
                          <div class="qualification-institution">{{ q.institution || 'Keine Institution angegeben' }}</div>
                          <div class="qualification-meta">
                            <span v-if="q.abschlussdatum">
                              <v-icon size="14">mdi-calendar-outline</v-icon>
                              {{ formatDate(q.abschlussdatum) }}
                            </span>
                            <span v-if="q.note">
                              <v-icon size="14">mdi-star-outline</v-icon>
                              Note: {{ q.note }}
                            </span>
                          </div>
                        </div>
                      </div>
                      <v-divider v-if="i < employee.qualifications.length - 1" class="item-divider" />
                    </template>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6" v-if="employee.documents?.length">
              <v-card class="modern-card h-100">
                <EmployeeSectionHeader
                  icon="mdi-file-document-outline"
                  color="grey"
                  title="Dokumente"
                  :subtitle="`${employee.documents.length} Dateien`"
                />
                <v-card-text class="pa-3">
                  <div class="document-list">
                    <template v-for="(doc, i) in employee.documents" :key="doc.id">
                      <a
                        :href="doc.datei_pfad || undefined"
                        :target="doc.datei_pfad ? '_blank' : undefined"
                        class="document-item"
                      >
                        <div class="document-icon"><v-icon size="18">mdi-file-document-outline</v-icon></div>
                        <div class="document-content">
                          <div class="document-name">{{ doc.name }}</div>
                          <div class="document-meta">
                            <span>{{ doc.typ }}</span>
                            <span v-if="doc.gueltig_bis">
                              <v-icon size="13">mdi-calendar-clock-outline</v-icon>
                              gültig bis {{ formatDate(doc.gueltig_bis) }}
                            </span>
                          </div>
                        </div>
                        <v-icon v-if="doc.datei_pfad" size="18" class="document-arrow">mdi-open-in-new</v-icon>
                      </a>
                      <v-divider v-if="i < employee.documents.length - 1" class="item-divider" />
                    </template>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

          </v-row>
        </v-window-item>

      </v-window>

      <!-- BOTTOM ACTION -->
      <div class="bottom-actions mt-3">
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.back()">
          Zurück
        </v-btn>

        <div class="d-flex ga-2 flex-wrap">
         <v-btn
  v-if="permissionStore.isAdmin"
  variant="tonal"
  color="error"
  prepend-icon="mdi-delete-outline"
  :loading="deleting"
  @click="confirmDelete"
>
  Mitarbeiter löschen
</v-btn>

          <v-btn
            variant="flat"
            prepend-icon="mdi-pencil-outline"
            class="edit-button-hero"
            @click="goToEdit"
          >
            Mitarbeiter bearbeiten
          </v-btn>
        </div>
      </div>

      <!-- DELETE CONFIRM DIALOG -->
      <v-dialog v-model="deleteDialog" max-width="480" persistent>
        <v-card class="rounded-xl">
          <v-card-title class="d-flex align-center pa-5">
            <v-avatar color="error" size="42" class="mr-3">
              <v-icon>mdi-alert-circle-outline</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold">Mitarbeiter löschen?</div>
              <div class="text-caption text-medium-emphasis">
                Diese Aktion kann nicht rückgängig gemacht werden.
              </div>
            </div>
          </v-card-title>

          <v-card-text class="pa-5 pt-2">
            <p class="mb-2">
              Du bist dabei, folgenden Mitarbeiter <strong>endgültig zu löschen</strong>:
            </p>
            <v-alert type="warning" variant="tonal" density="compact" class="mb-2">
              <div class="font-weight-bold">{{ fullName }}</div>
              <div class="text-caption">
                {{ employee?.mitarbeiter_nummer || '–' }}
                · {{ employee?.email || 'Keine E-Mail' }}
              </div>
            </v-alert>
            <p class="text-body-2 text-error">
              Alle zugehörigen Daten (Adressen, Bankkonten, Qualifikationen,
              Dokumente, Versicherung) sowie das Benutzerkonto und die
              hochgeladenen Dateien werden gelöscht.
            </p>
          </v-card-text>

          <v-card-actions class="pa-5 pt-0">
            <v-spacer />
            <v-btn
              variant="text"
              :disabled="deleting"
              @click="deleteDialog = false"
            >
              Abbrechen
            </v-btn>
            <v-btn
              color="error"
              variant="flat"
              prepend-icon="mdi-delete"
              :loading="deleting"
              @click="deleteEmployee"
            >
              Endgültig löschen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

    </template>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmployeeSectionHeader from './EmployeeSectionHeader.vue'
import { usePermissionStore } from '@/stores/permission'   // ✅ AJOUT

const route = useRoute()
const router = useRouter()
const permissionStore = usePermissionStore()

const employee = ref(null)
const loading = ref(true)
const error = ref(null)
const photoError = ref(false)
const supervisorPhotoError = ref(false)

const activeTab = ref('personal')

// Delete state
const deleteDialog = ref(false)
const deleting = ref(false)



/* ============================================================
   COMPUTED
============================================================ */

const fullName = computed(() =>
  employee.value ? `${employee.value.vorname} ${employee.value.nachname}` : ''
)

const initials = computed(() => {
  const e = employee.value
  if (!e) return '—'
  return `${e.vorname?.[0] || ''}${e.nachname?.[0] || ''}`.toUpperCase()
})

const heroInfo = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    { icon: 'mdi-calendar-account', label: 'Eintritt', value: formatDate(e.einstellungsdatum) },
    { icon: 'mdi-calendar-remove-outline', label: 'Austritt', value: formatDate(e.austrittsdatum) },
    { icon: 'mdi-clock-outline', label: 'Arbeitszeit', value: e.wochenarbeitszeit ? `${e.wochenarbeitszeit} h / Woche` : '—' },
    { icon: 'mdi-account-supervisor-outline', label: 'Vorgesetzter', value: e.vorgesetzter || '—' }
  ]
})

const kpis = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    { icon: 'mdi-map-marker-multiple-outline', label: 'Adressen', value: e.addresses?.length || 0 },
    { icon: 'mdi-bank-outline', label: 'Bankkonten', value: e.bank_accounts?.length || 0 },
    { icon: 'mdi-school-outline', label: 'Qualifikationen', value: e.qualifications?.length || 0 },
    { icon: 'mdi-file-document-outline', label: 'Dokumente', value: e.documents?.length || 0 }
  ]
})

const personalFields = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    { icon: 'mdi-office-building-outline', label: 'Abteilung', value: e.abteilung || '—' },
    { icon: 'mdi-badge-account-outline', label: 'Position', value: e.position || '—' },
    { icon: 'mdi-cake-variant', label: 'Geburtsdatum', value: formatDate(e.geburtsdatum) },
    { icon: 'mdi-calendar-account', label: 'Einstellungsdatum', value: formatDate(e.einstellungsdatum) },
    { icon: 'mdi-handshake-outline', label: 'Vertragsart', value: e.vertragsart || '—' },
    { icon: 'mdi-timer-outline', label: 'Wochenarbeitszeit', value: e.wochenarbeitszeit ? `${e.wochenarbeitszeit} h` : '—' }
  ]
})

const contactFields = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    { icon: 'mdi-email-outline', label: 'E-Mail', value: e.email },
    { icon: 'mdi-phone-outline', label: 'Telefon', value: e.telefon },
    { icon: 'mdi-cellphone', label: 'Mobil', value: e.mobil },
    { icon: 'mdi-account-outline', label: 'Benutzername', value: e.username }
  ]
})

const insuranceFields = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    { icon: 'mdi-shield-outline', label: 'Typ', value: e.versicherung_typ },
    { icon: 'mdi-office-building-outline', label: 'Gesellschaft', value: e.versicherung_gesellschaft },
    { icon: 'mdi-identifier', label: 'Versicherungsnummer', value: e.versicherung_nummer }
  ]
})

/* ============================================================
   HELPERS
============================================================ */

const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('de-DE', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    })
  } catch {
    return date
  }
}

/* ============================================================
   LOAD EMPLOYEE
============================================================ */

const API_BASE = (
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_BASE ||
  '/api'
).replace(/\/+$/, '')

const loadEmployee = async () => {
  loading.value = true
  error.value = null

  try {
    const identifier = String(route.params.uid || route.params.id || '')

    if (!identifier) {
      throw new Error('Die Mitarbeiter-UID fehlt in der URL.')
    }

    const isUuid = /^[0-9a-f-]{36}$/i.test(identifier)
    const param = isUuid ? 'uid' : 'id'

    const url =
      `${API_BASE}/employees_get.php` +
      `?${param}=${encodeURIComponent(identifier)}` +
      `&diagnostic=${Date.now()}`

    const response = await fetch(url, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'omit',
      cache: 'no-store'
    })

    const contentType = response.headers.get('content-type') || ''
    const raw = await response.text()

    if (!contentType.includes('application/json')) {
      throw new Error(
        'Der Server liefert HTML statt JSON. Bitte API-Proxy prüfen.'
      )
    }

    let data
    try {
      data = JSON.parse(raw)
    } catch {
      throw new Error('Die Serverantwort ist kein gültiges JSON.')
    }

    if (!response.ok || data.success !== true) {
      throw new Error(
        data.error || `Mitarbeiter konnte nicht geladen werden (HTTP ${response.status}).`
      )
    }

    if (!data.employee || typeof data.employee !== 'object') {
      throw new Error('Die API hat keine Mitarbeiterdaten zurückgegeben.')
    }

    const emp = data.employee

    if (emp.foto_pfad && !emp.photo) {
      emp.photo = emp.foto_pfad
    }

    if (emp.vorgesetzter_photo && !emp.vorgesetzter_foto) {
      emp.vorgesetzter_foto = emp.vorgesetzter_photo
    }

    employee.value = emp
    photoError.value = false
    supervisorPhotoError.value = false
  } catch (e) {
    console.error('[Employee details] Fehler:', e)
    error.value = e.message || 'Fehler beim Laden des Mitarbeiters.'
  } finally {
    loading.value = false
  }
}

/* ============================================================
   ACTIONS
============================================================ */

const goToEdit = async () => {
  const uid =
    employee.value?.employee_uid ||
    employee.value?.uid ||
    route.params.uid ||
    route.params.id

  if (!uid) {
    console.error('[goToEdit] UID introuvable', employee.value)
    error.value = 'Die Mitarbeiter-UID wurde nicht gefunden.'
    return
  }

  const target = `/employees/edit/${encodeURIComponent(String(uid))}`

  try {
    await router.push(target)
  } catch (err) {
    console.error('[goToEdit] Navigation fehlgeschlagen:', err)
  }
}

const mailEmployee = () => {
  if (employee.value?.email) window.location.href = `mailto:${employee.value.email}`
}

/* ============================================================
   DELETE EMPLOYEE
============================================================ */

const confirmDelete = () => {
  // ✅ Admin uniquement
  if (!permissionStore.isAdmin) {
    permissionStore.showAccessDenied()
    return
  }
  deleteDialog.value = true
}

const deleteEmployee = async () => {
  // ✅ Guard final
  if (!permissionStore.isAdmin) {
    permissionStore.showAccessDenied()
    deleteDialog.value = false
    return
  }

  const uid =
    employee.value?.employee_uid ||
    employee.value?.uid ||
    route.params.uid ||
    route.params.id

  if (!uid) {
    error.value = 'UID introuvable – Löschen nicht möglich.'
    return
  }

  deleting.value = true

  try {
    const url = `${API_BASE}/employees_delete.php?uid=${encodeURIComponent(uid)}`

    const response = await fetch(url, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        ...(localStorage.getItem('token')
          ? { Authorization: `Bearer ${localStorage.getItem('token')}` }
          : {})
      },
      credentials: 'include',
      cache: 'no-store'
    })

    // ✅ 403 → dialogue Access Denied
    if (response.status === 403) {
      permissionStore.showAccessDenied()
      deleteDialog.value = false
      return
    }

    const raw = await response.text()
    let data = null
    try { data = JSON.parse(raw) } catch { /* ignore */ }

    if (!response.ok || !data || data.success !== true) {
      throw new Error(data?.error || `HTTP ${response.status}`)
    }

    deleteDialog.value = false
    await router.push('/employees')
  } catch (err) {
    console.error('[DeleteEmployee]', err)
    error.value = `Löschen fehlgeschlagen: ${err.message}`
  } finally {
    deleting.value = false
  }
}

onMounted(loadEmployee)
</script>

<style scoped>

/* ============================================================
   VARIABLES
============================================================ */

.employee-page {
  --text-main: #0f172a;
  --text-soft: #334155;
  --text-muted: #64748b;
  --text-faint: #94a3b8;

  --grey-icon: #475569;
  --grey-icon-bg: #f1f5f9;

  --border: #e5e9f0;
  --border-strong: #d5dbe4;

  --surface: #ffffff;
  --background: #f6f8fb;

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;

  --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
  --shadow-md: 0 4px 14px rgba(15, 23, 42, 0.06);

  max-width: 1600px;
  margin: 0 auto;
  background: var(--background);
  min-height: 100vh;
  padding-top: 12px !important;
  padding-bottom: 12px !important;
}

/* ============================================================
   ESPACEMENTS
============================================================ */

.employee-page .v-row {
  margin: 0 !important;
  row-gap: 10px !important;
}

.employee-page .v-col {
  padding: 5px !important;
}

.employee-page .section-row {
  margin: 0 !important;
  row-gap: 10px !important;
}

.hero-card { margin-bottom: 4px !important; }

.bottom-actions {
  margin-top: 4px !important;
  padding: 8px 2px 4px !important;
}

/* ============================================================
   HERO HEADER
============================================================ */

.hero-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.hero-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-title {
  margin: 10px 0 0;
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 750;
  letter-spacing: -0.03em;
  color: var(--text-main);
  line-height: 1.15;
}

.hero-subtitle {
  margin-top: 6px;
  font-size: 0.88rem;
  color: var(--text-muted);
}

.hero-divider {
  margin: 18px 0 22px;
  border-color: var(--border) !important;
  opacity: 1;
}

/* ============================================================
   HERO ACTIONS
============================================================ */

.hero-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.hero-action-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
}

.hero-back-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
  color: var(--text-soft) !important;
}

.hero-back-btn:hover {
  background: #f1f5f9 !important;
}

.edit-button-hero {
  min-width: 150px;
  height: 44px !important;
  border-radius: var(--radius-md) !important;
  text-transform: none;
  font-weight: 600;
  color: #ffffff !important;
  background: #1e293b !important;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.edit-button-hero:hover {
  transform: translateY(-1px);
  background: #0f172a !important;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.28) !important;
}

/* ============================================================
   GENERAL CARD
============================================================ */

.modern-card {
  border: 1px solid var(--border) !important;
  border-radius: var(--radius-lg) !important;
  background: var(--surface) !important;
  box-shadow: var(--shadow-sm) !important;
  overflow: hidden;
  transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

.modern-card:hover {
  box-shadow: var(--shadow-md) !important;
  border-color: var(--border-strong) !important;
}

/* ============================================================
   HERO — FOND BLANC
============================================================ */

.hero-card {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-lg) !important;
  color: var(--text-main);
  background: linear-gradient(135deg, #ffffff 0%, #fbfcfe 55%, #f4f7fb 100%) !important;
  border: 1px solid var(--border) !important;
  box-shadow: var(--shadow-md) !important;
}

.hero-background {
  position: absolute;
  inset: 0;
  z-index: 0;
  opacity: 0.7;
  pointer-events: none;
  background-image:
    radial-gradient(circle at 88% 18%, rgba(15, 23, 42, 0.04) 0, transparent 38%),
    radial-gradient(circle at 8% 100%, rgba(15, 23, 42, 0.03) 0, transparent 34%);
}

.hero-glow {
  position: absolute;
  top: -60%;
  right: -20%;
  width: 60%;
  height: 220%;
  z-index: 0;
  pointer-events: none;
  background: radial-gradient(circle, rgba(15, 23, 42, 0.05) 0%, transparent 60%);
  filter: blur(30px);
}

.hero-content {
  position: relative;
  z-index: 1;
}

/* ============================================================
   HIERARCHY — AVATAR AGRANDI
============================================================ */

.employee-hierarchy {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 180px;
}

.person-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.person-label {
  margin-bottom: 6px;
  color: var(--text-faint);
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.avatar-wrapper {
  position: relative;
  width: 176px;
  height: 176px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.employee-avatar {
  background: #f1f5f9;
  border: 3px solid #ffffff;
  border-radius: 0 !important;
  box-shadow: 0 0 0 1px var(--border), 0 12px 28px rgba(15, 23, 42, 0.12);
  overflow: hidden;
}

.employee-avatar :deep(.v-avatar__content),
.employee-avatar :deep(img) {
  border-radius: 0 !important;
}

.employee-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 0 !important;
  display: block;
}

.avatar-initials {
  font-size: 2.6rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: -0.02em;
}

.hierarchy-line {
  position: relative;
  width: 2px;
  height: 28px;
  margin: 4px 0;
  border-radius: 999px;
  background: linear-gradient(to bottom, var(--border-strong), transparent);
}

.hierarchy-line::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  width: 8px;
  height: 8px;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  background: #cbd5e1;
  box-shadow: 0 0 0 4px #ffffff;
}

.supervisor-block { padding-top: 2px; }

.supervisor-avatar {
  overflow: hidden;
  background: #f1f5f9;
  border: 2px solid #ffffff;
  border-radius: 0 !important;
  box-shadow: 0 0 0 1px var(--border), 0 8px 18px rgba(15, 23, 42, 0.08);
}

.supervisor-avatar :deep(.v-avatar__content),
.supervisor-avatar :deep(img) {
  border-radius: 0 !important;
}

.supervisor-name {
  max-width: 180px;
  margin-top: 6px;
  color: var(--text-main);
  font-size: 0.82rem;
  font-weight: 650;
  line-height: 1.25;
  text-align: center;
}

/* ============================================================
   EMPLOYEE INFO — ALIGNEMENT EN COLONNES
============================================================ */

.employee-info-column {
  display: flex;
  align-items: center;
}

.employee-info-block {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.info-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 6px 16px;
  padding: 6px 0;
  width: 100%;
  align-items: start;
}

.info-row-single {
  grid-template-columns: 1fr;
}

.info-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.info-col-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-faint);
  line-height: 1.2;
}

.info-col-value {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-main);
  letter-spacing: -0.01em;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.3;
}

.info-col-mono {
  font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
  font-size: 0.86rem;
  letter-spacing: 0.02em;
}

.employee-divider {
  margin: 0 !important;
  border-color: var(--border) !important;
  opacity: 1;
  width: 100%;
}

.status-chip {
  font-weight: 700;
  color: white !important;
  letter-spacing: 0.02em;
}

/* ============================================================
   HERO INFO GRID
============================================================ */

.hero-info-grid {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.hero-info-item {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  background: #ffffff;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.hero-info-item:hover {
  transform: translateX(3px);
  box-shadow: var(--shadow-md);
  border-color: var(--border-strong);
}

.hero-info-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  margin-right: 10px;
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
}

.hero-info-label {
  font-size: 0.68rem;
  color: var(--text-faint);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 600;
}

.hero-info-value {
  margin-top: 2px;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text-main);
}

/* ============================================================
   KPI MINI
============================================================ */

.kpi-mini-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  margin-top: 6px;
  width: 100%;
  box-sizing: border-box;
}

.kpi-mini {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 10px 4px;
  border-radius: var(--radius-md);
  background: #ffffff;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  text-align: center;
  min-width: 0;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.kpi-mini:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: var(--border-strong);
}

.kpi-mini-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
  flex-shrink: 0;
}

.kpi-mini-icon .v-icon {
  font-size: 20px !important;
}

.kpi-mini-value {
  font-size: 1.1rem;
  font-weight: 750;
  color: var(--text-main);
  letter-spacing: -0.02em;
  line-height: 1;
}

.kpi-mini-label {
  font-size: 0.66rem;
  color: var(--text-muted);
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

/* ============================================================
   TABS
============================================================ */

.profile-tabs {
  border-radius: var(--radius-lg);
  background: #ffffff;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  padding: 4px;
}

.profile-tabs :deep(.v-tab) {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0;
  border-radius: var(--radius-md);
  min-height: 42px;
  color: #64748b;
}

.profile-tabs :deep(.v-tab--selected) {
  background: #f1f5f9 !important;
  color: #0f172a !important;
}

.profile-tabs :deep(.v-tab__slider) {
  display: none;
}

/* ============================================================
   LIGNES DE SÉPARATION
============================================================ */

.item-divider {
  margin: 2px 0 !important;
  border-color: var(--border) !important;
  opacity: 1 !important;
}

/* ============================================================
   CONTACT FIELDS
============================================================ */

.contact-fields {
  display: flex;
  flex-direction: column;
}

.contact-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 8px;
  border-radius: var(--radius-sm);
  transition: background 0.18s ease;
}

.contact-row:hover {
  background: #fafbfd;
}

.contact-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
  font-size: 15px;
}

.contact-label {
  flex: 0 0 110px;
  color: var(--text-muted);
  font-size: 0.72rem;
  font-weight: 600;
}

.contact-value {
  flex: 1;
  min-width: 0;
  color: var(--text-main);
  font-size: 0.82rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bank-card .contact-value {
  white-space: normal;
  word-break: break-all;
  font-variant-numeric: tabular-nums;
}

/* ============================================================
   CHIPS GRIS
============================================================ */

.chip-grey {
  background: #f1f5f9 !important;
  color: #334155 !important;
  font-weight: 700;
}

/* ============================================================
   ADDRESS
============================================================ */

.address-list { display: flex; flex-direction: column; }

.address-item {
  padding: 10px 4px;
  background: transparent;
  border: none;
  border-radius: 0;
  transition: background 0.2s ease;
}

.address-item:hover { background: #fafbfd; }

.address-main {
  color: var(--text-main);
  font-size: 0.86rem;
  font-weight: 700;
}

.address-city {
  margin-top: 2px;
  color: var(--text-soft);
  font-size: 0.82rem;
}

.address-country {
  margin-top: 1px;
  color: var(--text-muted);
  font-size: 0.75rem;
}

.empty-state {
  min-height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-faint);
  font-size: 0.85rem;
}

/* ============================================================
   BANK
============================================================ */

.bank-list { display: flex; flex-direction: column; }

.bank-card {
  padding: 10px 4px;
  background: transparent;
  border: none;
  border-radius: 0;
  transition: background 0.2s ease;
}

.bank-card:hover { background: #fafbfd; }

.bank-top { display: flex; align-items: center; }

.bank-logo {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
}

.bank-owner {
  color: var(--text-main);
  font-size: 0.88rem;
  font-weight: 700;
}

.bank-name {
  margin-top: 1px;
  color: var(--text-muted);
  font-size: 0.74rem;
}

/* ============================================================
   QUALIFICATIONS
============================================================ */

.qualification-list { display: flex; flex-direction: column; }

.qualification-card {
  display: flex;
  padding: 10px 4px;
  background: transparent;
  border: none;
  border-radius: 0;
  transition: background 0.2s ease;
}

.qualification-card:hover { background: #fafbfd; }

.qualification-icon {
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
}

.qualification-content { min-width: 0; margin-left: 12px; }

.qualification-title {
  color: var(--text-main);
  font-size: 0.86rem;
  font-weight: 700;
}

.qualification-institution {
  margin-top: 2px;
  color: var(--text-muted);
  font-size: 0.75rem;
}

.qualification-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 0.72rem;
}

.qualification-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* ============================================================
   DOCUMENTS
============================================================ */

.document-list { display: flex; flex-direction: column; }

.document-item {
  display: flex;
  align-items: center;
  padding: 10px 4px;
  background: transparent;
  border: none;
  border-radius: 0;
  color: inherit;
  text-decoration: none;
  transition: background 0.2s ease;
}

.document-item:hover { background: #fafbfd; }

.document-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  color: var(--grey-icon) !important;
  background: var(--grey-icon-bg) !important;
}

.document-content { min-width: 0; margin-left: 12px; }

.document-name {
  color: var(--text-main);
  font-size: 0.84rem;
  font-weight: 700;
}

.document-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 3px;
  color: var(--text-muted);
  font-size: 0.72rem;
}

.document-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.document-arrow {
  margin-left: auto;
  color: var(--text-faint);
  transition: color 0.2s ease, transform 0.2s ease;
}

.document-item:hover .document-arrow {
  color: var(--text-soft);
  transform: translate(2px, -2px);
}

/* ============================================================
   BOTTOM ACTIONS
============================================================ */

.bottom-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 2px 14px;
}

.bottom-actions .v-btn {
  text-transform: none;
  font-weight: 600;
  border-radius: var(--radius-md) !important;
}

/* ============================================================
   LOADING
============================================================ */

.loading-card {
  max-width: 620px;
  margin: 60px auto;
  border-radius: var(--radius-lg) !important;
}

/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 960px) {
  .hero-info-grid { margin-top: 8px; }

  .employee-info-column {
    justify-content: flex-start;
    align-items: flex-start;
  }

  .info-row {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px 12px;
  }

  .info-row-single {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .employee-page { padding: 8px !important; }
  .employee-page .v-col { padding: 4px !important; }
  .employee-page .v-row { row-gap: 8px !important; }

  .hero-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .hero-actions { width: 100%; }
  .hero-actions .v-btn { flex: 1; }
  .edit-button-hero { width: 100%; }

  .hero-card { border-radius: var(--radius-md) !important; }
  .hero-content { padding: 18px 14px !important; }

  .employee-hierarchy { min-width: 150px; }
  .avatar-wrapper { width: 144px; height: 144px; }
  .employee-avatar { width: 132px !important; height: 132px !important; }
  .hierarchy-line { height: 22px; }
  .supervisor-avatar { width: 72px !important; height: 72px !important; }

  .info-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px 10px;
    padding: 6px 0;
  }

  .info-col-label { font-size: 0.62rem; }
  .info-col-value { font-size: 0.85rem; }

  .kpi-mini-row { gap: 6px; margin-top: 6px; }
  .kpi-mini { padding: 8px 4px; gap: 4px; }
  .kpi-mini-icon { width: 28px; height: 28px; }
  .kpi-mini-icon .v-icon { font-size: 16px !important; }
  .kpi-mini-value { font-size: 1rem; }
  .kpi-mini-label { font-size: 0.6rem; letter-spacing: 0; }

  .modern-card { border-radius: var(--radius-md) !important; }

  .profile-tabs :deep(.v-tab) {
    min-width: auto;
    padding: 0 10px;
    font-size: 0.78rem;
  }

  .contact-label { flex: 0 0 95px; font-size: 0.7rem; }
  .contact-value { font-size: 0.8rem; }
  .contact-row { padding: 6px 6px; }

  .bottom-actions {
    flex-direction: column-reverse;
    gap: 8px;
  }
  .bottom-actions .v-btn { width: 100%; }
}

@media (max-width: 400px) {
  .kpi-mini-row { gap: 4px; }
  .kpi-mini { padding: 8px 2px; }
  .kpi-mini-icon { width: 26px; height: 26px; }
  .kpi-mini-icon .v-icon { font-size: 16px !important; }
  .kpi-mini-value { font-size: 0.9rem; }
  .kpi-mini-label { font-size: 0.54rem; }

  .info-row {
    grid-template-columns: 1fr;
    gap: 6px;
  }
}

</style>