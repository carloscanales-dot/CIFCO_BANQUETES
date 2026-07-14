<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import navigation from '@/Configs/navigationAdmin'

// 🔐 Acceso seguro al usuario
const page = usePage()
const user = page.props.auth?.user

// Función para filtrar items por roles
const filterByRole = (item) => {
  if (!user || !user.roles) return false
  return item.roles?.some(role => user.roles.includes(role))
}

// ⛑️ Validación segura y filtrado de items
const filteredItems = user && user.roles
  ? navigation.items.filter(item => {
      // Primero verificar si el usuario tiene acceso al item principal
      if (!filterByRole(item)) return false

      // Si tiene children, filtrar los children también
      if (item.children) {
        item.filteredChildren = item.children.filter(child => filterByRole(child))
        // Solo mostrar el grupo si tiene children accesibles
        return item.filteredChildren.length > 0
      }

      return true
    })
  : []
</script>

<template>
  <v-list nav>
    <template v-for="(item, key) in filteredItems" :key="key">
      <!-- Menú expandible con submenús -->
      <v-list-group v-if="item.children" :value="item.title">
        <template #activator="{ props }">
          <v-list-item
            v-bind="props"
            :prepend-icon="item.icon"
            :title="item.title"
          />
        </template>

        <Link
          v-for="(child, childKey) in item.filteredChildren"
          :key="childKey"
          :href="child.to"
          as="div"
        >
          <v-list-item
            :prepend-icon="child.icon"
            :title="child.title"
            :exact="child.exact"
            link
            :class="{ 'v-list-item--active': $page.url.startsWith(child.to) }"
          />
        </Link>
      </v-list-group>

      <!-- Item normal sin submenús -->
      <Link
        v-else
        :href="item.to"
        as="div"
      >
        <v-list-item
          :prepend-icon="item.icon"
          :title="item.title"
          :exact="item.exact"
          link
          :class="{ 'v-list-item--active': $page.url.startsWith(item.to) }"
        />
      </Link>
    </template>

    <Link href="/logout" method="post" as="div">
      <v-list-item prepend-icon="mdi-exit-to-app" title="Log Out" link />
    </Link>
  </v-list>
</template>
