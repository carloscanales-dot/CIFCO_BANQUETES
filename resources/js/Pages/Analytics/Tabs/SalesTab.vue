<script setup>
import { Chart, Bar, Line, Pie, Tooltip, Grid } from 'vue3-charts'

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

const axis = {
  primary: { type: 'band' },
  secondary: { type: 'linear' },
}

const margin = { left: 60, top: 20, right: 20, bottom: 40 }

const lineAxis = {
  primary: { type: 'band' },
  secondary: { type: 'linear', domain: ['dataMin', 'dataMax'], ticks: 5 }
}
</script>

<template>
  <v-container fluid>
    <div class="text-h6 text-medium-emphasis mb-4">
      Ventas a Clientes - Análisis de Ingresos
    </div>

    <!-- Resumen de Ventas -->
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card color="#1976d2">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              ${{ data.total ?? 0 }}
            </div>
            <div class="text-white text-caption">Total Ingresos</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#0d47a1">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              {{ data.transactionCount ?? 0 }}
            </div>
            <div class="text-white text-caption">Transacciones</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="#1565c0">
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
      <!-- Ingresos por Método de Pago (Bar) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Ingresos por Método de Pago</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2">
              <Chart
                v-if="data.byPaymentMethod.length"
                :size="{ width: 500, height: 250 }"
                :data="data.byPaymentMethod"
                :margin="margin"
                direction="vertical"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="3,3" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#00b4d8' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
              <div v-else class="text-center text-medium-emphasis py-8">
                Sin datos disponibles
              </div>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#00b4d8" small>Efectivo • Tarjeta • Chivo</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Top Productos Vendidos (Bar Horizontal) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Top Productos Vendidos</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2">
              <Chart
                v-if="data.byProduct.length"
                :size="{ width: 500, height: 250 }"
                :data="data.byProduct.slice(0, 5)"
                :margin="margin"
                direction="horizontal"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="2,2" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#0096c7' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
              <div v-else class="text-center text-medium-emphasis py-8">
                Sin datos disponibles
              </div>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#0096c7" small>Productos</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tendencia de Ventas (Line) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Tendencia de Ventas por Día</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2">
              <Chart
                v-if="data.byDate.length"
                :size="{ width: 500, height: 250 }"
                :data="data.byDate"
                :margin="margin"
                direction="vertical"
                :axis="lineAxis"
              >
                <template #layers>
                  <Grid strokeDasharray="2,2" />
                  <Line :dataKeys="['label', 'value']" :lineStyle="{ stroke: '#00b4d8', strokeWidth: 2 }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
              <div v-else class="text-center text-medium-emphasis py-8">
                Sin datos disponibles
              </div>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#00b4d8" small>Ingresos Diarios</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Ganancias por Estación (Bar) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Ganancias por Estación</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2">
              <Chart
                v-if="data.byStation.length"
                :size="{ width: 500, height: 250 }"
                :data="data.byStation"
                :margin="margin"
                direction="horizontal"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="3,3" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#48cae4' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
              <div v-else class="text-center text-medium-emphasis py-8">
                Sin datos disponibles
              </div>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#48cae4" small>Por Estación</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
