<template>
  <v-container fluid class="access-denied-container">
    <v-row justify="center" align="center" class="fill-height">
      <v-col cols="12" md="6" class="text-center">
        <v-icon size="120" color="error" class="mb-4">
          mdi-shield-alert-outline
        </v-icon>

        <h1 class="text-h4 font-weight-bold mb-3">
          Zugriff verweigert
        </h1>

        <p class="text-body-1 text-grey-darken-1 mb-6">
          Sie haben keine Berechtigung, auf diesen Bereich zuzugreifen.<br>
          Bitte wenden Sie sich an Ihren Administrator.
        </p>

        <div class="d-flex justify-center gap-2 flex-wrap">
          <v-btn
            color="primary"
            variant="elevated"
            prepend-icon="mdi-home"
            @click="goHome"
          >
            Zur Startseite
          </v-btn>
          <v-btn
            variant="outlined"
            prepend-icon="mdi-logout"
            @click="handleLogout"
          >
            Abmelden
          </v-btn>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { usePermissionStore } from '@/stores/permission'

const router = useRouter()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()

const goHome = () => {
  const priority = [
    { path: '/', perm: 'dashboard' },
    { path: '/tickets', perm: 'tickets' },
    { path: '/products', perm: 'products' },
    { path: '/customers', perm: 'customers' },
    { path: '/employees', perm: 'employees' },
    { path: '/quotes', perm: 'orders' },
    { path: '/help', perm: 'help' },
  ]
  for (const item of priority) {
    if (permissionStore.can(item.perm)) {
      router.push(item.path)
      return
    }
  }
  router.push('/login')
}

const handleLogout = async () => {
  try {
    await axios.post('/api/auth_stock.php?action=logout', {}, { withCredentials: true })
  } catch (err) {
    console.warn(err)
  } finally {
    authStore.logout?.()
    permissionStore.clearRole?.()
    router.push('/login')
  }
}
</script>

<style scoped>
.access-denied-container {
  min-height: 100vh;
  background: #f8fafc;
  padding: 24px;
}
.fill-height {
  min-height: calc(100vh - 48px);
}
.gap-2 { gap: 8px; }
</style>