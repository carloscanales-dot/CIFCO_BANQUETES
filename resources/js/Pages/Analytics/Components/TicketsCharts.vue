<script setup>
import { Chart } from "vue3-charts";

const props = defineProps({
  generated: Array,
  redeemed: Array,
  byStation: Array,
  byProduct: Array,
});

// Convierte estructuras tipo [{period, total}] -> labels/data
function toLabelData(arr, labelKey, valueKey) {
  return {
    labels: arr.map(x => x[labelKey]),
    datasets: [
      {
        label: "",
        data: arr.map(x => x[valueKey]),
      },
    ],
  };
}
</script>

<template>
  <v-row dense class="mt-4">

    <!-- =============================== -->
    <!--   TICKETS GENERADOS POR FECHA   -->
    <!-- =============================== -->
    <v-col cols="12" md="6">
      <v-card class="pa-5" elevation="1">
        <div class="d-flex align-center mb-3">
          <v-icon class="mr-2" color="black">mdi-calendar-plus</v-icon>
          <h3 class="text-subtitle-1 font-weight-bold">Tickets generados</h3>
        </div>

        <Chart v-if="generated?.length" type="line" :data="toLabelData(generated, 'period', 'total')"
          :options="{ responsive: true, maintainAspectRatio: false }" style="height: 250px" />
      </v-card>
    </v-col>

    <!-- =============================== -->
    <!--   TICKETS CANJEADOS POR FECHA   -->
    <!-- =============================== -->
    <v-col cols="12" md="6">
      <v-card class="pa-5" elevation="1">
        <div class="d-flex align-center mb-3">
          <v-icon class="mr-2" color="green">mdi-check-circle</v-icon>
          <h3 class="text-subtitle-1 font-weight-bold">Tickets canjeados</h3>
        </div>

        <Chart v-if="redeemed?.length" type="line" :data="toLabelData(redeemed, 'period', 'total')"
          :options="{ responsive: true, maintainAspectRatio: false }" style="height: 250px" />
      </v-card>
    </v-col>

    <!-- =============================== -->
    <!--       CANJES POR ESTACIÓN       -->
    <!-- =============================== -->
    <v-col cols="12" md="6">
      <v-card class="pa-5" elevation="1">
        <div class="d-flex align-center mb-3">
          <v-icon class="mr-2" color="blue">mdi-home-map-marker</v-icon>
          <h3 class="text-subtitle-1 font-weight-bold">Canjes por estación</h3>
        </div>

        <Chart v-if="byStation?.length" type="bar" :data="{
          labels: byStation.map(x => x.station_name),
          datasets: [{ data: byStation.map(x => x.redeemed_count) }]
        }" :options="{ responsive: true, maintainAspectRatio: false }" style="height: 300px" />
      </v-card>
    </v-col>

    <!-- =============================== -->
    <!--       CANJES POR PRODUCTO       -->
    <!-- =============================== -->
    <v-col cols="12" md="6">
      <v-card class="pa-5" elevation="1">
        <div class="d-flex align-center mb-3">
          <v-icon class="mr-2" color="purple">mdi-package-variant</v-icon>
          <h3 class="text-subtitle-1 font-weight-bold">Canjes por producto</h3>
        </div>

        <Chart v-if="byProduct?.length" type="donut" :data="{
          labels: byProduct.map(x => x.product_name),
          datasets: [{ data: byProduct.map(x => x.redeemed) }]
        }" :options="{ responsive: true, maintainAspectRatio: false }" style="height: 300px" />
      </v-card>
    </v-col>

  </v-row>
</template>
