<template>

  <Head title="Producto" />
  <AdminLayout>

    <!-- TÍTULO + BREADCRUMBS -->
    <div class="mb-4">
      <h5 class="text-h5 font-weight-bold">Nuevo producto</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>

    <!-- CARD PRINCIPAL -->
    <VCard class="elevation-1" rounded="lg">
      <VForm @submit.prevent="submit">

        <VCardTitle class="text-h6 font-weight-bold py-3">
          Información del Producto
        </VCardTitle>

        <VDivider />

        <VCardText class="pt-4">

          <!-- FILA 1 -->
          <VRow dense>
            <VCol cols="12" md="6">
              <VTextField v-model="form.product_name" label="Nombre" :error-messages="errors.product_name"
                density="comfortable" variant="outlined" class="mono-input" />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField v-model="form.prefix" label="Prefijo" :error-messages="errors.prefix" density="comfortable"
                variant="outlined" class="mono-input" />
            </VCol>
          </VRow>

          <!-- FILA 2 -->
          <VRow dense class="mt-1">
            <VCol cols="12" md="3">
              <VTextField v-model="form.unit_price" label="Precio unitario" :error-messages="errors.unit_price"
                density="comfortable" variant="outlined" class="mono-input" />
            </VCol>

            <VCol cols="12" md="3">
              <VTextField v-model="form.cost" label="Costo unitario" :error-messages="errors.cost" density="comfortable"
                variant="outlined" class="mono-input" />
            </VCol>

            <!-- RADIOGROUP COMPACTO -->
            <VCol cols="12" md="6">
              <VRadioGroup v-model="form.status" :error-messages="errors.status" inline hide-details
                class="compact-radio pt-2" label="">
                <VRadio value="1" label="Activo" color="grey-darken-3" density="compact" />

                <VRadio value="2" label="Inactivo" color="grey-darken-3" density="compact" />
              </VRadioGroup>
            </VCol>
          </VRow>

        </VCardText>

        <VCardActions class="px-4 pb-4">
          <VBtn prepend-icon="mdi-content-save" :disabled="isLoading" type="submit" color="black" variant="flat"
            class="text-white">
            Guardar
          </VBtn>

          <Link href="/ticket/product" as="div">
          <VBtn prepend-icon="mdi-cancel" variant="outlined" color="grey-darken-3">
            Cancelar
          </VBtn>
          </Link>
        </VCardActions>

      </VForm>
    </VCard>

  </AdminLayout>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { storeToRefs } from 'pinia'

const productStore = useProductStore()
const { form, errors, isLoading } = storeToRefs(productStore)

const submit = () => {
  productStore.store()
}

onMounted(() => {
  productStore.resetForm()
})
</script>

<script>
export default {
  data() {
    return {
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Producto', disabled: false, href: '/ticket/product' },
        { title: 'Crear', disabled: true },
      ],
    }
  },
}
</script>

<style scoped>
/* INPUT elegante monocromático */
.mono-input .v-field {
  background-color: #fafafa !important;
  border-radius: 6px !important;
}

/* RADIOGROUP COMPACTO */
.compact-radio .v-radio {
  margin-right: 10px !important;
}

.compact-radio .v-label {
  font-size: 12px !important;
}

.compact-radio .v-selection-control {
  padding: 0 !important;
  min-height: 22px !important;
}

.compact-radio .v-selection-control__wrapper {
  margin-right: 4px !important;
}

.compact-radio .v-icon {
  font-size: 16px !important;
}
</style>
