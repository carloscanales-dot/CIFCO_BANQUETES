<template>

  <Head title="Ferias" />
  <AdminLayout>

    <!-- ENCABEZADO -->
    <div class="mb-4">
      <h5 class="text-h5 font-weight-bold">
        {{ page.props.fair ? 'Actualizar feria' : 'Nueva feria' }}
      </h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <v-card class="elevation-1 rounded-lg pa-4">

      <!-- TABS -->
      <v-tabs v-model="tab" background-color="white" color="black" slider-color="black" class="mb-4 text-black">
        <v-tab value="fair" class="text-body-1 font-weight-medium">
          Feria
        </v-tab>

        <v-tab value="stations" class="text-body-1 font-weight-medium">
          Estaciones
        </v-tab>
      </v-tabs>

      <v-divider class="mb-4" />

      <!-- PANEL -->
      <v-window v-model="tab">

        <!-- ======================= -->
        <!-- TAB: FERIA              -->
        <!-- ======================= -->
        <v-window-item value="fair">
          <v-card flat class="pa-4 rounded-lg mono-bg">

            <v-form @submit.prevent="submitFair">

              <v-row dense>
                <v-col cols="12" md="9">

                  <v-row dense>

                    <v-col cols="12">
                      <v-text-field v-model="fairForm.fair_name" label="Nombre de la feria"
                        :error-messages="fairErrors.fair_name" variant="outlined" density="comfortable"
                        class="mono-input" />
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-text-field v-model="fairForm.start_date" type="date" label="Desde"
                        :error-messages="fairErrors.start_date" variant="outlined" density="comfortable"
                        class="mono-input" />
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-text-field v-model="fairForm.end_date" type="date" label="Hasta"
                        :error-messages="fairErrors.end_date" variant="outlined" density="comfortable"
                        class="mono-input" />
                    </v-col>

                  </v-row>

                </v-col>

                <!-- RADIOGROUP COMPACTO -->
                <v-col cols="12" md="3">
                  <v-radio-group v-model="fairForm.status" :error-messages="fairErrors.status" label="Estatus"
                    class="compact-radio pt-2">
                    <v-radio :value="1" label="Programada" density="compact" color="grey-darken-3" />
                    <v-radio :value="2" label="Abierta" density="compact" color="grey-darken-3" />
                    <v-radio :value="3" label="Cerrada" density="compact" color="grey-darken-3" />
                  </v-radio-group>
                </v-col>
              </v-row>

              <div class="d-flex justify-space-between mt-4">
                <v-btn type="submit" :disabled="fairLoading" color="black" variant="flat"
                  class="text-white px-6 rounded-md">
                  Guardar Feria
                </v-btn>

                <Link href="/ticket/fair" as="div">
                <v-btn variant="outlined" color="grey-darken-3" class="px-6 rounded-md">
                  Cancelar
                </v-btn>
                </Link>
              </div>

            </v-form>
          </v-card>
        </v-window-item>

        <!-- ======================= -->
        <!-- TAB: ESTACIONES         -->
        <!-- ======================= -->
        <v-window-item value="stations">
          <v-card flat class="pa-4 rounded-lg mono-bg">

            <!-- FORM ESTACIONES -->
            <v-form @submit.prevent="submitStation">
              <v-row dense>

                <v-col cols="12" md="12">
                  <v-text-field v-model="stationForm.station_name" label="Nombre estación"
                    :error-messages="stationErrors.station_name" variant="outlined" density="comfortable"
                    class="mono-input" />

                  <v-select v-model="stationForm.location_id" :items="locations" item-title="location_name"
                    item-value="id" label="Ubicación" :error-messages="stationErrors.location_id" variant="outlined"
                    density="comfortable" class="mono-input" />

                  <v-btn type="submit" color="black" variant="flat" class="text-white mt-3 px-6 rounded-md">
                    Agregar Estación
                  </v-btn>
                </v-col>

              </v-row>
            </v-form>

            <!-- TABLA ESTACIONES -->
            <v-data-table :items="stations" :headers="stationHeaders" class="mt-6 mono-table rounded-lg"
              density="comfortable">
              <template #["item.action"]="{ item }">
                <v-btn small variant="outlined" color="grey-darken-3" class="px-3 rounded-md"
                  @click="stationStore.destroy(item.id)">
                  Eliminar
                </v-btn>
              </template>
            </v-data-table>

          </v-card>
        </v-window-item>

      </v-window>
    </v-card>

  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import { useFairStore } from '@/Stores/Ticket/fairStore'
import { useLocationStore } from '@/Stores/Ticket/locationStore'
import { useStationStore } from '@/Stores/Ticket/stationStore'
import { storeToRefs } from 'pinia'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const fairStore = useFairStore()
const { form: fairForm, errors: fairErrors, isLoading: fairLoading } = storeToRefs(fairStore)

const locationStore = useLocationStore()
const { locations } = storeToRefs(locationStore)

const stationStore = useStationStore()
const { stations, form: stationForm, errors: stationErrors } = storeToRefs(stationStore)

const stationHeaders = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'station_name' },
  { title: 'Ubicación', key: 'location.location_name' },
  { title: 'Estado', key: 'status' },
  { title: 'Acciones', key: 'action', sortable: false }
]

const tab = ref('fair')

const submitFair = () => {
  page.props.fair ? fairStore.update(page.props.fair.id) : fairStore.store()
}

const submitStation = () => {
  stationStore.store(fairForm.value.id)
}

onMounted(() => {
  locationStore.loadAll()
  page.props.fair
    ? (Object.assign(fairForm.value, page.props.fair), stationStore.load(fairForm.value.id))
    : (fairStore.resetForm(), stationStore.resetForm())
})
</script>

<script>
export default {
  data() {
    return {
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Ferias', disabled: false, href: '/ticket/fair' },
        { title: 'Crear/Actualizar', disabled: true },
      ]
    }
  }
}
</script>

<style scoped>
.mono-input .v-field {
  background-color: #fafafa !important;
  border-radius: 6px !important;
}

/* TABLA monocromática */
.mono-table thead th {
  background-color: #f3f3f3 !important;
  font-weight: bold !important;
  color: #333 !important;
}

.mono-bg {
  background-color: #f9f9f9 !important;
  border: 1px solid #e0e0e0 !important;
}

/* RADIOGROUP COMPACTO */
.compact-radio .v-radio {
  margin-bottom: 3px !important;
}

.compact-radio .v-selection-control {
  padding: 0 !important;
  min-height: 22px !important;
}

.compact-radio .v-label {
  font-size: 12px !important;
}

.compact-radio .v-icon {
  font-size: 16px !important;
}
</style>
