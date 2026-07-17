<template>
  <v-container fluid class="page pa-8">
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card class="premium-card">

          <!-- Header -->
          <div class="header pa-8">
            <div class="d-flex align-center">

              <v-avatar
                color="primary"
                size="60"
              >
                <v-icon size="32">
                  mdi-account-plus
                </v-icon>
              </v-avatar>

              <div class="ml-5">
                <div class="text-h4 font-weight-bold">
                  Neuer Mitarbeiter
                </div>

                <div class="text-grey">
                  Mitarbeiterkonto erstellen
                </div>
              </div>

            </div>
          </div>

          <v-divider></v-divider>

          <v-card-text class="pa-8">

            <v-form
              ref="form"
              @submit.prevent="submit"
            >

              <v-row>

                <!-- Nom -->

                <v-col cols="12">
                  <v-text-field
                    v-model="employee.name"
                    label="Vollständiger Name"
                    prepend-inner-icon="mdi-account-outline"
                    variant="solo-filled"
                    density="comfortable"
                    rounded="lg"
                    :rules="[
                      v => !!v || 'Name ist erforderlich'
                    ]"
                  />
                </v-col>

                <!-- Email -->

                <v-col cols="12">
                  <v-text-field
                    v-model="employee.email"
                    label="E-Mail"
                    prepend-inner-icon="mdi-email-outline"
                    variant="solo-filled"
                    density="comfortable"
                    rounded="lg"
                    :rules="[
                      v => !!v || 'E-Mail ist erforderlich',
                      v => /.+@.+\..+/.test(v) || 'Ungültige E-Mail'
                    ]"
                  />
                </v-col>

                <!-- Password -->

                <v-col cols="12">
                  <v-text-field
                    v-model="employee.password"
                    :type="showPassword ? 'text' : 'password'"
                    label="Passwort"
                    prepend-inner-icon="mdi-lock-outline"
                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showPassword = !showPassword"
                    variant="solo-filled"
                    density="comfortable"
                    rounded="lg"
                    :rules="[
                      v => !!v || 'Passwort ist erforderlich',
                      v => v.length >= 8 || 'Mindestens 8 Zeichen'
                    ]"
                  />
                </v-col>

                <!-- Confirmation Password -->

                <v-col cols="12">
                  <v-text-field
                    v-model="confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    label="Passwort bestätigen"
                    prepend-inner-icon="mdi-lock-check-outline"
                    :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showConfirmPassword=!showConfirmPassword"
                    variant="solo-filled"
                    density="comfortable"
                    rounded="lg"
                    :rules="[
                      v => !!v || 'Bitte Passwort bestätigen',
                      v => v === employee.password || 'Die Passwörter stimmen nicht überein'
                    ]"
                  />
                </v-col>

                <!-- Role -->

                <v-col cols="12">
                  <v-select
                    v-model="employee.role"
                    :items="roles"
                    label="Rolle"
                    prepend-inner-icon="mdi-shield-account"
                    variant="solo-filled"
                    density="comfortable"
                    rounded="lg"
                    :rules="[
                      v => !!v || 'Rolle ist erforderlich'
                    ]"
                  />
                </v-col>

              </v-row>

              <v-divider class="my-8"></v-divider>

              <div class="d-flex justify-end ga-4">

                <v-btn
                  variant="text"
                  color="grey"
                  @click="$router.back()"
                >
                  Abbrechen
                </v-btn>

                <v-btn
                  color="primary"
                  type="submit"
                  size="large"
                  rounded="lg"
                  :loading="loading"
                >
                  <v-icon start>
                    mdi-content-save
                  </v-icon>

                  Mitarbeiter speichern
                </v-btn>

              </div>

            </v-form>

          </v-card-text>

        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = ref(null)
const loading = ref(false)

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const confirmPassword = ref('')

const roles = [
  {
    title: 'Mitarbeiter',
    value: 'employee'
  },
  {
    title: 'Manager',
    value: 'manager'
  },
  {
    title: 'Administrator',
    value: 'admin'
  }
]

const employee = ref({
  name: '',
  email: '',
  password: '',
  role: 'employee'
})

const submit = async () => {

  const { valid } = await form.value.validate()

  if (!valid) return

  if (employee.value.password !== confirmPassword.value) {
    alert('Die Passwörter stimmen nicht überein.')
    return
  }

  loading.value = true

  try {

    await axios.post('/api/employees', employee.value)

    router.push('/employees')

  } catch (error) {

    console.error(error)

    alert('Fehler beim Speichern.')

  } finally {

    loading.value = false

  }

}
</script>

<style scoped>

.page{

    min-height:100vh;

    background:linear-gradient(
        135deg,
        #f5f7fa,
        #eef3f9
    );

}

.premium-card{

    border-radius:24px;

    border:1px solid #E6EAF0;

    box-shadow:
        0 12px 40px rgba(0,0,0,.06);

    overflow:hidden;

}

.header{

    background:
        linear-gradient(
            90deg,
            white,
            #fafcff
        );

}

.v-field{

    border-radius:14px;

}

.v-btn{

    text-transform:none;

    font-weight:600;

}

.text-grey{

    color:#6b7280;

}
</style>