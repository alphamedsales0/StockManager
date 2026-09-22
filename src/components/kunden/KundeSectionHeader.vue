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
  border-bottom: 1px solid #e5eaf0;
}
.section-header-blue   { background: linear-gradient(90deg, #f0f7fc 0%, #ffffff 100%); }
.section-header-green  { background: linear-gradient(90deg, #f0faf4 0%, #ffffff 100%); }
.section-header-purple { background: linear-gradient(90deg, #f6f2ff 0%, #ffffff 100%); }
.section-header-orange { background: linear-gradient(90deg, #fff6ec 0%, #ffffff 100%); }
.section-header-red    { background: linear-gradient(90deg, #fdf0f0 0%, #ffffff 100%); }

.section-icon {
  width: 34px; height: 34px; flex: 0 0 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 6px;
}
.section-icon.blue   { color: #191b1d; background: #e8f4fb; }
.section-icon.green  { color: #159447; background: #e8f8ef; }
.section-icon.purple { color: #7655c8; background: #f0ebff; }
.section-icon.orange { color: #d87818; background: #fff2e4; }
.section-icon.red    { color: #c62828; background: #fdecea; }

.section-title {
  color: #172033; font-size: 0.92rem; font-weight: 700;
  letter-spacing: -0.01em; line-height: 1.2;
}
.section-subtitle {
  margin-top: 1px; color: #8a94a4; font-size: 0.72rem; line-height: 1.2;
}

@media (max-width: 600px) {
  .section-header { padding: 9px 10px; }
  .section-icon { width: 30px; height: 30px; flex-basis: 30px; }
}
</style>