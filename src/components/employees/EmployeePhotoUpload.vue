<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-camera-account</v-icon>
      Mitarbeiter Foto
    </v-card-title>
    <v-card-text class="text-center">
      <v-avatar size="160" color="grey-lighten-3" class="mb-5">
        <img
          v-if="photoPreview"
          :src="photoPreview"
          style="width:100%;height:100%;object-fit:cover;"
        />
        <v-icon v-else size="90" color="grey">mdi-account</v-icon>
      </v-avatar>
      <v-file-input
        label="Foto auswählen"
        accept="image/*"
        variant="outlined"
        prepend-inner-icon="mdi-camera"
        @change="selectPhoto"
      />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'

const photoFile = ref(null)
const photoPreview = ref(null)

const selectPhoto = (event) => {
  const file = event.target.files[0]
  if (!file) return
  photoFile.value = file
  // Alte URL freigeben
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoPreview.value = URL.createObjectURL(file)
}

// Speicherfreigabe beim Unmount
onUnmounted(() => {
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
})

// Export für die Hauptkomponente
defineExpose({ photoFile, photoPreview })
</script>