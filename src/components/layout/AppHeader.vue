<template>
  <v-app-bar color="white" elevation="1" density="comfortable">
    <v-app-bar-nav-icon 
      @click.stop="uiStore.toggleRail" 
      variant="text"
      size="default"
    />

    <v-text-field
      density="compact"
      variant="outlined"
      rounded="pill"
      placeholder="Search for datas & reports..."
      prepend-inner-icon="mdi-magnify"
      hide-details
      class="search-field"
    />

    <v-spacer />

    <div class="d-flex align-center ga-3">
      <!-- Messages -->
      <v-menu v-model="menuMsg" :close-on-content-click="false" location="bottom end">
        <template v-slot:activator="{ props }">
          <v-badge color="error" content="1" overlap offset-x="3" offset-y="3">
            <v-btn icon="mdi-message-text" v-bind="props" variant="text" density="comfortable"></v-btn>
          </v-badge>
        </template>
        <v-card width="300">
          <v-card-title class="text-subtitle-1">You have 2 new messages</v-card-title>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item>
              <template v-slot:prepend>
                <v-avatar size="32"><img src="https://randomuser.me/api/portraits/women/68.jpg"></v-avatar>
              </template>
              <v-list-item-title class="text-body-2">Michelle Moreno</v-list-item-title>
              <v-list-item-subtitle class="text-caption">Have sent a photo · 3 min ago</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <template v-slot:prepend>
                <v-avatar size="32"><img src="https://randomuser.me/api/portraits/men/32.jpg"></v-avatar>
              </template>
              <v-list-item-title class="text-body-2">Diane Myers</v-list-item-title>
              <v-list-item-subtitle class="text-caption">You are now connected · Yesterday</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-card-actions><v-btn text size="small">View all messages</v-btn></v-card-actions>
        </v-card>
      </v-menu>

      <!-- Email -->
      <v-menu v-model="menuEmail" location="bottom end">
        <template v-slot:activator="{ props }">
          <v-badge color="error" content="1" overlap offset-x="3" offset-y="3">
            <v-btn icon="mdi-email" v-bind="props" variant="text" density="comfortable"></v-btn>
          </v-badge>
        </template>
        <v-card width="300">
          <v-card-title class="text-subtitle-1">You have 3 New Emails</v-card-title>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item>
              <v-list-item-title class="text-body-2">Meeting about dashboard</v-list-item-title>
              <v-list-item-subtitle class="text-caption">Cynthia Harvey · 3 min ago</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-card-actions><v-btn text size="small">See all emails</v-btn></v-card-actions>
        </v-card>
      </v-menu>

      <!-- Notifications -->
      <v-menu v-model="menuNotif" location="bottom end">
        <template v-slot:activator="{ props }">
          <v-badge color="error" content="3" overlap offset-x="3" offset-y="3">
            <v-btn icon="mdi-bell" v-bind="props" variant="text" density="comfortable"></v-btn>
          </v-badge>
        </template>
        <v-card width="250">
          <v-card-title class="text-subtitle-1 font-weight-medium">You have 3 Notifications</v-card-title>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item>
              <v-list-item-title class="text-body-2">Email notification</v-list-item-title>
              <v-list-item-subtitle class="text-caption">January 15, 2025</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-card-actions>
            <v-btn text size="small">All notifications</v-btn>
          </v-card-actions>
        </v-card>
      </v-menu>

      <!-- Account Dropdown avec avatar, email et actions -->
      <v-menu v-model="menuAccount" location="bottom end" :close-on-content-click="false">
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" variant="text" class="text-none" density="comfortable">
            <v-avatar size="36" :image="user.avatar"></v-avatar>
            <span class="ml-2">{{ user.name }} <v-icon icon="mdi-chevron-down" size="small"></v-icon></span>
          </v-btn>
        </template>

        <v-card width="280" class="account-dropdown">
          <!-- En-tête avec info utilisateur -->
          <div class="d-flex pa-4 align-center">
            <v-avatar size="48" :image="user.avatar" class="mr-3"></v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-medium">{{ user.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ user.email }}</div>
            </div>
          </div>
          <v-divider></v-divider>

          <!-- Actions du compte -->
          <v-list density="compact" nav>
            <v-list-item prepend-icon="mdi-account" title="Account" value="account" @click="menuAccount = false">
            </v-list-item>
            <v-list-item prepend-icon="mdi-cog" title="Setting" value="settings" @click="menuAccount = false">
            </v-list-item>
            <v-list-item prepend-icon="mdi-currency-usd" title="Billing" value="billing" @click="menuAccount = false">
            </v-list-item>
          </v-list>
          <v-divider></v-divider>

          <!-- Déconnexion -->
          <div class="pa-2">
            <v-btn block variant="text" color="error" prepend-icon="mdi-logout" @click="logout">
              Logout
            </v-btn>
          </div>
        </v-card>
      </v-menu>
    </div>
  </v-app-bar>
</template>

<script setup>
import { ref } from 'vue'
import { useUiStore } from '../../stores/uiStore'

const uiStore = useUiStore()

// État des menus
const menuMsg = ref(false)
const menuEmail = ref(false)
const menuNotif = ref(false)
const menuAccount = ref(false)

// Données utilisateur (mock, à remplacer par les vraies données du store ou API)
const user = ref({
  name: 'john doe',
  email: 'johndoe@example.com',
  avatar: 'https://randomuser.me/api/portraits/men/1.jpg'  // image d'exemple
})

// Action de déconnexion (à adapter selon votre logique)
const logout = () => {
  console.log('Déconnexion...')
  menuAccount.value = false
  // Exemple : redirection ou appel au store d'auth
  // router.push('/login')
}
</script>

<style scoped>
.search-field {
  max-width: 260px;
}

:deep(.v-badge__badge) {
  font-size: 11px;
  min-width: 18px;
  height: 18px;
  line-height: 18px;
  padding: 0 4px;
}

/* Ajustement optionnel du dropdown */
.account-dropdown :deep(.v-list-item__prepend) {
  margin-right: 12px;
}
</style>