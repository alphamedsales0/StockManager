// src/main.js
import { createApp } from 'vue'
import App from './App.vue'
import axios from 'axios'

// Vuetify 3
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

import router from './router'
import { createPinia } from 'pinia'
import { useAuthStore } from './stores/auth'
import { usePermissionStore } from './stores/permission'  // ✅ NOUVEAU

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#4272d7',
          secondary: '#f5a623',
          error: '#f5365c',
          success: '#0acf97',
          info: '#00c5dc',
        }
      }
    }
  }
})

const app = createApp(App)

// ✅ Envoie le cookie de session sur TOUTES les requêtes
axios.defaults.withCredentials = true

const pinia = createPinia()

// ✅ Ordre important : pinia AVANT le router
app.use(pinia)

// ✅ Initialise la session AVANT le router
const authStore = useAuthStore()
const permissionStore = usePermissionStore()  // ✅

authStore.initialize()
  .then(() => {
    // ✅ Rétablir le rôle dans le permissionStore
    //    (essentiel après un refresh F5 pour que les permissions soient actives)
    if (authStore.user?.role) {
      permissionStore.setRole(authStore.user.role)
      console.log('✅ Rôle restauré :', authStore.user.role)
    } else {
      console.warn('⚠️ Aucun rôle trouvé après initialize()')
    }
  })
  .catch((err) => {
    console.error('❌ Erreur initialisation auth :', err)
  })
  .finally(() => {
    // Puis router + vuetify + mount
    app.use(router)
    app.use(vuetify)
    app.mount('#app')
  })