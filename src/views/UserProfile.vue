<!-- src/views/UserProfile.vue -->
<template>
  <v-container max-width="720" class="py-8">
    <v-card rounded="lg" elevation="2">
      <!-- En-tête avec avatar -->
      <v-card-item class="pa-6">
        <div class="d-flex align-center">
          <v-avatar size="72" color="grey-darken-3">
            <span class="text-white text-h5">{{ userInitials }}</span>
          </v-avatar>
          <div class="ml-4">
            <v-card-title class="text-h5 font-weight-bold pa-0">
              {{ displayName }}
            </v-card-title>
            <v-card-subtitle class="pa-0 mt-1">
              {{ authStore.user?.email || 'Keine E-Mail' }}
            </v-card-subtitle>
          </div>
        </div>
      </v-card-item>

      <v-divider />

      <!-- Détails du profil -->
      <v-card-text class="pa-6">
        <v-list lines="two">
          <v-list-item prepend-icon="mdi-account">
            <v-list-item-title>Name</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.name || 'Nicht angegeben' }}
            </v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-email">
            <v-list-item-title>E-Mail</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.email || 'Nicht angegeben' }}
            </v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-shield-account">
            <v-list-item-title>Rolle</v-list-item-title>
            <v-list-item-subtitle>
              {{ authStore.user?.role || 'Benutzer' }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn
          variant="text"
          color="primary"
          prepend-icon="mdi-arrow-left"
          @click="$router.back()"
        >
          Zurück
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const displayName = computed(
  () => authStore.user?.name || authStore.user?.email || 'Gast',
)

const userInitials = computed(() => {
  const u = authStore.user
  if (!u) return '?'
  const name = (u.name || '').trim()
  if (!name) return u.email?.charAt(0).toUpperCase() || '?'
  const parts = name.split(/\s+/)
  if (parts.length > 1) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})
</script>