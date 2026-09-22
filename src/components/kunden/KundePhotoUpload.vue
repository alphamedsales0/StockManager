<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-camera-account</v-icon>
      Kunden Foto
    </v-card-title>
    <v-card-text class="text-center">
      <div class="photo-preview mb-5">
        <img
          v-if="photoPreview || currentPhoto"
          :src="photoPreview || currentPhoto"
          alt="Kunden Foto"
          class="photo-img"
        />
        <div v-else class="photo-placeholder">
          <v-icon size="90" color="grey">mdi-account</v-icon>
        </div>
      </div>
      <div class="photo-actions">
        <v-file-input
          label="Foto auswählen"
          accept="image/png, image/jpeg, image/jpg, image/webp"
          variant="outlined"
          prepend-inner-icon="mdi-camera"
          density="comfortable"
          hide-details
          :error-messages="errorMessage"
          @change="selectPhoto"
          class="mb-2"
        />
        <v-btn
          v-if="photoPreview"
          variant="text"
          color="error"
          size="small"
          prepend-icon="mdi-close"
          @click="cancelSelection"
        >
          Auswahl verwerfen
        </v-btn>
      </div>
      <p class="text-caption text-grey mt-2">JPG, PNG, WebP — max. 5 MB</p>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

const props = defineProps({
  existingPhoto: { type: String, default: null }
})

const photoFile    = ref(null)
const photoPreview = ref(null)
const errorMessage = ref('')

const currentPhoto = computed(() => props.existingPhoto || null)

const selectPhoto = (event) => {
  let file = null
  if (event instanceof Event) file = event.target?.files?.[0] || null
  else if (Array.isArray(event)) file = event[0] || null
  else if (event instanceof File) file = event

  if (!file) return
  errorMessage.value = ''

  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    errorMessage.value = 'Die Datei ist zu groß (max. 5 MB).'
    return
  }

  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    errorMessage.value = 'Nur JPG, PNG oder WebP sind erlaubt.'
    return
  }

  photoFile.value = file
  if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
  photoPreview.value = URL.createObjectURL(file)
}

const cancelSelection = () => {
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
    photoPreview.value = null
  }
  photoFile.value = null
  errorMessage.value = ''
}

const reset = () => {
  if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
  photoFile.value    = null
  photoPreview.value = null
  errorMessage.value = ''
}

onUnmounted(() => {
  if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
})

defineExpose({ photoFile, photoPreview, reset })
</script>

<style scoped>
.photo-preview { display: flex; justify-content: center; }
.photo-img, .photo-placeholder {
  width: 160px; height: 160px;
  object-fit: cover; object-position: center;
  border-radius: 0;
  border: 2px solid #e2e8f0;
  background: #f8fafc;
  display: flex; align-items: center; justify-content: center;
}
.photo-actions { max-width: 400px; margin: 0 auto; }
</style>