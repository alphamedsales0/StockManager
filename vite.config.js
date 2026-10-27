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
    alias: { '@': path.resolve(__dirname, './src') }
  },
  server: {
    host: 'localhost',
    port: 5173,
    headers: { 'Cache-Control': 'no-store' },
    proxy: {
      '/api': {
        target: apiTarget,
        changeOrigin: true,
        secure: true,
        ws: false,
        cookieDomainRewrite: {
          '.alpha-med-care.com': 'localhost',
          'stckmng.alpha-med-care.com': 'localhost',
          'alpha-med-care.com': 'localhost'
        },
        cookiePathRewrite: '/',
        configure(proxy) {
          proxy.on('proxyReq', (proxyReq, req) => {
            proxyReq.setHeader('Origin', apiTarget)
            proxyReq.setHeader('Referer', apiTarget + '/')
            console.log('[API PROXY] →', req.method, req.url)
          })
          proxy.on('proxyRes', (proxyRes, req) => {
            console.log('[API PROXY] ←', req.url, '→', proxyRes.statusCode)
            const setCookie = proxyRes.headers['set-cookie']
            if (setCookie) console.log('[API PROXY] 🍪', JSON.stringify(setCookie))
          })
          proxy.on('error', (err, req) => {
            console.error('[API PROXY] ❌', req.url, err.message)
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