<script setup>
import { reactive, ref, inject, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useStationTicketStore } from '@/Stores/Ticket/stationTicketStore'
import { useStationStore } from '@/Stores/Ticket/stationStore'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { storeToRefs } from 'pinia'

const search = ref(null)
const queryString = ref([])
const helpers = inject('helpers')
const productStore = useProductStore()
const stationStore = useStationStore()
const stationTicketStore = useStationTicketStore()
const { products } = storeToRefs(productStore)
const { stations } = storeToRefs(stationStore)
const { items, totalItems, isLoading } = storeToRefs(stationTicketStore)

const filterForm = reactive({
  user_id: null,
  station_id: null,
  start_created_at: null,
  end_created_at: null,
})

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  if (search != null && search.length < 3) return

  let filters = {
    page: page,
    limit: itemsPerPage,
    sort: sortBy[0],
  }

  filters.search = helpers.removeEmptyAttribute(filterForm)

  stationTicketStore.index(filters)
}

const applyFilter = () => {
  search.value = String(Date.now())
  queryString.value = Object.entries(helpers.removeEmptyAttribute(filterForm))
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
    .join('&')
}

onMounted(() => {
  stationStore.ajaxList('A')
  productStore.ajaxList('A')
})
</script>
<template>
  <Head title="Tickets por estacion" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de tickets por estacion</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard title="Formulario de filtro">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6" sm="12">
            <VAutocomplete
              v-model="filterForm.station_id"
              label="Estacion"
              :items="stations"
              item-title="station_name"
              item-value="station_id"
              clearable
              hide-details
            >
            </VAutocomplete>
          </VCol>
          <VCol cols="12" md="6" sm="12">
            <VAutocomplete
              v-model="filterForm.product_id"
              label="Producto"
              :items="products"
              item-title="product_name"
              item-value="product_id"
              clearable
              hide-details
            >
            </VAutocomplete>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model="filterForm.start_created_at" type="date" label="Fecha de consumo inicial"></VTextField>
          </VCol>
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model="filterForm.end_created_at" type="date" label="Fecha de consumo final"></VTextField>
          </VCol>
        </VRow>
        <VRow dense>
          <VCol cols="12" md="12" sm="12">
            <VBtnToggle variant="tonal" divided>
              <VBtn prepend-icon="mdi-filter" text="Filtrar" color="primary" @click="applyFilter"></VBtn>
            </VBtnToggle>
          </VCol>
        </VRow>
        <VRow dense>
          <VCol cols="12" md="12" sm="12">
            <VDataTableServer
              :items="items"
              :items-length="totalItems"
              :headers="headers"
              :search="search"
              :loading="isLoading"
              @update:options="loadItems"
            >
            </VDataTableServer>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions>
        <a :href="`/ticket/stationTicket/export?${queryString}&type=excel`" target="_blank">
          <VIcon icon="mdi-file-excel"></VIcon> Excel
        </a>
      </VCardActions>
    </VCard>
  </AuthenticatedLayout>
</template>
<script>
export default {
  data() {
    return {
      headers: [
        { title: 'Producto', key: 'product_name' },
        { title: 'UUID', key: 'uuid' },
        { title: 'Estacion', key: 'station_name' },
        { title: 'Fecha de consumo', key: 'created_at' },
      ],
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Ticket por estacion', disabled: true },
      ],
    }
  },
}
</script>
