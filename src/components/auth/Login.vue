<template>
  <div class="login-wrapper">
    <v-container class="login-container">
      <v-card class="login-card" elevation="10" rounded="xl">
        <div class="form-container-wrapper">
          <!-- Guest -->
          <v-card :class="['form-card guest-card', { active: isGuestMode }]" elevation="0">
            <v-card-text class="pa-8">
              <div class="text-center">
                <h1 class="text-h5 font-weight-bold mb-4">Als Gast starten</h1>
                <p class="text-body-2 text-grey-darken-1 mb-6">
                  Keine Anmeldung erforderlich. Sie können direkt fortfahren.
                </p>
                <v-btn
                  color="grey-darken-3"
                  size="large"
                  rounded="xl"
                  class="font-weight-bold"
                  @click="continueAsGuest"
                  :loading="isLoading"
                >
                  Als Gast starten
                </v-btn>
              </div>
            </v-card-text>
          </v-card>

          <!-- Login -->
          <v-card
            v-show="!showResetPassword"
            :class="['form-card sign-in-card', { active: !isGuestMode }]"
            elevation="0"
          >
            <v-card-text class="pa-8">
              <v-form @submit.prevent="handleSignIn">
                <h1 class="text-h4 font-weight-bold mb-4">Anmelden</h1>

                <div class="logo-container mb-6">
                  <v-icon size="64" color="grey-darken-3">mdi-warehouse</v-icon>
                  <h2 class="text-h5 font-weight-bold mt-2">StockManager</h2>
                </div>

                <v-text-field
                  v-model="signInForm.email"
                  type="email"
                  placeholder="E-Mail"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                  hide-details
                  required
                  prepend-inner-icon="mdi-email"
                  @keyup.enter="handleSignIn"
                />

                <v-text-field
                  v-model="signInForm.password"
                  type="password"
                  placeholder="Passwort"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                  hide-details
                  required
                  prepend-inner-icon="mdi-lock"
                  @keyup.enter="handleSignIn"
                />

                <div v-if="loginError" class="error-message mb-3">
                  <v-alert type="error" density="compact">{{ loginError }}</v-alert>
                </div>
                <div v-if="loginSuccess" class="success-message mb-3">
                  <v-alert type="success" density="compact">Anmeldung erfolgreich!</v-alert>
                </div>

                <v-btn
                  type="button"
                  variant="text"
                  size="small"
                  class="text-caption pa-0 mb-6"
                  @click="forgotPassword"
                >
                  Passwort vergessen?
                </v-btn>

                <v-btn
                  type="submit"
                  color="grey-darken-3"
                  size="large"
                  rounded="xl"
                  block
                  class="font-weight-bold"
                  :loading="isLoading"
                  :disabled="isLoading"
                >
                  Anmelden
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Reset Password -->
          <v-card
            v-show="showResetPassword"
            :class="['form-card reset-password-card', { active: !isGuestMode }]"
            elevation="0"
          >
            <v-card-text class="pa-8">
              <div class="mb-6">
                <h1 class="text-h4 font-weight-bold mb-2">Passwort zurücksetzen</h1>
                <p class="text-body-2 text-grey-darken-1">
                  Geben Sie Ihre E-Mail ein – wir senden Ihnen einen Link.
                </p>
              </div>
              <v-form @submit.prevent="handleResetPassword">
                <v-text-field
                  v-model="resetPasswordForm.email"
                  type="email"
                  placeholder="E-Mail"
                  variant="outlined"
                  density="comfortable"
                  class="mb-6"
                  hide-details
                  prepend-inner-icon="mdi-email"
                />
                <v-btn
                  type="submit"
                  color="grey-darken-3"
                  size="large"
                  rounded="xl"
                  block
                  class="font-weight-bold mb-4"
                  :loading="resetPasswordLoading"
                >
                  Link senden
                </v-btn>
                <div v-if="resetPasswordSuccess" class="success-message text-center">
                  <v-icon color="success" class="mb-2">mdi-check-circle</v-icon>
                  <p class="text-caption text-success">Ein Link wurde gesendet.</p>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Overlays -->
          <div class="overlay-container" v-if="!showResetPassword">
            <div class="overlay">
              <div class="overlay-panel overlay-left">
                <div class="overlay-content">
                  <h1 class="text-h3 font-weight-bold mb-4">Willkommen!</h1>
                  <p class="text-body-1 mb-6">Melden Sie sich mit Ihren Zugangsdaten an.</p>
                  <v-btn class="ghost-btn" variant="outlined" color="white" size="large" rounded="xl" @click="togglePanel(false)">
                    Anmelden
                  </v-btn>
                </div>
              </div>
              <div class="overlay-panel overlay-right">
                <div class="overlay-content">
                  <v-icon size="64" color="white" class="mb-4">mdi-warehouse</v-icon>
                  <h1 class="text-h4 font-weight-bold mb-4">StockManager</h1>
                  <p class="text-body-1 mb-6">Verwalten Sie Ihre Lagerbestände effizient.</p>
                  <v-btn class="ghost-btn" variant="outlined" color="white" size="large" rounded="xl" @click="togglePanel(true)">
                    Gast-Modus
                  </v-btn>
                </div>
              </div>
            </div>
          </div>

          <div class="overlay-container reset-overlay" v-if="showResetPassword">
            <div class="overlay">
              <div class="overlay-panel overlay-reset">
                <div class="overlay-content">
                  <h1 class="text-h3 font-weight-bold mb-4">Zurück zur Anmeldung?</h1>
                  <v-btn class="ghost-btn" variant="outlined" color="white" size="large" rounded="xl" @click="showResetPassword = false">
                    Zurück zur Anmeldung
                  </v-btn>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-card>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/notifications'

const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const isGuestMode = ref(false)
const showResetPassword = ref(false)
const resetPasswordLoading = ref(false)
const resetPasswordSuccess = ref(false)
const isLoading = ref(false)
const loginError = ref('')
const loginSuccess = ref(false)

const signInForm = ref({ email: '', password: '' })
const resetPasswordForm = ref({ email: '' })

const handleSignIn = async () => {
  loginError.value = ''
  loginSuccess.value = false

  if (!signInForm.value.email || !signInForm.value.password) {
    loginError.value = 'Bitte E-Mail und Passwort eingeben'
    return
  }

  isLoading.value = true

  try {
    const response = await axios.post(
      '/api/auth_stock.php?action=login',
      {
        email: signInForm.value.email,
        password: signInForm.value.password
      },
      { withCredentials: true }
    )

    if (response.data.success) {
      authStore.setUser(response.data.user)
      notificationStore.showSuccess('Erfolgreich angemeldet', 'Willkommen zurück!')
      loginSuccess.value = true
      setTimeout(() => (loginSuccess.value = false), 1500)
      // Nach Login zur Dashboard-Seite navigieren
      router.push('/dashboard')
    } else {
      loginError.value = response.data.message || 'Anmeldung fehlgeschlagen'
      notificationStore.showError('Anmeldung fehlgeschlagen', loginError.value)
    }
  } catch (error) {
    console.error('Login error:', error)
    if (error.response && error.response.data && error.response.data.message) {
      loginError.value = error.response.data.message
    } else {
      loginError.value = 'E-Mail oder Passwort falsch'
    }
    notificationStore.showError('Anmeldung fehlgeschlagen', loginError.value)
  } finally {
    isLoading.value = false
  }
}

const continueAsGuest = async () => {
  isLoading.value = true
  try {
    // Gast im Store setzen (ohne Server-Request)
    authStore.setUser({
      id: 999,
      name: 'Gast',
      email: 'gast@alpha-med-care.com',
      role: 'Gast'
    })
    // Sicherstellen, dass isLoggedIn true ist
    authStore.setLoggedIn(true)
    notificationStore.showSuccess('Als Gast angemeldet', 'Eingeschränkter Zugriff.')
    // Zur Dashboard-Seite navigieren
    router.push('/dashboard')
  } catch (error) {
    console.error('Guest login error:', error)
    notificationStore.showError('Fehler', 'Bitte erneut versuchen.')
  } finally {
    isLoading.value = false
  }
}

const togglePanel = (showGuest) => {
  isGuestMode.value = showGuest
}

const forgotPassword = () => {
  showResetPassword.value = true
}

const handleResetPassword = async () => {
  if (!resetPasswordForm.value.email) {
    notificationStore.showWarning('Bitte E-Mail eingeben')
    return
  }
  resetPasswordLoading.value = true
  try {
    await new Promise(r => setTimeout(r, 1500))
    resetPasswordSuccess.value = true
    notificationStore.showSuccess('Link gesendet', 'Prüfen Sie Ihr Postfach.')
    setTimeout(() => {
      resetPasswordSuccess.value = false
      resetPasswordForm.value.email = ''
      showResetPassword.value = false
    }, 5000)
  } catch (error) {
    notificationStore.showError('Fehler', 'Bitte später erneut versuchen.')
  } finally {
    resetPasswordLoading.value = false
  }
}
</script>

<style scoped>
/* Styles unverändert */
.login-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #2c3e50 0%, #4a6a7f 100%);
  font-family: 'Roboto', sans-serif;
  padding: 20px;
}

.login-container {
  max-width: 800px;
  width: 100%;
  padding: 0;
}

.login-card {
  border-radius: 10px;
  overflow: hidden;
  position: relative;
  min-height: 500px;
}

.form-container-wrapper {
  display: flex;
  position: relative;
  min-height: 500px;
}

.form-card {
  position: absolute;
  top: 0;
  height: 100%;
  width: 50%;
  transition: all 0.6s ease-in-out;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
}

.sign-in-card {
  left: 0;
  opacity: 1;
  z-index: 2;
}

.guest-card {
  left: 0;
  opacity: 0;
  z-index: 1;
}

.guest-card.active {
  opacity: 1;
  z-index: 5;
  transform: translateX(100%);
}

.reset-password-card {
  left: 0;
  opacity: 1;
  z-index: 10;
  width: 50% !important;
  background: white;
}

.overlay-container {
  position: absolute;
  top: 0;
  left: 50%;
  width: 50%;
  height: 100%;
  overflow: hidden;
  transition: transform 0.6s ease-in-out;
  z-index: 10;
}

.guest-card.active ~ .overlay-container {
  transform: translateX(-100%);
}

.reset-overlay {
  left: 50%;
  width: 50%;
}

.overlay {
  color: #FFFFFF;
  position: relative;
  left: -100%;
  height: 100%;
  width: 200%;
  transform: translateX(0);
  transition: transform 0.6s ease-in-out;
}

.overlay-left {
  background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(52, 73, 94, 0.9));
}

.overlay-right {
  background: linear-gradient(rgba(52, 73, 94, 0.9), rgba(44, 62, 80, 0.9));
}

.reset-overlay .overlay {
  background: linear-gradient(rgba(30, 30, 30, 0.9), rgba(60, 60, 60, 0.9));
  left: 0;
  width: 100%;
}

.guest-card.active ~ .overlay-container .overlay {
  transform: translateX(50%);
}

.overlay-panel {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  padding: 0 40px;
  text-align: center;
  top: 0;
  height: 100%;
  width: 50%;
  transform: translateX(0);
  transition: transform 0.6s ease-in-out;
}

.overlay-left {
  transform: translateX(-20%);
}

.guest-card.active ~ .overlay-container .overlay-left {
  transform: translateX(0);
}

.overlay-right {
  right: 0;
  transform: translateX(0);
}

.overlay-reset {
  width: 100%;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.guest-card.active ~ .overlay-container .overlay-right {
  transform: translateX(20%);
}

.overlay-content {
  max-width: 400px;
  width: 100%;
  z-index: 2;
}

.logo-container {
  text-align: center;
  margin-bottom: 24px;
}

.ghost-btn {
  background-color: transparent !important;
  border: 1px solid white !important;
  color: white !important;
}
.ghost-btn:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  transform: scale(1.05);
  transition: all 0.3s ease;
}

.success-message {
  padding: 10px;
  background-color: rgba(76, 175, 80, 0.1);
  border-radius: 8px;
}

.error-message {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.reset-password-card {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-20px); }
  to { opacity: 1; transform: translateX(0); }
}

@media (max-width: 768px) {
  .form-card {
    width: 100%;
    position: relative;
  }
  .sign-in-card,
  .guest-card,
  .reset-password-card {
    transform: none !important;
    width: 100% !important;
  }
  .guest-card.active {
    transform: none !important;
  }
  .overlay-container {
    display: none;
  }
  .reset-overlay {
    display: none;
  }
}

.text-h3, .text-body-1 {
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
}
</style>