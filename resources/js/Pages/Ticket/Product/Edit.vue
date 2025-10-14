<script setup>
import { onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { storeToRefs } from 'pinia'

const page = usePage()
const productStore = useProductStore()
const { form, errors, isLoading } = storeToRefs(productStore)

const submit = () => {
  productStore.update(page.props.product.product_id)
}

onMounted(() => {
  Object.assign(productStore.form, page.props.product)
})
</script>

<template>
  <Head title="Producto" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Actualizacion del producto</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard>
      <VForm @submit.prevent="submit">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VTextField v-model="form.product_name" label="Nombre" :error-messages="errors.product_name" />
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VTextField v-model="form.prefix" label="Prefijo" :error-messages="errors.prefix"></VTextField>
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VTextField
                v-model="form.unit_price"
                label="Precio unitario"
                :error-messages="errors.unit_price"
              ></VTextField>
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VRadioGroup v-model="form.status" label="Estatus" :error-messages="errors.status" inline>
                <VRadio value="Activo" label="Activo"></VRadio>
                <VRadio value="Inactivo" label="Inactivo"></VRadio>
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VBtn
            prepend-icon="mdi-database"
            :disabled="isLoading"
            type="submit"
            text="Guardar"
            variant="tonal"
            color="primary"
          ></VBtn>
          <Link href="/ticket/product" as="div">
            <VBtn prepend-icon="mdi-cancel" text="Cancelar" variant="tonal"></VBtn>
          </Link>
        </VCardActions>
      </VForm>
    </VCard>
  </AuthenticatedLayout>
</template>
<script>
export default {
  data() {
    return {
      breadcrumbs: [
        { title: 'Panel', disabled: false, href: '/dashboard' },
        { title: 'Producto', disabled: false, href: '/ticket/stationProduct' },
        { title: 'Actualizar', disabled: true },
      ],
    }
  },
}
</script>
