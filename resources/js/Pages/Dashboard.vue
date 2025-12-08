<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import SalesTab from '@/Pages/Analytics/Tabs/SalesTab.vue'
import EmployeeSalesTab from '@/Pages/Analytics/Tabs/EmployeeSalesTab.vue'
import TicketsTab from '@/Pages/Analytics/Tabs/TicketsTab.vue'

import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const chartData = ref({
  sales: { byPaymentMethod: [], byProduct: [], byDate: [], byStation: [] },
  employeeSales: { byProduct: [], byDate: [], total: 0 },
  tickets: { byProduct: [], byDate: [], byStation: [], total: 0 }
})
const isLoading = ref(false)

// Tab activo
const tab = ref('sales')

const breadcrumbs = ref([
  {
    title: '',
    disabled: true,
  },
])

onMounted(async () => {
  isLoading.value = true
  try {
    const { data } = await axios.get('/dashboard/charts')
    chartData.value = data
  } catch (error) {
    console.error('Error al obtener datos del dashboard:', error)
  } finally {
    isLoading.value = false
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
