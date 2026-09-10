<template>
  <v-container fluid class="pa-6">

    <!-- LOADING -->
    <v-card v-if="loading" class="rounded-xl" elevation="3">
      <v-card-text class="text-center pa-8">
        <v-progress-circular indeterminate color="primary" size="64" />
        <p class="mt-4 text-medium-emphasis">Mitarbeiter wird geladen...</p>
      </v-card-text>
    </v-card>

    <!-- ERREUR -->
    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      class="rounded-xl"
      prominent
    >
      {{ error }}
    </v-alert>

    <!-- CONTENU -->
    <template v-else-if="employee">

      <!-- CARTE PRINCIPALE -->
      <v-card class="rounded-xl mb-6" elevation="3">
        <v-card-text class="pa-8">
          <v-row>

            <!-- COLONNE GAUCHE : AVATAR + NOM -->
            <v-col cols="12" md="3" class="text-center">
              <v-avatar size="180" color="grey-lighten-3">
                <img
                  v-if="employee.photo"
                  :src="employee.photo"
                  :alt="`${employee.vorname} ${employee.nachname}`"
                  style="width:100%; height:100%; object-fit:cover;"
                  @error="onPhotoError"
                />
                <v-icon v-else size="100">mdi-account</v-icon>
              </v-avatar>

              <h2 class="mt-4">
                {{ employee.vorname }} {{ employee.nachname }}
              </h2>

              <div class="text-caption text-medium-emphasis mb-2">
                {{ employee.mitarbeiter_nummer }}
              </div>

              <v-chip
                :color="employee.is_active ? 'success' : 'error'"
                size="small"
              >
                {{ employee.is_active ? 'Aktiv' : 'Inaktiv' }}
              </v-chip>
            </v-col>

            <!-- COLONNE DROITE : DÉTAILS -->
            <v-col cols="12" md="9">

              <!-- DONNÉES PERSONNELLES -->
              <h2 class="d-flex align-center">
                <v-icon class="mr-2">mdi-account-details</v-icon>
                Persönliche Daten
              </h2>
              <v-divider class="mb-4" />

              <v-row dense>
                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">E-Mail</div>
                  <div class="font-weight-medium">{{ employee.email || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Benutzername</div>
                  <div class="font-weight-medium">{{ employee.username || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Telefon</div>
                  <div class="font-weight-medium">{{ employee.telefon || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Mobil</div>
                  <div class="font-weight-medium">{{ employee.mobil || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Abteilung</div>
                  <div class="font-weight-medium">{{ employee.abteilung || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Position</div>
                  <div class="font-weight-medium">{{ employee.position || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Geburtsdatum</div>
                  <div class="font-weight-medium">{{ formatDate(employee.geburtsdatum) }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Einstellungsdatum</div>
                  <div class="font-weight-medium">{{ formatDate(employee.einstellungsdatum) }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Vertragsart</div>
                  <div class="font-weight-medium">{{ employee.vertragsart || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Wochenarbeitszeit</div>
                  <div class="font-weight-medium">
                    {{ employee.wochenarbeitszeit ? employee.wochenarbeitszeit + ' h' : '—' }}
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Steuerklasse</div>
                  <div class="font-weight-medium">{{ employee.steuerklasse || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Konfession</div>
                  <div class="font-weight-medium">{{ employee.konfession || '—' }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-medium-emphasis">Vorgesetzter</div>
                  <div class="font-weight-medium">{{ employee.vorgesetzter || '—' }}</div>
                </v-col>
              </v-row>

              <!-- ADRESSEN -->
              <h2 class="d-flex align-center mt-8">
                <v-icon class="mr-2">mdi-home</v-icon>
                Adresse
              </h2>
              <v-divider class="mb-4" />

              <div v-if="employee.addresses && employee.addresses.length">
                <div
                  v-for="addr in employee.addresses"
                  :key="addr.id"
                  class="mb-3"
                >
                  <v-chip size="x-small" color="primary" variant="tonal" class="mb-1">
                    {{ addr.adresstyp }}
                  </v-chip>
                  <div>{{ addr.strasse }} {{ addr.hausnummer }}</div>
                  <div>{{ addr.plz }} {{ addr.stadt }}</div>
                  <div class="text-medium-emphasis">{{ addr.land }}</div>
                </div>
              </div>
              <div v-else class="text-medium-emphasis">Keine Adresse hinterlegt</div>

              <!-- BOUTON ÉDITER -->
              <div class="mt-8">
                <v-btn
                  color="primary"
                  prepend-icon="mdi-pencil"
                  @click="goToEdit"
                >
                  Bearbeiten
                </v-btn>
              </div>

            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- BANKKONTEN -->
      <v-card v-if="employee.bank_accounts?.length" class="rounded-xl mb-6" elevation="2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-bank</v-icon>
          Bankverbindungen
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-row>
            <v-col
              v-for="bank in employee.bank_accounts"
              :key="bank.id"
              cols="12"
              md="6"
            >
              <v-card variant="outlined" class="pa-3">
                <div class="font-weight-bold">{{ bank.kontoinhaber }}</div>
                <div class="text-caption">IBAN : {{ bank.iban }}</div>
                <div class="text-caption" v-if="bank.bic">BIC : {{ bank.bic }}</div>
                <div class="text-caption" v-if="bank.bankname">{{ bank.bankname }}</div>
                <v-chip
                  size="x-small"
                  :color="bank.ist_aktiv ? 'success' : 'grey'"
                  class="mt-2"
                >
                  {{ bank.ist_aktiv ? 'Aktiv' : 'Inaktiv' }}
                </v-chip>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- QUALIFIKATIONEN -->
      <v-card v-if="employee.qualifications?.length" class="rounded-xl mb-6" elevation="2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-school</v-icon>
          Qualifikationen
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-list density="compact">
            <v-list-item
              v-for="q in employee.qualifications"
              :key="q.id"
            >
              <template #prepend>
                <v-icon>mdi-certificate</v-icon>
              </template>
              <v-list-item-title>{{ q.bezeichnung }}</v-list-item-title>
              <v-list-item-subtitle>
                {{ q.institution || '—' }}
                <span v-if="q.abschlussdatum"> • {{ formatDate(q.abschlussdatum) }}</span>
                <span v-if="q.note"> • Note : {{ q.note }}</span>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>

      <!-- DOKUMENTE -->
      <v-card v-if="employee.documents?.length" class="rounded-xl mb-6" elevation="2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-file-document</v-icon>
          Dokumente
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-list density="compact">
            <v-list-item
              v-for="doc in employee.documents"
              :key="doc.id"
              :href="doc.datei_pfad || undefined"
              :target="doc.datei_pfad ? '_blank' : undefined"
            >
              <template #prepend>
                <v-icon>mdi-paperclip</v-icon>
              </template>
              <v-list-item-title>{{ doc.name }}</v-list-item-title>
              <v-list-item-subtitle>
                {{ doc.typ }}
                <span v-if="doc.gueltig_bis"> • gültig bis {{ formatDate(doc.gueltig_bis) }}</span>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>

      <!-- VERSICHERUNG -->
      <v-card v-if="employee.versicherung_gesellschaft" class="rounded-xl mb-6" elevation="2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-shield-account</v-icon>
          Versicherung
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis">Typ</div>
              <div>{{ employee.versicherung_typ || '—' }}</div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis">Gesellschaft</div>
              <div>{{ employee.versicherung_gesellschaft || '—' }}</div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis">Nummer</div>
              <div>{{ employee.versicherung_nummer || '—' }}</div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

    </template>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route  = useRoute()
const router = useRouter()

const employee = ref(null)
const loading  = ref(true)
const error    = ref(null)

// -------- Chargement --------
const loadEmployee = async () => {
  loading.value = true
  error.value   = null

  try {
    // On utilise l'UID (recommandé) ou l'id (fallback)
    const identifier = route.params.uid || route.params.id
    const isUid = /^[0-9a-f\-]{36}$/i.test(identifier)

    const url = isUid
      ? `/api/employees_get.php?uid=${identifier}`
      : `/api/employees_get.php?id=${identifier}`

    const res = await axios.get(url)

    if (!res.data.success) {
      throw new Error(res.data.error || 'Unbekannter Fehler')
    }

    employee.value = res.data.employee

    // Ajouter un champ `photo` à partir de `foto_pfad`
    if (employee.value.foto_pfad) {
      employee.value.photo = employee.value.foto_pfad
    }

  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.error || e.message || 'Fehler beim Laden'
  } finally {
    loading.value = false
  }
}

// -------- Helpers --------
const formatDate = (d) => {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return d
  }
}

const onPhotoError = (e) => {
  e.target.style.display = 'none'
}

const goToEdit = () => {
  const uid = employee.value?.uid || route.params.uid || route.params.id
  router.push(`/employees/edit/${uid}`)
}

// -------- Init --------
onMounted(loadEmployee)
</script>

<style scoped>
h2 {
  font-size: 1.15rem;
  font-weight: 600;
  color: #1e293b;
}

.text-caption {
  font-size: 0.75rem;
}

.v-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.v-card:hover {
  transform: translateY(-2px);
}
</style>