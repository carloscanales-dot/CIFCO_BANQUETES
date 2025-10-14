<script setup>
import { Chart, Bar, Line, Area, Tooltip, Grid } from 'vue3-charts'

const props = defineProps({
  barData: Array,
  pieData: Array,
  gainsByStation: Array,
  gainsByDate: Array,
})

// Ejes y márgenes
const axis = {
  primary: { type: 'band' },
  secondary: { type: 'linear' },
}

const margin = { left: 40, top: 20, right: 20, bottom: 40 }

// Ejes para líneas (puedes personalizarlo más)
const lineAxis = {
  primary: {
    type: 'band',
    format: (val) => val
  },
  secondary: {
    type: 'linear',
    domain: ['dataMin', 'dataMax'],
    ticks: 5
  }
}
</script>

<template>
  <v-container fluid>
    <v-row dense>
      <!-- Tickets por Estación (Bar) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Tickets por Estación</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2.5">
              <Chart
                :size="{ width: 500, height: 200 }"
                :data="barData"
                :margin="margin"
                direction="horizontal"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="3,3" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#90e0ef' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#90e0ef" small>Tickets</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Distribución por producto (ahora Line) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Distribución por Producto</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2.5">
              <Chart
                :size="{ width: 500, height: 200 }"
                :data="pieData"
                :margin="margin"
                direction="horizontal"
                :axis="lineAxis"
              >
                <template #layers>
                  <Grid strokeDasharray="2,2" />
                  <Line :dataKeys="['label', 'value']" :lineStyle="{ stroke: '#0077b6' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="primary" small>Productos</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Ganancias por Estación (convertido a AREA) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Ganancias por Estación</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2.5">
              <Chart
                :size="{ width: 500, height: 200 }"
                :data="gainsByStation"
                :margin="margin"
                direction="vertical"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="3,3" />
                  <Area :dataKeys="['label', 'value']" :areaStyle="{ fill: '#48cae4', stroke: '#0077b6' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#48cae4" small>Ganancias</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Ganancias por Día (Bar) -->
      <v-col cols="12" md="6" lg="6">
        <v-card>
          <v-card-title class="text-h6">Ganancias por Día</v-card-title>
          <v-card-text>
            <v-responsive aspect-ratio="2.5">
              <Chart
                :size="{ width: 500, height: 200 }"
                :data="gainsByDate"
                :margin="margin"
                direction="vertical"
                :axis="axis"
              >
                <template #layers>
                  <Grid strokeDasharray="3,3" />
                  <Bar :dataKeys="['label', 'value']" :barStyle="{ fill: '#0096c7' }" />
                </template>
                <template #widgets>
                  <Tooltip />
                </template>
              </Chart>
            </v-responsive>
            <div class="mt-2">
              <v-chip color="#0096c7" small>Ganancias</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
