<template>

  <v-container fluid class="employee-page pa-3 pa-md-4">

    <!-- =========================
         LOADING
    ========================== -->

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

    <!-- =========================
         ERROR
    ========================== -->

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

    <!-- =========================
         CONTENT
    ========================== -->

    <template v-else-if="employee">

      <!-- =========================
           HERO
      ========================== -->

      <v-card class="hero-card mb-2">
        <div class="hero-background"></div>
        <div class="hero-glow"></div>

        <v-card-text class="hero-content pa-4 pa-md-5">

          <!-- EN-TÊTE INTÉGRÉ AU HERO -->
          <div class="hero-header">
            <div>
              <div class="text-overline hero-overline font-weight-bold">MITARBEITER</div>
              <h1 class="hero-title">Mitarbeiterprofil</h1>
              <div class="hero-subtitle">
                Übersicht und Stammdaten des Mitarbeiters
              </div>
            </div>

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

          <v-divider class="hero-divider" />

          <v-row align="center" class="ma-0">

            <!-- PHOTO / HIERARCHY -->
            <v-col cols="12" md="auto" class="d-flex justify-center justify-md-start">
              <div class="employee-hierarchy">
                <div class="person-block">
                  <div class="person-label">Mitarbeiter</div>
                  <div class="avatar-wrapper">
                    <v-avatar size="130" class="employee-avatar">
                      <img
                        v-if="employee.photo && !photoError"
                        :src="employee.photo"
                        :alt="fullName"
                        class="employee-photo"
                        @error="photoError = true"
                      />
                      <v-icon v-else size="62" color="primary">mdi-account</v-icon>
                    </v-avatar>
                    <div
                      class="status-dot"
                      :class="employee.is_active ? 'status-active' : 'status-inactive'"
                    ></div>
                  </div>
                </div>

                <template v-if="employee.vorgesetzter">
                  <div class="hierarchy-line"></div>
                  <div class="person-block supervisor-block">
                    <div class="person-label">Vorgesetzter</div>
                    <v-avatar size="68" class="supervisor-avatar">
                      <img
                        v-if="employee.vorgesetzter_foto && !supervisorPhotoError"
                        :src="employee.vorgesetzter_foto"
                        :alt="employee.vorgesetzter"
                        class="employee-photo"
                        @error="supervisorPhotoError = true"
                      />
                      <v-icon v-else size="36" color="primary">mdi-account-tie-outline</v-icon>
                    </v-avatar>
                    <div class="supervisor-name">{{ employee.vorgesetzter }}</div>
                  </div>
                </template>
              </div>
            </v-col>

            <!-- NAME -->
            <v-col cols="12" md class="text-center text-md-left">
              <div class="d-flex flex-column flex-md-row align-center align-md-center ga-2">
                <h2 class="employee-name">{{ fullName }}</h2>
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

              <div class="employee-number mt-2">
                <v-icon size="16" class="mr-1">mdi-card-account-details-outline</v-icon>
                Mitarbeiter-Nr. <strong>{{ employee.mitarbeiter_nummer }}</strong>
              </div>

              <div v-if="employee.position" class="employee-position mt-2">
                {{ employee.position }}
              </div>

              <div v-if="employee.abteilung" class="employee-department mt-1">
                <v-icon size="15" class="mr-1">mdi-domain</v-icon>
                {{ employee.abteilung }}
              </div>
            </v-col>

            <!-- QUICK INFO -->
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
              </div>
            </v-col>

          </v-row>
        </v-card-text>
      </v-card>

      <!-- =========================
           SECTIONS — 3 CARTES PAR LIGNE
      ========================== -->

      <v-row class="ma-0 section-row">

        <!-- 1. PERSÖNLICHE DATEN -->
        <v-col cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-account-details-outline"
              color="blue"
              title="Persönliche Daten"
              subtitle="Allgemeine Informationen"
            />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="field in personalFields" :key="field.label" class="contact-row">
                  <div class="contact-icon">
                    <v-icon size="16">{{ field.icon }}</v-icon>
                  </div>
                  <div class="contact-label">{{ field.label }}</div>
                  <div class="contact-value">{{ field.value }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- 2. KONTAKTDATEN + ADRESSEN -->
        <v-col cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-home-outline"
              color="green"
              title="Kontaktdaten"
            />
            <v-card-text class="pa-3">
              <div class="contact-fields mb-3">
                <div v-for="field in contactFields" :key="field.label" class="contact-row">
                  <div class="contact-icon">
                    <v-icon size="16">{{ field.icon }}</v-icon>
                  </div>
                  <div class="contact-label">{{ field.label }}</div>
                  <div class="contact-value">{{ field.value || '—' }}</div>
                </div>
              </div>

              <v-divider class="mb-3" />

              <div v-if="employee.addresses?.length" class="address-list">
                <div class="address-header">
                  <div class="address-icon">
                    <v-icon size="17">mdi-map-marker-outline</v-icon>
                  </div>
                  <div class="address-header-title">Hinterlegte Anschriften</div>
                </div>

                <div
                  v-for="addr in employee.addresses"
                  :key="addr.id"
                  class="address-item"
                >
                  <div class="d-flex align-center mb-2">
                    <v-chip size="x-small" color="primary" variant="flat" class="font-weight-bold">
                      {{ addr.adresstyp }}
                    </v-chip>
                  </div>
                  <div class="address-main">{{ addr.strasse }} {{ addr.hausnummer }}</div>
                  <div class="address-city">{{ addr.plz }} {{ addr.stadt }}</div>
                  <div class="address-country">{{ addr.land }}</div>
                </div>
              </div>

              <div v-else class="empty-state">
                <v-icon size="36">mdi-map-marker-off-outline</v-icon>
                <div class="mt-2">Keine Adresse hinterlegt</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- 3. BANKVERBINDUNGEN -->
        <v-col v-if="employee.bank_accounts?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-bank-outline"
              color="purple"
              title="Bankverbindungen"
              subtitle="Hinterlegte Bankkonten"
            />
            <v-card-text class="pa-3">
              <div v-for="bank in employee.bank_accounts" :key="bank.id" class="bank-card">
                <div class="bank-top">
                  <div class="bank-logo">
                    <v-icon size="22">mdi-bank</v-icon>
                  </div>
                  <div class="flex-grow-1 ml-2">
                    <div class="bank-owner">{{ bank.kontoinhaber }}</div>
                    <div class="bank-name">{{ bank.bankname || 'Bankverbindung' }}</div>
                  </div>
                  <v-chip
                    size="x-small"
                    :color="bank.ist_aktiv ? 'success' : 'grey'"
                    variant="flat"
                  >
                    {{ bank.ist_aktiv ? 'Aktiv' : 'Inaktiv' }}
                  </v-chip>
                </div>

                <v-divider class="my-3" />

                <!-- DETAILS AU FORMAT "CONTACT ROW" -->
                <div class="contact-fields">
                  <div class="contact-row">
                    <div class="contact-icon">
                      <v-icon size="16">mdi-account-outline</v-icon>
                    </div>
                    <div class="contact-label">Kontoinhaber</div>
                    <div class="contact-value">{{ bank.kontoinhaber || '—' }}</div>
                  </div>

                  <div class="contact-row">
                    <div class="contact-icon">
                      <v-icon size="16">mdi-identifier</v-icon>
                    </div>
                    <div class="contact-label">IBAN</div>
                    <div class="contact-value">{{ bank.iban }}</div>
                  </div>

                  <div v-if="bank.bic" class="contact-row">
                    <div class="contact-icon">
                      <v-icon size="16">mdi-bank-outline</v-icon>
                    </div>
                    <div class="contact-label">BIC</div>
                    <div class="contact-value">{{ bank.bic }}</div>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- 4. VERSICHERUNG -->
        <v-col v-if="employee.versicherung_gesellschaft" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-shield-check-outline"
              color="green"
              title="Versicherung"
              subtitle="Versicherungsinformationen"
            />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="field in insuranceFields" :key="field.label" class="contact-row">
                  <div class="contact-icon">
                    <v-icon size="16">{{ field.icon }}</v-icon>
                  </div>
                  <div class="contact-label">{{ field.label }}</div>
                  <div class="contact-value">{{ field.value || '—' }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- 5. QUALIFIKATIONEN -->
        <v-col v-if="employee.qualifications?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-school-outline"
              color="orange"
              title="Qualifikationen"
              subtitle="Ausbildungen, Abschlüsse und Zertifikate"
            />
            <v-card-text class="pa-3">
              <div class="qualification-grid">
                <div
                  v-for="q in employee.qualifications"
                  :key="q.id"
                  class="qualification-card"
                >
                  <div class="qualification-icon">
                    <v-icon size="20">mdi-certificate-outline</v-icon>
                  </div>
                  <div class="qualification-content">
                    <div class="qualification-title">{{ q.bezeichnung }}</div>
                    <div class="qualification-institution">
                      {{ q.institution || 'Keine Institution angegeben' }}
                    </div>
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
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- 6. DOKUMENTE -->
        <v-col v-if="employee.documents?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <EmployeeSectionHeader
              icon="mdi-file-document-outline"
              color="blue"
              title="Dokumente"
              subtitle="Dokumente und Nachweise"
            />
            <v-card-text class="pa-3">
              <div class="document-list">
                <a
                  v-for="doc in employee.documents"
                  :key="doc.id"
                  :href="doc.datei_pfad || undefined"
                  :target="doc.datei_pfad ? '_blank' : undefined"
                  class="document-item"
                >
                  <div class="document-icon">
                    <v-icon size="18">mdi-file-document-outline</v-icon>
                  </div>
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
                  <v-icon v-if="doc.datei_pfad" size="18" class="document-arrow">
                    mdi-open-in-new
                  </v-icon>
                </a>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

      </v-row>

      <!-- =========================
           BOTTOM ACTION
      ========================== -->

      <div class="bottom-actions mt-2">
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.back()">
          Zurück
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-pencil-outline" @click="goToEdit">
          Mitarbeiter bearbeiten
        </v-btn>
      </div>

    </template>

  </v-container>

</template>


<script setup>

import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import EmployeeSectionHeader from './EmployeeSectionHeader.vue'

const route = useRoute()
const router = useRouter()

const employee = ref(null)
const loading = ref(true)
const error = ref(null)
const photoError = ref(false)
const supervisorPhotoError = ref(false)


// ============================================================
// COMPUTED
// ============================================================

const fullName = computed(() =>
  employee.value ? `${employee.value.vorname} ${employee.value.nachname}` : ''
)

const heroInfo = computed(() => {
  const e = employee.value
  if (!e) return []
  return [
    {
      icon: 'mdi-calendar-account',
      label: 'Eintritt',
      value: formatDate(e.einstellungsdatum)
    },
    {
      icon: 'mdi-clock-outline',
      label: 'Arbeitszeit',
      value: e.wochenarbeitszeit ? `${e.wochenarbeitszeit} h / Woche` : '—'
    },
    {
      icon: 'mdi-account-supervisor-outline',
      label: 'Vorgesetzter',
      value: e.vorgesetzter || '—'
    }
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
    {
      icon: 'mdi-timer-outline',
      label: 'Wochenarbeitszeit',
      value: e.wochenarbeitszeit ? `${e.wochenarbeitszeit} h` : '—'
    },
    { icon: 'mdi-percent-outline', label: 'Steuerklasse', value: e.steuerklasse || '—' },
    { icon: 'mdi-church', label: 'Konfession', value: e.konfession || '—' }
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
    {
      icon: 'mdi-office-building-outline',
      label: 'Gesellschaft',
      value: e.versicherung_gesellschaft
    },
    { icon: 'mdi-identifier', label: 'Versicherungsnummer', value: e.versicherung_nummer }
  ]
})


// ============================================================
// HELPERS
// ============================================================

const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return date
  }
}

const isUuid = (id) => /^[0-9a-f-]{36}$/i.test(id)


// ============================================================
// LOAD EMPLOYEE
// ============================================================

const loadEmployee = async () => {
  loading.value = true
  error.value = null

  try {
    const identifier = route.params.uid || route.params.id
    const param = isUuid(identifier) ? 'uid' : 'id'
    const { data } = await axios.get(`/api/employees_get.php?${param}=${identifier}`)

    if (!data.success) throw new Error(data.error || 'Unbekannter Fehler')

    const emp = data.employee

    // Normalisation des noms de champs photo
    if (emp?.foto_pfad && !emp.photo) emp.photo = emp.foto_pfad
    if (emp?.vorgesetzter_photo && !emp.vorgesetzter_foto) {
      emp.vorgesetzter_foto = emp.vorgesetzter_photo
    }

    employee.value = emp
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.error || e.message || 'Fehler beim Laden'
  } finally {
    loading.value = false
  }
}


// ============================================================
// EDIT
// ============================================================

const goToEdit = () => {
  const uid = employee.value?.uid || route.params.uid || route.params.id
  router.push(`/employees/edit/${uid}`)
}


// ============================================================
// INIT
// ============================================================

onMounted(loadEmployee)

</script>


<style scoped>

/* ============================================================
   VARIABLES
============================================================ */

.employee-page {
  --sidebar-1: #2c2d2e;
  --sidebar-2: #232324;
  --sidebar-3: #101011;

  --accent-blue: #191b1d;
  --accent-blue-bg: #e8f4fb;

  --accent-green: #159447;
  --accent-green-bg: #e8f8ef;

  --accent-purple: #7655c8;
  --accent-purple-bg: #f0ebff;

  --accent-orange: #d87818;
  --accent-orange-bg: #fff2e4;

  --text-main: #172033;
  --text-muted: #8a94a4;
  --text-soft: #334155;

  --border: #e5eaf0;

  --surface: #ffffff;
  --background: #f6f8fb;

  max-width: 1600px;
  margin: 0 auto;

  background: var(--background);
  min-height: 100vh;

  padding-top: 8px !important;
  padding-bottom: 8px !important;
}


/* ============================================================
   ESPACEMENTS RÉDUITS
============================================================ */

.employee-page .v-row {
  margin: 0 !important;
  row-gap: 4px !important;
}

.employee-page .v-col {
  padding: 2px !important;
}

.employee-page .section-row {
  margin: 0 !important;
  row-gap: 4px !important;
}

.hero-card {
  margin-bottom: 6px !important;
}

.bottom-actions {
  margin-top: 8px !important;
  padding-top: 0 !important;
  padding-bottom: 6px !important;
}


/* ============================================================
   HERO HEADER (titre + bouton intégrés)
============================================================ */

.hero-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.hero-overline {
  color: rgba(255, 255, 255, 0.7) !important;
  letter-spacing: 0.1em;
  font-size: 0.7rem !important;
}

.hero-title {
  margin: 2px 0 0;
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 750;
  letter-spacing: -0.03em;
  color: #ffffff;
  line-height: 1.15;
}

.hero-subtitle {
  margin-top: 4px;
  font-size: 0.86rem;
  color: rgba(255, 255, 255, 0.72);
}

.hero-divider {
  margin: 14px 0 18px;
  opacity: 0.18;
  border-color: rgba(255, 255, 255, 0.4) !important;
}

/* Bouton Bearbeiten adapté au fond sombre */
.edit-button-hero {
  min-width: 150px;
  border-radius: 8px;
  text-transform: none;
  font-weight: 600;
  color: #172033 !important;
  background: #ffffff !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.edit-button-hero:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.35) !important;
  background: #f5f7fa !important;
}


/* ============================================================
   GENERAL CARD
============================================================ */

.modern-card {
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  background: var(--surface) !important;
  box-shadow:
    0 1px 3px rgba(15, 23, 42, 0.03),
    0 4px 12px rgba(15, 23, 42, 0.04) !important;
  overflow: hidden;
}


/* ============================================================
   HERO
============================================================ */

.hero-card {
  position: relative;
  overflow: hidden;
  border-radius: 10px !important;
  color: white;
  background: linear-gradient(
    135deg,
    var(--sidebar-1) 0%,
    var(--sidebar-2) 45%,
    var(--sidebar-3) 100%
  ) !important;
  box-shadow: 0 10px 28px rgba(12, 72, 129, 0.22) !important;
}

.hero-background {
  position: absolute;
  inset: 0;
  opacity: 0.16;
  background-image:
    radial-gradient(circle at 85% 20%, white 0, transparent 32%),
    radial-gradient(circle at 10% 100%, white 0, transparent 30%);
}

.hero-glow {
  position: absolute;
  top: -60%;
  right: -20%;
  width: 60%;
  height: 220%;
  pointer-events: none;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.14) 0%,
    transparent 60%
  );
  filter: blur(30px);
}

.hero-content {
  position: relative;
  z-index: 1;
}


/* ============================================================
   ADDRESS HEADER (dans la carte Kontakt)
============================================================ */

.address-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.address-header .address-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--accent-green);
  background: var(--accent-green-bg);
}

.address-header-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-soft);
  line-height: 1.2;
}


/* ============================================================
   EMPLOYEE / SUPERVISOR HIERARCHY
============================================================ */

.employee-hierarchy {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 150px;
}

.person-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.person-label {
  margin-bottom: 6px;
  color: rgba(255, 255, 255, 0.68);
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.avatar-wrapper {
  position: relative;
  width: 144px;
  height: 144px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.employee-avatar {
  background: rgba(0, 0, 0, 0.25);
  border: 5px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
}

.employee-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.status-dot {
  position: absolute;
  right: 6px;
  bottom: 10px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
}

.status-active {
  background: #22c55e;
}

.status-inactive {
  background: #ef4444;
}


/* ============================================================
   VERTICAL LINE
============================================================ */

.hierarchy-line {
  position: relative;
  width: 2px;
  height: 30px;
  margin: 4px 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.15);
}

.hierarchy-line::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  width: 7px;
  height: 7px;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.12);
}


/* ============================================================
   SUPERVISOR
============================================================ */

.supervisor-block {
  padding-top: 1px;
}

.supervisor-avatar {
  overflow: hidden;
  background: rgba(0, 0, 0, 0.25);
  border: 3px solid rgba(255, 255, 255, 0.45);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
}

.supervisor-name {
  max-width: 150px;
  margin-top: 6px;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 650;
  line-height: 1.25;
  text-align: center;
}


/* ============================================================
   EMPLOYEE INFORMATION
============================================================ */

.employee-name {
  margin: 0;
  font-size: clamp(1.5rem, 3vw, 2rem);
  line-height: 1.15;
  font-weight: 750;
  letter-spacing: -0.025em;
  color: #ffffff;
}

.status-chip {
  font-weight: 700;
  color: white !important;
}

.employee-number {
  display: inline-flex;
  align-items: center;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.86rem;
}

.employee-number strong {
  color: white;
  margin-left: 4px;
}

.employee-position {
  font-size: 1rem;
  font-weight: 600;
  color: #ffffff;
}

.employee-department {
  display: inline-flex;
  align-items: center;
  font-size: 0.88rem;
  color: rgba(255, 255, 255, 0.78);
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
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(6px);
  transition: background 0.25s ease, transform 0.25s ease;
}

.hero-info-item:hover {
  background: rgba(0, 0, 0, 0.35);
  transform: translateX(2px);
}

.hero-info-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  margin-right: 9px;
  background: rgba(0, 0, 0, 0.25);
}

.hero-info-label {
  font-size: 0.68rem;
  color: rgba(255, 255, 255, 0.68);
  letter-spacing: 0.03em;
}

.hero-info-value {
  margin-top: 1px;
  font-size: 0.86rem;
  font-weight: 600;
  color: #ffffff;
}


/* ============================================================
   CONTACT FIELDS
============================================================ */

.contact-fields {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.contact-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: 5px;
  transition: background 0.2s ease;
}

.contact-row:hover {
  background: #f7f9fc;
}

.contact-icon {
  width: 28px;
  height: 28px;
  flex: 0 0 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  color: var(--accent-blue);
  background: var(--accent-blue-bg);
  font-size: 15px;
}

.contact-label {
  flex: 0 0 100px;
  color: var(--text-muted);
  font-size: 0.72rem;
  font-weight: 600;
}

.contact-value {
  flex: 1;
  min-width: 0;
  color: #1f2937;
  font-size: 0.83rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Valeurs longues (IBAN, etc.) : autoriser le retour à la ligne */
.bank-card .contact-value {
  white-space: normal;
  word-break: break-all;
  font-variant-numeric: tabular-nums;
}


/* ============================================================
   ADDRESS
============================================================ */

.address-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.address-item {
  padding: 10px;
  border: 1px solid #e8edf2;
  border-radius: 6px;
  background: #fafbfd;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.address-item:hover {
  border-color: #cbe6d6;
  background: #f6fbf8;
}

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
  color: #9aa4b2;
  font-size: 0.85rem;
}


/* ============================================================
   BANK
============================================================ */

.bank-card {
  padding: 12px;
  border: 1px solid #e7eaf0;
  border-radius: 6px;
  background: #fafbfc;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.bank-card + .bank-card {
  margin-top: 6px;
}

.bank-card:hover {
  border-color: #dcd0f5;
  background: #faf8ff;
}

.bank-top {
  display: flex;
  align-items: center;
}

.bank-logo {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--accent-purple);
  background: var(--accent-purple-bg);
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

.qualification-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}

.qualification-card {
  display: flex;
  padding: 10px;
  border: 1px solid #e8edf2;
  border-radius: 6px;
  background: #fafbfd;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.qualification-card:hover {
  border-color: #f5d8b3;
  background: #fffaf4;
}

.qualification-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--accent-orange);
  background: var(--accent-orange-bg);
}

.qualification-content {
  min-width: 0;
  margin-left: 10px;
}

.qualification-title {
  color: var(--text-main);
  font-size: 0.85rem;
  font-weight: 700;
}

.qualification-institution {
  margin-top: 1px;
  color: #7d8795;
  font-size: 0.75rem;
}

.qualification-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 5px;
  color: #7d8795;
  font-size: 0.72rem;
}

.qualification-meta span {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}


/* ============================================================
   DOCUMENTS
============================================================ */

.document-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.document-item {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid transparent;
  background: #fafbfd;
  color: inherit;
  text-decoration: none;
  transition: all 0.2s ease;
}

.document-item:hover {
  background: #eff7fc;
  border-color: #cbe5f5;
  transform: translateX(2px);
}

.document-icon {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--accent-blue);
  background: var(--accent-blue-bg);
}

.document-content {
  min-width: 0;
  margin-left: 10px;
}

.document-name {
  color: var(--text-main);
  font-size: 0.83rem;
  font-weight: 700;
}

.document-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 2px;
  color: var(--text-muted);
  font-size: 0.72rem;
}

.document-meta span {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.document-arrow {
  margin-left: auto;
  color: #9ba5b3;
  transition: color 0.2s ease, transform 0.2s ease;
}

.document-item:hover .document-arrow {
  color: var(--accent-blue);
  transform: translate(2px, -2px);
}


/* ============================================================
   BOTTOM ACTIONS
============================================================ */

.bottom-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 2px 14px;
}

.bottom-actions .v-btn {
  text-transform: none;
  font-weight: 600;
}


/* ============================================================
   LOADING
============================================================ */

.loading-card {
  max-width: 620px;
  margin: 60px auto;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 960px) {
  .hero-info-grid {
    margin-top: 8px;
  }
}

@media (max-width: 600px) {

  .employee-page {
    padding: 6px !important;
  }

  .employee-page .v-col {
    padding: 2px !important;
  }

  .employee-page .v-row {
    row-gap: 4px !important;
  }

  /* Header Hero en colonne sur mobile */
  .hero-header {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .edit-button-hero {
    width: 100%;
  }

  .hero-card {
    border-radius: 8px !important;
  }

  .hero-content {
    padding: 16px 14px !important;
  }

  .employee-name {
    font-size: 1.45rem;
  }

  .employee-hierarchy {
    min-width: 130px;
  }

  .avatar-wrapper {
    width: 120px;
    height: 120px;
  }

  .employee-avatar {
    width: 110px !important;
    height: 110px !important;
  }

  .status-dot {
    right: 4px;
    bottom: 6px;
    width: 20px;
    height: 20px;
  }

  .hierarchy-line {
    height: 24px;
  }

  .supervisor-avatar {
    width: 58px !important;
    height: 58px !important;
  }

  .modern-card {
    border-radius: 6px !important;
  }

  .contact-label {
    flex: 0 0 90px;
    font-size: 0.7rem;
  }

  .contact-value {
    font-size: 0.8rem;
  }

  .contact-row {
    padding: 5px 7px;
  }

  .bottom-actions {
    flex-direction: column-reverse;
    gap: 6px;
  }

  .bottom-actions .v-btn {
    width: 100%;
  }
}

</style>