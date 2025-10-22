<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useFairStore } from '@/Stores/Ticket/fairStore'
import { useLocationStore } from '@/Stores/Ticket/locationStore'
import { useStationStore } from '@/Stores/Ticket/stationStore'
import { storeToRefs } from 'pinia'

const page = usePage()

// Stores
const fairStore = useFairStore()
const { form: fairForm, errors: fairErrors, isLoading: fairLoading } = storeToRefs(fairStore)

const locationStore = useLocationStore()
const { locations, form: locationForm, errors: locationErrors } = storeToRefs(locationStore)

const stationStore = useStationStore()
const { stations, form: stationForm, errors: stationErrors } = storeToRefs(stationStore)

const locationHeaders = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'location_name' },
  { title: 'Acciones', key: 'action', sortable: false }
]

const stationHeaders = [
    { title: 'ID', key: 'id' },
    { title: 'Nombre', key: 'station_name' },
    { title: 'Estado', key: 'status' },
    { title: 'Acciones', key: 'action', sortable: false }
]

// Tabs
const tab = ref('fair')
const selectedLocationId = ref(null)

// Submit functions
const submitFair = () => {
  page.props.fair ? fairStore.update(page.props.fair.id) : fairStore.store()
}

const submitLocation = () => {
  locationStore.store(fairForm.id) // Asociar a la feria actual
}

const submitStation = () => {
  stationStore.store(selectedLocationId.value) // Asociar a la ubicación seleccionada
}

// Cargar datos al editar
onMounted(() => {
  if (page.props.fair) {
    Object.assign(fairForm.value, page.props.fair)
    locationStore.load(fairForm.value.id)
  }
})
</script>

<template>
  <Head title="Ferias" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">
        {{ page.props.fair ? 'Actualizar feria' : 'Nueva feria' }}
      </h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <v-sheet elevation="4">
      <!-- Tabs -->
      <v-tabs v-model="tab" color="primary">
        <v-tab value="fair">Feria</v-tab>
        <v-tab value="locations">Ubicaciones</v-tab>
        <v-tab value="stations">Estaciones</v-tab>
      </v-tabs>

      <v-divider></v-divider>

      <!-- Tab content -->
      <v-window v-model="tab">
        <!-- TAB FERIA -->
        <v-window-item value="fair">
          <v-sheet class="pa-4">
            <v-form @submit.prevent="submitFair">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="fairForm.fair_name" label="Nombre de la feria" :error-messages="fairErrors.fair_name"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-radio-group v-model="fairForm.status" label="Estatus" :error-messages="fairErrors.status" inline>
                    <v-radio :value="1" label="Programada"/>
                    <v-radio :value="2" label="Abierta"/>
                    <v-radio :value="3" label="Cerrada"/>
                  </v-radio-group>
                </v-col>
              </v-row>
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="fairForm.start_date" type="date" label="Desde" :error-messages="fairErrors.start_date"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="fairForm.end_date" type="date" label="Hasta" :error-messages="fairErrors.end_date"/>
                </v-col>
              </v-row>
              <v-row>
                <v-col cols="12">
                  <v-btn type="submit" :disabled="fairLoading" color="primary">Guardar Feria</v-btn>
                  <Link href="/ticket/fair" as="div">
                    <v-btn text color="error">Cancelar</v-btn>
                  </Link>
                </v-col>
              </v-row>
            </v-form>
          </v-sheet>
        </v-window-item>

        <!-- TAB UBICACIONES -->
        <v-window-item value="locations">
          <v-sheet class="pa-4">
            <v-form @submit.prevent="submitLocation">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="locationForm.location_name" label="Nombre ubicación" :error-messages="locationErrors.location_name"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-btn type="submit" color="primary">Agregar Ubicación</v-btn>
                </v-col>
              </v-row>
            </v-form>

            <v-data-table :items="locations" :headers="locationHeaders">
              <template #[`item.action`]="{ item }">
                <v-btn small color="error" @click="locationStore.destroy(item.id)">Eliminar</v-btn>
              </template>
            </v-data-table>
          </v-sheet>
        </v-window-item>

        <!-- TAB ESTACIONES -->
        <v-window-item value="stations">
          <v-sheet class="pa-4">
            <v-select
              v-model="selectedLocationId"
              :items="locations"
              item-title="location_name"
              item-value="id"
              label="Selecciona ubicación"
              clearable
            />

            <v-form @submit.prevent="submitStation">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="stationForm.station_name" label="Nombre estación" :error-messages="stationErrors.station_name"/>
                </v-col>
                <v-col cols="12" md="6">
                  <v-btn type="submit" color="primary">Agregar Estación</v-btn>
                </v-col>
              </v-row>
            </v-form>

            <v-data-table
              :items="stations.filter(s => s.location_id === selectedLocationId)"
              :headers="stationHeaders"
            >
              <template #[`item.action`]="{ item }">
                <v-btn small color="error" @click="stationStore.destroy(item.id)">Eliminar</v-btn>
              </template>
            </v-data-table>
          </v-sheet>
        </v-window-item>
      </v-window>
    </v-sheet>
  </AuthenticatedLayout>
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