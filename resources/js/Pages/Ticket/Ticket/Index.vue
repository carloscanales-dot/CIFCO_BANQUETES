<script setup>
import { storeToRefs } from 'pinia'
import { reactive, ref, inject, onMounted, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useTicketStore } from '@/Stores/Ticket/ticketStore'
import { useProductStore } from '@/Stores/Ticket/productStore'
import GenTicketDialog from '@/Components/Ticket/GenTicketDialog.vue'
import { filterItems } from 'vuetify/lib/composables/filter'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const search = ref(null)
const cancelId = ref(null)
const queryString = ref([])
const cancelDialog = ref(false)
const generateDialog = ref(false)
const helpers = inject('helpers')
const page = usePage()
const ticketStore = useTicketStore()
const productStore = useProductStore()
const { products } = storeToRefs(productStore)
const { items, totalItems, isLoading } = storeToRefs(ticketStore)

// Ferias para el filtro (buscable). Se muestran todas; el listado no se
// autofiltra para no ocultar cortesías legacy (fair_id nulo).
const statusText = { 1: 'Programada', 2: 'Abierta', 3: 'Cerrada' }
const fairOptions = computed(() =>
  (page.props.fairs || []).map((f) => ({ ...f, label: `${f.fair_name} · ${statusText[f.status] || ''}` }))
)

const filterForm = reactive({
  uuid: null,
  status: null,
  product_id: null,
  start_id: null,
  end_id: null,
  fair_id: null,
})

const cancelItem = (item) => {
  cancelId.value = item.ticket_id
  cancelDialog.value = true
}

const submitCancel = () => {
  ticketStore.cancel(cancelId.value)
  cancelDialog.value = false
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

  // 👇 Vuelve a cargar los datos del backend
  ticketStore.index({
    page: 1,
    limit: 10,
    sort: { key: 'ticket_id', order: 'desc' },
    search: helpers.removeEmptyAttribute(filterForm),
  })
}


onMounted(() => {
  productStore.ajaxList('Activo')
})
</script>
<template>
  <Head title="Ticket" />
  <AdminLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de tickets</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard title="Formulario de filtro">
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="6" sm="12">
            <VAutocomplete
              v-model="filterForm.fair_id"
              label="Feria"
              :items="fairOptions"
              item-title="label"
              item-value="id"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="6" sm="12">
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
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model.number="filterForm.start_id" label="ID inicial" type="number" hide-details clearable></VTextField>
          </VCol>
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model.number="filterForm.end_id" label="ID final" type="number" hide-details clearable></VTextField>
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
              :items="items || []"
              :items-length="totalItems || 0"
              :headers="headers"
              :search="search"
              :loading="isLoading"
              @update:options="loadItems"
            >
              <template #[`item.status`]="{ item }">
                {{ item.status }}
              </template>
              <template #[`item.action`]="{ item }">
                <!-- <Link :href="`/fund/currentfund/${item.current_fund_id}/edit`" as="button">
                  <VIcon color="warning" icon="mdi-pencil" />
                </Link> -->
                <VBtn
                  icon="mdi-cancel"
                  color="error"
                  variant="text"
                  size="small"
                  @click="cancelItem(item)"
                  :disabled="item.status === 'ANULADO'"
                >
                  <VIcon>mdi-cancel</VIcon>
                  <VTooltip activator="parent" location="top">Anular Ticket</VTooltip>
                </VBtn>
              </template>
            </VDataTableServer>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions>
        <VBtn
          color="success"
          variant="tonal"
          prepend-icon="mdi-file-excel"
          :href="`/ticket/reader/report?${queryString}&type=excel`"
          target="_blank"
        >
          Exportar Excel
        </VBtn>
        <VBtn
          color="error"
          variant="tonal"
          prepend-icon="mdi-file-pdf-box"
          :href="`/ticket/reader/report?${queryString}&type=pdf`"
          target="_blank"
          class="ml-2"
        >
          Exportar PDF
        </VBtn>
      </VCardActions>
    </VCard>
    <DeleteDialog
      v-model="cancelDialog"
      title="Anular el ticket"
      message="¿Está seguro que desea anular este ticket? Esta acción no se puede deshacer y el ticket no podrá ser usado en el scanner."
      @close-delete-dialog="cancelDialog = false"
      @delete-item="submitCancel"
    ></DeleteDialog>
    <GenTicketDialog v-model="generateDialog" @result="genSubmit"></GenTicketDialog>
  </AdminLayout>
</template>
<script>
export default {
  data() {
    return {
      headers: [
        { title: 'ID', key: 'ticket_id' },
        { title: 'Producto', key: 'product_name' },
        { title: 'UUID', key: 'uuid' },
        { title: 'Precio', key: 'unit_price' },
        { title: 'Feria', key: 'fair_name' },
        { title: 'Generado para', key: 'generated_for' },
        { title: 'Estatus', key: 'status' },
        { title: 'Acción', key: 'action', sortable: false },
      ],
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Tickets', disabled: true },
      ],
      statusList: [
        { title: 'Pendiente', value: 'D' },
        { title: 'Aplicado', value: 'C' },
        { title: 'Anulado', value: 'A' },
      ],
    }
  },
}
</script>
