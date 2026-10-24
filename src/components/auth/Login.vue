<template>
  <div class="login-wrapper">
    <!-- ============================================================ -->
    <!-- ✅ OVERLAY LOADING -->
    <!-- ============================================================ -->
    <v-overlay
      v-model="isLoading"
      class="align-center justify-center login-loading-overlay"
      persistent
      :scrim="'rgba(10, 37, 64, 0.78)'"
    >
      <div class="login-loading-content">
        <v-progress-circular
          indeterminate
          size="72"
          color="white"
          width="5"
        />
        <div class="login-loading-text">
          {{ loadingText }}
        </div>
        <div class="login-loading-subtext">
          Bitte warten Sie einen Moment
        </div>
      </div>
    </v-overlay>

    <div class="bg-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="shape shape-4"></div>
    </div>

    <v-container class="login-container">
      <v-card class="login-card" elevation="0" rounded="lg">
        <div class="card-grid">
          <!-- ============================================================ -->
          <!-- PANNEAU GAUCHE : FORMULAIRE -->
          <!-- ============================================================ -->
          <div class="form-panel">
            <div class="brand-header">
              <img :src="logoUrl" alt="StockManager Logo" class="logo-image-hero" />
              <h1 class="brand-name">StockManager</h1>
              <p class="brand-tagline">Verwaltung · Lager · Effizienz</p>
            </div>

            <transition name="slide-fade" mode="out-in">
              <!-- LOGIN -->
              <div v-if="!showResetPassword" key="login" class="form-inner">
                <div class="form-header">
                  <h2 class="form-title">Willkommen zurück</h2>
                  <p class="form-subtitle">Bitte melden Sie sich an, um fortzufahren.</p>
                </div>

                <v-form @submit.prevent="handleSignIn" class="form-fields">
                  <div class="field-group">
                    <label class="field-label" for="login-email">E-Mail</label>
                    <v-text-field
                      id="login-email"
                      v-model="signInForm.email"
                      type="email"
                      placeholder="name@firma.de"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-email-outline"
                      bg-color="grey-lighten-5"
                      hide-details
                      autocomplete="email"
                      required
                      :disabled="isLoading"
                      class="modern-field"
                      @keyup.enter="handleSignIn"
                    />
                  </div>

                  <div class="field-group">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <label class="field-label mb-0" for="login-password">Passwort</label>
                      <button
                        type="button"
                        class="link-btn"
                        @click="forgotPassword"
                        tabindex="0"
                      >
                        Vergessen?
                      </button>
                    </div>
                    <v-text-field
                      id="login-password"
                      v-model="signInForm.password"
                      :type="showPassword ? 'text' : 'password'"
                      placeholder="••••••••"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-lock-outline"
                      :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      bg-color="grey-lighten-5"
                      hide-details
                      autocomplete="current-password"
                      required
                      :disabled="isLoading"
                      class="modern-field"
                      @click:append-inner="showPassword = !showPassword"
                      @keyup.enter="handleSignIn"
                    />
                  </div>

                  <div class="d-flex align-center justify-space-between mt-2">
                    <v-checkbox
                      v-model="rememberMe"
                      label="Angemeldet bleiben"
                      color="primary"
                      density="compact"
                      hide-details
                      class="remember-checkbox"
                    />
                  </div>

                  <transition name="fade">
                    <v-alert
                      v-if="loginError"
                      type="error"
                      variant="tonal"
                      density="compact"
                      class="mt-3"
                      rounded="sm"
                      closable
                      @click:close="loginError = ''"
                    >
                      <div class="d-flex align-center gap-2">
                        <v-icon size="18">mdi-alert-circle</v-icon>
                        {{ loginError }}
                      </div>
                    </v-alert>
                  </transition>

                  <v-btn
                    type="submit"
                    class="submit-btn mt-4"
                    size="large"
                    block
                    rounded="sm"
                    :disabled="isLoading"
                  >
                    <span>{{ isLoading ? 'Anmeldung läuft...' : 'Anmelden' }}</span>
                  </v-btn>
                </v-form>

                <div class="form-footer">
                  <v-icon size="14" class="mr-1">mdi-shield-check</v-icon>
                  Gesicherte Verbindung
                </div>
              </div>

              <!-- RESET -->
              <div v-else key="reset" class="form-inner">
                <button class="back-btn mb-4" @click="showResetPassword = false">
                  <v-icon size="16">mdi-arrow-left</v-icon>
                  Zurück zur Anmeldung
                </button>

                <div class="form-header">
                  <div class="icon-circle mb-3">
                    <v-icon size="28" color="primary">mdi-lock-reset</v-icon>
                  </div>
                  <h2 class="form-title">Passwort zurücksetzen</h2>
                  <p class="form-subtitle">
                    Geben Sie Ihre E-Mail ein — wir senden Ihnen einen Link zum Zurücksetzen.
                  </p>
                </div>

                <v-form @submit.prevent="handleResetPassword" class="form-fields">
                  <div class="field-group">
                    <label class="field-label" for="reset-email">E-Mail</label>
                    <v-text-field
                      id="reset-email"
                      v-model="resetPasswordForm.email"
                      type="email"
                      placeholder="name@firma.de"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-email-outline"
                      bg-color="grey-lighten-5"
                      hide-details
                      autocomplete="email"
                      :disabled="resetPasswordLoading"
                      class="modern-field"
                      @keyup.enter="handleResetPassword"
                    />
                  </div>

                  <v-btn
                    type="submit"
                    class="submit-btn mt-4"
                    size="large"
                    block
                    rounded="sm"
                    :loading="resetPasswordLoading"
                    :disabled="resetPasswordLoading || !resetPasswordForm.email"
                  >
                    <span>Link senden</span>
                  </v-btn>

                  <transition name="fade">
                    <v-alert
                      v-if="resetPasswordSuccess"
                      type="success"
                      variant="tonal"
                      density="compact"
                      class="mt-3"
                      rounded="sm"
                    >
                      <div class="d-flex align-center gap-2">
                        <v-icon size="18">mdi-check-circle</v-icon>
                        Ein Link wurde gesendet. Prüfen Sie Ihr Postfach.
                      </div>
                    </v-alert>
                  </transition>
                </v-form>
              </div>
            </transition>
          </div>

          <!-- ============================================================ -->
          <!-- PANNEAU DROIT : VISUEL -->
          <!-- ============================================================ -->
          <div class="visual-panel">
            <div class="visual-content">
              <div class="visual-icon-wrap">
                <img :src="logoUrl" alt="StockManager Logo" class="visual-logo" />
              </div>
              <h2 class="visual-title">
                Willkommen bei<br />
                <strong>StockManager</strong>
              </h2>
              <p class="visual-text">
                Verwalten Sie Ihre Lagerbestände, Produkte und Bestellungen — alles an einem Ort.
              </p>

              <div class="visual-features">
                <div class="feature-item">
                  <div class="feature-icon">
                    <v-icon size="18" color="white">mdi-package-variant-closed</v-icon>
                  </div>
                  <span>Produktverwaltung</span>
                </div>
                <div class="feature-item">
                  <div class="feature-icon">
                    <v-icon size="18" color="white">mdi-chart-line</v-icon>
                  </div>
                  <span>Live-Statistiken</span>
                </div>
                <div class="feature-item">
                  <div class="feature-icon">
                    <v-icon size="18" color="white">mdi-shield-lock</v-icon>
                  </div>
                  <span>Sichere Zugriffe</span>
                </div>
              </div>
            </div>

            <div class="visual-shape visual-shape-1"></div>
            <div class="visual-shape visual-shape-2"></div>
          </div>
        </div>
      </v-card>

      <p class="copyright-text">
        © {{ currentYear }} StockManager · Alle Rechte vorbehalten
      </p>
    </v-container>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import { usePermissionStore } from '../../stores/permission'
import { useNotificationStore } from '../../stores/notifications'

const router = useRouter()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()
const notificationStore = useNotificationStore()

// ✅ URL du logo (adapter selon ton projet)
const logoUrl = '/images/logo.png'

// ============================================================
// CONFIG LOADING
// ============================================================
const MIN_LOADING_DURATION = 800   // ms — durée minimale du loading

// ============================================================
// STATE
// ============================================================
const showResetPassword = ref(false)
const resetPasswordLoading = ref(false)
const resetPasswordSuccess = ref(false)
const isLoading = ref(false)
const loginError = ref('')
const loginSuccess = ref(false)
const showPassword = ref(false)
const rememberMe = ref(false)
const loadingText = ref('Anmeldung läuft...')

const signInForm = ref({ email: '', password: '' })
const resetPasswordForm = ref({ email: '' })

const currentYear = new Date().getFullYear()

// ============================================================
// HELPERS
// ============================================================
// ✅ Garantit une durée minimale pour une promesse
const withMinDuration = async (promise, minMs = MIN_LOADING_DURATION) => {
  const start = Date.now()
  try {
    return await promise
  } finally {
    const elapsed = Date.now() - start
    const remaining = minMs - elapsed
    if (remaining > 0) {
      await new Promise(resolve => setTimeout(resolve, remaining))
    }
  }
}

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
  const savedEmail = localStorage.getItem('stock_manager_email')
  if (savedEmail) {
    signInForm.value.email = savedEmail
    rememberMe.value = true
  }
})

// ============================================================
// LOGIN — ✅ sans flash
// ============================================================
const handleSignIn = async () => {
  loginError.value = ''
  loginSuccess.value = false

  if (!signInForm.value.email || !signInForm.value.password) {
    loginError.value = 'Bitte E-Mail und Passwort eingeben'
    return
  }

  loadingText.value = 'Anmeldung läuft...'
  isLoading.value = true

  // ✅ Texte évolutif pendant le loading
  const textTimer1 = setTimeout(() => {
    if (isLoading.value) loadingText.value = 'Verbindung wird hergestellt...'
  }, 400)
  const textTimer2 = setTimeout(() => {
    if (isLoading.value) loadingText.value = 'Fast geschafft...'
  }, 900)

  try {
    const response = await withMinDuration(
      axios.post(
        '/api/auth_stock.php?action=login',
        {
          email: signInForm.value.email,
          password: signInForm.value.password
        },
        { withCredentials: true }
      )
    )

    if (response.data.success && response.data.user) {
      authStore.setUser(response.data.user)
      authStore.setLoggedIn(true)

      if (typeof permissionStore.setRole === 'function') {
        permissionStore.setRole(response.data.user.role)
      }

      if (rememberMe.value) {
        localStorage.setItem('stock_manager_email', signInForm.value.email)
      } else {
        localStorage.removeItem('stock_manager_email')
      }

      notificationStore.showSuccess('Erfolgreich angemeldet', 'Willkommen zurück!')
      loginSuccess.value = true

      const landing = permissionStore.can && permissionStore.can('dashboard')
        ? '/'
        : '/tickets'

      // ✅ Redirection IMMÉDIATE — pas de flash
      await router.push(landing)
    } else {
      loginError.value = response.data.message || 'E-Mail oder Passwort falsch'
      notificationStore.showError('Anmeldung fehlgeschlagen', loginError.value)
    }
  } catch (error) {
    console.error('Login error:', error)

    // ✅ Durée minimale même en cas d'erreur
    await withMinDuration(Promise.resolve(), MIN_LOADING_DURATION)

    if (error.response?.data?.message) {
      loginError.value = error.response.data.message
    } else if (error.request) {
      loginError.value = 'Keine Antwort vom Server. Bitte später erneut versuchen.'
    } else {
      loginError.value = 'E-Mail oder Passwort falsch'
    }

    notificationStore.showError('Anmeldung fehlgeschlagen', loginError.value)
  } finally {
    clearTimeout(textTimer1)
    clearTimeout(textTimer2)
    loadingText.value = 'Anmeldung läuft...'

    // ✅ NE PAS remettre isLoading = false en cas de succès :
    //    la vue va être démontée par router.push, l'overlay disparaîtra avec elle.
    if (!loginSuccess.value) {
      isLoading.value = false
    }
  }
}

// ============================================================
// RESET PASSWORD
// ============================================================
const forgotPassword = () => {
  showResetPassword.value = true
  loginError.value = ''
  loginSuccess.value = false
}

const handleResetPassword = async () => {
  if (!resetPasswordForm.value.email) {
    notificationStore.showWarning('Bitte E-Mail eingeben')
    return
  }
  resetPasswordLoading.value = true
  try {
    await withMinDuration(new Promise(r => setTimeout(r, 1500)), 1500)
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
/* ============================================================
   BASE — Bleu #116bd1
   ============================================================ */
.login-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #1d6fdb 0%, #2972e0 60%, #116bd1 100%);
  font-family: 'Roboto', system-ui, sans-serif;
  padding: 24px;
  position: relative;
  overflow: hidden;
}

.login-container {
  max-width: 1000px;
  width: 100%;
  padding: 0;
  position: relative;
  z-index: 1;
}

/* ============================================================
   ✅ LOGIN LOADING OVERLAY
   ============================================================ */
.login-loading-overlay :deep(.v-overlay__scrim) {
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.login-loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 32px 44px;
  border-radius: 12px;
  background: rgba(17, 107, 209, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.05);
  animation: loadingPulse 2s ease-in-out infinite;
}

@keyframes loadingPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.login-loading-text {
  margin-top: 20px;
  font-size: 1.1rem;
  font-weight: 700;
  color: white;
  letter-spacing: 0.3px;
  min-height: 1.4em;
  transition: opacity 0.2s ease;
}

.login-loading-subtext {
  margin-top: 6px;
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.7);
  letter-spacing: 0.2px;
}

/* ============================================================
   ARRIÈRE-PLAN ANIMÉ
   ============================================================ */
.bg-shapes {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.4;
  animation: floatShape 20s ease-in-out infinite;
}

.shape-1 {
  width: 500px;
  height: 500px;
  background: #116bd1;
  top: -200px;
  left: -150px;
  animation-delay: 0s;
}

.shape-2 {
  width: 400px;
  height: 400px;
  background: #0d54a3;
  bottom: -150px;
  right: -100px;
  animation-delay: -7s;
}

.shape-3 {
  width: 300px;
  height: 300px;
  background: #3b8ee0;
  top: 40%;
  right: 10%;
  animation-delay: -14s;
}

.shape-4 {
  width: 250px;
  height: 250px;
  background: #116bd1;
  bottom: 20%;
  left: 5%;
  animation-delay: -3s;
}

@keyframes floatShape {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(30px, -40px) scale(1.05); }
  50% { transform: translate(-20px, 30px) scale(0.95); }
  75% { transform: translate(20px, 20px) scale(1.02); }
}

/* ============================================================
   CARTE PRINCIPALE
   ============================================================ */
.login-card {
  border-radius: 8px !important;
  overflow: hidden;
  background: rgba(248, 250, 252, 0.98);
  backdrop-filter: blur(20px);
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1) !important;
  animation: cardEnter 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes cardEnter {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.card-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 620px;
}

/* ============================================================
   PANNEAU FORMULAIRE
   ============================================================ */
.form-panel {
  padding: 48px 44px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  background: #f8fafc;
}

.brand-header {
  text-align: center;
  margin-bottom: 32px;
}

/* ✅ Logo HERO */
.logo-image-hero {
  display: block;
  margin: 0 auto 16px;
  width: 140px;
  height: 140px;
  object-fit: contain;
}

.brand-name {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.5px;
}

.brand-tagline {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 4px 0 0;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-weight: 600;
}

/* ============================================================
   HEADER FORMULAIRE
   ============================================================ */
.form-inner {
  width: 100%;
}

.form-header {
  text-align: center;
  margin-bottom: 24px;
}

.form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px;
}

.form-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

/* ============================================================
   CHAMPS
   ============================================================ */
.form-fields {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.field-group {
  margin-bottom: 14px;
}

.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.modern-field :deep(.v-field) {
  border-radius: 4px !important;
  transition: all 0.2s ease;
}

.modern-field :deep(.v-field--focused) {
  box-shadow: 0 0 0 3px rgba(17, 107, 209, 0.18);
}

.modern-field :deep(.v-field__input) {
  font-size: 0.95rem;
}

.modern-field :deep(.v-field--focused .v-icon) {
  color: #116bd1 !important;
}

.link-btn {
  background: none;
  border: none;
  padding: 0;
  color: #116bd1;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: color 0.15s;
}

.link-btn:hover {
  color: #0d54a3;
  text-decoration: underline;
}

.remember-checkbox :deep(.v-label) {
  font-size: 0.8rem;
  color: #64748b;
  opacity: 1;
}

/* ============================================================
   BOUTON SUBMIT
   ============================================================ */
.submit-btn {
  background: linear-gradient(135deg, #116bd1 0%, #3b8ee0 100%) !important;
  color: white !important;
  font-weight: 700 !important;
  letter-spacing: 0.3px !important;
  text-transform: none !important;
  height: 48px !important;
  font-size: 1rem !important;
  border-radius: 4px !important;
  box-shadow: 0 8px 20px rgba(17, 107, 209, 0.35) !important;
  transition: all 0.25s ease !important;
}

.submit-btn:hover:not(.v-btn--loading):not(.v-btn--disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(17, 107, 209, 0.45) !important;
}

.submit-btn:active:not(.v-btn--loading):not(.v-btn--disabled) {
  transform: translateY(0);
}

/* ============================================================
   FOOTER FORM
   ============================================================ */
.form-footer {
  text-align: center;
  margin-top: 20px;
  font-size: 0.75rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ============================================================
   BOUTON RETOUR + ICÔNE CIRCLE
   ============================================================ */
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 4px;
  transition: all 0.15s;
}

.back-btn:hover {
  background: #e6f0fb;
  color: #116bd1;
}

.icon-circle {
  width: 56px;
  height: 56px;
  margin: 0 auto;
  border-radius: 8px;
  background: #e6f0fb;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #c7ddf7;
}

/* ============================================================
   PANNEAU VISUEL — #116bd1
   ============================================================ */
.visual-panel {
  background: linear-gradient(135deg, #116bd1 0%, #0d54a3 50%, #3b8ee0 100%);
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 44px;
}

.visual-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
  pointer-events: none;
}

.visual-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
  max-width: 340px;
}

.visual-icon-wrap {
  margin-bottom: 24px;
  animation: floatIcon 3s ease-in-out infinite;
  display: flex;
  align-items: center;
  justify-content: center;
}

.visual-logo {
  width: 180px;
  height: 180px;
  object-fit: contain;
  filter: brightness(0) invert(1);
  opacity: 0.95;
}

@keyframes floatIcon {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.visual-title {
  font-size: 1.75rem;
  font-weight: 400;
  line-height: 1.3;
  margin: 0 0 12px;
  color: white;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.visual-title strong {
  font-weight: 800;
}

.visual-text {
  font-size: 0.95rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.9);
  margin: 0 0 32px;
}

.visual-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
  text-align: left;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 6px;
  backdrop-filter: blur(8px);
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}

.feature-item:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(4px);
}

.feature-icon {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.22);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.visual-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  pointer-events: none;
}

.visual-shape-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -100px;
}

.visual-shape-2 {
  width: 200px;
  height: 200px;
  bottom: -80px;
  left: -80px;
}

/* ============================================================
   COPYRIGHT
   ============================================================ */
.copyright-text {
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.75rem;
  margin-top: 24px;
  margin-bottom: 0;
  position: relative;
  z-index: 1;
}

/* ============================================================
   TRANSITIONS
   ============================================================ */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 900px) {
  .card-grid {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .visual-panel {
    display: none;
  }

  .form-panel {
    padding: 40px 32px;
  }
}

@media (max-width: 600px) {
  .login-wrapper {
    padding: 12px;
  }

  .form-panel {
    padding: 32px 24px;
  }

  .brand-header {
    margin-bottom: 24px;
  }

  .logo-image-hero {
    width: 90px;
    height: 90px;
  }

  .brand-name {
    font-size: 1.3rem;
  }

  .form-title {
    font-size: 1.25rem;
  }

  .submit-btn {
    height: 44px !important;
    font-size: 0.95rem !important;
  }

  .shape-1,
  .shape-2,
  .shape-3,
  .shape-4 {
    filter: blur(60px);
    opacity: 0.3;
  }

  .login-loading-content {
    padding: 24px 32px;
  }

  .login-loading-text {
    font-size: 1rem;
  }

  .login-loading-subtext {
    font-size: 0.75rem;
  }
}

/* ============================================================
   ACCESSIBILITÉ
   ============================================================ */
.link-btn:focus-visible,
.back-btn:focus-visible {
  outline: 2px solid #116bd1;
  outline-offset: 2px;
  border-radius: 4px;
}

@media (prefers-reduced-motion: reduce) {
  .shape,
  .visual-icon-wrap,
  .login-card,
  .login-loading-content {
    animation: none !important;
  }

  .slide-fade-enter-active,
  .slide-fade-leave-active,
  .fade-enter-active,
  .fade-leave-active {
    transition: none !important;
  }
}
</style>