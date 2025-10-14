<script setup>
import NavigationMenu from '@/Components/NavigationMenu.vue'
import CifcoLogo from '@/Components/CifcoLogo.vue' // Importá el logo
</script>

<template>
  <v-app class="bg-grey-lighten-4">
    <v-navigation-drawer v-model="drawer" :rail="rail" permanent>
      <v-list>
        <v-list-item :title="$page.props.auth.user.name" :subtitle="$page.props.auth.user.email">
          <!-- Logo en lugar del avatar -->
          <template #prepend>
            <CifcoLogo style="height: 42px; width: 42px; margin-right: 5px;" />
          </template>
        </v-list-item>
      </v-list>

      <v-divider />
      <NavigationMenu />
    </v-navigation-drawer>

    <v-app-bar color="#303a44">
      <v-app-bar-nav-icon v-if="$vuetify.display.mobile" @click.stop="drawer = !drawer" />
      <v-app-bar-nav-icon v-else @click.stop="rail = !rail" />
      <v-toolbar-title text="BISTRO CIFCO" />
    </v-app-bar>

    <v-main>
      <v-container>
        <slot />
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
        if (flash === null) return

        switch (flash.type) {
          case 'success':
            this.$toast.success(flash.message)
            break
          case 'error':
            this.$toast.error(flash.message)
            break
          case 'info':
            this.$toast.info(flash.message)
            break
          case 'warning':
            this.$toast.warning(flash.message)
            break
          default:
            break
        }
      },
    },
  },
  mounted() {
    this.drawer = !this.$vuetify.display.mobile
  },
}
</script>
