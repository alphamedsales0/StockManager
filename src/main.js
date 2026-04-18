// src/main.js
import { createApp } from 'vue'
import App from './App.vue'

// Vuetify 3
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

import router from './router'  // add this

import { createPinia } from 'pinia'

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
const pinia = createPinia()

app.use(router)   // add this
app.use(pinia)
app.use(vuetify)
app.mount('#app')