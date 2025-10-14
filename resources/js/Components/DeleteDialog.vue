<script setup>
defineProps({
  title: { type: String, required: true },
  message: { type: String, default: '¿Estás seguro/a de que quieres eliminar este artículo?' },
})
defineEmits(['closeDeleteDialog', 'deleteItem'])
</script>
<template>
  <VDialog persistent width="auto">
    <VCard :title="title">
      <VCardText>
        {{ message }}
      </VCardText>
      <VCardActions>
        <VBtn prepend-icon="mdi-cancel" color="primary" @click="$emit('closeDeleteDialog')" text="Cancelar"></VBtn>
        <VBtn prepend-icon="mdi-delete" color="primary" @click="$emit('deleteItem')" text="Eliminar"></VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
