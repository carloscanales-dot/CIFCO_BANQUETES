<script setup>
import { onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useFairStore } from '@/Stores/Ticket/fairStore'
import { storeToRefs } from 'pinia'

const page = usePage()
const fairStore = useFairStore()
const { form, errors, isLoading } = storeToRefs(fairStore)

const submit = () => {
  fairStore.update(page.props.fair.fair_id)
}

onMounted(() => {
  Object.assign(fairStore.form, page.props.fair)
})
</script>

<template>
  <Head title="Ferias" />
  <AuthenticatedLayout>
    <div class="mb-3">
      <h5 class="text-h5 font-weight-bold">Actualizacion de feria</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <VCard>
      <VForm @submit.prevent="submit">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VTextField v-model="form.fair_name" label="Nombre de la feria" :error-messages="errors.fair_name" />
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VRadioGroup v-model="form.status" label="Estatus" :error-messages="errors.status" inline>
                <VRadio value="Programada" label="Programada"></VRadio>
                <VRadio value="Abierta" label="Abierta"></VRadio>
                <VRadio value="Cerrada" label="Cerrada"></VRadio>
              </VRadioGroup>
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VTextField
                v-model="form.start_date"
                type="date"
                label="Desde"
                :error-messages="errors.start_date"
              ></VTextField>
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VTextField
                v-model="form.end_date"
                type="date"
                label="Hasta"
                :error-messages="errors.end_date"
              ></VTextField>
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
          <Link href="/ticket/fair" as="div">
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
        { title: 'Ferias', disabled: false, href: '/ticket/fair' },
        { title: 'Actualizar', disabled: true },
      ],
    }
  },
}
</script>
