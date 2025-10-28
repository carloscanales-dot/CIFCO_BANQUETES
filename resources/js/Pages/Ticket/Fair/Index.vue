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
// justo después de tus imports y refs
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

  let filters = {
    page: page,
    limit: itemsPerPage,
    sort: sortBy[0],
  }

  filters.search = helpers.removeEmptyAttribute(filterForm)

  fairStore.index(filters)
}

const applyFilter = () => {
  search.value = String(Date.now())
}
</script>
<template>
  <Head title="Feria" />
  <AdminLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de ferias</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard title="Filtro de ferias">
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model="filterForm.fair_name" label="Nombre de la feria" hide-details clearable></VTextField>
          </VCol>
          <VCol cols="12" md="3" sm="12">
            <VSelect
              v-model="filterForm.status"
              label="Estatus"
              :items="statusList"
              item-title="title"
              item-value="key"
              hide-details
              clearable
            ></VSelect>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="12" sm="12">
            <VBtnToggle variant="tonal" divided>
              <VBtn prepend-icon="mdi-filter" text="Filtrar" color="primary" @click="applyFilter"></VBtn>
              <VBtn prepend-icon="mdi-plus" text="Agregar" @click="router.visit('/ticket/fair/create')"></VBtn>
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
              <!-- Columna Status: muestra texto en vez de número -->
              <template #[`item.status`]="{ item }">
                <span>{{ statusText[item.status] || '' }}</span>
              </template>

              <!-- Columna Acción -->
              <template #[`item.action`]="{ item }">
                <Link :href="`/ticket/fair/${item.id}/edit`" as="button">
                  <VIcon color="warning" icon="mdi-pencil" />
                </Link>
              </template>
            </VDataTableServer>

          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <DeleteDialog
      v-model="deleteDialog"
      title="Eliminar el producto"
      @close-delete-dialog="deleteDialog = false"
      @delete-item="submitDelete"
    ></DeleteDialog>
  </AdminLayout>
</template>
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
        { title: 'Programada', key: 'Programada' },
        { title: 'Abierta', key: 'Abierta' },
        { title: 'Cerrada', key: 'Cerrada' },
      ],
    }
  },
}
</script>
