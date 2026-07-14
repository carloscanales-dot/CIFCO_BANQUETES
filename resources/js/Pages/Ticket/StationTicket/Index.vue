<script setup>
import { onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useStationTicketStore } from '@/Stores/Ticket/stationTicketStore'
import { storeToRefs } from 'pinia'

const stationTicketStore = useStationTicketStore()
const { items, totalItems, isLoading } = storeToRefs(stationTicketStore)

// no filter form needed for this view

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  let filters = {
    page: page,
    limit: itemsPerPage,
    sort: sortBy[0],
  }

  stationTicketStore.index(filters)
}

onMounted(() => {
  // initial load will be triggered by the table options event
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
        <VRow dense>
          <VCol cols="12" md="12" sm="12">
            <VDataTableServer
              :items="items || []"
              :items-length="totalItems || 0"
              :headers="headers"
              :loading="isLoading"
              @update:options="loadItems"
            >
            </VDataTableServer>
          </VCol>
        </VRow>
      </VCardText>
      <!-- no actions for this simplified view -->
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
