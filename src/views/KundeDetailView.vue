<template>
  <v-container fluid class="kunde-page pa-3 pa-md-4">

    <!-- LOADING -->
    <v-card v-if="loading" class="modern-card loading-card">
      <v-card-text class="text-center pa-10">
        <v-progress-circular indeterminate color="primary" size="48" width="4" />
        <div class="mt-4 text-subtitle-1 font-weight-medium">
          Kunde wird geladen…
        </div>
      </v-card-text>
    </v-card>

    <!-- ERROR -->
    <v-alert v-else-if="error" type="error" variant="tonal" class="modern-card" prominent>
      <div class="font-weight-bold mb-1">Kunde konnte nicht geladen werden</div>
      <div>{{ error }}</div>
    </v-alert>

    <template v-else-if="kunde">

      <!-- ================= HERO ================= -->
      <v-card class="hero-card mb-3">
        <div class="hero-background"></div>
        <div class="hero-glow"></div>

        <v-card-text class="hero-content pa-5 pa-md-6">

          <div class="hero-header">
            <div>
              <div class="hero-pill">
                <v-icon size="12">mdi-account-circle-outline</v-icon>
                KUNDE
              </div>
              <h1 class="hero-title">Kundenprofil</h1>
              <div class="hero-subtitle">
                Übersicht und Stammdaten des Kunden
              </div>
            </div>

            <div class="hero-header-actions">
              <v-btn
                variant="text"
                prepend-icon="mdi-arrow-left"
                class="hero-back-btn"
                @click="router.back()"
              >
                Zurück
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

            <!-- AVATAR + STATUS DOT -->
            <v-col cols="12" md="auto" class="d-flex justify-center justify-md-start">
              <div class="avatar-wrapper">
                <v-avatar size="140" class="kunde-avatar">
                  <img
                    v-if="kunde.profilbild && !photoError"
                    :src="kunde.profilbild"
                    class="kunde-photo"
                    :alt="displayName"
                    @error="photoError = true"
                  />
                  <v-icon v-else size="60" color="white">mdi-account</v-icon>
                </v-avatar>
                <div
                  class="status-dot"
                  :class="kunde.email_verifiziert ? 'status-active' : 'status-inactive'"
                ></div>
              </div>
            </v-col>

            <!-- IDENTITY -->
            <v-col cols="12" md class="text-center text-md-left">
              <div class="identity-name-row">
                <h2 class="kunde-name">{{ displayName }}</h2>
                <span
                  class="type-badge"
                  :class="kunde.person_type === 'Gewerbekunde' ? 'type-business' : 'type-private'"
                >
                  <v-icon size="11">
                    {{ kunde.person_type === 'Gewerbekunde' ? 'mdi-domain' : 'mdi-account' }}
                  </v-icon>
                  {{ kunde.person_type }}
                </span>
              </div>

              <div class="identity-meta">
                <div class="meta-row">
                  <v-icon size="14">mdi-card-account-details-outline</v-icon>
                  <span class="meta-label">Kunden-Nr.</span>
                  <strong class="meta-value">{{ kunde.kundennummer || '—' }}</strong>
                </div>

                <div v-if="kunde.email" class="meta-row">
                  <v-icon size="14">mdi-email-outline</v-icon>
                  <span class="meta-value">{{ kunde.email }}</span>
                </div>

                <div v-if="kunde.kundengruppe" class="meta-row">
                  <v-icon size="14">mdi-tag-multiple</v-icon>
                  <span class="meta-value">{{ kunde.kundengruppe }}</span>
                </div>
              </div>
            </v-col>

            <!-- QUICK INFO -->
            <v-col cols="12" md="4">
              <div class="hero-info-grid">
                <div
                  v-for="info in heroInfo"
                  :key="info.label"
                  class="hero-info-item"
                >
                  <div class="hero-info-icon">
                    <v-icon size="16">{{ info.icon }}</v-icon>
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

      <!-- ================= SECTIONS — grid avec séparateurs verticaux ================= -->
      <div class="sections-grid">

        <!-- STAMMDATEN -->
        <section class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-blue">
              <v-icon size="18">mdi-account-details-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Stammdaten</div>
              <div class="section-subtitle">Allgemeine Informationen</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="field in personalFields"
              :key="field.label"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">{{ field.icon }}</v-icon></div>
              <div class="contact-label">{{ field.label }}</div>
              <div class="contact-value">{{ field.value }}</div>
            </div>
          </div>
        </section>

        <!-- ADRESSEN -->
        <section class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-green">
              <v-icon size="18">mdi-home-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Adressen</div>
              <div class="section-subtitle">
                {{ kunde.adressen?.length ? `${kunde.adressen.length} hinterlegt` : 'Keine Adresse' }}
              </div>
            </div>
          </div>
          <div v-if="kunde.adressen?.length" class="address-list">
            <div
              v-for="addr in kunde.adressen"
              :key="addr.id"
              class="address-item"
            >
              <div class="d-flex align-center mb-2">
                <span class="address-type-chip">{{ addr.typ }}</span>
              </div>
              <div class="address-main">
                {{ addr.strasse }} {{ addr.hausnummer }}
              </div>
              <div class="address-city">
                {{ addr.plz }} {{ addr.ort }}
              </div>
              <div class="address-country">{{ addr.land }}</div>
            </div>
          </div>
          <div v-else class="empty-state">
            <v-icon size="36">mdi-map-marker-off-outline</v-icon>
            <div class="mt-2">Keine Adresse hinterlegt</div>
          </div>
        </section>

        <!-- KOMMUNIKATION -->
        <section v-if="kunde.kommunikation?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-blue">
              <v-icon size="18">mdi-phone-message</v-icon>
            </div>
            <div>
              <div class="section-title">Kommunikation</div>
              <div class="section-subtitle">{{ kunde.kommunikation.length }} Einträge</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="k in kunde.kommunikation"
              :key="k.id"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">mdi-phone</v-icon></div>
              <div class="contact-label">{{ k.typ }}</div>
              <div class="contact-value">{{ k.wert }}</div>
            </div>
          </div>
        </section>

        <!-- BANKVERBINDUNGEN -->
        <section v-if="kunde.bankverbindungen?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-purple">
              <v-icon size="18">mdi-bank-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Bankverbindungen</div>
              <div class="section-subtitle">{{ kunde.bankverbindungen.length }} Konto(en)</div>
            </div>
          </div>
          <div class="bank-list">
            <div
              v-for="bank in kunde.bankverbindungen"
              :key="bank.id"
              class="bank-card"
            >
              <div class="bank-top">
                <div class="bank-logo">
                  <v-icon size="22">mdi-bank</v-icon>
                </div>
                <div class="flex-grow-1 ml-2">
                  <div class="bank-owner">{{ bank.kontoinhaber }}</div>
                  <div class="bank-name">{{ bank.bankname || 'Bankverbindung' }}</div>
                </div>
                <span
                  class="bank-status"
                  :class="bank.ist_standard ? 'bank-status-default' : 'bank-status-alt'"
                >
                  {{ bank.ist_standard ? 'Standard' : 'Alternativ' }}
                </span>
              </div>
              <v-divider class="my-3" />
              <div class="contact-fields">
                <div class="contact-row">
                  <div class="contact-icon"><v-icon size="15">mdi-identifier</v-icon></div>
                  <div class="contact-label">IBAN</div>
                  <div class="contact-value">{{ bank.iban }}</div>
                </div>
                <div v-if="bank.bic" class="contact-row">
                  <div class="contact-icon"><v-icon size="15">mdi-bank-outline</v-icon></div>
                  <div class="contact-label">BIC</div>
                  <div class="contact-value">{{ bank.bic }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- RECHNUNGSDATEN -->
        <section v-if="kunde.zahlungsweise || kunde.versandart" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-orange">
              <v-icon size="18">mdi-file-document-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Rechnungsdaten</div>
              <div class="section-subtitle">Zahlung & Versand</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="field in invoiceFields"
              :key="field.label"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">{{ field.icon }}</v-icon></div>
              <div class="contact-label">{{ field.label }}</div>
              <div class="contact-value">{{ field.value || '—' }}</div>
            </div>
          </div>
        </section>

        <!-- PREISGRUPPEN -->
        <section v-if="kunde.preisgruppen?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-orange">
              <v-icon size="18">mdi-tag-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Preisgruppen</div>
              <div class="section-subtitle">{{ kunde.preisgruppen.length }} Einträge</div>
            </div>
          </div>
          <div class="qualification-grid">
            <div
              v-for="p in kunde.preisgruppen"
              :key="p.id"
              class="qualification-card"
            >
              <div class="qualification-icon">
                <v-icon size="18">mdi-tag</v-icon>
              </div>
              <div class="qualification-content">
                <div class="qualification-title">{{ p.preisgruppe }}</div>
                <div class="qualification-institution">
                  Priorität: {{ p.prioritaet }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ARTIKELRABATTE -->
        <section v-if="kunde.artikelrabatte?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-green">
              <v-icon size="18">mdi-sale</v-icon>
            </div>
            <div>
              <div class="section-title">Artikelrabatte</div>
              <div class="section-subtitle">{{ kunde.artikelrabatte.length }} Stufen</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="a in kunde.artikelrabatte"
              :key="a.id"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">mdi-percent</v-icon></div>
              <div class="contact-label">Stufe {{ a.stufe }}</div>
              <div class="contact-value">{{ a.prozent }} %</div>
            </div>
          </div>
        </section>

        <!-- SKONTO -->
        <section v-if="kunde.skonto?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-orange">
              <v-icon size="18">mdi-percent-circle-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Skonto</div>
              <div class="section-subtitle">{{ kunde.skonto.length }} Stufen</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="s in kunde.skonto"
              :key="s.id"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">mdi-percent</v-icon></div>
              <div class="contact-label">Stufe {{ s.stufe }}</div>
              <div class="contact-value">{{ s.prozent }} % / {{ s.tage }} Tage</div>
            </div>
          </div>
        </section>

        <!-- MAHNGEBÜHREN -->
        <section v-if="kunde.mahngebuehren?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-red">
              <v-icon size="18">mdi-alert-octagon-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Mahngebühren</div>
              <div class="section-subtitle">{{ kunde.mahngebuehren.length }} Stufen</div>
            </div>
          </div>
          <div class="contact-fields">
            <div
              v-for="m in kunde.mahngebuehren"
              :key="m.id"
              class="contact-row"
            >
              <div class="contact-icon"><v-icon size="15">mdi-cash</v-icon></div>
              <div class="contact-label">Stufe {{ m.stufe }}</div>
              <div class="contact-value">{{ m.betrag }} €</div>
            </div>
          </div>
        </section>

        <!-- BEMERKUNGEN -->
        <section v-if="kunde.bemerkungen?.length" class="info-section">
          <div class="section-header">
            <div class="section-icon section-icon-orange">
              <v-icon size="18">mdi-note-text-outline</v-icon>
            </div>
            <div>
              <div class="section-title">Bemerkungen</div>
              <div class="section-subtitle">{{ kunde.bemerkungen.length }} Einträge</div>
            </div>
          </div>
          <div class="qualification-grid">
            <div
              v-for="b in kunde.bemerkungen"
              :key="b.id"
              class="qualification-card"
            >
              <div class="qualification-icon">
                <v-icon size="18">mdi-comment-text-outline</v-icon>
              </div>
              <div class="qualification-content">
                <div class="qualification-title">
                  {{ b.typ === 'internal' ? 'Intern' : 'Kunde' }}
                </div>
                <div class="qualification-institution">{{ b.bemerkung }}</div>
              </div>
            </div>
          </div>
        </section>

      </div>

      <!-- BOTTOM ACTIONS -->
      <div class="bottom-actions mt-3">
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.back()">
          Zurück
        </v-btn>
        <v-btn
          variant="flat"
          prepend-icon="mdi-pencil-outline"
          class="edit-button-hero"
          @click="goToEdit"
        >
          Kunde bearbeiten
        </v-btn>
      </div>

    </template>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const API_BASE = import.meta.env.VITE_API_BASE || '/api'

const kunde = ref(null)
const loading = ref(true)
const error = ref(null)
const photoError = ref(false)

// ============================================================
// COMPUTED
// ============================================================
const fullName = computed(() => {
  if (!kunde.value) return ''
  if (kunde.value.person_type === 'Gewerbekunde') {
    return kunde.value.firma || 'Gewerbekunde'
  }
  return `${kunde.value.vorname || ''} ${kunde.value.nachname || ''}`.trim()
})
const displayName = fullName

const heroInfo = computed(() => {
  const k = kunde.value
  if (!k) return []
  return [
    { icon: 'mdi-tag-multiple', label: 'Kundengruppe', value: k.kundengruppe || '—' },
    { icon: 'mdi-factory', label: 'Branche', value: k.branche || '—' },
    { icon: 'mdi-check-circle', label: 'E-Mail verifiziert', value: k.email_verifiziert ? 'Ja' : 'Nein' }
  ]
})

const personalFields = computed(() => {
  const k = kunde.value
  if (!k) return []
  const fields = []

  if (k.person_type === 'Gewerbekunde') {
    fields.push(
      { icon: 'mdi-domain', label: 'Firma', value: k.firma || '—' },
      { icon: 'mdi-plus-box-outline', label: 'Zusatz', value: k.zusatz || '—' },
      { icon: 'mdi-account-tie', label: 'Ansprechpartner', value: k.kontakt || '—' },
      { icon: 'mdi-book-open-variant', label: 'HRB-Nr.', value: k.hrb_register_nummer || '—' },
      { icon: 'mdi-gavel', label: 'Gerichtsstand', value: k.hrb_gerichtsstand || '—' }
    )
  } else {
    fields.push(
      { icon: 'mdi-account-voice', label: 'Anrede', value: k.anrede || '—' },
      { icon: 'mdi-account', label: 'Vorname', value: k.vorname || '—' },
      { icon: 'mdi-account', label: 'Nachname', value: k.nachname || '—' }
    )
  }

  fields.push(
    { icon: 'mdi-identifier', label: 'Steuernummer', value: k.steuernummer || '—' },
    { icon: 'mdi-identifier', label: 'USt-IdNr.', value: k.ust_id_nr || '—' }
  )

  return fields
})

const invoiceFields = computed(() => {
  const k = kunde.value
  if (!k) return []
  return [
    { icon: 'mdi-cash-multiple', label: 'Zahlungsweise', value: k.zahlungsweise },
    { icon: 'mdi-truck-outline', label: 'Versandart', value: k.versandart },
    { icon: 'mdi-calendar-clock', label: 'Zahlungsziel', value: k.zahlungsziel ? `${k.zahlungsziel} Tage` : '—' },
    { icon: 'mdi-percent', label: 'Gesamtrabatt', value: k.gesamtrabatt ? `${k.gesamtrabatt} %` : '—' }
  ]
})

// ============================================================
// LOAD
// ============================================================
const loadKunde = async () => {
  loading.value = true
  error.value = null
  try {
    const id = route.params.id
    const { data } = await axios.get(
      `${API_BASE}/kunden_get.php?id=${id}`,
      { withCredentials: true }
    )
    if (!data.success) throw new Error(data.error || 'Unbekannter Fehler')
    kunde.value = data.kunde
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.error || e.message || 'Fehler beim Laden'
  } finally {
    loading.value = false
  }
}

// ============================================================
// NAVIGATION
// ============================================================
const goToEdit = () => {
  const id = kunde.value?.id
  if (!id) return
  router.push(`/customers/edit/${id}`)
}

onMounted(loadKunde)
</script>

<style scoped>
/* ============================================================
   PALETTE — BLEU MÉDICAL + GRIS
============================================================ */
.kunde-page {
  --medical-900: #0c4a6e;
  --medical-800: #075985;
  --medical-700: #0369a1;
  --medical-600: #0284c7;
  --medical-500: #0ea5e9;
  --medical-100: #e0f2fe;
  --medical-50:  #f0f9ff;

  --grey-900: #0f172a;
  --grey-700: #334155;
  --grey-600: #475569;
  --grey-500: #64748b;
  --grey-400: #94a3b8;
  --grey-200: #e2e8f0;
  --grey-100: #f1f5f9;
  --grey-50:  #f8fafc;

  --accent-green: #159447;
  --accent-green-bg: #e8f8ef;
  --accent-purple: #7655c8;
  --accent-purple-bg: #f0ebff;
  --accent-orange: #d87818;
  --accent-orange-bg: #fff2e4;
  --accent-red: #c62828;
  --accent-red-bg: #fdecea;

  --text-main: #0f172a;
  --text-soft: #334155;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --surface: #ffffff;
  --background: #f8fafc;

  max-width: 1600px;
  margin: 0 auto;
  background: var(--background);
  min-height: 100vh;
  padding-top: 8px !important;
  padding-bottom: 8px !important;
}

.hero-card { margin-bottom: 6px !important; }
.bottom-actions { margin-top: 8px !important; padding-bottom: 6px !important; }

/* ============================================================
   HERO
============================================================ */
.hero-card {
  position: relative;
  overflow: hidden;
  border-radius: 14px !important;
  background: linear-gradient(135deg, #207dac 0%, #3781a7 45%, #1d6688 100%) !important;
  border: none !important;
  box-shadow: 0 10px 28px rgba(2, 132, 199, 0.25) !important;
}
.hero-background {
  position: absolute;
  inset: 0;
  opacity: 0.15;
  pointer-events: none;
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
  background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, transparent 60%);
  filter: blur(30px);
}
.hero-content {
  position: relative;
  z-index: 1;
}
.hero-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}
.hero-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  backdrop-filter: blur(4px);
}
.hero-title {
  margin: 8px 0 0;
  font-size: clamp(1.4rem, 3vw, 1.9rem);
  font-weight: 750;
  letter-spacing: -0.03em;
  color: #ffffff;
  line-height: 1.15;
}
.hero-subtitle {
  margin-top: 4px;
  font-size: 0.84rem;
  color: rgba(255, 255, 255, 0.78);
}
.hero-divider {
  margin: 16px 0 18px;
  opacity: 0.2;
  border-color: rgba(255, 255, 255, 0.4) !important;
}
.hero-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.hero-back-btn {
  text-transform: none;
  font-weight: 600;
  color: #ffffff !important;
  border-radius: 10px !important;
}
.hero-back-btn:hover {
  background: rgba(255, 255, 255, 0.15) !important;
}
.edit-button-hero {
  min-width: 150px;
  height: 44px !important;
  border-radius: 10px !important;
  text-transform: none;
  font-weight: 700;
  color: var(--medical-700) !important;
  background: #ffffff !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.edit-button-hero:hover {
  transform: translateY(-1px);
  background: #f0f9ff !important;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.28) !important;
}

/* AVATAR + STATUS */
.avatar-wrapper {
  position: relative;
  width: 156px;
  height: 156px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.kunde-avatar {
  background: rgba(255, 255, 255, 0.15);
  border: 4px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
}
.kunde-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.status-dot {
  position: absolute;
  right: 8px;
  bottom: 12px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
}
.status-active   { background: #22c55e; }
.status-inactive { background: #ef4444; }

/* IDENTITY */
.identity-name-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}
@media (min-width: 960px) {
  .identity-name-row { justify-content: flex-start; }
}
.kunde-name {
  margin: 0;
  font-size: clamp(1.4rem, 3vw, 1.9rem);
  line-height: 1.15;
  font-weight: 750;
  letter-spacing: -0.025em;
  color: #ffffff;
}
.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.type-private {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.type-business {
  background: rgba(255, 255, 255, 0.28);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.4);
}
.identity-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 10px;
  align-items: center;
}
@media (min-width: 960px) {
  .identity-meta { align-items: flex-start; }
}
.meta-row {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.85rem;
}
.meta-label {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.76rem;
}
.meta-value {
  color: #ffffff;
  font-weight: 600;
}
.meta-row strong.meta-value {
  font-weight: 750;
}

/* QUICK INFO */
.hero-info-grid {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.hero-info-item {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  transition: background 0.25s ease, transform 0.25s ease;
}
.hero-info-item:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(2px);
}
.hero-info-icon {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  margin-right: 9px;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}
.hero-info-label {
  font-size: 0.66rem;
  color: rgba(255, 255, 255, 0.68);
  letter-spacing: 0.03em;
  text-transform: uppercase;
}
.hero-info-value {
  margin-top: 1px;
  font-size: 0.84rem;
  font-weight: 600;
  color: #ffffff;
}

/* ============================================================
   ⭐ SECTIONS GRID — même disposition 3 cols, sans cartes,
   avec séparateurs verticaux fins
============================================================ */
.sections-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);    /* ✅ 3 colonnes = même chose que md="6" lg="4" */
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

/* Chaque section */
.info-section {
  padding: 16px 18px;
  border-left: 1px solid var(--border);    /* ✅ séparateur vertical fin */
  border-bottom: 1px solid var(--border);  /* ✅ séparateur horizontal fin */
  min-width: 0;
}

/* Pas de bordure gauche sur la 1ère colonne de chaque ligne */
.info-section:nth-child(3n + 1) {
  border-left: none;
}

/* Pas de bordure basse sur la dernière ligne */
.info-section:nth-last-child(-n + 3):nth-child(3n + 1),
.info-section:nth-last-child(-n + 2),
.info-section:last-child {
  border-bottom: none;
}

/* ============================================================
   SECTION HEADER
============================================================ */
.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--grey-100);
}
.section-icon {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}
.section-icon-blue   { color: var(--medical-700);  background: var(--medical-50); }
.section-icon-green  { color: var(--accent-green); background: var(--accent-green-bg); }
.section-icon-purple { color: var(--accent-purple); background: var(--accent-purple-bg); }
.section-icon-orange { color: var(--accent-orange); background: var(--accent-orange-bg); }
.section-icon-red    { color: var(--accent-red);   background: var(--accent-red-bg); }

.section-title {
  font-size: 0.85rem;
  font-weight: 750;
  color: var(--text-main);
  letter-spacing: -0.01em;
  line-height: 1.2;
}
.section-subtitle {
  margin-top: 2px;
  font-size: 0.7rem;
  color: var(--text-muted);
  font-weight: 500;
}

/* ============================================================
   CONTACT FIELDS
============================================================ */
.contact-fields {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.contact-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 4px;
  border-radius: 6px;
  transition: background 0.18s ease;
}
.contact-row:hover {
  background: var(--grey-50);
}
.contact-icon {
  width: 26px;
  height: 26px;
  flex: 0 0 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--medical-700);
  background: var(--medical-50);
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
  color: var(--grey-900);
  font-size: 0.83rem;
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
   ADDRESS
============================================================ */
.address-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.address-item {
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--grey-50);
  transition: border-color 0.2s ease, background 0.2s ease;
}
.address-item:hover {
  border-color: #bbf7d0;
  background: #f6fbf8;
}
.address-type-chip {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--medical-50);
  color: var(--medical-700);
  border: 1px solid var(--medical-100);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.02em;
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
  min-height: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--grey-400);
  font-size: 0.85rem;
}

/* ============================================================
   BANK
============================================================ */
.bank-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.bank-card {
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--grey-50);
  transition: border-color 0.2s ease, background 0.2s ease;
}
.bank-card:hover {
  border-color: #ddd6fe;
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
  border-radius: 8px;
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
.bank-status {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.bank-status-default {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.bank-status-alt {
  background: var(--grey-100);
  color: var(--grey-600);
  border: 1px solid var(--grey-200);
}

/* ============================================================
   QUALIFICATIONS / BEMERKUNGEN
============================================================ */
.qualification-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}
.qualification-card {
  display: flex;
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--grey-50);
  transition: border-color 0.2s ease, background 0.2s ease;
}
.qualification-card:hover {
  border-color: #fde68a;
  background: #fffbeb;
}
.qualification-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
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
  margin-top: 2px;
  color: var(--text-muted);
  font-size: 0.76rem;
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
  border-radius: 10px !important;
}

/* ============================================================
   LOADING
============================================================ */
.loading-card {
  max-width: 620px;
  margin: 60px auto;
  border-radius: 14px !important;
}

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 960px) {
  .hero-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  .hero-header-actions { width: 100%; }
  .hero-header-actions .v-btn { flex: 1; }
  .edit-button-hero { width: 100%; }

  /* 2 colonnes */
  .sections-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .info-section {
    border-left: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  /* reset nth-child 3n+1 → on passe à 2n+1 */
  .info-section:nth-child(3n + 1) {
    border-left: 1px solid var(--border);
  }
  .info-section:nth-child(2n + 1) {
    border-left: none;
  }
  /* reset nth-last-child : plus de règle 3 */
  .info-section:nth-last-child(-n + 3):nth-child(3n + 1) {
    border-bottom: 1px solid var(--border);
  }
  .info-section:nth-last-child(-n + 2) {
    border-bottom: none;
  }
}

@media (max-width: 600px) {
  .kunde-page { padding: 6px !important; }
  .avatar-wrapper { width: 120px; height: 120px; }
  .kunde-avatar { width: 112px !important; height: 112px !important; }
  .status-dot { right: 4px; bottom: 6px; width: 20px; height: 20px; }

  /* 1 colonne */
  .sections-grid {
    grid-template-columns: 1fr;
  }
  .info-section {
    border-left: none !important;
    border-bottom: 1px solid var(--border);
  }
  .info-section:last-child {
    border-bottom: none;
  }

  .contact-label { flex: 0 0 90px; font-size: 0.7rem; }
  .contact-value { font-size: 0.8rem; }

  .bottom-actions { flex-direction: column-reverse; gap: 6px; }
  .bottom-actions .v-btn { width: 100%; }
}
</style>