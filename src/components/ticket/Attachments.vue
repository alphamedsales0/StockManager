<!-- components/ticket/Attachments.vue -->
<template>
  <v-card v-if="ticket" class="info-card glass-effect mb-5" :class="{ 'dark-glass': darkMode }">
    <v-card-title class="section-header gradient-bg">
      <v-icon start color="white">mdi-paperclip</v-icon>
      Anhänge ({{ attachments.length }})
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-list class="transparent-list">
        <v-list-item v-for="file in attachments" :key="file.id">
          <template #prepend><v-icon>mdi-file</v-icon></template>
          <div>
            <div class="item-label">{{ file.name }}</div>
            <div class="item-value">{{ formatFileSize(file.size) }}</div>
          </div>
          <template #append>
            <v-btn icon variant="text" @click="download(file)">
              <v-icon>mdi-download</v-icon>
            </v-btn>
          </template>
        </v-list-item>
        <v-list-item v-if="!attachments.length">
          <div class="text-grey text-center py-4">Keine Anhänge vorhanden</div>
        </v-list-item>
      </v-list>
      <v-file-input
        v-model="newFiles"
        label="Dateien anhängen"
        multiple
        variant="outlined"
        density="comfortable"
        class="mt-4"
      />
      <v-btn color="primary" block @click="upload" :loading="uploading" :disabled="!newFiles?.length">
        Hochladen
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  ticket: Object,
  attachments: Array,
  darkMode: Boolean,
})

const emit = defineEmits(['upload', 'download'])

const newFiles = ref([])
const uploading = ref(false)

const upload = () => {
  if (!newFiles.value.length) return
  emit('upload', newFiles.value)
  newFiles.value = []
}

const download = (file) => {
  emit('download', file)
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>