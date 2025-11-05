<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const props = defineProps({
  locations: Array
})

// Estados
const modal = ref(false)
const isEdit = ref(false)
const selectedLocation = ref(null)
const deleteDialog = ref(false)

// Formulario
const form = useForm({
  id: null,
  location_name: ''
})

// Encabezados
const headers = [
  { title: 'Nombre', key: 'location_name' },
  { title: 'Acciones', key: 'actions', align: 'center', width: '120px' }
]

// Abrir modal
function openModal(location = null) {
  if (location) {
    isEdit.value = true
    form.id = location.id
    form.location_name = location.location_name
  } else {
    isEdit.value = false
    form.reset()
  }
  modal.value = true
}

// Cerrar modal
function closeModal() {
  modal.value = false
  form.reset()
}

// Guardar o actualizar
function submitForm() {
  const options = {
    onSuccess: () => {
      closeModal()
      toast.success(isEdit.value ? 'Locación actualizada correctamente.' : 'Locación creada correctamente.')
    },
    onError: () => {
      toast.error('Verifica los campos e inténtalo nuevamente.')
    }
  }

  if (isEdit.value) {
    router.put(`/ticket/location/${form.id}`, form, options)
  } else {
    router.post('/ticket/location', form, options)
  }
}

// Confirmar eliminación
function confirmDelete(location) {
  selectedLocation.value = location
  deleteDialog.value = true
}

// Eliminar
function deleteLocation() {
  if (!selectedLocation.value) return
  router.delete(`/ticket/location/${selectedLocation.value.id}`, {
    onSuccess: () => {
      toast.success('Locación eliminada correctamente.')
      deleteDialog.value = false
    },
    onError: () => toast.error('No se pudo eliminar la locación.')
  })
}
</script>


<template>
  <AdminLayout>
    <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
      <v-card flat class="pa-4 elevation-1" style="background-color: white;">
        <!-- Encabezado -->
        <v-row class="align-center justify-space-between mb-2">
          <v-col cols="12" sm="6">
            <v-card-title class="text-h6 font-weight-bold text-left pa-0">Locaciones</v-card-title>
          </v-col>
          <v-col cols="12" sm="6" class="d-flex justify-end">
            <v-btn color="black" variant="elevated" @click="openModal()">
              <v-icon left>mdi-plus</v-icon> Agregar Locación
            </v-btn>
          </v-col>
        </v-row>

        <v-divider></v-divider>

        <!-- Tabla de locaciones -->
        <v-data-table :items="props.locations" :headers="headers" class="elevation-0" dense>
          <template #item.actions="{ item }">
            <div class="action-buttons">
              <v-btn icon color="black" variant="text" density="compact" @click="openModal(item)">
                <v-icon size="18">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon color="black" variant="text" density="compact" @click="confirmDelete(item)">
                <v-icon size="18">mdi-delete</v-icon>
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Modal Crear/Editar -->
      <v-dialog v-model="modal" max-width="500">
        <v-card style="background-color: #ffffff; color: black;">
          <v-card-title class="text-h6 font-weight-bold text-center">
            {{ isEdit ? 'Editar Locación' : 'Agregar Locación' }}
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pt-4">
            <v-form @submit.prevent="submitForm">
              <v-text-field v-model="form.location_name" label="Nombre de la Locación" variant="outlined" color="black"
                density="comfortable" :error-messages="form.errors.location_name" class="mb-3" required />
            </v-form>
          </v-card-text>

          <v-divider></v-divider>

          <v-card-actions class="justify-end">
            <v-btn text color="red" @click="closeModal()" variant="elevated">Cancelar</v-btn>
            <v-btn color="black" variant="elevated" @click="submitForm()">
              {{ isEdit ? 'Actualizar' : 'Crear' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Modal de eliminación -->
      <v-dialog v-model="deleteDialog" max-width="450">
        <v-card style="background-color: #ffffff; color: black;">
          <v-card-title class="text-h6 font-weight-bold text-center">Confirmar Eliminación</v-card-title>
          <v-divider></v-divider>
          <v-card-text class="text-center">
            ¿Estás seguro de que deseas eliminar la locación
            <strong>{{ selectedLocation?.location_name }}</strong>?
          </v-card-text>
          <v-divider></v-divider>
          <v-card-actions class="justify-end">
            <v-btn text color="red" @click="deleteDialog = false" variant="elevated">Cancelar</v-btn>
            <v-btn color="black" variant="elevated" @click="deleteLocation()">Eliminar</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AdminLayout>
</template>

<style scoped>
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 4px;
}

.v-data-table th:nth-child(3) {
  width: 120px !important;
}

.v-data-table td:nth-child(3) {
  text-align: center;
  padding: 0 !important;
}

:deep(.v-data-table thead th) {
  font-weight: 700 !important;
  color: black !important;
  background-color: #f7f7f7 !important;
}

.v-card-text strong {
  font-weight: 700;
  color: black;
}
</style>
