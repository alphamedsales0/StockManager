<template>
  <v-container fluid class="kunde-page pa-3 pa-md-4">
    <v-card v-if="loading" class="modern-card loading-card">
      <v-card-text class="text-center pa-10">
        <v-progress-circular indeterminate color="primary" size="48" width="4" />
        <div class="mt-4 text-subtitle-1 font-weight-medium">Kunde wird geladen...</div>
      </v-card-text>
    </v-card>

    <v-alert v-else-if="error" type="error" variant="tonal" class="modern-card" prominent>
      <div class="font-weight-bold mb-1">Kunde konnte nicht geladen werden</div>
      <div>{{ error }}</div>
    </v-alert>

    <template v-else-if="kunde">
      <v-card class="hero-card mb-2">
        <div class="hero-background"></div>
        <div class="hero-glow"></div>
        <v-card-text class="hero-content pa-4 pa-md-5">
          <div class="hero-header">
            <div>
              <div class="text-overline hero-overline font-weight-bold">KUNDE</div>
              <h1 class="hero-title">Kundenprofil</h1>
              <div class="hero-subtitle">Übersicht und Stammdaten des Kunden</div>
            </div>
            <v-btn size="large" variant="flat" prepend-icon="mdi-pencil-outline" class="edit-button-hero" @click="goToEdit">
              Bearbeiten
            </v-btn>
          </div>

          <v-divider class="hero-divider" />

          <v-row align="center" class="ma-0">
            <v-col cols="12" md="auto" class="d-flex justify-center justify-md-start">
              <div class="avatar-wrapper">
                <v-avatar size="130" class="kunde-avatar">
                  <img v-if="kunde.profilbild && !photoError" :src="kunde.profilbild" class="kunde-photo" @error="photoError = true" />
                  <v-icon v-else size="62" color="primary">mdi-account</v-icon>
                </v-avatar>
                <div class="status-dot" :class="kunde.email_verifiziert ? 'status-active' : 'status-inactive'"></div>
              </div>
            </v-col>

            <v-col cols="12" md class="text-center text-md-left">
              <div class="d-flex flex-column flex-md-row align-center align-md-center ga-2">
                <h2 class="kunde-name">{{ displayName }}</h2>
                <v-chip :color="kunde.person_type === 'Gewerbekunde' ? 'warning' : 'primary'" variant="flat" size="small">
                  {{ kunde.person_type }}
                </v-chip>
              </div>
              <div class="kunde-number mt-2">
                <v-icon size="16" class="mr-1">mdi-card-account-details-outline</v-icon>
                Kunden-Nr. <strong>{{ kunde.kundennummer }}</strong>
              </div>
              <div v-if="kunde.email" class="kunde-position mt-2">
                <v-icon size="15" class="mr-1">mdi-email-outline</v-icon>
                {{ kunde.email }}
              </div>
              <div v-if="kunde.kundengruppe" class="kunde-department mt-1">
                <v-icon size="15" class="mr-1">mdi-tag-multiple</v-icon>
                {{ kunde.kundengruppe }}
              </div>
            </v-col>

            <v-col cols="12" md="4">
              <div class="hero-info-grid">
                <div v-for="info in heroInfo" :key="info.label" class="hero-info-item">
                  <div class="hero-info-icon"><v-icon size="17">{{ info.icon }}</v-icon></div>
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

      <v-row class="ma-0 section-row">
        <v-col cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-account-details-outline" color="blue" title="Stammdaten" subtitle="Allgemeine Informationen" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="field in personalFields" :key="field.label" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">{{ field.icon }}</v-icon></div>
                  <div class="contact-label">{{ field.label }}</div>
                  <div class="contact-value">{{ field.value }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-home-outline" color="green" title="Adressen" />
            <v-card-text class="pa-3">
              <div v-if="kunde.adressen?.length" class="address-list">
                <div v-for="addr in kunde.adressen" :key="addr.id" class="address-item">
                  <div class="d-flex align-center mb-2">
                    <v-chip size="x-small" color="primary" variant="flat" class="font-weight-bold">{{ addr.typ }}</v-chip>
                  </div>
                  <div class="address-main">{{ addr.strasse }} {{ addr.hausnummer }}</div>
                  <div class="address-city">{{ addr.plz }} {{ addr.ort }}</div>
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

        <v-col v-if="kunde.kommunikation?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-phone-message" color="blue" title="Kommunikation" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="k in kunde.kommunikation" :key="k.id" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">mdi-phone</v-icon></div>
                  <div class="contact-label">{{ k.typ }}</div>
                  <div class="contact-value">{{ k.wert }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.bankverbindungen?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-bank-outline" color="purple" title="Bankverbindungen" />
            <v-card-text class="pa-3">
              <div v-for="bank in kunde.bankverbindungen" :key="bank.id" class="bank-card">
                <div class="bank-top">
                  <div class="bank-logo"><v-icon size="22">mdi-bank</v-icon></div>
                  <div class="flex-grow-1 ml-2">
                    <div class="bank-owner">{{ bank.kontoinhaber }}</div>
                    <div class="bank-name">{{ bank.bankname || 'Bankverbindung' }}</div>
                  </div>
                  <v-chip size="x-small" :color="bank.ist_standard ? 'success' : 'grey'" variant="flat">
                    {{ bank.ist_standard ? 'Standard' : 'Alternativ' }}
                  </v-chip>
                </div>
                <v-divider class="my-3" />
                <div class="contact-fields">
                  <div class="contact-row">
                    <div class="contact-icon"><v-icon size="16">mdi-identifier</v-icon></div>
                    <div class="contact-label">IBAN</div>
                    <div class="contact-value">{{ bank.iban }}</div>
                  </div>
                  <div v-if="bank.bic" class="contact-row">
                    <div class="contact-icon"><v-icon size="16">mdi-bank-outline</v-icon></div>
                    <div class="contact-label">BIC</div>
                    <div class="contact-value">{{ bank.bic }}</div>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.zahlungsweise || kunde.versandart" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-file-document-outline" color="orange" title="Rechnungsdaten" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="field in invoiceFields" :key="field.label" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">{{ field.icon }}</v-icon></div>
                  <div class="contact-label">{{ field.label }}</div>
                  <div class="contact-value">{{ field.value || '—' }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.preisgruppen?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-tag-outline" color="orange" title="Preisgruppen" />
            <v-card-text class="pa-3">
              <div class="qualification-grid">
                <div v-for="p in kunde.preisgruppen" :key="p.id" class="qualification-card">
                  <div class="qualification-icon"><v-icon size="20">mdi-tag</v-icon></div>
                  <div class="qualification-content">
                    <div class="qualification-title">{{ p.preisgruppe }}</div>
                    <div class="qualification-institution">Priorität: {{ p.prioritaet }}</div>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.artikelrabatte?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-sale" color="green" title="Artikelrabatte" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="a in kunde.artikelrabatte" :key="a.id" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">mdi-percent</v-icon></div>
                  <div class="contact-label">Stufe {{ a.stufe }}</div>
                  <div class="contact-value">{{ a.prozent }} %</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.skonto?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-percent-circle-outline" color="orange" title="Skonto" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="s in kunde.skonto" :key="s.id" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">mdi-percent</v-icon></div>
                  <div class="contact-label">Stufe {{ s.stufe }}</div>
                  <div class="contact-value">{{ s.prozent }} % / {{ s.tage }} Tage</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.mahngebuehren?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-alert-octagon-outline" color="red" title="Mahngebühren" />
            <v-card-text class="pa-3">
              <div class="contact-fields">
                <div v-for="m in kunde.mahngebuehren" :key="m.id" class="contact-row">
                  <div class="contact-icon"><v-icon size="16">mdi-cash</v-icon></div>
                  <div class="contact-label">Stufe {{ m.stufe }}</div>
                  <div class="contact-value">{{ m.betrag }} €</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="kunde.bemerkungen?.length" cols="12" md="6" lg="4">
          <v-card class="modern-card h-100">
            <KundeSectionHeader icon="mdi-note-text-outline" color="orange" title="Bemerkungen" />
            <v-card-text class="pa-3">
              <div class="qualification-grid">
                <div v-for="b in kunde.bemerkungen" :key="b.id" class="qualification-card">
                  <div class="qualification-icon"><v-icon size="20">mdi-comment-text-outline</v-icon></div>
                  <div class="qualification-content">
                    <div class="qualification-title">{{ b.typ === 'internal' ? 'Intern' : 'Kunde' }}</div>
                    <div class="qualification-institution">{{ b.bemerkung }}</div>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <div class="bottom-actions mt-2">
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.back()">Zurück</v-btn>
        <v-btn color="primary" prepend-icon="mdi-pencil-outline" @click="goToEdit">Kunde bearbeiten</v-btn>
      </div>
    </template>
  </v-container>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import KundeSectionHeader from '../components/kunden/KundeSectionHeader.vue'

const route = useRoute()
const router = useRouter()

// ✅ API base — même pattern que profile.js / auth.js
const API_BASE = import.meta.env.VITE_API_BASE || '/api'

const kunde = ref(null)
const loading = ref(true)
const error = ref(null)
const photoError = ref(false)

const fullName = computed(() => {
  if (!kunde.value) return ''
  if (kunde.value.person_type === 'Gewerbekunde') return kunde.value.firma || 'Gewerbekunde'
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

const loadKunde = async () => {
  loading.value = true
  error.value = null
  try {
    const id = route.params.id
    const { data } = await axios.get(`${API_BASE}/kunden_get.php?id=${id}`)
    if (!data.success) throw new Error(data.error || 'Unbekannter Fehler')
    kunde.value = data.kunde
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.error || e.message || 'Fehler beim Laden'
  } finally {
    loading.value = false
  }
}

// ✅ Route corrigée : /customers/edit/:id (au lieu de /kunden/edit/:id)
const goToEdit = () => {
  const id = kunde.value?.id
  if (!id) return
  router.push(`/customers/edit/${id}`)
}

onMounted(loadKunde)
</script>

<style scoped>
.kunde-page {
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
  --accent-red: #c62828;
  --accent-red-bg: #fdecea;
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
.kunde-page .v-row { margin: 0 !important; row-gap: 4px !important; }
.kunde-page .v-col { padding: 2px !important; }
.kunde-page .section-row { margin: 0 !important; row-gap: 4px !important; }
.hero-card { margin-bottom: 6px !important; }
.bottom-actions { margin-top: 8px !important; padding-bottom: 6px !important; }

.hero-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px; }
.hero-overline { color: rgba(255,255,255,0.7) !important; letter-spacing: 0.1em; font-size: 0.7rem !important; }
.hero-title { margin: 2px 0 0; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 750; letter-spacing: -0.03em; color: #fff; line-height: 1.15; }
.hero-subtitle { margin-top: 4px; font-size: 0.86rem; color: rgba(255,255,255,0.72); }
.hero-divider { margin: 14px 0 18px; opacity: 0.18; border-color: rgba(255,255,255,0.4) !important; }
.edit-button-hero {
  min-width: 150px; border-radius: 8px; text-transform: none; font-weight: 600;
  color: #172033 !important; background: #fff !important;
  box-shadow: 0 6px 16px rgba(0,0,0,0.25) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.edit-button-hero:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(0,0,0,0.35) !important; background: #f5f7fa !important; }

.modern-card {
  border: 1px solid var(--border) !important;
  border-radius: 8px !important;
  background: var(--surface) !important;
  box-shadow: 0 1px 3px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04) !important;
  overflow: hidden;
}

.hero-card {
  position: relative; overflow: hidden; border-radius: 10px !important; color: white;
  background: linear-gradient(135deg, var(--sidebar-1) 0%, var(--sidebar-2) 45%, var(--sidebar-3) 100%) !important;
  box-shadow: 0 10px 28px rgba(12,72,129,0.22) !important;
}
.hero-background { position: absolute; inset: 0; opacity: 0.16; background-image: radial-gradient(circle at 85% 20%, white 0, transparent 32%), radial-gradient(circle at 10% 100%, white 0, transparent 30%); }
.hero-glow { position: absolute; top: -60%; right: -20%; width: 60%; height: 220%; pointer-events: none; background: radial-gradient(circle, rgba(255,255,255,0.14) 0%, transparent 60%); filter: blur(30px); }
.hero-content { position: relative; z-index: 1; }

.avatar-wrapper { position: relative; width: 144px; height: 144px; display: flex; align-items: center; justify-content: center; }
.kunde-avatar { background: rgba(0,0,0,0.25); border: 5px solid rgba(255,255,255,0.35); box-shadow: 0 10px 26px rgba(0,0,0,0.22); }
.kunde-photo { width: 100%; height: 100%; object-fit: cover; }
.status-dot { position: absolute; right: 6px; bottom: 10px; width: 22px; height: 22px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
.status-active { background: #22c55e; }
.status-inactive { background: #ef4444; }

.kunde-name { margin: 0; font-size: clamp(1.5rem, 3vw, 2rem); line-height: 1.15; font-weight: 750; letter-spacing: -0.025em; color: #fff; }
.kunde-number { display: inline-flex; align-items: center; color: rgba(255,255,255,0.78); font-size: 0.86rem; }
.kunde-number strong { color: white; margin-left: 4px; }
.kunde-position { font-size: 1rem; font-weight: 600; color: #fff; }
.kunde-department { display: inline-flex; align-items: center; font-size: 0.88rem; color: rgba(255,255,255,0.78); }

.hero-info-grid { display: flex; flex-direction: column; gap: 6px; }
.hero-info-item { display: flex; align-items: center; padding: 8px 10px; border-radius: 6px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.14); backdrop-filter: blur(6px); transition: background 0.25s ease, transform 0.25s ease; }
.hero-info-item:hover { background: rgba(0,0,0,0.35); transform: translateX(2px); }
.hero-info-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; margin-right: 9px; background: rgba(0,0,0,0.25); }
.hero-info-label { font-size: 0.68rem; color: rgba(255,255,255,0.68); letter-spacing: 0.03em; }
.hero-info-value { margin-top: 1px; font-size: 0.86rem; font-weight: 600; color: #fff; }

.contact-fields { display: flex; flex-direction: column; gap: 3px; }
.contact-row { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 5px; transition: background 0.2s ease; }
.contact-row:hover { background: #f7f9fc; }
.contact-icon { width: 28px; height: 28px; flex: 0 0 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; color: var(--accent-blue); background: var(--accent-blue-bg); font-size: 15px; }
.contact-label { flex: 0 0 100px; color: var(--text-muted); font-size: 0.72rem; font-weight: 600; }
.contact-value { flex: 1; min-width: 0; color: #1f2937; font-size: 0.83rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bank-card .contact-value { white-space: normal; word-break: break-all; font-variant-numeric: tabular-nums; }

.address-list { display: flex; flex-direction: column; gap: 6px; }
.address-item { padding: 10px; border: 1px solid #e8edf2; border-radius: 6px; background: #fafbfd; transition: border-color 0.2s ease, background 0.2s ease; }
.address-item:hover { border-color: #cbe6d6; background: #f6fbf8; }
.address-main { color: var(--text-main); font-size: 0.86rem; font-weight: 700; }
.address-city { margin-top: 2px; color: var(--text-soft); font-size: 0.82rem; }
.address-country { margin-top: 1px; color: var(--text-muted); font-size: 0.75rem; }
.empty-state { min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9aa4b2; font-size: 0.85rem; }

.bank-card { padding: 12px; border: 1px solid #e7eaf0; border-radius: 6px; background: #fafbfc; transition: border-color 0.2s ease, background 0.2s ease; }
.bank-card + .bank-card { margin-top: 6px; }
.bank-card:hover { border-color: #dcd0f5; background: #faf8ff; }
.bank-top { display: flex; align-items: center; }
.bank-logo { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: var(--accent-purple); background: var(--accent-purple-bg); }
.bank-owner { color: var(--text-main); font-size: 0.88rem; font-weight: 700; }
.bank-name { margin-top: 1px; color: var(--text-muted); font-size: 0.74rem; }

.qualification-grid { display: grid; grid-template-columns: 1fr; gap: 6px; }
.qualification-card { display: flex; padding: 10px; border: 1px solid #e8edf2; border-radius: 6px; background: #fafbfd; transition: border-color 0.2s ease, background 0.2s ease; }
.qualification-card:hover { border-color: #f5d8b3; background: #fffaf4; }
.qualification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: var(--accent-orange); background: var(--accent-orange-bg); }
.qualification-content { min-width: 0; margin-left: 10px; }
.qualification-title { color: var(--text-main); font-size: 0.85rem; font-weight: 700; }
.qualification-institution { margin-top: 1px; color: #7d8795; font-size: 0.75rem; }

.bottom-actions { display: flex; justify-content: space-between; align-items: center; padding: 6px 2px 14px; }
.bottom-actions .v-btn { text-transform: none; font-weight: 600; }
.loading-card { max-width: 620px; margin: 60px auto; }

@media (max-width: 600px) {
  .kunde-page { padding: 6px !important; }
  .hero-header { flex-direction: column; align-items: stretch; gap: 10px; }
  .edit-button-hero { width: 100%; }
  .avatar-wrapper { width: 120px; height: 120px; }
  .kunde-avatar { width: 110px !important; height: 110px !important; }
  .status-dot { right: 4px; bottom: 6px; width: 20px; height: 20px; }
  .modern-card { border-radius: 6px !important; }
  .contact-label { flex: 0 0 90px; font-size: 0.7rem; }
  .contact-value { font-size: 0.8rem; }
  .bottom-actions { flex-direction: column-reverse; gap: 6px; }
  .bottom-actions .v-btn { width: 100%; }
}
</style>