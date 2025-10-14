<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import CifcoLogo from '@/Components/CifcoLogo.vue';
import VuetifyLogo from '@/Components/VuetifyLogo.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  auth: Object,
})
</script>

<template>
  <Head title="Welcome" />
  <v-app>
    <v-main style="background: url('/fondo1.jpg') center center / cover no-repeat;">
      <v-container fill-height>
        <v-row align="center" justify="center" style="height: 100vh;">
          <v-col cols="12" sm="12" md="10" lg="4">
            <v-card class="pa-4 rounded-lg elevation-8 text-center" style="background-color: rgba(255, 255, 255, 0.9);">
              <v-card-title class="justify-center">
                <div class="d-flex justify-center mb-4">
                  <CifcoLogo class="ml-1" style="height: 125px;" />
                </div>
                <span class="text-h5 font-weight-bold">CIFCO BISTRO</span>
              </v-card-title>

              <v-card-text>
                <p class="text-body-2">
                  CONTROL DE FERIA BISTRO
                </p>
              </v-card-text>

              <v-card-actions class="justify-center">
                <div v-if="props.auth.user != null">
                  <Link href="/dashboard" as="div">
                    <v-btn color="primary">Dashboard</v-btn>
                  </Link>
                </div>
                <div v-else>
                  <div class="d-flex justify-center">
                    <Link v-if="props.canLogin" href="/login" class="mr-2" as="div">
                      <v-btn color="primary">Login</v-btn>
                    </Link>
                    <!-- <Link v-if="props.canRegister" href="/register" as="div">
                      <v-btn color="primary">Sign Up</v-btn>
                    </Link> -->
                  </div>
                </div>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>


<script>
export default {
  name: 'WelcomePage',
}
</script>
