<script setup>
import { ref } from "vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    stations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] }, // [{id:0,name:'Canjeado'}, {id:1,name:'Pendiente'}]
})

// Formulario reactivo con valores iniciales
const form = ref({
    date_from: props.filters.date_from ?? "",
    date_to: props.filters.date_to ?? "",
    station_id: props.filters.station_id ?? "",
    product_id: props.filters.product_id ?? "",
    status: props.filters.status !== undefined ? props.filters.status : "",
    group: props.filters.group ?? "day",
})

/**
 * Aplicar filtros → recarga el análisis de tickets
 */
function applyFilters() {
    router.get(
        route("dashboard.tickets.data"),
        form.value,
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

/**
 * Limpiar filtros → recargar sin parámetros
 */
function clearFilters() {
    form.value = {
        date_from: "",
        date_to: "",
        station_id: "",
        product_id: "",
        status: "",
        group: "day",
    }

    router.get(
        route("dashboard.tickets.data"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}
</script>

<template>
    <v-card class="pa-6">

        <!-- TÍTULO PRINCIPAL -->
        <div class="d-flex align-center mb-6">
            <v-icon color="primary" class="mr-2">mdi-filter-variant</v-icon>
            <h3 class="text-h6 font-weight-bold">Filtros avanzados</h3>
        </div>

        <!-- ========================= -->
        <!--      SECCIÓN: FECHAS      -->
        <!-- ========================= -->
        <v-divider class="mb-4" />
        <div class="text-subtitle-2 text-medium-emphasis mb-2">
            <v-icon size="16" class="mr-1">mdi-calendar</v-icon>
            Rango de fechas
        </div>

        <v-row dense>
            <v-col cols="12" md="6">
                <v-text-field type="date" label="Fecha desde" v-model="form.date_from" variant="solo"
                    density="comfortable" prepend-inner-icon="mdi-calendar-start" />
            </v-col>

            <v-col cols="12" md="6">
                <v-text-field type="date" label="Fecha hasta" v-model="form.date_to" variant="solo"
                    density="comfortable" prepend-inner-icon="mdi-calendar-end" />
            </v-col>
        </v-row>

        <!-- ========================= -->
        <!--   SECCIÓN: CATEGORÍAS     -->
        <!-- ========================= -->
        <v-divider class="my-6" />
        <div class="text-subtitle-2 text-medium-emphasis mb-2">
            <v-icon size="16" class="mr-1">mdi-format-list-bulleted</v-icon>
            Opciones de filtrado
        </div>

        <v-row dense>
            <v-col cols="12" md="6">
                <v-select label="Agrupar por" :items="[
                    { title: 'Diario', value: 'day' },
                    { title: 'Semanal', value: 'week' },
                    { title: 'Mensual', value: 'month' }
                ]" v-model="form.group" variant="solo" density="comfortable" prepend-inner-icon="mdi-chart-line" />
            </v-col>

            <v-col cols="12" md="6">
                <v-select label="Estación" :items="stations.map(s => ({
                    title: s.station_name,
                    value: s.id
                }))" v-model="form.station_id" clearable variant="solo" density="comfortable"
                    prepend-inner-icon="mdi-map-marker" />
            </v-col>

            <v-col cols="12" md="6">
                <v-select label="Producto" :items="products.map(p => ({
                    title: p.product_name,
                    value: p.id
                }))" v-model="form.product_id" clearable variant="solo" density="comfortable"
                    prepend-inner-icon="mdi-package-variant" />
            </v-col>

            <v-col cols="12" md="6">
                <v-select label="Estado" :items="[
                    { title: 'Todos', value: '' },
                    ...statuses.map(s => ({
                        title: s.name,   // Canjeado | Pendiente
                        value: s.id      // 0 | 1
                    }))
                ]" v-model="form.status" variant="solo" density="comfortable"
                    prepend-inner-icon="mdi-ticket-confirmation" />
            </v-col>
        </v-row>

        <!-- ========================= -->
        <!--        BOTONES            -->
        <!-- ========================= -->
        <v-divider class="my-6" />

        <v-row align="center">
            <v-col cols="6" class="text-left">
                <v-btn color="black" prepend-icon="mdi-filter-check" class="px-6" @click="applyFilters">
                    Aplicar
                </v-btn>
            </v-col>

            <v-col cols="6" class="text-right">
                <v-btn color="grey-darken-1" variant="tonal" prepend-icon="mdi-broom" class="px-6"
                    @click="clearFilters">
                    Limpiar
                </v-btn>
            </v-col>
        </v-row>

    </v-card>
</template>
