<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useStationStore } from '@/Stores/Ticket/stationStore'
import { useProductStore } from '@/Stores/Ticket/productStore'
import { useUserStore } from '@/Stores/Admin/User/userStore'
import { storeToRefs } from 'pinia'

const stationStore = useStationStore()
const productStore = useProductStore()
const userStore = useUserStore()
const { products } = storeToRefs(productStore)
const { users } = storeToRefs(userStore)
const { form, errors, isLoading } = storeToRefs(stationStore)

const submit = () => {
  stationStore.store()
}

onMounted(() => {
  productStore.ajaxList('A')
  userStore.ajaxList('A')
})
</script>

<template>
  <Head title="Estaciones de servicio" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Consulta de estaciones de servicio</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard>
      <VForm @submit.prevent="submit">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VTextField
                v-model="form.station_name"
                label="Nombre de la estacion"
                :error-messages="errors.station_name"
              />
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VRadioGroup v-model="form.status" label="Estatus" :error-messages="errors.status" inline>
                <VRadio value="Activa" label="Activa"></VRadio>
                <VRadio value="Inactiva" label="Inactiva"></VRadio>
              </VRadioGroup>
            </VCol>
          </VRow>
          <VRow dense>
            <VCol cols="12" md="6" sm="12">
              <VSelect
                v-model="form.product_ids"
                label="Productos por estacion"
                :items="products"
                item-title="product_name"
                item-value="product_id"
                clearable
                multiple
                chips
              ></VSelect>
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VAutocomplete
                v-model="form.user_ids"
                label="Usuarios por estacion"
                :items="users"
                item-title="name"
                item-value="user_id"
              ></VAutocomplete>
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VBtn
            prepend-icon="mdi-plus"
            :disabled="isLoading"
            type="submit"
            text="Guardar"
            variant="tonal"
            color="primary"
          ></VBtn>
          <Link href="/ticket/station" as="div">
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
        { title: 'Estaciones de servicio', disabled: false, href: '/ticket/station' },
        { title: 'Crear', disabled: true },
      ],
    }
  },
}
</script>
