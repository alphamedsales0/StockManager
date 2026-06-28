<template>
  <v-card v-if="ticket" class="comment-card glass-effect" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-history</v-icon>
      Aktivitäten & Kommentare
      <v-chip size="small" color="white" text-color="primary" class="ml-2">
        {{ totalCount }}
      </v-chip>
    </v-card-title>
    <v-divider />
    <v-card-text>
      <div class="timeline">
        <transition-group name="timeline-item">
          <div v-for="activity in displayedActivities" :key="activity.id" class="timeline-item">
            <div class="timeline-dot" :style="{ backgroundColor: activity.color }"></div>
            <div class="timeline-content">
              <div class="d-flex align-center mb-2">
                <v-avatar :color="activity.avatarColor" size="36" class="mr-3">
                  <span class="text-caption font-weight-bold">{{ getInitials(activity.author) }}</span>
                </v-avatar>
                <div>
                  <div class="font-weight-bold">{{ activity.author }}</div>
                  <div class="text-caption text-grey">{{ formatDateTime(activity.created_at) }}</div>
                </div>
              </div>
              <div class="activity-text">
                <v-icon v-if="activity.type === 'status'" size="16" class="mr-1">mdi-tune</v-icon>
                <v-icon v-else-if="activity.type === 'comment'" size="16" class="mr-1">mdi-comment</v-icon>
                {{ activity.text }}
              </div>
            </div>
          </div>
        </transition-group>
      </div>

      <!-- Bouton "Voir plus / moins" (visible seulement si > 5 activités) -->
      <div v-if="totalCount > 5" class="d-flex justify-center mt-4">
        <v-btn variant="text" color="primary" @click="toggleShowAll" :prepend-icon="showAll ? 'mdi-chevron-up' : 'mdi-chevron-down'">
          {{ showAll ? 'Weniger anzeigen' : `Alle ${totalCount} anzeigen` }}
        </v-btn>
      </div>

      <v-divider class="my-6" />
      <div class="text-subtitle-1 font-weight-bold mb-4">Neuen Kommentar hinzufügen</div>
      <v-textarea v-model="newComment" label="Kommentar eingeben..." rows="4" variant="outlined" auto-grow />
      <div class="d-flex justify-end mt-4">
        <v-btn color="primary" size="large" :disabled="!newComment.trim()" @click="addComment">
          <v-icon start>mdi-send</v-icon> Kommentar speichern
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  ticket: Object,
  activities: Array,
  darkMode: Boolean,
})

const emit = defineEmits(['addComment'])

const newComment = ref('')
const showAll = ref(false)

// Total des activités
const totalCount = computed(() => props.activities.length)

// Activités affichées : soit les 5 premières (les plus récentes), soit tout si showAll
const displayedActivities = computed(() => {
  if (showAll.value || totalCount.value <= 5) {
    return props.activities
  }
  return props.activities.slice(0, 5)
})

// Basculer l'affichage complet / limité
const toggleShowAll = () => {
  showAll.value = !showAll.value
}

// Ajouter un commentaire
const addComment = () => {
  if (!newComment.value.trim()) return
  emit('addComment', newComment.value)
  newComment.value = ''
}

// Formater la date
const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// Initiales de l'auteur
const getInitials = (name) => {
  return name?.split(' ').map(word => word.charAt(0)).join('').slice(0, 2).toUpperCase()
}
</script>