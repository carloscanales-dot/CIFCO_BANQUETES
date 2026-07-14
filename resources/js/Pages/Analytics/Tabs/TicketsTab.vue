<script setup>
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import ChartDataLabels from 'chartjs-plugin-datalabels'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ChartDataLabels)

const props = defineProps({
  data: {
    type: Object,
    default: () => ({
      byProduct: [],
      byDate: [],
      byStation: [],
      total: 0
    })
  }
})

// Paleta de colores para tickets
const ticketColors = [
  '#66bb6a', '#4caf50', '#43a047', '#388e3c', '#2e7d32',
  '#81c784', '#66bb6a', '#4caf50', '#43a047', '#388e3c'
]

const dateTicketColors = [
  '#26a69a', '#00897b', '#00796b', '#00695c', '#004d40',
  '#4db6ac', '#26a69a', '#00897b', '#00796b', '#00695c'
]

const stationTicketColors = [
  '#ab47bc', '#9c27b0', '#8e24aa', '#7b1fa2', '#6a1b9a',
  '#ba68c8', '#ab47bc', '#9c27b0', '#8e24aa', '#7b1fa2'
]

// Opciones de gráficas
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  layout: {
    padding: {
      top: 30
    }
  },
  plugins: {
    legend: {
      display: false
    },
    datalabels: {
      anchor: 'end',
      align: 'top',
      formatter: (value) => value,
      font: {
        weight: 'bold',
        size: 11
      },
      color: '#333'
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}

// Datos reactivos para cada gráfica
const productChartData = computed(() => ({
  labels: props.data.byProduct.slice(0, 8).map(item => item.label),
  datasets: [{
    label: 'Tickets',
    backgroundColor: ticketColors,
    data: props.data.byProduct.slice(0, 8).map(item => item.value)
  }]
}))

const dateChartData = computed(() => ({
  labels: props.data.byDate.map(item => item.label),
  datasets: [{
    label: 'Tickets',
    backgroundColor: '#4caf50',
    data: props.data.byDate.map(item => item.value)
  }]
}))

const stationChartData = computed(() => ({
  labels: props.data.byStation.slice(0, 8).map(item => item.label),
  datasets: [{
    label: 'Tickets',
    backgroundColor: stationTicketColors,
    data: props.data.byStation.slice(0, 8).map(item => item.value)
  }]
}))

</script>

<template>
  <v-container fluid>
    <div class="text-h6 text-medium-emphasis mb-4">
      Tickets QR - Lectura y Canje de Cortesía
    </div>

    <!-- Resumen de Total -->
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card color="#388e3c">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              {{ data.total ?? 0 }}
            </div>
            <div class="text-white text-caption">Total de Tickets Canjeados</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense>
      <!-- Tickets por Producto -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Tickets Canjeados por Producto</v-card-title>
          <v-card-text>
            <div v-if="data.byProduct.length" style="height: 350px">
              <Bar :data="productChartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              Sin datos disponibles
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tendencia de Canjes por Día -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Tendencia de Canjes por Día</v-card-title>
          <v-card-text>
            <div v-if="data.byDate.length" style="height: 350px">
              <Bar :data="dateChartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              Sin datos disponibles
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Canjes por Estación -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Canjes por Estación</v-card-title>
          <v-card-text>
            <div v-if="data.byStation.length" style="height: 350px">
              <Bar :data="stationChartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              Sin datos disponibles
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Información Adicional -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Información de Tickets</v-card-title>
          <v-card-text>
            <v-alert color="info" variant="tonal" class="mb-0">
              <div class="text-subtitle-2 font-weight-bold mb-2">Detalles:</div>
              <ul class="text-caption mb-0">
                <li>Tickets de cortesía/promoción canjeados vía QR</li>
                <li>Se registra por estación y producto</li>
                <li>Útil para trackear actividad de promociones</li>
              </ul>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
