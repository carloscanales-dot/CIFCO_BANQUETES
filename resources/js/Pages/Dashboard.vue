<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import SalesTab from '@/Pages/Analytics/Tabs/SalesTab.vue'
import EmployeeSalesTab from '@/Pages/Analytics/Tabs/EmployeeSalesTab.vue'
import TicketsTab from '@/Pages/Analytics/Tabs/TicketsTab.vue'

import { Head } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const chartData = ref({
  sales: { byPaymentMethod: [], byProduct: [], byDate: [], byStation: [] },
  employeeSales: { byProduct: [], byDate: [], total: 0 },
  tickets: { byProduct: [], byDate: [], byStation: [], total: 0 }
})
const isLoading = ref(false)
const lastUpdate = ref(null)
let refreshInterval = null

// Tab activo
const tab = ref('sales')

const breadcrumbs = ref([
  {
    title: '',
    disabled: true,
  },
])

// Función para obtener datos (silenciosa para auto-refresh)
const fetchData = async (showLoading = true) => {
  if (showLoading) {
    isLoading.value = true
  }

  try {
    const { data } = await axios.get('/dashboard/charts')

    // Actualizar solo los valores sin reemplazar el objeto completo
    // Esto evita re-renders innecesarios
    Object.assign(chartData.value.sales, data.sales)
    Object.assign(chartData.value.employeeSales, data.employeeSales)
    Object.assign(chartData.value.tickets, data.tickets)

    lastUpdate.value = new Date()
  } catch (error) {
    console.error('Error al obtener datos del dashboard:', error)
  } finally {
    if (showLoading) {
      isLoading.value = false
    }
  }
}

// Recargar datos manualmente (con loading visible)
const refreshData = () => {
  fetchData(true)
}

onMounted(() => {
  // Cargar datos iniciales
  fetchData(true)

  // Auto-refresh cada 10 segundos (10000ms) - sin mostrar loading
  refreshInterval = setInterval(() => {
    fetchData(false)
  }, 10000)
})

onUnmounted(() => {
  // Limpiar el intervalo cuando se desmonte el componente
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<template>

  <Head title="Panel" />

  <AdminLayout>
    <div class="mb-5 d-flex justify-space-between align-center">
      <div>
        <h5 class="text-h5 font-weight-bold">Panel de comando</h5>
        <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
      </div>
      <div class="d-flex align-center gap-2">
        <VChip v-if="lastUpdate" size="small" :color="isLoading ? 'warning' : 'success'" variant="tonal">
          <VIcon start :icon="isLoading ? 'mdi-loading mdi-spin' : 'mdi-check-circle'"></VIcon>
          {{ lastUpdate.toLocaleTimeString() }}
        </VChip>
        <VTooltip text="Actualización automática cada 10 segundos" location="bottom">
          <template #activator="{ props }">
            <VIcon v-bind="props" icon="mdi-information-outline" size="small" color="info"></VIcon>
          </template>
        </VTooltip>
        <VBtn
          color="primary"
          variant="tonal"
          size="small"
          prepend-icon="mdi-refresh"
          :loading="isLoading"
          @click="refreshData"
        >
          Actualizar
        </VBtn>
      </div>
    </div>

    <v-card>

      <!-- 🔥 Tabs correctas (sin v-card-title) -->
      <v-tabs v-model="tab" background-color="transparent" class="px-4 pt-2">
        <v-tab value="sales">Ventas a Clientes</v-tab>
        <v-tab value="employee">Ventas a Empleado</v-tab>
        <v-tab value="tickets">Tickets (QR)</v-tab>
      </v-tabs>

      <v-divider />

      <!-- Contenido de Tabs -->
      <v-window v-model="tab">

        <!-- TAB 1 — VENTAS A CLIENTES -->
        <v-window-item value="sales">
          <v-card-text>
            <v-progress-linear v-if="isLoading" indeterminate></v-progress-linear>
            <SalesTab v-else :data="chartData.sales" />
          </v-card-text>
        </v-window-item>

        <!-- TAB 2 — VENTAS A EMPLEADO -->
        <v-window-item value="employee">
          <v-card-text>
            <v-progress-linear v-if="isLoading" indeterminate></v-progress-linear>
            <EmployeeSalesTab v-else :data="chartData.employeeSales" />
          </v-card-text>
        </v-window-item>

        <!-- TAB 3 — TICKETS (QR) -->
        <v-window-item value="tickets">
          <v-card-text>
            <v-progress-linear v-if="isLoading" indeterminate></v-progress-linear>
            <TicketsTab v-else :data="chartData.tickets" />
          </v-card-text>
        </v-window-item>

      </v-window>
    </v-card>
  </AdminLayout>
</template>
