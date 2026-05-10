import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'https://alpha-med-care.com',
        changeOrigin: true,
        secure: true,
        rewrite: (path) => path, // Pfad unverändert lassen (bleibt /api/...)
      }
    }
  }
})