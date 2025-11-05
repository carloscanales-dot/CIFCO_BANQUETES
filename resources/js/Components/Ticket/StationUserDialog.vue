<template>
  <VDialog v-model="dialog" max-width="600px" persistent="">
    <VCard>
      <VCardTitle>
        <span class="text-h6">
          Asignar usuarios a {{ station?.station_name }}
        </span>
      </VCardTitle>

      <VCardText>
        <VForm ref="form" v-model="valid">
          <VSelect
            v-model="selectedUsers"
            :items="users"
            item-title="name"
            item-value="id"
            label="Selecciona usuarios"
            multiple
            chips
            clearable
          />
        </VForm>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn text @click="closeDialog">Cancelar</VBtn>
        <VBtn color="primary" @click="saveUsers">Guardar</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, defineProps, defineEmits, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  station: Object,
})

const emit = defineEmits(['update:modelValue'])

const dialog = ref(false)
const valid = ref(false)
const users = ref([])
const selectedUsers = ref([])

// Sincronizar apertura/cierre con el padre
watch(
  () => props.modelValue,
  (val) => (dialog.value = val)
)

watch(dialog, (val) => emit('update:modelValue', val))

// Cargar todos los usuarios al montar
onMounted(async () => {
  try {
    const response = await axios.get('/admin/user/list')
    users.value = response.data.users
  } catch (error) {
    console.error('Error cargando usuarios:', error)
    users.value = []
  }
})

// Cargar usuarios asignados cuando se abre el modal
watch(dialog, (val) => {
  if (val) loadAssignedUsers()
})

const closeDialog = () => {
  selectedUsers.value = [] // limpia inmediatamente
  dialog.value = false
}

const saveUsers = async () => {
  try {
    await axios.post(`/ticket/stations/${props.station.id}/users`, {
      user_ids: selectedUsers.value,
    })
    dialog.value = false
  } catch (error) {
    console.error('Error guardando usuarios:', error)
  }
}

const loadAssignedUsers = async () => {
  try {
    const response = await axios.get(`/ticket/stations/${props.station.id}/users`)
    // Guardamos solo los IDs para que el VSelect los marque
    selectedUsers.value = response.data.map(u => u.id)
  } catch (error) {
    console.error('Error cargando usuarios asignados:', error)
    selectedUsers.value = []
  }
}
</script>
