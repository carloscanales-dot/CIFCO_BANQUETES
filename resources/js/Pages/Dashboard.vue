<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import DashboardCharts from '@/Components/DashboardCharts.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const barData = ref([])
const pieData = ref([])
const gainsByStation = ref([])
const gainsByDate = ref([])

const breadcrumbs = ref([
  {
    title: 'Panel de comando',
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
  <AuthenticatedLayout>
    <div class="mb-5">
      <h5 class="text-h5 font-weight-bold">Panel de comando</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <v-card class="mb-5">
      <v-card-text>
        <div class="text-h6 text-medium-emphasis">Bienvenido/a de nuevo, {{ $page.props.auth.user.name }}!</div>
      </v-card-text>
    </v-card>

    <v-card>
      <v-card-text>
        <DashboardCharts
          v-if="barData.length && pieData.length"
          :barData="barData"
          :pieData="pieData"
          :gainsByStation="gainsByStation"
          :gainsByDate="gainsByDate"
        />
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>
