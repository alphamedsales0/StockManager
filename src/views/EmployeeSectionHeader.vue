<template>
  <div class="section-header" :class="`section-header-${color}`">
    <div class="section-icon" :class="color">
      <v-icon size="18">{{ icon }}</v-icon>
    </div>
    <div>
      <div class="section-title">{{ title }}</div>
      <div v-if="subtitle" class="section-subtitle">{{ subtitle }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, required: true },
  color: { type: String, default: 'blue' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' }
})
</script>
<style scoped>
.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid #eef2f7;
  background-color: #ffffff !important;
  background-image: none !important;
  color: #172033 !important;
}

/* Dégradés clairs en plus du fond blanc */
.section-header-blue   { background-image: linear-gradient(90deg, #fbfdff 0%, #ffffff 100%) !important; }
.section-header-green  { background-image: linear-gradient(90deg, #fbfefc 0%, #ffffff 100%) !important; }
.section-header-purple { background-image: linear-gradient(90deg, #fdfbff 0%, #ffffff 100%) !important; }
.section-header-orange { background-image: linear-gradient(90deg, #fffdf9 0%, #ffffff 100%) !important; }

.section-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.section-icon.blue   { color: #3a7ca5 !important; background-color: #f0f8fd !important; }
.section-icon.green  { color: #2aa15c !important; background-color: #f0faf5 !important; }
.section-icon.purple { color: #8a6fd4 !important; background-color: #f7f4ff !important; }
.section-icon.orange { color: #e08a2e !important; background-color: #fff7ef !important; }

.section-title {
  color: #172033 !important;
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.section-subtitle {
  margin-top: 1px;
  color: #9aa3b2 !important;
  font-size: 0.72rem;
  line-height: 1.2;
}

@media (max-width: 600px) {
  .section-header { padding: 9px 10px; }
  .section-icon { width: 30px; height: 30px; flex-basis: 30px; }
}
</style>