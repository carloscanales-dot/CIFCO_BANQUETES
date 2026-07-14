<template>

  <Head title="Productos" />
  <AdminLayout>

    <!-- TÍTULO + BREADCRUMBS -->
    <div class="mb-4">
      <h5 class="text-h5 font-weight-bold">Consulta de productos</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <!-- CARD PRINCIPAL -->
    <VCard class="elevation-1" rounded="lg">
      <VCardTitle class="text-h6 font-weight-bold py-3">
        Filtros de búsqueda
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">

        <!-- FORMULARIO -->
        <VRow dense>
          <VCol cols="12">
            <VTextField v-model="filterForm.product_name" label="Nombre del producto" hide-details density="comfortable"
              variant="outlined" class="mono-input" clearable />
          </VCol>
        </VRow>

        <VRow dense class="mt-1">
          <VCol cols="12" md="6">
            <VTextField v-model="filterForm.prefix" label="Prefijo del producto" hide-details density="comfortable"
              variant="outlined" class="mono-input" clearable />
          </VCol>

          <VCol cols="12" md="6">
            <VRadioGroup v-model="filterForm.status" hide-details inline class="compact-radio">
              <VRadio label="Activa" value="Activa" color="grey-darken-3" density="compact" />
              <VRadio label="Inactiva" value="Inactiva" color="grey-darken-3" density="compact" />
            </VRadioGroup>

          </VCol>
        </VRow>

        <!-- BOTONES -->
        <div class="d-flex justify-space-between align-center mt-4">
          <!-- Izquierda -->
          <VBtn prepend-icon="mdi-filter" color="black" variant="flat" class="text-white" @click="applyFilter">
            Filtrar
          </VBtn>

          <!-- Derecha -->
          <VBtn prepend-icon="mdi-plus" color="grey-darken-3" variant="outlined"
            @click="router.visit('/ticket/product/create')">
            Agregar
          </VBtn>
        </div>

        <!-- TABLA -->
        <div class="mt-5">
          <VDataTableServer :items="items || []" :items-length="totalItems || 0" :headers="headers" :loading="isLoading"
            :search="search" class="mono-table elevation-1" @update:options="loadItems">

            <!-- ESTATUS -->
            <template #["item.status"]="{ item }">
              <VChip :color="item.status ? 'green-darken-1' : 'red-darken-1'" variant="flat" size="small"
                class="text-white">
                {{ item.status ? 'Activo' : 'Inactivo' }}
              </VChip>
            </template>

            <!-- ACCIÓN -->
            <template #["item.action"]="{ item }">
              <Link :href="`/ticket/product/${item.product_id}/edit`" as="button">
              <VIcon icon="mdi-pencil" color="grey-darken-1" size="20" class="mr-1 cursor-pointer" />
              </Link>
            </template>

          </VDataTableServer>
        </div>

      </VCardText>
    </VCard>

    <!-- DIALOG DE ELIMINAR -->
    <DeleteDialog v-model="deleteDialog" title="Eliminar el producto" @close-delete-dialog="deleteDialog = false"
      @delete-item="submitDelete" />

  </AdminLayout>
</template>

<script setup>
import { reactive, ref, inject } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { storeToRefs } from 'pinia'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const search = ref(null)
const deleteId = ref(null)
const deleteDialog = ref(false)

const helpers = inject('helpers')
const productStore = useProductStore()
const { items, totalItems, isLoading } = storeToRefs(productStore)

const filterForm = reactive({
  prefix: null,
  product_name: null,
  status: null,
})

const deleteItem = item => {
  deleteId.value = item.product_id
  deleteDialog.value = true
}

const submitDelete = () => {
  productStore.destroy(deleteId.value)
  deleteDialog.value = false
}

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  if (search != null && search.length < 3) return

  productStore.index({
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
        { title: 'Nombre del producto', key: 'product_name' },
        { title: 'Prefijo del producto', key: 'prefix' },
        { title: 'Precio unitario', key: 'unit_price' },
        { title: 'Costo unitario', key: 'cost' },
        { title: 'Estatus', key: 'status' },
        { title: 'Acción', key: 'action', sortable: false },
      ],
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Producto', disabled: true },
      ],
    }
  },
}
</script>

<style scoped>
.mono-input .v-field {
  background-color: #fafafa !important;
  border-radius: 6px !important;
}

.mono-table {
  border-radius: 10px !important;
  overflow: hidden;
}

.mono-table thead th {
  background-color: #f3f3f3 !important;
  font-weight: bold !important;
  color: #333 !important;
}

.v-data-table__wrapper {
  border-radius: 10px !important;
}

.compact-radio .v-radio {
  margin-top: 12px !important ;
  margin-right: 10px;
  /* menos espacio horizontal */
}

.compact-radio .v-label {
  font-size: 12px !important;
  /* texto más pequeño */
}

.compact-radio .v-selection-control {
  padding: 0 !important;
  /* elimina espacios internos */
  min-height: 22px !important;
}

.compact-radio .v-selection-control__wrapper {
  margin-right: 4px !important;
}

.compact-radio .v-icon {
  font-size: 16px !important;
  /* radio más pequeño */
}
</style>
