<!-- src/views/UserProfile.vue -->
<template>
  <v-container max-width="720" class="py-8">
    <v-card rounded="lg" elevation="2">
      <!-- En-tête avec avatar -->
      <v-card-item class="pa-6">
        <div class="d-flex align-center">
          <v-avatar size="72" color="grey-darken-3">
            <span class="text-white text-h5">{{ userInitials }}</span>
          </v-avatar>
          <div class="ml-4">
            <v-card-title class="text-h5 font-weight-bold pa-0">
              {{ displayName }}
            </v-card-title>
            <v-card-subtitle class="pa-0 mt-1">
              {{ authStore.user?.email || 'Keine E-Mail' }}
            </v-card-subtitle>
          </div>
        </div>
      </v-card-item>

      <v-divider />

      <!-- Détails du profil -->
      <v-card-text class="pa-6">
        <v-list lines="two">
          <v-list-item prepend-icon="mdi-account">
            <v-list-item-title>Name</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.name || 'Nicht angegeben' }}
            </v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-email">
            <v-list-item-title>E-Mail</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.email || 'Nicht angegeben' }}
            </v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-shield-account">
            <v-list-item-title>Rolle</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.role || 'Benutzer' }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-card-text>

      <v-divider />

      <!-- Section changement de mot de passe -->
      <v-card-text class="pa-6">
        <div class="text-subtitle-1 font-weight-bold mb-4">
          Passwort ändern
        </div>

        <!-- Étape 1 : Demander le code -->
        <template v-if="step === 'request'">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Aus Sicherheitsgründen senden wir Ihnen einen 6-stelligen
            Verifizierungscode an <strong>{{ authStore.user?.email }}</strong>.
          </p>
          <v-btn
            color="primary"
            prepend-icon="mdi-email-fast"
            :loading="loadingSend"
            :disabled="!authStore.user?.email"
            @click="requestCode"
          >
            Code per E-Mail senden
          </v-btn>
        </template>

        <!-- Étape 2 : Vérifier le code -->
        <template v-else-if="step === 'verify'">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Geben Sie den 6-stelligen Code ein, den wir an
            <strong>{{ authStore.user?.email }}</strong> gesendet haben.
          </p>
          <v-otp-input
            v-model="verificationCode"
            length="6"
            variant="outlined"
            type="number"
            class="mb-4"
          />
          <div class="d-flex ga-2">
            <v-btn
              color="primary"
              :loading="loadingVerify"
              :disabled="verificationCode.length !== 6"
              @click="verifyCode"
            >
              Code bestätigen
            </v-btn>
            <v-btn variant="text" :disabled="loadingVerify" @click="requestCode">
              Code erneut senden
            </v-btn>
          </div>
        </template>

        <!-- Étape 3 : Nouveau mot de passe -->
        <template v-else-if="step === 'reset'">
          <v-alert
            type="success"
            variant="tonal"
            density="compact"
            class="mb-4"
            text="Code bestätigt. Bitte geben Sie Ihr neues Passwort ein."
          />
          <v-text-field
            v-model="newPassword"
            label="Neues Passwort"
            :append-inner-icon="showPwd ? 'mdi-eye-off' : 'mdi-eye'"
            :type="showPwd ? 'text' : 'password'"
            variant="outlined"
            class="mb-2"
            @click:append-inner="showPwd = !showPwd"
          />
          <v-text-field
            v-model="confirmPassword"
            label="Passwort bestätigen"
            :type="showPwd ? 'text' : 'password'"
            variant="outlined"
            class="mb-4"
          />

          <v-alert
            v-if="passwordError"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-4"
            :text="passwordError"
          />

          <v-btn
            color="primary"
            :loading="loadingReset"
            :disabled="!canSubmitPassword"
            @click="submitNewPassword"
          >
            Passwort aktualisieren
          </v-btn>
        </template>

        <!-- Étape 4 : succès -->
        <template v-else-if="step === 'done'">
          <v-alert
            type="success"
            variant="tonal"
            text="Ihr Passwort wurde erfolgreich geändert."
            class="mb-4"
          />
          <v-btn variant="tonal" @click="resetFlow">Zurück</v-btn>
        </template>

        <!-- Messages d'erreur génériques -->
        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          density="compact"
          class="mt-4"
          :text="errorMessage"
        />
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn
          variant="text"
          color="primary"
          prepend-icon="mdi-arrow-left"
          @click="$router.back()"
        >
          Zurück
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// --- URL de base de l'API (à adapter si besoin) ---
const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api'

// --- Affichage profil ---
const displayName = computed(
  () => authStore.user?.name || authStore.user?.email || 'Gast',
)

const userInitials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  const name = (u.name || '').trim()
  if (!name) return u.email?.charAt(0).toUpperCase() || '?'
  const parts = name.split(/\s+/)
  if (parts.length > 1) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

// --- État du flow changement de mot de passe ---
const step = ref('request') // request | verify | reset | done
const verificationCode = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showPwd = ref(false)

const loadingSend = ref(false)
const loadingVerify = ref(false)
const loadingReset = ref(false)
const errorMessage = ref('')
const passwordError = ref('')

const canSubmitPassword = computed(() => {
  return (
    newPassword.value.length >= 8 &&
    newPassword.value === confirmPassword.value &&
    !loadingReset.value
  )
})

function resetFlow() {
  step.value = 'request'
  verificationCode.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
  errorMessage.value = ''
  passwordError.value = ''
}

// --- Étape 1 : demander le code ---
async function requestCode() {
  errorMessage.value = ''
  loadingSend.value = true
  try {
    const res = await fetch(`${API_BASE}/send_verification_code_stock.php`, {
      method: 'POST',
      credentials: 'include', // IMPORTANT pour envoyer/recevoir le cookie de session
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: authStore.user?.email }),
    })
    const data = await res.json()
    if (!data.success) {
      errorMessage.value = data.message || 'Fehler beim Senden des Codes.'
      return
    }
    verificationCode.value = ''
    step.value = 'verify'
  } catch (e) {
    errorMessage.value = 'Netzwerkfehler. Bitte erneut versuchen.'
  } finally {
    loadingSend.value = false
  }
}

// --- Étape 2 : vérifier le code ---
async function verifyCode() {
  errorMessage.value = ''
  loadingVerify.value = true
  try {
    const res = await fetch(`${API_BASE}/verify_code_stock.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: verificationCode.value }),
    })
    const data = await res.json()
    if (!data.success) {
      errorMessage.value = data.message || 'Code ungültig.'
      return
    }
    step.value = 'reset'
  } catch (e) {
    errorMessage.value = 'Netzwerkfehler. Bitte erneut versuchen.'
  } finally {
    loadingVerify.value = false
  }
}

// --- Étape 3 : envoyer le nouveau mot de passe ---
async function submitNewPassword() {
  errorMessage.value = ''
  passwordError.value = ''

  if (newPassword.value.length < 8) {
    passwordError.value = 'Das Passwort muss mindestens 8 Zeichen enthalten.'
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    passwordError.value = 'Die Passwörter stimmen nicht überein.'
    return
  }

  loadingReset.value = true
  try {
        const res = await fetch(`${API_BASE}/change_password_profile_stock.php`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            new_password: newPassword.value,
            // optionnel : identifiant utilisateur si la session PHP n'est pas fiable
            email: authStore.user?.email,
        }),
    })
    const data = await res.json()
    if (!data.success) {
      errorMessage.value = data.message || 'Fehler beim Aktualisieren.'
      return
    }
    step.value = 'done'
  } catch (e) {
    errorMessage.value = 'Netzwerkfehler. Bitte erneut versuchen.'
  } finally {
    loadingReset.value = false
  }
}
</script>