<template>
  <v-card rounded="lg" elevation="3" class="pa-0 activity-panel">
    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4 px-4 py-3">
      <span class="text-h6 font-weight-semibold">
        <v-icon start size="20">mdi-history</v-icon>
        Letzte Aktivitäten
        <v-chip
          v-if="activities.length"
          size="x-small"
          color="primary"
          variant="tonal"
          class="ml-2"
        >
          {{ activities.length }}
        </v-chip>
      </span>

      <div class="d-flex align-center ga-1">
        <v-btn
          size="small"
          variant="text"
          color="primary"
          prepend-icon="mdi-arrow-right"
          class="view-all-btn"
          @click="goToAllActivities"
        >
          Alle anzeigen
        </v-btn>

        <v-btn
          icon
          variant="text"
          size="small"
          :color="loading ? 'primary' : 'grey-darken-1'"
          :disabled="loading"
          @click="refreshActivities"
        >
          <v-icon size="20" :class="{ 'spin-icon': loading }">
            {{ loading ? 'mdi-loading' : 'mdi-refresh' }}
          </v-icon>
          <v-tooltip activator="parent" location="top">
            {{ loading ? 'Aktualisiere...' : 'Aktualisieren' }}
          </v-tooltip>
        </v-btn>
      </div>
    </v-card-title>

    <!-- LOADING SKELETON -->
    <div v-if="loading && (showSkeleton || !activities.length)" class="pa-4">
      <div v-for="n in MAX_DISPLAY" :key="n" class="skeleton-item">
        <div class="skeleton-dot" />
        <div class="skeleton-content">
          <div class="skeleton-line skeleton-line-title" />
          <div class="skeleton-line skeleton-line-time" />
        </div>
      </div>
    </div>

    <!-- ERREUR -->
    <div v-else-if="error" class="pa-6 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 text-grey-darken-1 mt-2 mb-3">{{ error }}</p>
      <v-btn size="small" variant="tonal" color="primary" @click="refreshActivities">
        Erneut versuchen
      </v-btn>
    </div>

    <!-- VIDE -->
    <div v-else-if="!displayedActivities.length" class="pa-8 text-center">
      <v-icon size="56" color="grey-lighten-1">mdi-history</v-icon>
      <p class="text-body-2 text-grey-darken-1 mt-2 mb-0">
        Keine Aktivitäten vorhanden
      </p>
    </div>

    <!-- TIMELINE (4 max) -->
    <div
      v-else
      class="timeline-container pa-4"
      :class="{ 'is-refreshing': loading && !showSkeleton }"
    >
      <transition-group name="list" tag="div">
        <div
          v-for="(activity, idx) in displayedActivities"
          :key="activityKey(activity)"
          class="timeline-item"
          @click="goToActivity(activity)"
        >
          <div v-if="idx !== displayedActivities.length - 1" class="timeline-line"></div>

          <div class="timeline-dot" :class="activity.color">
            <v-icon size="24" color="white">{{ activity.icon }}</v-icon>
          </div>

          <div class="timeline-content">
            <div class="activity-card">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center flex-grow-1" style="min-width: 0;">
                  <span class="text-subtitle-2 font-weight-medium text-truncate">
                    {{ activity.title }}
                  </span>
                  <v-chip
                    v-if="activity.new"
                    size="x-small"
                    color="red"
                    class="ml-2 flex-shrink-0"
                    label
                    variant="flat"
                  >
                    NEW
                  </v-chip>
                </div>
                <v-icon size="18" color="grey-lighten-1" class="ml-2 flex-shrink-0">
                  mdi-chevron-right
                </v-icon>
              </div>
              <div class="d-flex align-center mt-1">
                <v-icon size="14" color="grey" class="mr-1">mdi-clock-outline</v-icon>
                <span class="text-caption text-grey">
                  {{ formatRelativeTime(activity.time) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </transition-group>

      <!-- ✅ "+ X weitere" -->
      <div v-if="activities.length > MAX_DISPLAY" class="more-indicator">
        <v-btn
          variant="text"
          size="small"
          color="primary"
          block
          @click="goToAllActivities"
        >
          <v-icon start size="16">mdi-plus</v-icon>
          {{ activities.length - MAX_DISPLAY }} weitere Aktivitäten anzeigen
        </v-btn>
      </div>
    </div>

    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="2500"
      location="bottom right"
      variant="tonal"
    >
      <div class="d-flex align-center gap-2">
        <v-icon size="18">
          {{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
        </v-icon>
        {{ snackbar.text }}
      </div>
    </v-snackbar>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// ============================================================
// CONFIG
// ============================================================
const MAX_DISPLAY = 4          // ✅ 4 activités affichées
const FETCH_LIMIT = 20         // Récupère plus pour avoir le compteur "+ X weitere"
const POLL_INTERVAL = 60000

// ============================================================
// STATE
// ============================================================
const activities = ref([])
const loading = ref(false)
const error = ref(null)
const showSkeleton = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

let pollInterval = null

// ============================================================
// COMPUTED
// ============================================================
const displayedActivities = computed(() => activities.value.slice(0, MAX_DISPLAY))

// ============================================================
// KEY UNIQUE
// ============================================================
const activityKey = (activity) => {
  return `${activity.type}__${activity.time}__${activity.title}`
}

// ============================================================
// LOAD
// ============================================================
const loadActivities = async (opts = {}) => {
  const { showFeedback = false, showSkeletonOnce = false } = opts

  loading.value = true
  error.value = null
  if (showSkeletonOnce) showSkeleton.value = true

  const prevKeys = new Set(activities.value.map(a => activityKey(a)))

  try {
    const res = await axios.get(
      `/api/get_recent_activities.php?limit=${FETCH_LIMIT}`,
      { withCredentials: true }
    )

    if (res.data?.success) {
      const fresh = res.data.activities || []
      const newActivities = fresh.filter(a => !prevKeys.has(activityKey(a)))
      activities.value = fresh

      if (showFeedback) {
        snackbar.value = {
          show: true,
          text: newActivities.length > 0
            ? `${newActivities.length} neue Aktivität${newActivities.length > 1 ? 'en' : ''}`
            : 'Aktivitäten aktualisiert',
          color: 'success'
        }
      }
    } else {
      error.value = res.data?.error || 'Fehler beim Laden'
      if (showFeedback) {
        snackbar.value = { show: true, text: error.value, color: 'error' }
      }
    }
  } catch (e) {
    console.error('Activities error:', e)
    error.value = e.response?.data?.error || 'Verbindungsfehler'
    if (showFeedback) {
      snackbar.value = { show: true, text: error.value, color: 'error' }
    }
  } finally {
    loading.value = false
    showSkeleton.value = false
  }
}

// ============================================================
// REFRESH (feedback + durée min 400ms)
// ============================================================
const refreshActivities = async () => {
  const start = Date.now()
  await loadActivities({
    showFeedback: true,
    showSkeletonOnce: !activities.value.length
  })
  const elapsed = Date.now() - start
  if (elapsed < 400) {
    await new Promise(r => setTimeout(r, 400 - elapsed))
  }
}

// ============================================================
// NAVIGATION
// ============================================================
const goToActivity = (activity) => {
  if (activity.link) {
    router.push(activity.link)
  }
}

const goToAllActivities = () => {
  router.push('/activities')
}

// ============================================================
// RELATIVE TIME
// ============================================================
const formatRelativeTime = (dateStr) => {
  if (!dateStr) return '—'

  const date = new Date(dateStr.replace(' ', 'T'))
  const now = new Date()
  const diffMs = now - date
  const diffSec = Math.floor(diffMs / 1000)
  const diffMin = Math.floor(diffSec / 60)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)

  if (diffSec < 60) return 'Gerade eben'
  if (diffMin < 60) return `Vor ${diffMin} Minute${diffMin > 1 ? 'n' : ''}`
  if (diffHour < 24) return `Vor ${diffHour} Stunde${diffHour > 1 ? 'n' : ''}`

  const isYesterday =
    new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1).toDateString() ===
    new Date(date.getFullYear(), date.getMonth(), date.getDate()).toDateString()
  if (isYesterday) {
    return `Gestern, ${date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })}`
  }

  if (diffDay < 7) return `Vor ${diffDay} Tag${diffDay > 1 ? 'en' : ''}`

  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
  loadActivities()
  pollInterval = setInterval(() => loadActivities(), POLL_INTERVAL)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
/* ============================================================
   BASE
   ============================================================ */
.activity-panel {
  height: 100%;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* ============================================================
   BOUTON "VOIR TOUT"
   ============================================================ */
.view-all-btn {
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 0.78rem !important;
  letter-spacing: 0.2px !important;
}

/* ============================================================
   SPIN REFRESH
   ============================================================ */
.spin-icon {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ============================================================
   TIMELINE
   ============================================================ */
.timeline-container {
  position: relative;
  transition: opacity 0.25s ease;
}

.timeline-container.is-refreshing {
  opacity: 0.5;
  pointer-events: none;
}

.timeline-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  margin-bottom: 28px;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-line {
  position: absolute;
  left: 18px;
  top: 36px;
  bottom: -28px;
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
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.timeline-item:hover .timeline-dot {
  transform: scale(1.08);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.timeline-dot.success { background: linear-gradient(135deg, #43a047, #2e7d32); }
.timeline-dot.info { background: linear-gradient(135deg, #1e88e5, #1565c0); }
.timeline-dot.primary { background: linear-gradient(135deg, #5c6bc0, #3949ab); }
.timeline-dot.warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }
.timeline-dot.error { background: linear-gradient(135deg, #e53935, #c62828); }
.timeline-dot.deep-purple { background: linear-gradient(135deg, #7e57c2, #5e35b1); }
.timeline-dot.deep-orange { background: linear-gradient(135deg, #ff7043, #e64a19); }

/* ============================================================
   CONTENT
   ============================================================ */
.timeline-content {
  margin-left: 16px;
  flex: 1;
  min-width: 0;
}

.activity-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 10px 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
  transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
  border: 1px solid #f0f0f0;
}

.timeline-item:hover .activity-card {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateX(4px);
  border-color: #e0e0e0;
}

.text-grey {
  color: #6b6b6b;
}

/* ============================================================
   "+ X weitere"
   ============================================================ */
.more-indicator {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #e0e0e0;
}

.more-indicator .v-btn {
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 0.8rem !important;
  letter-spacing: 0.2px !important;
}

/* ============================================================
   ANIMATIONS
   ============================================================ */
.list-enter-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-leave-active {
  transition: all 0.3s ease;
  position: absolute;
  width: 100%;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.list-move {
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ============================================================
   SKELETON
   ============================================================ */
.skeleton-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.skeleton-dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  flex-shrink: 0;
}

.skeleton-content {
  flex: 1;
}

.skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  margin-bottom: 8px;
}

.skeleton-line-title {
  width: 80%;
}

.skeleton-line-time {
  width: 40%;
  height: 10px;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 600px) {
  .timeline-item {
    margin-bottom: 20px;
  }

  .activity-card {
    padding: 8px 10px;
  }

  .text-subtitle-2 {
    font-size: 0.82rem !important;
  }

  .view-all-btn {
    font-size: 0.72rem !important;
  }
}
</style>