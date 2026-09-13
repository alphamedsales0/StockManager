import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  // 🔑 BASE URL pour le déploiement dans un sous-dossier
  base: '/stockmanager/',

  plugins: [
    vue()
  ],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
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
    // S'assurer que les assets sont bien générés dans /stockmanager/assets/
    outDir: 'dist',
    assetsDir: 'assets',
    // Important pour les sous-dossiers
    emptyOutDir: true
  }
})