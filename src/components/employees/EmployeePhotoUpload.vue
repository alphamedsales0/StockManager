<template>
  <v-card class="mb-5 rounded-xl" elevation="2">
    <v-card-title>
      <v-icon color="primary" class="mr-2">mdi-camera-account</v-icon>
      Mitarbeiter Foto
    </v-card-title>

    <v-card-text class="text-center">
      <!-- ================= APERÇU ================= -->
      <div class="photo-preview mb-5">
        <img
          v-if="photoPreview || currentPhoto"
          :src="photoPreview || currentPhoto"
          alt="Mitarbeiter Foto"
          class="photo-img"
        />
        <div v-else class="photo-placeholder">
          <v-icon size="90" color="grey">mdi-account</v-icon>
        </div>
      </div>

      <!-- ================= BOUTONS ================= -->
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

      <p class="text-caption text-grey mt-2">
        JPG, PNG, WebP — max. 5 MB
      </p>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

/* =====================================================
   PROPS
===================================================== */
const props = defineProps({
  existingPhoto: { type: String, default: null }
})

/* =====================================================
   STATE
===================================================== */
const photoFile    = ref(null)
const photoPreview = ref(null)
const errorMessage = ref('')

/* =====================================================
   COMPUTED
===================================================== */
const currentPhoto = computed(() => props.existingPhoto || null)

/* =====================================================
   SÉLECTION DE LA PHOTO
===================================================== */
const selectPhoto = (event) => {
  // v-file-input peut renvoyer soit un File, soit un tableau, soit un Event
  let file = null

  if (event instanceof Event) {
    file = event.target?.files?.[0] || null
  } else if (Array.isArray(event)) {
    file = event[0] || null
  } else if (event instanceof File) {
    file = event
  }

  if (!file) return

  errorMessage.value = ''

  // --- Validation taille (max 5 MB) ---
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    errorMessage.value = 'Die Datei ist zu groß (max. 5 MB).'
    return
  }

  // --- Validation type MIME ---
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    errorMessage.value = 'Nur JPG, PNG oder WebP sind erlaubt.'
    return
  }

  // --- OK : on stocke le fichier + aperçu ---
  photoFile.value = file

  // Libère l'ancienne URL blob
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoPreview.value = URL.createObjectURL(file)
}

/* =====================================================
   ANNULER LA SÉLECTION EN COURS
===================================================== */
const cancelSelection = () => {
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
    photoPreview.value = null
  }
  photoFile.value = null
  errorMessage.value = ''
}

/* =====================================================
   RESET — appelée par le parent après création
===================================================== */
const reset = () => {
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoFile.value    = null
  photoPreview.value = null
  errorMessage.value = ''
}

/* =====================================================
   CLEANUP
===================================================== */
onUnmounted(() => {
  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
})

/* =====================================================
   EXPOSE AU PARENT
===================================================== */
defineExpose({
  photoFile,
  photoPreview,
  reset
})
</script>

<style scoped>
/* =====================================================
   APERÇU
===================================================== */
.photo-preview {
  display: flex;
  justify-content: center;
}

.photo-img,
.photo-placeholder {
  width: 160px;
  height: 160px;

  /* Image centrée dans son cadre */
  object-fit: cover;
  object-position: center;

  /* Pas de border-radius (aligné avec ProfilImage Kunden) */
  border-radius: 0;

  /* Border solide conservée */
  border: 2px solid #e2e8f0;

  background: #f8fafc;

  display: flex;
  align-items: center;
  justify-content: center;
}

/* =====================================================
   ACTIONS
===================================================== */
.photo-actions {
  max-width: 400px;
  margin: 0 auto;
}
</style>