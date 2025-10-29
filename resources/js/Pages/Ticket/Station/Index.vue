<script setup>
import { reactive, ref, inject } from 'vue'
import { Head } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useStationStore } from '@/Stores/Ticket/stationStore'
import { storeToRefs } from 'pinia'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StationProductDialog from '@/Components/Ticket/StationProductDialog.vue'

const search = ref(null)
const deleteId = ref(null)
const deleteDialog = ref(false)
const productDialog = ref(false)
const selectedStation = ref(null)

const helpers = inject('helpers')
const stationStore = useStationStore()
const { items, totalItems, isLoading } = storeToRefs(stationStore)

const filterForm = reactive({
  station_name: null,
  status: null,
})

const headers = [
  { title: 'Nombre de la estación', key: 'station_name' },
  { title: 'Estatus', key: 'status' },
  { title: 'Feria', key: 'fair.fair_name' },
  { title: 'Ubicación', key: 'location.location_name' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

const breadcrumbs = [
  { title: 'Panel', disabled: false, href: '/dashboard' },
  { title: 'Estaciones de servicio', disabled: true },
]

const deleteItem = (item) => {
  deleteId.value = item.station_id
  deleteDialog.value = true
}

const openProductDialog = (station) => {
  selectedStation.value = station
  productDialog.value = true
}

const submitDelete = () => {
  stationStore.destroy(deleteId.value)
  deleteDialog.value = false
}

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  if (search != null && search.length < 3) return

  const filters = {
    page: page,
    limit: itemsPerPage,
    sort: sortBy?.[0],
    search: helpers.removeEmptyAttribute(filterForm),
  }

  stationStore.index(filters)
}

const applyFilter = () => {
  search.value = String(Date.now())
}
</script>

<template>
  <Head title="Estaciones de servicio" />
  <AdminLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de estaciones de servicio</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <VCard title="Formulario de filtro">
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="6">
            <VTextField
              v-model="filterForm.station_name"
              label="Nombre de la estación"
              hide-details
              clearable
            />
          </VCol>

          <VCol cols="12" md="6">
            <VRadioGroup v-model="filterForm.status" label="Estatus" hide-details inline>
              <VRadio value="Activa" label="Activa" />
              <VRadio value="Inactiva" label="Inactiva" />
            </VRadioGroup>
          </VCol>
        </VRow>

        <VRow dense>
          <VCol cols="12">
            <VBtn prepend-icon="mdi-filter" color="primary" @click="applyFilter">
              Filtrar
            </VBtn>
          </VCol>
        </VRow>

        <VRow dense>
          <VCol cols="12">
            <VDataTableServer
              :items="items"
              :items-length="totalItems"
              :headers="headers"
              :search="search"
              :loading="isLoading"
              @update:options="loadItems"
            >
              <template #item.actions="{ item }">
                <VBtn color="success" @click="openProductDialog(item)">
                  PRODUCTO
                </VBtn>
              </template>
            </VDataTableServer>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <DeleteDialog
      v-model="deleteDialog"
      title="Eliminar estación"
      @close-delete-dialog="deleteDialog = false"
      @delete-item="submitDelete"
    />

    <StationProductDialog
      v-model="productDialog"
      :station="selectedStation"
    />
  </AdminLayout>
</template>
