<script setup>
import { ref, onMounted } from 'vue'
import axios from "axios"
import TicketsFilters from '@/Pages/Analytics/Components/TicketsFilters.vue'
import TicketsCharts from '@/Pages/Analytics/Components/TicketsCharts.vue'
import TicketsTable from '@/Pages/Analytics/Components/TicketsTable.vue'

// Estado local — NO vienen del dashboard
const filters = ref({})
const tickets = ref({})
const stations = ref([])
const products = ref([])
const generated = ref([])
const redeemed = ref([])
const byStation = ref([])
const byProduct = ref([])
const statuses = ref([])

// Cargar datos desde la API/Laravel
async function loadAnalytics(params = {}) {
  try {
    const { data } = await axios.get('/dashboard/tickets-data', { params })

    filters.value = data.activeFilters
    tickets.value = data.tickets
    stations.value = data.stationsList
    products.value = data.productsList
    generated.value = data.generated
    redeemed.value = data.redeemed
    byStation.value = data.byStation
    byProduct.value = data.byProduct
    statuses.value = data.statusList   // ✅ IMPORTANTE

  } catch (error) {
    console.error("Error cargando análisis de tickets:", error)
  }
}

onMounted(() => {
  loadAnalytics()
})
</script>

<template>
  <div>
    <TicketsFilters :filters="filters" :stations="stations" :products="products" :statuses="statuses" />

    <TicketsCharts :generated="generated" :redeemed="redeemed" :byStation="byStation" :byProduct="byProduct" />

    <TicketsTable :tickets="tickets" />
  </div>
</template>
