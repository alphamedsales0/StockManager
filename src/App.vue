<template>
  <v-app>
    <Sidebar v-if="authStore.isLoggedIn" />
    <AppHeader v-if="authStore.isLoggedIn" />
    
    <v-main>
      <router-view />
    </v-main>
    
    <NotificationsPanel v-if="authStore.isLoggedIn" />
    <AppFooter v-if="authStore.isLoggedIn && showFooter" />
  </v-app>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Sidebar from './components/layout/Sidebar.vue'
import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import NotificationsPanel from './components/common/NotificationsPanel.vue'

const route = useRoute()
const authStore = useAuthStore()

const showFooter = computed(() => {
  const hideOnRoutes = ['login']
  return !hideOnRoutes.includes(route.name)
})

onMounted(async () => {
  await authStore.initialize()
})
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
  font-family: 'Roboto', sans-serif;
  background-color: #f5f5f5;
}
.v-application, .v-app-bar {
  padding-left: 0 !important;
  margin-left: 0 !important;
}
</style>