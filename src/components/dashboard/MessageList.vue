<template>
  <v-card rounded="lg" elevation="2">
    <v-card-title><v-icon icon="mdi-message-text" class="mr-2"></v-icon> New Messages</v-card-title>
    <v-divider></v-divider>
    <v-card-text>
      <v-list>
        <v-list-item v-for="msg in visibleMessages" :key="msg.name" :prepend-avatar="msg.avatar">
          <v-list-item-title>{{ msg.name }}</v-list-item-title>
          <v-list-item-subtitle>{{ msg.message }}</v-list-item-subtitle>
          <template v-slot:append><span class="text-caption text-grey">{{ msg.time }}</span></template>
        </v-list-item>
      </v-list>
      <div class="text-center mt-3" v-if="messages.length > visibleCount">
        <v-btn variant="tonal" rounded="pill" @click="loadMore">load more</v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'
const messages = ref([
  { name: "John Smith", message: "Have sent a photo", time: "12 Min ago", avatar: "https://randomuser.me/api/portraits/men/10.jpg" },
  { name: "Nicholas Martinez", message: "You are now connected", time: "11:00 PM", avatar: "https://randomuser.me/api/portraits/men/22.jpg" },
  { name: "Michelle Sims", message: "Lorem ipsum dolor sit amet", time: "Yesterday", avatar: "https://randomuser.me/api/portraits/women/44.jpg" }
])
const visibleCount = ref(2)
const visibleMessages = computed(() => messages.value.slice(0, visibleCount.value))
const loadMore = () => { visibleCount.value += 2 }
</script>