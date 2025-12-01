<script setup>
import { ref, watch, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useDisplay } from 'vuetify'
import CifcoLogo from '@/Components/CifcoLogo.vue'

const drawer = ref(false)
const rail = ref(false)

const page = usePage()
const display = useDisplay()

const getFlash = () => {
  try {
    if (page && page.props) {
      if (typeof page.props === 'object' && 'value' in page.props) {
        return page.props.value?.flash ?? null
      }
      return page.props?.flash ?? null
    }
  } catch (e) {}
  return null
}

watch(
  getFlash,
  (flash) => {
    if (!flash) return
    // muestra valores concretos (evita console.log de Proxy)
    const flashObj = JSON.parse(JSON.stringify(flash))
    const toast = (typeof window !== 'undefined' && window.$toast) ? window.$toast :
                  (typeof globalThis !== 'undefined' && globalThis.$toast) ? globalThis.$toast :
                  null

    switch (flashObj.type) {
      case 'success': toast?.success?.(flashObj.message) ?? console.log('SUCCESS:', flashObj.message); break
      case 'error':   toast?.error?.(flashObj.message) ?? console.error('ERROR:', flashObj.message); break
      case 'info':    toast?.info?.(flashObj.message) ?? console.info('INFO:', flashObj.message); break
      case 'warning': toast?.warning?.(flashObj.message) ?? console.warn('WARNING:', flashObj.message); break
      default: console.log('FLASH (unknown):', flashObj)
    }
  },
  { immediate: true }
)

onMounted(() => {
  drawer.value = !(display?.mobile?.value ?? false)
})
</script>

<template>
  <v-app>
    <v-navigation-drawer v-model="drawer" :rail="rail" permanent>
      <v-list>
        <v-list-item>
          <template #prepend>
            <CifcoLogo style="height:42px;width:42px;margin-right:5px;margin-left:-8px;" />
          </template>

          <!-- Evitamos v-list-item-content (no resuelto); usamos elementos válidos -->
          <div style="display:flex;flex-direction:column;">
            <span class="v-list-item__title">{{ $page.props.auth.user.name }}</span>
            <span class="v-list-item__subtitle">{{ $page.props.auth.user.email }}</span>
          </div>
        </v-list-item>
      </v-list>

      <v-divider />

      <v-list density="compact" nav>
        <Link href="/ventas" class="text-decoration-none" style="color: black;">
          <v-list-item :active="$page.url.startsWith('/ventas')">
            <template #prepend><v-icon>mdi-point-of-sale</v-icon></template>
            <v-list-item-title>Ventas</v-list-item-title>
          </v-list-item>
        </Link>

        <Link href="/historial-ventas" class="text-decoration-none" style="color: black;">
          <v-list-item :active="$page.url.startsWith('/historial-ventas')">
            <template #prepend><v-icon>mdi-history</v-icon></template>
            <v-list-item-title>Historial de Ventas</v-list-item-title>
          </v-list-item>
        </Link>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar color="#303a44">
      <v-app-bar-nav-icon v-if="display?.mobile?.value" @click.stop="drawer = !drawer" />
      <v-app-bar-nav-icon v-else @click.stop="rail = !rail" />
      <v-toolbar-title text="Módulo de Caja" />
      <div style="margin-left:auto;">
        <Link href="/landing" class="text-decoration-none">
          <v-btn color="white" variant="text">
            <v-icon start icon="mdi-arrow-left" />
            Regresar
          </v-btn>
        </Link>
      </div>
    </v-app-bar>

    <v-main>
      <v-container>
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
/* Si quieres puedes ajustar estas clases o usar las clases de Vuetify */
.v-list-item__title { font-weight: 500; }
.v-list-item__subtitle { font-size: 0.85rem; opacity: 0.8; }
</style>
