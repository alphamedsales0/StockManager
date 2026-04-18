<template>
  <v-app>
    <!-- Sidebar avec rail dynamique -->
    <Sidebar v-if="isAuthenticated" />
    
    <!-- App Bar -->
    <AppHeader v-if="isAuthenticated" />
    
    <!-- Main Content : prend tout l'espace restant -->
    <v-main>
      <router-view />
    </v-main>
    
    <NotificationsPanel v-if="isAuthenticated" />
    <AppFooter v-if="isAuthenticated && showFooter" />
  </v-app>
</template>

<script setup>

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from './components/layout/Sidebar.vue'
import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import NotificationsPanel from './components/common/NotificationsPanel.vue'

const route = useRoute()

const isAuthenticated = computed(() => {
  const user = localStorage.getItem('currentUser')
  return user !== null && user !== undefined
})

const showFooter = computed(() => {
  const hideOnRoutes = ['login']
  return !hideOnRoutes.includes(route.name)
})

</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  font-family: 'Roboto', sans-serif;
  background-color: #f5f5f5;
}

.v-application,
.v-app-bar {
  padding-left: 0 !important;
  margin-left: 0 !important;
}

</style>