<script setup>
import { ref, watch, computed } from 'vue'
import { usePage, useForm, router, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'

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
  { title: 'Estado', key: 'status_id', sortable: false, align: 'center', width: '130px' },
  { title: 'Acciones', key: 'actions', align: 'center', width: '120px' },
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
  const url = form.id ? `/payment-terminals/${form.id}` : `/payment-terminals`
  const method = form.id ? 'put' : 'post'

  router[method](url, form, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(isEdit.value ? 'Terminal actualizada correctamente.' : 'Terminal creada correctamente.')
      closeModal()
      router.reload({ only: ['terminals'] })
    },
    onError: () => toast.error('Verifica los campos e inténtalo nuevamente.'),
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
    onError: () => toast.error('No se pudo eliminar la terminal.'),
  })
}

// Paginación
function changePage(p = 1) {
  router.get('/payment-terminals', { page: p, q: filters.value.q, perPage: filters.value.perPage }, { preserveState: true })
}
</script>

<template>
  <Head title="Terminales de Pago" />
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
          <!-- Estación -->
          <template #item.station="{ item }">
            <span>{{ item.station?.station_name || 'Sin estación' }}</span>
          </template>

          <!-- Usuario -->
          <template #item.user="{ item }">
            <span v-if="item.user">{{ item.user.name }}</span>
            <span v-else class="text-grey">Sin asignar</span>
          </template>

          <!-- Estado -->
          <template #item.status_id="{ item }">
            <v-chip size="small" :color="item.status_id === 5
              ? 'green'
              : item.status_id === 6
                ? 'red'
                : item.status_id === 7
                  ? 'orange'
                  : 'grey'
              " variant="flat" text-color="white">
              {{
                item.status_id === 5
                  ? 'Abierta'
                  : item.status_id === 6
                    ? 'Cerrada'
                    : item.status_id === 7
                      ? 'Pre-Cierre'
                      : 'Desconocido'
              }}
            </v-chip>
          </template>

          <!-- Acciones -->
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
        <!-- Modal Crear / Editar Terminal -->
        <v-dialog v-model="modal" max-width="500">
          <v-card>
            <v-card-title class="text-h6">
              {{ isEdit ? 'Editar Terminal' : 'Agregar Terminal' }}
            </v-card-title>

            <v-card-text>
              <v-text-field v-model="form.terminal_name" label="Nombre de la Terminal" variant="outlined" dense
                required />

              <v-select v-model="form.station_id" :items="stations" item-title="station_name" item-value="id"
                label="Estación" variant="outlined" dense required />

              <v-select v-model="form.user_id" :items="users" item-title="name" item-value="id" label="Cajero"
                variant="outlined" dense />
            </v-card-text>

            <v-card-actions>
              <v-spacer />
              <v-btn text @click="closeModal">Cancelar</v-btn>
              <v-btn color="black" variant="elevated" @click="submitForm">
                {{ isEdit ? 'Actualizar' : 'Crear' }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Modal Confirmar Eliminación -->
        <v-dialog v-model="deleteDialog" max-width="400">
          <v-card>
            <v-card-title class="text-h6">Confirmar Eliminación</v-card-title>
            <v-card-text>
              ¿Deseas eliminar la terminal
              <strong>{{ selectedTerminal?.terminal_name }}</strong>?
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn text @click="deleteDialog = false">Cancelar</v-btn>
              <v-btn color="red" variant="elevated" @click="deleteTerminal">
                Eliminar
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

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
</style>
