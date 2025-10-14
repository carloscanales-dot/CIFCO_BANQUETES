<script setup>
import { storeToRefs } from 'pinia'
import { reactive, ref, inject, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useTicketStore } from '@/Stores/Ticket/ticketStore'
import { useProductStore } from '@/Stores/Ticket/productStore'
import GenTicketDialog from '@/Components/Ticket/GenTicketDialog.vue'
import { filterItems } from 'vuetify/lib/composables/filter'

const search = ref(null)
const deleteId = ref(null)
const queryString = ref([])
const deleteDialog = ref(false)
const generateDialog = ref(false)
const helpers = inject('helpers')
const ticketStore = useTicketStore()
const productStore = useProductStore()
const { products } = storeToRefs(productStore)
const { items, totalItems, isLoading } = storeToRefs(ticketStore)

const filterForm = reactive({
  uuid: null,
  status: null,
  product_id: null,
})

const deleteItem = (item) => {
  deleteId.value = item.product_id
  deleteDialog.value = true
}

const submitDelete = () => {
  ticketStore.destroy(deleteId.value)
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

  ticketStore.index(filters)
}

const applyFilter = () => {
  search.value = String(Date.now())
  queryString.value = Object.entries(helpers.removeEmptyAttribute(filterForm))
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
    .join('&')
}

const genSubmit = () => {
  generateDialog.value = false
}

onMounted(() => {
  productStore.ajaxList('Activo')
})
</script>
<template>
  <Head title="Ticket" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de tickets</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard title="Formulario de filtro">
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="12" sm="12">
            <VAutocomplete
              v-model="filterForm.product_id"
              label="Nombre del producto"
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
            <VTextField v-model="filterForm.uuid" label="UUID del ticket" hide-details clearable></VTextField>
          </VCol>
          <VCol cols="12" md="6" sm="12">
            <VSelect
              v-model="filterForm.status"
              label="Estatus"
              :items="statusList"
              item-title="title"
              item-value="value"
              hide-details
              clearable
            >
            </VSelect>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="12" sm="12">
            <VBtnToggle variant="tonal" divided>
              <VBtn prepend-icon="mdi-filter" text="Filtrar" @click="applyFilter"></VBtn>
              <VBtn prepend-icon="mdi-cogs" text="Generar tickets" @click="generateDialog = true"></VBtn>
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
              <template #[`item.status`]="{ item }">
                {{ item.status === 'D' ? 'Disponible' : 'Canjeado' }}
              </template>
              <template #[`item.action`]="{ item }">
                <Link :href="`/fund/currentfund/${item.current_fund_id}/edit`" as="button">
                  <VIcon color="warning" icon="mdi-pencil" />
                </Link>
                <VIcon class="ml-2" color="error" icon="mdi-delete" @click="deleteItem(item)" />
              </template>
            </VDataTableServer>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions>
        <a :href="`/ticket/reader/report?${queryString}&type=excel`" target="_blank">
          <VIcon icon="mdi-file-excel"></VIcon> Excel
        </a>
        <a :href="`/ticket/reader/report?${queryString}&type=pdf`" target="_blank">
          <VIcon icon="di-file-pdf-box"></VIcon> Pdf
        </a>
      </VCardActions>
    </VCard>
    <DeleteDialog
      v-model="deleteDialog"
      title="Eliminar el producto"
      @close-delete-dialog="deleteDialog = false"
      @delete-item="submitDelete"
    ></DeleteDialog>
    <GenTicketDialog v-model="generateDialog" @result="genSubmit"></GenTicketDialog>
  </AuthenticatedLayout>
</template>
<script>
export default {
  data() {
    return {
      headers: [
        { title: 'Producto', key: 'product_name' },
        { title: 'UUID', key: 'uuid' },
        { title: 'Precio', key: 'unit_price' },
        { title: 'Estatus', key: 'status' },
        { title: 'Acción', key: 'action', sortable: false },
      ],
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Tickets', disabled: true },
      ],
      statusList: [
        { title: 'Disponible', value: 'D' },
        { title: 'Canjeado', value: 'C' },
        { title: 'Anulado', value: 'A' },
      ],
    }
  },
}
</script>
