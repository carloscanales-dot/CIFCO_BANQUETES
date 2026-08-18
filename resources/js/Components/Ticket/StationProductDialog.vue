<template>
  <VDialog v-model="localDialog" max-width="600">
    <VCard>
      <VCardTitle>
        <span class="text-h6">Agregar productos a {{ station?.station_name }}</span>
      </VCardTitle>

      <VCardText>
        <div class="d-flex align-center justify-space-between mb-2">
          <span class="text-subtitle-2">Selecciona los productos:</span>
          <span class="text-caption text-medium-emphasis">{{ selectedProducts.length }} seleccionado(s)</span>
        </div>

        <VTextField
          v-model="search"
          label="Buscar producto"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          class="mb-2"
        />

        <VList max-height="320" class="overflow-y-auto">
          <VListItem
            v-for="product in filteredProducts"
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

          <VListItem v-if="filteredProducts.length === 0">
            <VListItemTitle class="text-medium-emphasis">Sin coincidencias</VListItemTitle>
          </VListItem>
        </VList>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn color="grey" text @click="closeDialog">Cancelar</VBtn>
        <VBtn color="primary" text @click="saveProducts">Guardar</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  station: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const localDialog = ref(false)
const selectedProducts = ref([])
const productOptions = ref([])
const search = ref('')

// ✅ Soportar estaciones con station_id o id
const stationId = computed(() => props.station?.station_id ?? props.station?.id)

// Filtro en vivo de la lista de productos por nombre.
const filteredProducts = computed(() => {
  const q = (search.value || '').trim().toLowerCase()
  if (!q) return productOptions.value
  return productOptions.value.filter(p => (p.product_name || '').toLowerCase().includes(q))
})

watch(() => props.modelValue, (val) => { localDialog.value = val })

watch(localDialog, async (isOpen) => {
  emit('update:modelValue', isOpen)

  if (isOpen && stationId.value) {
    const res = await axios.get(`/ticket/station/${stationId.value}/products`)
    selectedProducts.value = res.data.map(p => p.id)
  }

  if (!isOpen) {
    selectedProducts.value = []
    search.value = ''
  }
})

// ✅ Cargar y normalizar productos disponibles
onMounted(async () => {
  const res = await axios.get('/ticket/product/all')
  productOptions.value = res.data.map(p => ({
    id: p.id ?? p.product_id,        // ✅ Normalización segura
    product_name: p.product_name
  }))
})

// Guardar cambios
const saveProducts = async () => {
  await axios.post(`/ticket/station/${stationId.value}/products`, {
    product_ids: selectedProducts.value
  })
  localDialog.value = false
}

const closeDialog = () => {
  localDialog.value = false
}
</script>
