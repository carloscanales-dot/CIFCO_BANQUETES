<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import CifcoLogo from '@/Components/CifcoLogo.vue';
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <v-app class="bg-grey-lighten-4">
    <v-main style="background: url('/fondo1.jpg') center center / cover no-repeat;">
      <v-container fluid>
        <v-row align="center" justify="center" style="height: 100vh">
          <v-col cols="12" sm="12" md="10" lg="3">

            <!-- Logo fuera de la card -->
            <div class="d-flex justify-center">
              <Link href="/" as="div">
                <CifcoLogo style="height: 75px;" />
              </Link>
            </div>

            <!-- Card con logo centrado -->
            <v-card class="px-6 py-4 mt-3 elevation-2 rounded-lg">

              <!-- Centrado del logo dentro de la card -->
              <div class="d-flex justify-center mb-4">
                <CifcoLogo style="height: 75px;" />
              </div>

              <!-- Slot para contenido -->
              <slot />
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

