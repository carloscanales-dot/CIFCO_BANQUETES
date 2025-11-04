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

  // 🔹 Si no hay feria cargada (nueva), resetea el formulario
  if (!page.props.fair) {
    fairStore.resetForm()
  } else {
    // 🔹 Si se está editando una feria, carga sus datos
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

    <v-sheet elevation="4" class="pa-6 rounded-md"
      style="background-color: #fff; color: #000; border: 1px solid #e0e0e0;">
      <!-- Tabs -->
      <v-tabs v-model="tab" background-color="white" color="black" slider-color="black" class="mb-4">
        <v-tab value="fair" class="text-black text-body-1 font-weight-medium">
          Feria
        </v-tab>
        <v-tab value="stations" class="text-black text-body-1 font-weight-medium">
          Estaciones
        </v-tab>
      </v-tabs>

      <v-divider style="border-color: #000;"></v-divider>

      <!-- Contenido -->
      <v-window v-model="tab" class="mt-4">
        <!-- TAB FERIA -->
        <v-window-item value="fair">
          <v-sheet class="pa-4 rounded-md" style="background-color: #f9f9f9; border: 1px solid #e0e0e0;">
            <v-form @submit.prevent="submitFair">
              <v-row class="g-4 align-center flex-wrap">
                <!-- Columna de campos -->
                <v-col cols="12" md="9">
                  <v-row class="g-4">
                    <v-col cols="12" md="12">
                      <v-text-field v-model="fairForm.fair_name" label="Nombre de la feria" variant="outlined"
                        color="black" base-color="black" bg-color="white" :error-messages="fairErrors.fair_name" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="fairForm.start_date" type="date" label="Desde" variant="outlined"
                        color="black" base-color="black" bg-color="white" :error-messages="fairErrors.start_date" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="fairForm.end_date" type="date" label="Hasta" variant="outlined"
                        color="black" base-color="black" bg-color="white" :error-messages="fairErrors.end_date" />
                    </v-col>
                  </v-row>
                </v-col>
                <!-- Columna de radio buttons -->
                <v-col cols="12" md="3">
                  <v-radio-group v-model="fairForm.status" label="Estatus" :error-messages="fairErrors.status"
                    color="black" class="d-flex justify-center">
                    <v-radio :value="1" label="Programada" />
                    <v-radio :value="2" label="Abierta" />
                    <v-radio :value="3" label="Cerrada" />
                  </v-radio-group>
                </v-col>
              </v-row>
              <v-col cols="12" class="d-flex gap-3 justify-space-between">
                <v-btn type="submit" :disabled="fairLoading" style="background-color: #000; color: #fff;"
                  class="px-6 text-none rounded-md" variant="elevated">
                  Guardar Feria
                </v-btn>
                <Link href="/ticket/fair" as="div">
                <v-btn variant="elevated" color="red" class="px-6 text-none rounded-md">
                  Cancelar
                </v-btn>
                </Link>
              </v-col>
            </v-form>
          </v-sheet>
        </v-window-item>
        <!-- TAB ESTACIONES -->
        <v-window-item value="stations">
          <v-sheet class="pa-4 rounded-md" style="background-color: #f9f9f9; border: 1px solid #e0e0e0;">
            <!-- Formulario estaciones -->
            <v-form @submit.prevent="submitStation">
              <v-row class="g-4 align-center flex-wrap justify-end">
                <v-col cols="12" md="12">
                  <v-text-field v-model="stationForm.station_name" label="Nombre estación" variant="solo" color="black"
                    base-color="black" bg-color="white" :error-messages="stationErrors.station_name" />
                  <v-select v-model="stationForm.location_id" :items="locations" item-title="location_name"
                    item-value="id" label="Selecciona ubicación" variant="solo" color="black" base-color="black"
                    bg-color="white" :error-messages="stationErrors.location_id" />
                  <v-btn type="submit" style="background-color: #000; color: #fff;" class="px-6 text-none rounded-md">
                    Agregar Estación
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
            <!-- Tabla estaciones -->
            <v-data-table :items="stations" :headers="stationHeaders" class="mt-6 rounded-lg" density="comfortable"
              style="border: 1px solid #e0e0e0; background-color: #fff; color: #000;">
              <template #[`item.action`]="{ item }">
                <v-btn small variant="outlined" style="border-color: #000; color: #000;"
                  @click="stationStore.destroy(item.id)">
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
