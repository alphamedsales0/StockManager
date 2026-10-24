<template>
  <v-container fluid class="activities-container">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div class="d-flex align-center gap-2">
        <v-btn
          icon="mdi-arrow-left"
          variant="text"
          size="small"
          @click="$router.push('/')"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <h1 class="text-h5 font-weight-bold mb-0">
          <v-icon start size="24">mdi-history</v-icon>
          Alle Aktivitäten
        </h1>
        <v-chip
          v-if="activities.length"
          color="primary"
          variant="tonal"
          size="small"
        >
          {{ activities.length }}
        </v-chip>
      </div>

      <v-btn
        :loading="loading"
        :disabled="loading"
        color="primary"
        variant="tonal"
        prepend-icon="mdi-refresh"
        size="small"
        @click="loadActivities(true)"
      >
        Aktualisieren
      </v-btn>
    </div>

    <!-- Loading -->
    <div v-if="loading && !activities.length" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="48" />
      <p class="text-body-2 text-grey mt-3">Lade Aktivitäten...</p>
    </div>

    <!-- Erreur -->
    <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
      {{ error }}
    </v-alert>

    <!-- Vide -->
    <div v-else-if="!activities.length" class="text-center py-12">
      <v-icon size="80" color="grey-lighten-1">mdi-history</v-icon>
      <p class="text-h6 mt-3 mb-0">Keine Aktivitäten vorhanden</p>
    </div>

    <!-- Timeline complète -->
    <v-card v-else rounded="lg" elevation="2" class="pa-4">
      <div class="timeline-full">
        <div
          v-for="(activity, idx) in activities"
          :key="`${activity.type}-${activity.time}-${idx}`"
          class="timeline-item"
          @click="goToActivity(activity)"
        >
          <div v-if="idx !== activities.length - 1" class="timeline-line"></div>

          <div class="timeline-dot" :class="activity.color">
            <v-icon size="22" color="white">{{ activity.icon }}</v-icon>
          </div>

          <div class="timeline-content">
            <div class="activity-card">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div class="d-flex align-center flex-grow-1" style="min-width: 0;">
                  <span class="text-body-1 font-weight-medium">
                    {{ activity.title }}
                  </span>
                  <v-chip
                    v-if="activity.new"
                    size="x-small"
                    color="red"
                    class="ml-2"
                    label
                    variant="flat"
                  >
                    NEW
                  </v-chip>
                </div>
                <div class="d-flex align-center">
                  <v-icon size="16" color="grey" class="mr-1">mdi-clock-outline</v-icon>
                  <span class="text-caption text-grey">
                    {{ formatRelativeTime(activity.time) }}
                  </span>
                </div>
              </div>
              <div class="mt-2">
                <v-chip
                  size="x-small"
                  :color="activity.color"
                  variant="tonal"
                  class="text-uppercase"
                >
                  {{ typeLabel(activity.type) }}
                </v-chip>
              </div>
            </div>
          </div>
        </div>
      </div>
    </v-card>

    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="2000"
      location="bottom right"
      variant="tonal"
    >
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const activities = ref([])
const loading = ref(false)
const error = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const loadActivities = async (showFeedback = false) => {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/api/get_recent_activities.php?limit=50', {
      withCredentials: true
    })
    if (res.data?.success) {
      activities.value = res.data.activities || []
      if (showFeedback) {
        snackbar.value = { show: true, text: 'Aktivitäten aktualisiert', color: 'success' }
      }
    } else {
      error.value = res.data?.error || 'Fehler beim Laden'
    }
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.error || 'Verbindungsfehler'
  } finally {
    loading.value = false
  }
}

const goToActivity = (activity) => {
  if (activity.link) {
    router.push(activity.link)
  }
}

const typeLabel = (type) => {
  const map = {
    product_created: 'Produkt',
    ticket_created: 'Ticket',
    ticket_resolved: 'Ticket gelöst',
    customer_created: 'Kunde',
    order_created: 'Bestellung',
    employee_created: 'Mitarbeiter'
  }
  return map[type] || type
}

const formatRelativeTime = (dateStr) => {
  if (!dateStr) return '—'
  const date = new Date(dateStr.replace(' ', 'T'))
  const now = new Date()
  const diffMs = now - date
  const diffMin = Math.floor(diffMs / 60000)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)

  if (diffMs < 60000) return 'Gerade eben'
  if (diffMin < 60) return `Vor ${diffMin} Min.`
  if (diffHour < 24) return `Vor ${diffHour} Std.`
  if (diffDay < 7) return `Vor ${diffDay} Tag${diffDay > 1 ? 'en' : ''}`

  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => loadActivities())
</script>

<style scoped>
.activities-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 24px 16px;
}

.timeline-full .timeline-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  margin-bottom: 24px;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.timeline-full .timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-line {
  position: absolute;
  left: 18px;
  top: 36px;
  bottom: -24px;
  width: 2px;
  background: linear-gradient(to bottom, #bdbdbd, #e0e0e0);
  border-radius: 2px;
}

.timeline-dot {
  position: relative;
  z-index: 2;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  transition: transform 0.2s ease;
}

.timeline-item:hover .timeline-dot {
  transform: scale(1.1);
}

.timeline-dot.success { background: linear-gradient(135deg, #43a047, #2e7d32); }
.timeline-dot.info { background: linear-gradient(135deg, #1e88e5, #1565c0); }
.timeline-dot.primary { background: linear-gradient(135deg, #5c6bc0, #3949ab); }
.timeline-dot.warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }
.timeline-dot.error { background: linear-gradient(135deg, #e53935, #c62828); }
.timeline-dot.deep-purple { background: linear-gradient(135deg, #7e57c2, #5e35b1); }
.timeline-dot.deep-orange { background: linear-gradient(135deg, #ff7043, #e64a19); }

.timeline-content {
  margin-left: 16px;
  flex: 1;
  min-width: 0;
}

.activity-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f0f0f0;
  transition: all 0.2s ease;
}

.timeline-item:hover .activity-card {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateX(4px);
  border-color: #e0e0e0;
}
</style>