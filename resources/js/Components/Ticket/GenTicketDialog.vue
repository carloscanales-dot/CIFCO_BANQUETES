<script setup>
import { onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { storeToRefs } from 'pinia'
import { useTicketStore } from '@/Stores/Ticket/ticketStore'
import { useProductStore } from '@/Stores/Ticket/productStore'

const toast = useToast()
const ticketStore = useTicketStore()
const productStore = useProductStore()
const { products } = storeToRefs(productStore)
const { form, errors, isLoading } = storeToRefs(ticketStore)

const emits = defineEmits(['result'])

const submit = async () => {
  try {
    await ticketStore.ajaxStore()
    emits('result', { success: true })
  } catch (error) {
    toast.error(error.message)
  }
}

onMounted(() => {
  productStore.ajaxList('Activo')
})
</script>
<template>
  <VDialog persistent max-width="600">
    <VForm @submit.prevent="submit">
      <VCard prepend-icon="mdi-plus" title="Generacion de tickets">
        <VCardText>
          <VRow>
            <VCol cols="12" md="12" sm="12">
              <VAutocomplete
                v-model="form.product_id"
                label="Nombre del producto"
                :items="products"
                item-title="product_name"
                item-value="product_id"
                clearable
                :error-messages="errors.product_id"
              >
              </VAutocomplete>
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" md="6" sm="12">
              <VNumberInput
                v-model="form.quantity"
                label="Cantidad"
                min="1"
                clearable
                :error-messages="errors.quantity"
              ></VNumberInput>
            </VCol>
            <VCol cols="12" md="6" sm="12">
              <VSelect
                v-model="form.status"
                label="Estatus"
                :items="statusList"
                item-title="title"
                item-value="value"
                clearable
                :error-messages="errors.status"
              ></VSelect>
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
            :disabled="form.processing"
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
      statusList: [{ title: 'Disponible', value: 'D' }],
    }
  },
}
</script>
