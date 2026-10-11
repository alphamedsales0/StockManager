import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const apiTarget = 'https://stckmng.alpha-med-care.com'

export default defineConfig({
  base: '/',
  plugins: [vue()],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },

  server: {
    host: 'localhost',
    port: 5173,

    // ✅ Désactive le cache pour le HTML de l'app
    headers: {
      'Cache-Control': 'no-store'
    },

    proxy: {
      '/api': {
        target: apiTarget,
        changeOrigin: true,
        secure: true,
        cookieDomainRewrite: 'localhost',
        cookiePathRewrite: '/',

        configure(proxy) {
          proxy.on('proxyReq', (proxyReq, req) => {
            console.log('[API PROXY] Anfrage:', req.url)
          })

          proxy.on('proxyRes', (proxyRes, req) => {
            console.log('[API PROXY] Antwort:', req.url)
            console.log('[API PROXY] Status:', proxyRes.statusCode)
            console.log(
              '[API PROXY] Content-Type:',
              proxyRes.headers['content-type']
            )
          })

          proxy.on('error', (err, req) => {
            console.error('[API PROXY] Fehler:', req.url, err.message)
          })
        }
      },

      '/uploads': {
        target: apiTarget,
        changeOrigin: true,
        secure: true
      }
    }
  },

  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true
  }
})