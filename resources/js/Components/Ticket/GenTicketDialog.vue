<script setup>
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { storeToRefs } from 'pinia'
import axios from 'axios'
import { useTicketStore } from '@/Stores/Ticket/ticketStore'
import { useProductStore } from '@/Stores/Ticket/productStore'

const toast = useToast()
const ticketStore = useTicketStore()
const productStore = useProductStore()
const { products } = storeToRefs(productStore)

const emits = defineEmits(['result'])

// Solo ferias ABIERTAS pueden recibir cortesías nuevas.
const openFairs = ref([])
onMounted(async () => {
  try {
    const { data } = await axios.get('/ticket/fair/list/O')
    openFairs.value = data.fairs ?? []
  } catch {
    openFairs.value = []
  }
})

const submit = async () => {
  if (!ticketStore.form.fair_id) {
    toast.error('Selecciona la feria para la que se generan las cortesías.')
    return
  }
  if (!ticketStore.form.product_id) {
    toast.error('Please select a product.')
    return
  }
  if (!ticketStore.form.quantity) {
    toast.error('Please enter a quantity.')
    return
  }

  try {
    await ticketStore.ajaxStore()
    emits('result', { success: true })
  } catch (error) {
    // error is handled in the store
  }
}


</script>
<template>
  <VDialog persistent max-width="600">
    <VForm @submit.prevent="submit">
      <VCard prepend-icon="mdi-plus" title="Generacion de tickets">
        <VCardText>
          <VRow>
            <VCol cols="12" md="12" sm="12">
              <VAutocomplete
                v-model="ticketStore.form.fair_id"
                label="Feria (abierta)"
                :items="openFairs"
                item-title="fair_name"
                item-value="id"
                clearable
                no-data-text="No hay ferias abiertas"
                :error-messages="ticketStore.errors.fair_id"
              />
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="12" sm="12">
              <VAutocomplete
                v-model="ticketStore.form.product_id"
                label="Nombre del producto"
                :items="products"
                item-title="product_name"
                item-value="product_id"
                clearable
                :error-messages="ticketStore.errors.product_id"
              >
              </VAutocomplete>
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VNumberInput
                v-model="ticketStore.form.quantity"
                label="Cantidad"
                :min="1"
                clearable
                :error-messages="ticketStore.errors.quantity"
              ></VNumberInput>
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VSelect
                v-model="ticketStore.form.status_id"
                label="Estado inicial"
                :items="statusList"
                item-title="title"
                item-value="value"
                clearable
                :error-messages="ticketStore.errors.status_id"
              ></VSelect>
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="12" sm="12">
              <VTextField
                v-model="ticketStore.form.generated_for"
                label="Generado para"
                clearable
                :error-messages="ticketStore.errors.generated_for"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VBtn
            prepend-icon="mdi-plus"
            type="submit"
            color="primary"
            text="Guardar"
            variant="tonal"
            :disabled="ticketStore.form.processing"
          ></VBtn>
          <VBtn
            prepend-icon="mdi-cancel"
            color="danger"
            variant="tonal"
            text="Cancelar"
            @click="emits('result', { success: true })"
          ></VBtn>
        </VCardActions>
      </VCard>
    </VForm>
  </VDialog>
</template>
<script>
export default {
  data() {
    return {
      statusList: [
        { title: 'Pendiente', value: 3 },
        { title: 'Aplicado', value: 1 },
        { title: 'Anulado', value: 2 },
      ],
    }
  },
}
</script>
