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
      total: 0,
      transactionCount: 0
    })
  }
})

// Paleta de colores para ventas a empleado
const creditColors = [
  '#f57c00', '#ff9800', '#ffa726', '#ffb74d', '#ffcc80',
  '#fb8c00', '#f57c00', '#ef6c00', '#e65100', '#d84315',
  '#bf360c', '#a1887f', '#8d6e63', '#6d4c41', '#5d4037'
]

const dateColors = [
  '#ff6b6b', '#ee5a6f', '#c44569', '#f39c12', '#e67e22',
  '#d35400', '#e74c3c', '#c0392b', '#9b59b6', '#8e44ad'
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
  labels: props.data.byProduct.slice(0, 8).map(item => item.label),
  datasets: [{
    label: 'Ventas ($)',
    backgroundColor: creditColors,
    data: props.data.byProduct.slice(0, 8).map(item => item.value)
  }]
}))

const dateChartData = computed(() => ({
  labels: props.data.byDate.map(item => item.label),
  datasets: [{
    label: 'Ventas ($)',
    backgroundColor: '#ff9800',
    data: props.data.byDate.map(item => item.value)
  }]
}))

</script>

<template>
  <v-container fluid>
    <div class="text-h6 text-medium-emphasis mb-4">
      Ventas a Empleado - Ventas Registrados
    </div>

    <!-- Resumen de Total -->
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card color="#388e3c">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              ${{ data.total ?? 0 }}
            </div>
            <div class="text-white text-caption">Total de Ventas a Empleado</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#2e7d32">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              {{ data.transactionCount ?? 0 }}
            </div>
            <div class="text-white text-caption">Transacciones</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#1b5e20">
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
      <!-- Top Productos en Crédito -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Top Productos en Ventas a Empleado</v-card-title>
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

      <!-- Tendencia de Créditos por Día -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="text-h6">Tendencia de Ventas a Empleado por Día</v-card-title>
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

      <!-- Información Adicional -->
      <v-col cols="12">
        <v-card>
          <v-card-title class="text-h6">Información de Ventas a Empleado</v-card-title>
          <v-card-text>
            <v-alert color="warning" variant="tonal" class="mb-0">
              <div class="text-subtitle-2 font-weight-bold mb-2">Notas:</div>
              <ul class="text-caption mb-0">
                <li>Ventas de productos a empleados con precio especial</li>
                <li>Los montos mostrados son las ganancias totales por producto y por día</li>
                <li>Estos montos son adicionales a las ventas regulares a clientes</li>
              </ul>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
