<template>
  <div class="reset-page">
    <div class="reset-card">
      <div class="reset-header">
        <h1>Passwort zurücksetzen</h1>
      </div>

      <!-- Chargement initial -->
      <div v-if="checking" class="state-box">
        <v-progress-circular indeterminate color="primary" size="40" />
        <p>Link wird geprüft...</p>
      </div>

      <!-- Token invalide -->
      <div v-else-if="!tokenValid" class="state-box error">
        <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
        <h3>Link ungültig oder abgelaufen</h3>
        <p>{{ tokenError }}</p>
        <p class="hint">Bitte wenden Sie sich an Ihren Administrator.</p>
      </div>

      <!-- Formulaire -->
      <form v-else @submit.prevent="submit">
        <p class="welcome">
          Hallo <strong>{{ fullName }}</strong>,<br>
          Konto: <strong>{{ maskedEmail }}</strong>
        </p>

        <div class="form-group">
          <label for="newPassword">Neues Passwort *</label>
          <input
            id="newPassword"
            v-model="newPassword"
            type="password"
            required
            minlength="8"
            placeholder="Mindestens 8 Zeichen, 1 Großbuchstabe, 1 Ziffer"
          />
        </div>

        <div class="form-group">
          <label for="confirmPassword">Passwort bestätigen *</label>
          <input
            id="confirmPassword"
            v-model="confirmPassword"
            type="password"
            required
          />
          <small v-if="mismatch" class="error-hint">
            Passwörter stimmen nicht überein.
          </small>
        </div>

        <button
          type="submit"
          class="btn-primary"
          :disabled="loading || mismatch || !isValid"
        >
          <span v-if="loading" class="spinner"></span>
          {{ loading ? 'Wird gespeichert...' : 'Passwort speichern' }}
        </button>

        <div v-if="error" class="error-message">❌ {{ error }}</div>
        <div v-if="success" class="success-message">
          ✅ Passwort geändert! Sie können sich jetzt <a href="/login">anmelden</a>.
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const token = route.query.token || ''

const checking = ref(true)
const tokenValid = ref(false)
const tokenError = ref('')
const maskedEmail = ref('')
const fullName = ref('')

const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const error = ref('')
const success = ref(false)

const mismatch = computed(() =>
  newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value
)
const isValid = computed(() =>
  newPassword.value.length >= 8 &&
  /[A-Z]/.test(newPassword.value) &&
  /[0-9]/.test(newPassword.value)
)

onMounted(async () => {
  if (!token) {
    checking.value = false
    tokenError.value = 'Kein Token vorhanden.'
    return
  }
  try {
    const res = await fetch('/api/employee_verify_reset_token.php?token=' + encodeURIComponent(token))
    const data = await res.json()
    tokenValid.value = !!data.valid
    if (!data.valid) tokenError.value = data.message || 'Token ungültig.'
    else {
      maskedEmail.value = data.email_masked
      fullName.value = data.full_name || ''
    }
  } catch (e) {
    tokenError.value = 'Netzwerkfehler.'
  } finally {
    checking.value = false
  }
})

async function submit() {
  if (mismatch.value || !isValid.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/employee_reset_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, newPassword: newPassword.value }),
    })
    const data = await res.json()
    if (data.success) {
      success.value = true
    } else {
      error.value = data.message
    }
  } catch {
    error.value = 'Netzwerkfehler.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.reset-page {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #f6f8fb 0%, #eef2f9 100%); padding: 1rem;
}
.reset-card {
  width: min(480px, 100%); padding: 2rem; background: #fff;
  border-radius: 20px; box-shadow: 0 25px 60px rgba(15,23,42,.12);
}
.reset-header h1 { margin: 0 0 1.5rem; font-size: 1.4rem; color: #0f172a; }
.state-box { text-align: center; padding: 2rem 0; }
.state-box.error { color: #991b1b; }
.state-box h3 { margin: 1rem 0 .5rem; }
.hint { color: #64748b; font-size: .85rem; margin-top: 1rem; }
.welcome { background: #f8fafc; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .9rem; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: .4rem; font-size: .85rem; font-weight: 600; color: #334155; }
.form-group input {
  width: 100%; height: 46px; padding: 0 .9rem; border: 1px solid #cbd5e1;
  border-radius: 10px; font-size: .92rem;
}
.form-group input:focus {
  outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.error-hint { display: block; margin-top: .3rem; font-size: .75rem; color: #dc2626; }
.btn-primary {
  width: 100%; height: 48px; border: none; border-radius: 10px;
  background: #2563eb; color: #fff; font-weight: 650; font-size: .95rem;
  cursor: pointer; margin-top: .5rem;
}
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.error-message, .success-message {
  margin-top: 1rem; padding: .75rem 1rem; border-radius: 10px; font-size: .88rem;
}
.error-message { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
.success-message { background: #ecfdf5; color: #065f46; border: 1px solid #86efac; }
.success-message a { color: #065f46; font-weight: 700; }
.spinner {
  display: inline-block; width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.3); border-top-color: #fff;
  border-radius: 50%; animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>