<template>
  <Head title="Bienvenido" />
  <v-app>
    <v-app-bar color="#303a44">
      <v-toolbar-title>Bienvenido, {{ page.props.auth?.user?.name ?? '' }}</v-toolbar-title>
      <v-spacer></v-spacer>
      <Link href="/logout" method="post" as="button">
        <v-btn>Cerrar Sesión</v-btn>
      </Link>
    </v-app-bar>

    <v-main :style="mainStyle">
      <!-- Loader global: se muestra mientras no existan props o la imagen esté cargando -->
      <v-container v-if="loadingProps" class="d-flex justify-center align-center fill-height">
        <v-progress-circular indeterminate color="white" size="64" />
      </v-container>

      <!-- Contenido: solo cuando props ya están listos -->
      <v-container v-else class="fill-height py-10">
        <v-row justify="center" align="center" dense>
          <v-col v-if="isAdmin" cols="12" sm="6" md="3" lg="3">
            <v-card href="/dashboard" class="mx-auto text-center pa-6" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
              <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                <v-icon size="64" class="mb-4">mdi-cog</v-icon>
                <div class="text-h6">Administrar</div>
                <div>Acceder al panel de administración.</div>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Resto de cards -->
          <v-col cols="12" sm="6" md="3" lg="3">
            <v-card href="/ticket/reader/index" class="mx-auto text-center pa-6" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
              <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                <v-icon size="64" class="mb-4">mdi-gift</v-icon>
                <div class="text-h6">Cortesías</div>
                <div>Acceder al dashboard de cortesías.</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6" md="3" lg="3">
            <v-card href="/ventas" class="mx-auto text-center pa-6" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
              <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                <v-icon size="64" class="mb-4">mdi-point-of-sale</v-icon>
                <div class="text-h6">Ventas</div>
                <div>Acceder a la página de ventas.</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6" md="3" lg="3">
            <v-card href="/creditos" class="mx-auto text-center pa-6" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
              <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                <v-icon size="64" class="mb-4">mdi-account-credit-card</v-icon>
                <div class="text-h6">Ventas Empleados</div>
                <div>Acceder a la página de créditos empleados.</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { usePage, Link, Head } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'

const page = usePage()

// props reactivo de Inertia
const props = computed(() => page.props)

// image loader (opcional)
const imageLoading = ref(true)
onMounted(() => {
  const img = new Image()
  img.src = '/fondo-lr.jpeg'
  img.onload = () => (imageLoading.value = false)
  img.onerror = () => (imageLoading.value = false)
})

// isAdmin lee DIRECTAMENTE el flag que ya viene del backend
const isAdmin = computed(() => Boolean(props.value?.is_admin))

// loadingProps: true si no hay props aún o la imagen de fondo sigue cargando
const loadingProps = computed(() => {
  const noProps = !(props.value && Object.keys(props.value).length > 0)
  return noProps || imageLoading.value
})

const mainStyle = computed(() => ({
  backgroundColor: '#303a44',
  backgroundImage: !imageLoading.value ? `url('/fondo-lr.jpeg')` : 'none',
  backgroundSize: 'cover',
  backgroundPosition: 'center center'
}))
</script>
