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
      byPaymentMethod: [],
      byProduct: [],
      byDate: [],
      byStation: [],
      total: 0,
      transactionCount: 0
    })
  }
})

// Paleta de colores variados
const colors = [
  '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
  '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF9F40',
  '#36A2EB', '#FFCE56', '#9966FF', '#FF6384', '#4BC0C0'
]

const paymentColors = ['#4caf50', '#2196f3', '#ff9800']
const stationColors = ['#e91e63', '#9c27b0', '#3f51b5', '#00bcd4', '#009688', '#8bc34a', '#ff5722', '#795548']

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
      formatter: (value) => '$' + value,
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
  labels: props.data.byProduct.slice(0, 10).map(item => item.label),
  datasets: [{
    label: 'Ventas ($)',
    backgroundColor: colors,
    data: props.data.byProduct.slice(0, 10).map(item => item.value)
  }]
}))

const paymentChartData = computed(() => ({
  labels: props.data.byPaymentMethod.map(item => item.label),
  datasets: [{
    label: 'Ingresos ($)',
    backgroundColor: paymentColors,
    data: props.data.byPaymentMethod.map(item => item.value)
  }]
}))

const dateChartData = computed(() => ({
  labels: props.data.byDate.map(item => item.label),
  datasets: [{
    label: 'Ventas ($)',
    backgroundColor: '#36A2EB',
    data: props.data.byDate.map(item => item.value)
  }]
}))

const stationChartData = computed(() => ({
  labels: props.data.byStation.slice(0, 8).map(item => item.label),
  datasets: [{
    label: 'Ganancias ($)',
    backgroundColor: stationColors,
    data: props.data.byStation.slice(0, 8).map(item => item.value)
  }]
}))

</script>

<template>
  <v-container fluid>
    <div class="text-h6 text-medium-emphasis mb-4">
      Ventas a Clientes - Análisis de Ingresos
    </div>

    <!-- Resumen de Ventas -->
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card color="#4caf50">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              ${{ data.total ?? 0 }}
            </div>
            <div class="text-white text-caption">Total Ingresos</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#ff9800">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              {{ data.transactionCount ?? 0 }}
            </div>
            <div class="text-white text-caption">Transacciones</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#9c27b0">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              ${{ data.transactionCount > 0 ? (data.total / data.transactionCount).toFixed(2) : 0 }}
            </div>
            <div class="text-white text-caption">Promedio por Transacción</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense>
      <!-- Top Productos Vendidos -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Top Productos Vendidos</v-card-title>
          <v-card-text>
            <div v-if="data.byProduct.length" style="height: 370px">
              <Bar :data="productChartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              Sin datos disponibles
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Ingresos por Método de Pago -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Ingresos por Método de Pago</v-card-title>
          <v-card-text>
            <div v-if="data.byPaymentMethod.length" style="height: 370px">
              <Bar :data="paymentChartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              Sin datos disponibles
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tendencia de Ventas por Día -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Tendencia de Ventas por Día</v-card-title>
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

      <!-- Ganancias por Estación -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Ganancias por Estación</v-card-title>
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
    </v-row>
  </v-container>
</template>
