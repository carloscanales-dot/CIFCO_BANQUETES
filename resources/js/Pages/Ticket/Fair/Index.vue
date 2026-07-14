<template>

  <Head title="Feria" />
  <AdminLayout>

    <!-- TÍTULO + BREADCRUMBS -->
    <div class="mb-4">
      <h5 class="text-h5 font-weight-bold">Consulta de ferias</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <!-- CARD PRINCIPAL -->
    <VCard class="elevation-1" rounded="lg">
      <VCardTitle class="text-h6 font-weight-bold py-3">
        Filtros de búsqueda
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">

        <!-- FILTROS -->
        <VRow dense>
          <VCol cols="12" md="6">
            <VTextField v-model="filterForm.fair_name" label="Nombre de la feria" hide-details density="comfortable"
              variant="outlined" class="mono-input" clearable />
          </VCol>

          <VCol cols="12" md="3">
            <VSelect v-model="filterForm.status" :items="statusList" item-title="title" item-value="key" label="Estatus"
              hide-details density="comfortable" variant="outlined" class="mono-input" clearable />
          </VCol>
        </VRow>

        <!-- BOTONES -->
        <div class="d-flex justify-space-between align-center mt-4">
          <VBtn prepend-icon="mdi-filter" color="black" variant="flat" class="text-white" @click="applyFilter">
            Filtrar
          </VBtn>

          <VBtn prepend-icon="mdi-plus" color="grey-darken-3" variant="outlined"
            @click="router.visit('/ticket/fair/create')">
            Agregar
          </VBtn>
        </div>

        <!-- TABLA -->
        <div class="mt-5">
          <VDataTableServer :items="items || []" :items-length="totalItems || 0" :headers="headers" :loading="isLoading"
            :search="search" class="mono-table elevation-1" @update:options="loadItems">
            <!-- ESTATUS -->
            <template #["item.status"]="{ item }">
              <VChip variant="tonal" color="grey-darken-2" size="x-small" class="px-2 py-1 text-white"
                style="font-size: 10px;">
                {{ statusText[item.status] || '' }}
              </VChip>
            </template>

            <!-- ACCIÓN -->
            <template #["item.action"]="{ item }">
              <Link :href="`/ticket/fair/${item.id}/edit`" as="button">
              <VIcon icon="mdi-pencil" size="20" color="grey-darken-2" />
              </Link>
            </template>

          </VDataTableServer>
        </div>

      </VCardText>
    </VCard>

    <!-- DELETE DIALOG -->
    <DeleteDialog v-model="deleteDialog" title="Eliminar feria" @close-delete-dialog="deleteDialog = false"
      @delete-item="submitDelete" />

  </AdminLayout>
</template>

<script setup>
import { reactive, ref, inject } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useFairStore } from '@/Stores/Ticket/fairStore'
import { storeToRefs } from 'pinia'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const search = ref(null)
const deleteId = ref(null)
const deleteDialog = ref(false)

const statusText = {
  1: 'Programada',
  2: 'Abierta',
  3: 'Cerrada'
}

const helpers = inject('helpers')
const fairStore = useFairStore()
const { items, totalItems, isLoading } = storeToRefs(fairStore)

const filterForm = reactive({
  fair_name: null,
  status: null,
})

const deleteItem = (item) => {
  deleteId.value = item.fair_id
  deleteDialog.value = true
}

const submitDelete = () => {
  fairStore.destroy(deleteId.value)
  deleteDialog.value = false
}

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  if (search != null && search.length < 3) return

  fairStore.index({
    page,
    limit: itemsPerPage,
    sort: sortBy[0],
    search: helpers.removeEmptyAttribute(filterForm),
  })
}

const applyFilter = () => {
  search.value = String(Date.now())
}
</script>

<script>
export default {
  data() {
    return {
      headers: [
        { title: 'Nombre de la feria', key: 'fair_name' },
        { title: 'Desde', key: 'start_date' },
        { title: 'Hasta', key: 'end_date' },
        { title: 'Estatus', key: 'status' },
        { title: 'Acción', key: 'action', sortable: false },
      ],
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Feria', disabled: true },
      ],
      statusList: [
        { title: 'Programada', key: 1 },
        { title: 'Abierta', key: 2 },
        { title: 'Cerrada', key: 3 },
      ],
    }
  },
}
</script>

<style scoped>
/* Input monocromático */
.mono-input .v-field {
  background-color: #fafafa !important;
  border-radius: 6px !important;
}

/* Tabla elegante */
.mono-table thead th {
  background-color: #f3f3f3 !important;
  font-weight: bold !important;
  color: #333 !important;
}

.mono-table {
  border-radius: 10px !important;
  overflow: hidden;
}
</style>
