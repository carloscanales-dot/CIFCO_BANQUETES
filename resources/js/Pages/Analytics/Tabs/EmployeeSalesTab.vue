<script setup>
import { Chart, Bar, Line, Tooltip, Grid } from 'vue3-charts'

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
      Ventas a Empleado - Créditos Registrados
    </div>

    <!-- Resumen de Total -->
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card color="#388e3c">
          <v-card-text>
            <div class="text-white text-h5 font-weight-bold">
              ${{ data.total ?? 0 }}
            </div>
            <div class="text-white text-caption">Total de Créditos</div>
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
      <!-- Top Productos en Crédito (Bar Horizontal) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Top Productos en Crédito</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2">
              <Chart
                v-if="data.byProduct.length"
                :size="{ width: 500, height: 250 }"
                :data="data.byProduct.slice(0, 8)"
                :margin="margin"
                direction="horizontal"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="2,2" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#f57c00' }" />
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
              <v-chip color="#f57c00" small>Productos</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tendencia de Créditos (Line) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Tendencia de Créditos por Día</v-card-title>
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
                  <Line :dataKeys="['label', 'value']" :lineStyle="{ stroke: '#f57c00', strokeWidth: 2 }" />
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
              <v-chip color="#f57c00" small>Créditos Diarios</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Información Adicional -->
      <v-col cols="12">
        <v-card>
          <v-card-title class="text-h6">Información de Créditos</v-card-title>
          <v-card-text>
            <v-alert color="warning" variant="tonal" class="mb-0">
              <div class="text-subtitle-2 font-weight-bold mb-2">Notas:</div>
              <ul class="text-caption mb-0">
                <li>Los créditos son productos vendidos a empleados</li>
                <li>Estos montos son adicionales a las ventas regulares</li>
                <li>Verificar que los créditos sean posteriormente cobrados</li>
              </ul>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
