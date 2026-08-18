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

          <div class="d-flex ga-2">
            <VBtn prepend-icon="mdi-content-copy" color="grey-darken-3" variant="tonal" @click="openCloneDialog">
              Clonar feria
            </VBtn>
            <VBtn prepend-icon="mdi-plus" color="grey-darken-3" variant="outlined"
              @click="router.visit('/ticket/fair/create')">
              Agregar
            </VBtn>
          </div>
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
              <div class="d-flex align-center ga-2">
                <VBtn v-if="item.status !== 2" size="x-small" color="green-darken-2" variant="tonal"
                  prepend-icon="mdi-lock-open-variant" :loading="isLoading" @click="fairStore.changeStatus(item.id, 2)">
                  Abrir
                </VBtn>
                <VBtn v-else size="x-small" color="red-darken-2" variant="tonal" prepend-icon="mdi-lock"
                  :loading="isLoading" @click="fairStore.changeStatus(item.id, 3)">
                  Cerrar
                </VBtn>

                <Link :href="`/ticket/fair/${item.id}/edit`" as="button">
                <VIcon icon="mdi-pencil" size="20" color="grey-darken-2" />
                </Link>
              </div>
            </template>

          </VDataTableServer>
        </div>

      </VCardText>
    </VCard>

    <!-- CLONAR FERIA DIALOG -->
    <VDialog v-model="cloneDialog" max-width="620" persistent>
      <VCard rounded="lg">
        <VCardTitle class="text-h6 font-weight-bold py-3">Clonar feria</VCardTitle>
        <VDivider />
        <VCardText class="pt-4">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Crea una feria nueva copiando los stands de una feria origen como punto de partida.
            Los usuarios y productos no se duplican: solo se re-asignan a los stands nuevos. Todo queda
            editable después.
          </p>

          <VRow dense>
            <VCol cols="12">
              <VAutocomplete v-model="cloneForm.source_fair_id" :items="fairs || []" item-title="fair_name"
                item-value="id" label="Feria origen" density="comfortable" variant="outlined" clearable
                auto-select-first no-data-text="Sin coincidencias" />
            </VCol>
            <VCol cols="12" md="8">
              <VTextField v-model="cloneForm.fair_name" label="Nombre de la feria nueva" density="comfortable"
                variant="outlined" />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect v-model="cloneForm.status" :items="cloneStatusList" item-title="title" item-value="value"
                label="Estado inicial" density="comfortable" variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="cloneForm.start_date" type="date" label="Desde" density="comfortable"
                variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="cloneForm.end_date" type="date" label="Hasta" density="comfortable"
                variant="outlined" />
            </VCol>
          </VRow>

          <VDivider class="my-3" />
          <div class="text-subtitle-2 mb-2">Qué copiar a los stands nuevos</div>
          <VRow dense>
            <VCol cols="12" md="6">
              <VSwitch v-model="cloneForm.only_active_stations" color="black" hide-details density="compact"
                label="Solo stands activos" />
              <VSwitch v-model="cloneForm.copy_products" color="black" hide-details density="compact"
                label="Copiar productos por stand" />
            </VCol>
            <VCol cols="12" md="6">
              <VSwitch v-model="cloneForm.copy_users" color="black" hide-details density="compact"
                label="Copiar usuarios por stand" />
              <VSwitch v-model="cloneForm.copy_printers" color="black" hide-details density="compact"
                label="Copiar impresoras por stand" />
              <VSwitch v-model="cloneForm.copy_terminals" color="black" hide-details density="compact"
                label="Copiar terminales (cajas cerradas)" />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="px-4 py-3">
          <VSpacer />
          <VBtn variant="text" @click="cloneDialog = false">Cancelar</VBtn>
          <VBtn color="black" variant="flat" class="text-white" :loading="isLoading"
            :disabled="!cloneForm.source_fair_id || !cloneForm.fair_name || !cloneForm.start_date || !cloneForm.end_date"
            @click="submitClone">
            Clonar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

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
const { items, totalItems, isLoading, fairs } = storeToRefs(fairStore)

const filterForm = reactive({
  fair_name: null,
  status: null,
})

// --- Clonar feria ---
const cloneDialog = ref(false)
const cloneForm = reactive({
  source_fair_id: null,
  fair_name: '',
  start_date: '',
  end_date: '',
  status: 1, // Programada por defecto
  copy_products: true,
  copy_users: false,
  copy_printers: false,
  copy_terminals: false,
  only_active_stations: true,
})

const cloneStatusList = [
  { title: 'Programada', value: 1 },
  { title: 'Abierta', value: 2 },
]

const openCloneDialog = () => {
  fairStore.ajaxList('T') // cargar todas las ferias como posible origen
  cloneDialog.value = true
}

const submitClone = () => {
  fairStore.clone({ ...cloneForm })
  cloneDialog.value = false
}

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
