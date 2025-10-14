<script setup>
const props = defineProps({
  ticket: {
    type: Object,
    required: true,
  },
})
</script>
<template>
  <VDialog persistent max-width="250px">
    <VCard title="Producto">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6" sm="12"> Producto: {{ ticket.product_name }} </VCol>
        </VRow>
      </VCardText>
      <VCardActions>
        <VBtn variant="tonal" text="Aceptar"></VBtn>
        <VBtn variant="tonal" text="Cancelar"></VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
