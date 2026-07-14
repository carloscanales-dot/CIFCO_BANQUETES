<template>
  <Head title="Transacciones de Empleados" />
  <AdminLayout>
    <v-container fluid class="pa-6">
      <div class="mb-5">
        <h5 class="text-h5 font-weight-bold">Gestión de Transacciones de Empleados</h5>
        <p class="text-medium-emphasis">Visualiza y gestiona las transacciones de créditos a empleados</p>
      </div>

      <!-- Filtros Rápidos -->
      <v-card class="mb-4">
        <v-card-text class="pa-4">
          <v-row dense>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="search"
                label="Buscar por ID, Empleado..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                hide-details
                clearable
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="3">
              <v-select
                v-model="filterStatus"
                :items="statusOptions"
                label="Estado"
                variant="outlined"
                density="compact"
                hide-details
              ></v-select>
            </v-col>

            <v-col cols="12" md="3">
              <v-text-field
                v-model="filterDate"
                label="Fecha"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
                clearable
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="2">
              <v-btn color="warning" variant="flat" class="text-white" block @click="loadTransactions" :loading="loading">
                Filtrar
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Tabla de Transacciones -->
      <v-card>
        <v-card-title class="text-h6 font-weight-bold py-3">
          <v-icon class="mr-2">mdi-account-cash</v-icon>
          Últimas Transacciones de Empleados
        </v-card-title>

        <v-divider />

        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="transactions"
            :loading="loading"
            :search="search"
            items-per-page="15"
            class="elevation-0"
          >
            <template v-slot:item.transaction_date="{ item }">
              <div class="text-caption">
                {{ formatDate(item.transaction_date) }}
              </div>
            </template>

            <template v-slot:item.amount="{ item }">
              <v-chip color="warning" variant="flat" size="small" class="font-weight-medium">
                ${{ parseFloat(item.amount || 0).toFixed(2) }}
              </v-chip>
            </template>

            <template v-slot:item.employee="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.employee_name || 'N/A' }}</div>
              </div>
            </template>

            <template v-slot:item.station="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.station_name || 'N/A' }}</div>
              </div>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip
                :color="getStatusColor(item.status_id)"
                variant="flat"
                size="small"
                class="text-white"
              >
                {{ getStatusText(item.status_id) }}
              </v-chip>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-tooltip text="Ver Detalles">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="viewDetails(item)"
                  ></v-btn>
                </template>
              </v-tooltip>

              <v-tooltip text="Anular" v-if="item.status_id === 1">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-cancel"
                    size="small"
                    variant="text"
                    color="error"
                    @click="confirmCancel(item)"
                  ></v-btn>
                </template>
              </v-tooltip>
            </template>

            <template v-slot:no-data>
              <div class="text-center pa-4">
                <v-icon size="64" color="grey">mdi-account-cash-outline</v-icon>
                <p class="text-h6 text-medium-emphasis mt-2">No hay transacciones de empleados</p>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Dialog: Confirmar Anulación -->
    <v-dialog v-model="cancelDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h6 font-weight-bold py-3">
          <v-icon class="mr-2">mdi-alert</v-icon>
          Confirmar Anulación de Crédito
        </v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p>¿Estás seguro de que deseas anular este crédito a empleado?</p>
          <v-alert color="warning" variant="tonal" class="mt-3">
            <div class="text-caption">
              <strong>Nota:</strong> La transacción se mantendrá en los registros pero será marcada como anulada. El crédito no se devolverá al empleado.
            </div>
          </v-alert>
          <div v-if="selectedTransaction" class="mt-4">
            <div><strong>ID:</strong> {{ selectedTransaction.id }}</div>
            <div><strong>Empleado:</strong> {{ selectedTransaction.employee_name }}</div>
            <div><strong>Monto:</strong> ${{ parseFloat(selectedTransaction.amount || 0).toFixed(2) }}</div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="cancelDialog = false">Cancelar</v-btn>
          <v-btn color="error" variant="flat" @click="cancelTransaction" :loading="cancelling">Anular</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog: Detalles de Transacción -->
    <v-dialog v-model="detailsDialog" max-width="700">
      <v-card v-if="selectedTransaction">
        <v-card-title class="text-h6 font-weight-bold py-3">
          <v-icon class="mr-2">mdi-account-cash</v-icon>
          Detalles de Transacción #{{ selectedTransaction.id }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <v-row dense>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Fecha</div>
              <div class="font-weight-medium">{{ formatDate(selectedTransaction.transaction_date) }}</div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Monto Total</div>
              <div class="font-weight-medium">${{ parseFloat(selectedTransaction.amount || 0).toFixed(2) }}</div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Empleado</div>
              <div class="font-weight-medium">{{ selectedTransaction.employee_name || 'N/A' }}</div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Estación</div>
              <div class="font-weight-medium">{{ selectedTransaction.station_name || 'N/A' }}</div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Usuario</div>
              <div class="font-weight-medium">{{ selectedTransaction.user_name || 'N/A' }}</div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-medium-emphasis">Estado</div>
              <v-chip
                :color="getStatusColor(selectedTransaction.status_id)"
                variant="flat"
                size="small"
                class="text-white"
              >
                {{ getStatusText(selectedTransaction.status_id) }}
              </v-chip>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <div class="text-subtitle-2 font-weight-bold mb-2">Productos</div>
          <v-table density="compact">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="detail in selectedTransaction.details" :key="detail.product_id">
                <td>{{ detail.product_name }}</td>
                <td class="text-right">{{ detail.quantity }}</td>
                <td class="text-right">${{ parseFloat(detail.unit_price || 0).toFixed(2) }}</td>
                <td class="text-right">${{ parseFloat(detail.total || 0).toFixed(2) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="detailsDialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="top right">
      {{ snackbar.message }}
    </v-snackbar>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const transactions = ref([])
const loading = ref(false)
const cancelling = ref(false)
const search = ref('')
const filterStatus = ref('all')
const filterDate = ref(null)

const cancelDialog = ref(false)
const detailsDialog = ref(false)
const selectedTransaction = ref(null)

const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
})

const statusOptions = [
  { title: 'Todos', value: 'all' },
  { title: 'Completadas', value: 1 },
  { title: 'Anuladas', value: 2 },
  { title: 'Canceladas', value: 4 },
]

const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Fecha', key: 'transaction_date', sortable: true },
  { title: 'Empleado', key: 'employee', sortable: false },
  { title: 'Estación', key: 'station', sortable: false },
  { title: 'Monto', key: 'amount', sortable: true, align: 'center' },
  { title: 'Estado', key: 'status', sortable: true, align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'center' },
]

onMounted(() => {
  loadTransactions()
})

const loadTransactions = async () => {
  loading.value = true
  try {
    const params = {
      search: search.value,
      status: filterStatus.value !== 'all' ? filterStatus.value : null,
      date: filterDate.value,
    }

    const response = await axios.get('/api/transacciones/empleados/list', { params })
    transactions.value = response.data
  } catch (error) {
    console.error('Error cargando transacciones:', error)
    showSnackbar('Error al cargar transacciones', 'error')
  } finally {
    loading.value = false
  }
}

const viewDetails = async (transaction) => {
  try {
    const response = await axios.get(`/api/transacciones/empleados/${transaction.id}/details`)
    selectedTransaction.value = response.data
    detailsDialog.value = true
  } catch (error) {
    console.error('Error cargando detalles:', error)
    showSnackbar('Error al cargar detalles', 'error')
  }
}

const confirmCancel = (transaction) => {
  selectedTransaction.value = transaction
  cancelDialog.value = true
}

const cancelTransaction = async () => {
  if (!selectedTransaction.value) return

  cancelling.value = true
  try {
    await axios.put(`/api/transacciones/empleados/${selectedTransaction.value.id}/cancel`, {
      status_id: 2
    })
    showSnackbar('Transacción anulada exitosamente', 'success')
    cancelDialog.value = false
    selectedTransaction.value = null
    await loadTransactions()
  } catch (error) {
    console.error('Error anulando transacción:', error)
    showSnackbar('Error al anular transacción', 'error')
  } finally {
    cancelling.value = false
  }
}

const getStatusText = (statusId) => {
  const statuses = {
    1: 'Completada',
    2: 'Anulada',
    4: 'Cancelada',
  }
  return statuses[statusId] || 'Desconocido'
}

const getStatusColor = (statusId) => {
  const colors = {
    1: 'green-darken-1',
    2: 'orange-darken-1',
    4: 'red-darken-1',
  }
  return colors[statusId] || 'grey'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const showSnackbar = (message, color) => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}
</script>
