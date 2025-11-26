<script setup>
import { ref, watch } from 'vue'
import { useForm, router, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const form = useForm({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// ✅ Validaciones dinámicas locales
const localErrors = ref({
  current_password: null,
  new_password: null,
  new_password_confirmation: null
})

// 🔍 Reglas dinámicas en tiempo real
watch(
  () => form.new_password,
  (val) => {
    if (val.length < 8) {
      localErrors.value.new_password = 'Debe tener al menos 8 caracteres.'
    } else if (!/[A-Z]/.test(val)) {
      localErrors.value.new_password = 'Debe incluir una letra mayúscula.'
    } else if (!/[0-9]/.test(val)) {
      localErrors.value.new_password = 'Debe incluir un número.'
    } else if (!/[!@#$%^&*]/.test(val)) {
      localErrors.value.new_password = 'Debe incluir un carácter especial.'
    } else {
      localErrors.value.new_password = null
    }
  }
)

watch(
  () => form.new_password_confirmation,
  (val) => {
    localErrors.value.new_password_confirmation =
      val !== form.new_password ? 'Las contraseñas no coinciden.' : null
  }
)

function submit() {
  if (localErrors.value.new_password || localErrors.value.new_password_confirmation) {
    toast.error('Corrige los errores antes de continuar.')
    return
  }

  router.post('/user/update-password', form, {
    onSuccess: () => {
      toast.success('Contraseña actualizada correctamente. Serás redirigido al login...')
      form.reset()

      // Esperar un momento antes de cerrar sesión
      setTimeout(() => {
        router.post('/logout') // Cierra sesión y redirige al login
      }, 2000)
    },
    onError: (errors) => {
      if (errors.current_password) {
        toast.error(errors.current_password)
      } else {
        toast.error('Verifica los campos e inténtalo nuevamente.')
      }
    }
  })
}
</script>

<template>
  <Head title="Actualizar Contraseña" />
  <AdminLayout>
    <v-container class="py-10 d-flex justify-center">
      <v-card class="pa-6 w-100" max-width="500" style="background-color: white;">
        <v-card-title class="text-h6 font-weight-bold text-center mb-4">
          Actualizar Contraseña
        </v-card-title>

        <v-divider></v-divider>

        <v-form @submit.prevent="submit" class="mt-5">
          <!-- Contraseña actual -->
          <v-text-field
            v-model="form.current_password"
            :append-inner-icon="showCurrent ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showCurrent = !showCurrent"
            :type="showCurrent ? 'text' : 'password'"
            label="Contraseña Actual"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="form.errors.current_password"
            required
            class="mb-3"
          />

          <!-- Nueva contraseña -->
          <v-text-field
            v-model="form.new_password"
            :append-inner-icon="showNew ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showNew = !showNew"
            :type="showNew ? 'text' : 'password'"
            label="Nueva Contraseña"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="form.errors.new_password || localErrors.new_password"
            required
            class="mb-3"
          />

          <!-- Confirmación -->
          <v-text-field
            v-model="form.new_password_confirmation"
            :append-inner-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showConfirm = !showConfirm"
            :type="showConfirm ? 'text' : 'password'"
            label="Confirmar Nueva Contraseña"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="localErrors.new_password_confirmation"
            required
          />

          <v-divider class="my-4"></v-divider>

          <div class="d-flex justify-end">
            <v-btn color="black" variant="elevated" type="submit">
              Actualizar
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-container>
  </AdminLayout>
</template>

<style scoped>
.v-card {
  border-radius: 16px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
}

.v-btn {
  text-transform: none;
  font-weight: 600;
}
</style>
