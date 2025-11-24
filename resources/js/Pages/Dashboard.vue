<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import DashboardCharts from '@/Components/DashboardCharts.vue'
import TicketsTab from '@/Pages/Analytics/Tabs/TicketsTab.vue'

import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const barData = ref([])
const pieData = ref([])
const gainsByStation = ref([])
const gainsByDate = ref([])

// Tab activo
const tab = ref('panel')

const breadcrumbs = ref([
  {
    title: '',
    disabled: true,
  },
])

onMounted(async () => {
  try {
    const { data } = await axios.get('/dashboard/charts')
    barData.value = data.bar
    pieData.value = data.pie
    gainsByStation.value = data.gains_by_station
    gainsByDate.value = data.gains_by_date
  } catch (error) {
    console.error('Error al obtener datos del dashboard:', error)
  }
})
</script>

<template>

  <Head title="Panel" />

  <AdminLayout>
    <div class="mb-5">
      <h5 class="text-h5 font-weight-bold">Panel de comando</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <v-card>

      <!-- 🔥 Tabs correctas (sin v-card-title) -->
      <v-tabs v-model="tab" background-color="transparent" class="px-4 pt-2">
        <v-tab value="panel">Panel General</v-tab>
        <v-tab value="tickets">Tickets</v-tab>
        <v-tab value="ventas">Ventas</v-tab>
      </v-tabs>

      <v-divider />

      <!-- Contenido de Tabs -->
      <v-window v-model="tab">

        <!-- TAB 1 — PANEL GENERAL -->
        <v-window-item value="panel">
          <v-card-text>

            <div class="text-h6 text-medium-emphasis mb-4">
              Bienvenido/a de nuevo, {{ $page.props.auth.user.name }}!
            </div>

            <DashboardCharts v-if="barData.length && pieData.length" :barData="barData" :pieData="pieData"
              :gainsByStation="gainsByStation" :gainsByDate="gainsByDate" />

          </v-card-text>
        </v-window-item>

        <!-- TAB 2 — TICKETS -->
        <v-window-item value="tickets">
          <v-card-text>
            <TicketsTab />
          </v-card-text>
        </v-window-item>

        <!-- TAB 3 — VENTAS -->
        <v-window-item value="ventas">
          <v-card-text>
            <h3 class="text-h6 font-weight-bold">Módulo de ventas</h3>
            <p class="text-medium-emphasis">Próximamente…</p>
          </v-card-text>
        </v-window-item>

      </v-window>
    </v-card>
  </AdminLayout>
</template>
