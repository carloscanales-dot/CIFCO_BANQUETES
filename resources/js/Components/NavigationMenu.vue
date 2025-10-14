<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import navigation from '@/Configs/navigation'

// 🔐 Acceso seguro al usuario
const page = usePage()
const user = page.props.auth?.user

// ⛑️ Validación segura
const filteredItems = user
  ? navigation.items.filter(item => item.roles?.includes(user.role))
  : []
</script>

<template>
  <v-list nav>
    <Link
      v-for="(item, key) in filteredItems"
      :key="key"
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

    <Link href="/logout" method="post" as="div">
      <v-list-item prepend-icon="mdi-exit-to-app" title="Log Out" link />
    </Link>
  </v-list>
</template>
