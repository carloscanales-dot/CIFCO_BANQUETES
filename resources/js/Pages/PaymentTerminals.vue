<script setup>
import { ref, watch, computed } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const page = usePage()

// Props de Inertia
const terminals = computed(() => page.props.terminals ?? { data: [], total: 0, per_page: 10, last_page: 1, current_page: 1 })
const stations = computed(() => page.props.stations ?? [])
const users = computed(() => page.props.users ?? [])
const filters = computed(() => page.props.filters ?? { q: '', perPage: 10 })

// Paginación
const pageNumber = ref(terminals.value?.current_page ?? 1)
watch(pageNumber, (p) => changePage(p))

// Estados UI
const modal = ref(false)
const isEdit = ref(false)
const selectedTerminal = ref(null)
const deleteDialog = ref(false)
const toggling = ref(new Set()) // controla los switches en proceso

// ✅ Helper seguro para el template
function isToggling(id) {
  return toggling.value.has(id)
}

// Formulario
const form = useForm({
  id: null,
  terminal_name: '',
  station_id: null,
  user_id: null,
})

// Encabezados
const headers = [
  { title: 'ID', key: 'id', align: 'start', width: '80px' },
  { title: 'Nombre', key: 'terminal_name' },
  { title: 'Estación', key: 'station', sortable: false },
  { title: 'Usuario', key: 'user', sortable: false },
  { title: 'Estado', key: 'status', sortable: false, align: 'center', width: '130px' },
  { title: 'Acciones', key: 'actions', align: 'center', width: '120px' }
]

// Abrir modal
function openModal(terminal = null) {
  if (terminal) {
    isEdit.value = true
    form.id = terminal.id
    form.terminal_name = terminal.terminal_name
    form.station_id = terminal.station_id
    form.user_id = terminal.user_id
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
  const url = form.id
    ? `/payment-terminals/${form.id}`
    : `/payment-terminals`

  const method = form.id ? 'put' : 'post'

  router[method](url, form, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(isEdit.value ? 'Terminal actualizada correctamente.' : 'Terminal creada correctamente.')
      closeModal()
      router.reload({ only: ['terminals'] })
    },
    onError: () => toast.error('Verifica los campos e inténtalo nuevamente.')
  })
}

// Confirmar eliminación
function confirmDelete(terminal) {
  selectedTerminal.value = terminal
  deleteDialog.value = true
}

// Eliminar
function deleteTerminal() {
  if (!selectedTerminal.value) return
  router.delete(`/payment-terminals/${selectedTerminal.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Terminal eliminada correctamente.')
      deleteDialog.value = false
      router.reload({ only: ['terminals'] })
    },
    onError: () => toast.error('No se pudo eliminar la terminal.')
  })
}

// ✅ Toggle con axios (estable y reactivo)
async function toggleStatus(item) {
  if (toggling.value.has(item.id)) return

  const originalStatus = item.status
  const newStatus = originalStatus === 1 ? 2 : 1
  item.status = newStatus
  toggling.value.add(item.id)

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token

    const response = await axios.post(`/payment-terminals/${item.id}/toggle-status`)
    if (response.data?.status !== undefined) {
      item.status = response.data.status
    }

    toast.success(`Terminal ${item.status === 1 ? 'abierta' : 'cerrada'} correctamente.`)
  } catch (error) {
    item.status = originalStatus
    console.error(error)
    toast.error('No se pudo cambiar el estado de la terminal.')
  } finally {
    toggling.value.delete(item.id)
  }
}

// Paginación
function changePage(p = 1) {
  router.get('/payment-terminals', { page: p, q: filters.value.q, perPage: filters.value.perPage }, { preserveState: true })
}
</script>

<template>
  <AdminLayout>
    <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
      <v-card flat class="pa-4 elevation-1" style="background-color: white;">
        <v-row class="align-center justify-space-between mb-2">
          <v-col cols="12" sm="6">
            <v-card-title class="text-h6 font-weight-bold text-left pa-0">
              Terminales de Pago
            </v-card-title>
          </v-col>
          <v-col cols="12" sm="6" class="d-flex justify-end">
            <v-btn color="black" variant="elevated" @click="openModal()">
              <v-icon left>mdi-plus</v-icon> Agregar Terminal
            </v-btn>
          </v-col>
        </v-row>

        <v-divider></v-divider>

        <v-data-table :items="terminals.data ?? []" :headers="headers" class="elevation-0" dense>
          <template #item.station="{ item }">
            <span>{{ item.station?.station_name || 'Sin estación' }}</span>
          </template>

          <template #item.user="{ item }">
            <span v-if="item.user">{{ item.user.name }}</span>
            <span v-else class="text-grey">Sin asignar</span>
          </template>

          <template #item.status="{ item }">
            <v-switch
              :model-value="item.status === 1"
              :disabled="isToggling(item.id)"
              inset
              color="green"
              density="compact"
              hide-details
              @update:model-value="() => toggleStatus(item)"
            />
          </template>

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
    </v-container>
  </AdminLayout>
</template>

<style scoped>
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 4px;
}

:deep(.v-data-table thead th) {
  font-weight: 700 !important;
  color: black !important;
  background-color: #f7f7f7 !important;
}

.text-grey {
  color: #9e9e9e !important;
}

.v-card-text strong {
  font-weight: 700;
  color: black;
}

:deep(.v-switch) {
  scale: 0.85;
  margin-top: -4px;
}
</style>
