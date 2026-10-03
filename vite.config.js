// vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  // ✅ Racine — car le sous-domaine sert /stockmanager à la racine
  base: '/',

  plugins: [vue()],

  resolve: {
    alias: { '@': path.resolve(__dirname, './src') }
  },

  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'https://alpha-med-care.com',
        changeOrigin: true,
        secure: false
      },
      '/uploads': {
        target: 'https://alpha-med-care.com',
        changeOrigin: true,
        secure: false
      }
    }
  },

  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true
  }
})