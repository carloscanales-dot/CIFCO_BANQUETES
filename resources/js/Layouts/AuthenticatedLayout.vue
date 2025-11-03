<script setup>
import NavigationMenu from '@/Components/NavigationMenu.vue'
import CifcoLogo from '@/Components/CifcoLogo.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <v-app class="bg-grey-lighten-4">
    <!-- Drawer lateral -->
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail"
      permanent
      style="overflow-y: auto;"
    >
      <v-list>
        <v-list-item :title="$page.props.auth.user.name" :subtitle="$page.props.auth.user.email">
          <template #prepend>
            <CifcoLogo style="height: 42px; width: 42px; margin-right: 5px; margin-left: -8px;" />
          </template>
        </v-list-item>
      </v-list>

      <v-divider />

      <!-- Menú principal -->
      <NavigationMenu />

      <!-- Acceso fijo al cambio de contraseña -->
      <v-divider class="mt-2" />
      <v-list density="compact" class="mt-auto mb-2">
        <v-list-item
          prepend-icon="mdi-lock-outline"
          title="Cambiar Contraseña"
          @click="$inertia.visit('/user/update-password')"
          class="hover:bg-black/5 rounded-lg"
        />
      </v-list>
    </v-navigation-drawer>

    <!-- Barra superior -->
    <v-app-bar color="#303a44">
      <v-app-bar-nav-icon v-if="$vuetify.display.mobile" @click.stop="drawer = !drawer" />
      <v-app-bar-nav-icon v-else @click.stop="rail = !rail" />
      <v-toolbar-title text="BISTRO CIFCO" />
      <v-spacer></v-spacer>
      <Link href="/landing" class="text-decoration-none">
        <v-btn color="white" variant="text">
          <v-icon start icon="mdi-arrow-left"></v-icon>
          Regresar
        </v-btn>
      </Link>
    </v-app-bar>

    <!-- Contenido principal -->
    <v-main>
      <v-container>
        <slot /> <!-- 👈 Necesario para renderizar las vistas Inertia -->
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
export default {
  data() {
    return {
      drawer: false,
      rail: false,
    }
  },
  watch: {
    $page: {
      handler() {
        const flash = this.$page.props.flash
        if (!flash) return
        switch (flash.type) {
          case 'success': this.$toast.success(flash.message); break
          case 'error': this.$toast.error(flash.message); break
          case 'info': this.$toast.info(flash.message); break
          case 'warning': this.$toast.warning(flash.message); break
        }
      },
    },
  },
  mounted() {
    this.drawer = !this.$vuetify.display.mobile
  },
}
</script>

<style scoped>
.v-list-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
