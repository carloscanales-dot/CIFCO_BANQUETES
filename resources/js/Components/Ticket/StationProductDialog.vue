<template>
  <VDialog v-model="localDialog" max-width="600" persistent>
    <VCard>
      <VCardTitle>
        <span class="text-h6">Agregar productos a {{ station?.station_name }}</span>
      </VCardTitle>

      <VCardText>
        <div class="text-subtitle-2 mb-2">Selecciona los productos:</div>

        <VList>
          <VListItem
            v-for="product in productOptions"
            :key="product.id"
            density="comfortable"
          >
            <template #prepend>
              <VCheckbox
                v-model="selectedProducts"
                :value="product.id"
                hide-details
              />
            </template>
            <VListItemTitle>{{ product.product_name }}</VListItemTitle>
          </VListItem>
        </VList>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn text="Cancelar" color="grey" @click="closeDialog" />
        <VBtn text="Guardar" color="primary" @click="saveProducts" />
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  station: Object,
})

const emit = defineEmits(['update:modelValue'])

const localDialog = ref(props.modelValue)
const selectedProducts = ref([])
const productOptions = ref([])

// Abrir/Cerrar diálogo
watch(
  () => props.modelValue,
  (val) => {
    localDialog.value = val
  }
)

// Cargar todos los productos disponibles al montar el componente
onMounted(async () => {
  try {
    const response = await axios.get('/ticket/product/all')
    productOptions.value = response.data
  } catch (error) {
    console.error('Error al cargar productos:', error)
  }
})

// Cargar productos asignados cada vez que se abra el modal
watch(
  () => localDialog.value,
  async (val) => {
    if (val && props.station?.id) {
      try {
        const response = await axios.get(`/ticket/station/${props.station.id}/products`)
        selectedProducts.value = response.data.map(p => p.id)
      } catch (error) {
        console.error('Error al cargar productos asignados:', error)
      }
    }
    if (!val) {
      selectedProducts.value = [] // limpiar al cerrar
    }
  }
)

// Cerrar diálogo
const closeDialog = () => {
  emit('update:modelValue', false)
}

// Guardar productos seleccionados en la estación
const saveProducts = async () => {
  if (!props.station?.id) return

  try {
    await axios.post(`/ticket/station/${props.station.id}/products`, {
      product_ids: selectedProducts.value, // puede ser []
    })
    closeDialog()
  } catch (error) {
    console.error('Error al guardar productos:', error)
  }
}

</script>
