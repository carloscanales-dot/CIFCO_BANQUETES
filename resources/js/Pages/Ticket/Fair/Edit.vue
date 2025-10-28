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

// Stores
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

// Tabs
const tab = ref('fair')
// Submit functions
const submitFair = () => {
  page.props.fair ? fairStore.update(page.props.fair.id) : fairStore.store()
}

const submitStation = () => {
  stationStore.store(fairForm.value.id)
}

// Cargar datos al editar
onMounted(() => {
  locationStore.loadAll()
  if (page.props.fair) {
    Object.assign(fairForm.value, page.props.fair)
    stationStore.load(fairForm.value.id)
  }
})
</script>

<template>

  <Head title="Ferias" />
  <AdminLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">
        {{ page.props.fair ? 'Actualizar feria' : 'Nueva feria' }}
      </h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

   <v-sheet elevation="4" class="pa-4 rounded-lg">
  <!-- Tabs -->
  <v-tabs v-model="tab" color="primary" background-color="white" class="mb-4 rounded-lg">
    <v-tab value="fair">Feria</v-tab>
    <v-tab value="stations">Estaciones</v-tab>
  </v-tabs>

  <v-divider></v-divider>

  <!-- Tab content -->
  <v-window v-model="tab" class="mt-4">
    <!-- TAB FERIA -->
    <v-window-item value="fair">
      <v-sheet class="pa-4" elevation="2" rounded="lg">
        <v-form @submit.prevent="submitFair">
          <v-row class="g-4 align-items-center">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="fairForm.fair_name"
                label="Nombre de la feria"
                :error-messages="fairErrors.fair_name"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-radio-group
                v-model="fairForm.status"
                label="Estatus"
                :error-messages="fairErrors.status"
                inline
              >
                <v-radio :value="1" label="Programada" />
                <v-radio :value="2" label="Abierta" />
                <v-radio :value="3" label="Cerrada" />
              </v-radio-group>
            </v-col>
          </v-row>

          <v-row class="g-4">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="fairForm.start_date"
                type="date"
                label="Desde"
                :error-messages="fairErrors.start_date"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="fairForm.end_date"
                type="date"
                label="Hasta"
                :error-messages="fairErrors.end_date"
                variant="outlined"
              />
            </v-col>
          </v-row>

          <v-row class="mt-4">
            <v-col cols="12" class="d-flex gap-3">
              <v-btn type="submit" :disabled="fairLoading" color="primary" class="px-6">
                Guardar Feria
              </v-btn>
              <Link href="/ticket/fair" as="div">
                <v-btn text color="error" class="px-6">Cancelar</v-btn>
              </Link>
            </v-col>
          </v-row>
        </v-form>
      </v-sheet>
    </v-window-item>

    <!-- TAB ESTACIONES -->
    <v-window-item value="stations">
      <v-sheet class="pa-4" elevation="2" rounded="lg">
        <!-- Formulario de estaciones -->
        <v-form @submit.prevent="submitStation">
          <v-row class="align-items-center g-4">
            <v-col cols="12" md="5">
              <v-text-field
                v-model="stationForm.station_name"
                label="Nombre estación"
                variant="solo"
                :error-messages="stationErrors.station_name"
              />
            </v-col>

            <v-col cols="12" md="5">
              <v-select
                v-model="stationForm.location_id"
                :items="locations"
                item-title="location_name"
                item-value="id"
                label="Selecciona ubicación"
                variant="solo"
                :error-messages="stationErrors.location_id"
              />
            </v-col>

            <v-col cols="12" md="2" class="d-flex justify-end">
              <v-btn type="submit" color="black" class="text-white px-4">
                Agregar Estación
              </v-btn>
            </v-col>
          </v-row>
        </v-form>

        <!-- Tabla de estaciones -->
        <v-data-table
          :items="stations"
          :headers="stationHeaders"
          class="mt-6"
          elevation="1"
          dense
        >
          <template #[`item.status`]="{ item }">
            <span>{{ item.status ? 'Activa' : 'Inactiva' }}</span>
          </template>

          <template #[`item.action`]="{ item }">
            <v-btn small color="error" @click="stationStore.destroy(item.id)">
              Eliminar
            </v-btn>
          </template>
        </v-data-table>
      </v-sheet>
    </v-window-item>
  </v-window>
</v-sheet>

  </AdminLayout>
</template>

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
