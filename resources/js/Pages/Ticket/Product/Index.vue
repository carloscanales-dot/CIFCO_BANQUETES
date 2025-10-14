<script setup>
import { reactive, ref, inject } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DeleteDialog from '@/Components/DeleteDialog.vue'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { storeToRefs } from 'pinia'

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

const deleteItem = (item) => {
  deleteId.value = item.product_id
  deleteDialog.value = true
}

const submitDelete = () => {
  productStore.destroy(deleteId.value)
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

  productStore.index(filters)
}

const applyFilter = () => {
  search.value = String(Date.now())
}
</script>
<template>
  <Head title="Productos" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de productos</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard title="Formulario de filtro">
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="12" sm="12">
            <VTextField
              v-model="filterForm.product_name"
              label="Nombre del producto"
              hide-details
              clearable
            ></VTextField>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="6" sm="12">
            <VTextField v-model="filterForm.prefix" label="Prefijo del producto" hide-details clearable></VTextField>
          </VCol>
          <VCol cols="12" md="6" sm="12">
            <VRadioGroup v-model="filterForm.status" label="Estatus" hide-details inline>
              <VRadio value="Activa" label="Activa"></VRadio>
              <VRadio value="Inactiva" label="Inactiva"></VRadio>
            </VRadioGroup>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="12" sm="12">
            <VBtnToggle variant="tonal" divided>
              <VBtn prepend-icon="mdi-filter" text="Filtrar" color="primary" @click="applyFilter"></VBtn>
              <VBtn prepend-icon="mdi-plus" text="Agregar" @click="router.visit('/ticket/product/create')"></VBtn>
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
              <template #[`item.action`]="{ item }">
                <Link :href="`/ticket/product/${item.product_id}/edit`" as="button">
                  <VIcon color="warning" icon="mdi-pencil" />
                </Link>
                <VIcon class="ml-2" color="error" icon="mdi-delete" @click="deleteItem(item)" />
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
  </AuthenticatedLayout>
</template>
<script>
export default {
  data() {
    return {
      headers: [
        { title: 'Nombre del producto', key: 'product_name' },
        { title: 'Prefijo del producto', key: 'prefix' },
        { title: 'Precio unitario', key: 'unit_price' },
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
