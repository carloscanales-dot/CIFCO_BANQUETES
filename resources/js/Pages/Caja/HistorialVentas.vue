<template>
  <Head title="Historial de Ventas" />
  <CajaLayout>
    <v-container>
      <v-card>
        <v-card-title>Historial de Ventas</v-card-title>

        <v-card-text>
          <v-data-table
            v-if="hasData"
            :items="transactionsSafe.data"
            :headers="headers"
            item-key="id"
            density="comfortable"
            class="elevation-0"
            hide-default-footer
          >
            <!-- Status column -->
            <template #item.status="{ item }">
              <v-chip :color="getStatusColor(item)" dark small>
                {{ getStatusName(item) }}
              </v-chip>
            </template>

            <!-- Monto column (format) -->
            <template #item.amount="{ item }">
              {{ formatCurrency(getAmount(item)) }}
            </template>

            <!-- Fecha column (format) -->
            <template #item.created_at="{ item }">
              {{ formatDate(getDate(item)) }}
            </template>

            <!-- Usuario column (in case item has user object) -->
            <template #item.user="{ item }">
              {{ getUserName(item) }}
            </template>

            <!-- Station column -->
            <template #item.station="{ item }">
              {{ getStationName(item) }}
            </template>

            <!-- Metodo pago column -->
            <template #item.payment_method="{ item }">
              <div>{{ getPaymentMethodLabel(item) }}</div>
              <div v-if="isMixed(item)" class="text-caption text-medium-emphasis">
                Ef: {{ formatCurrency(item.amount_cash) }} · Tar: {{ formatCurrency(item.amount_card) }}
              </div>
            </template>

            <!-- T. Trasaccion column -->
            <template #item.transaction_type_id="{ item }">
              {{ getTransactionTypeName(item) }}
            </template>

            <!-- Acciones column -->
            <template #item.actions="{ item }">
              <v-btn
                v-if="isMostRecentTransaction(item)"
                icon
                size="small"
                color="primary"
                @click="openReprintDialog(item)"
                title="Reimprimir"
              >
                <v-icon>mdi-printer</v-icon>
              </v-btn>
            </template>
          </v-data-table>

          <div v-else class="text-center pa-6">
            <div v-if="loading">Cargando...</div>
            <div v-else>No hay transacciones para mostrar.</div>
          </div>

          <div class="d-flex justify-end mt-4">
            <v-pagination
              v-model="page"
              :length="transactionsSafe.last_page"
              @update:model-value="onPageChange"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Modal de confirmación de reimpresión -->
    <v-dialog v-model="showReprintDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="text-h5">Confirmar Reimpresión</v-card-title>
        <v-card-text>
          <p class="mb-2">¿Desea reimprimir el ticket de la transacción #{{ selectedTransaction?.id }}?</p>
          <v-alert
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            Esta acción quedará registrada en el sistema con su usuario y estación.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="closeReprintDialog" :disabled="reprinting">Cancelar</v-btn>
          <v-btn
            color="primary"
            @click="confirmReprint"
            :loading="reprinting"
            :disabled="reprinting"
          >
            Reimprimir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar para notificaciones -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
      location="top right"
    >
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn color="white" variant="text" @click="snackbar.show = false">
          X
        </v-btn>
      </template>
    </v-snackbar>

  </CajaLayout>
</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { ref, computed, reactive } from 'vue'
import { router, Head} from '@inertiajs/vue3'
import axios from 'axios'

/* Props */
const props = defineProps({
  transactions: Object,
})

/* State */
const loading = ref(false)
const page = ref(props.transactions?.current_page ?? 1)
const showReprintDialog = ref(false)
const selectedTransaction = ref(null)
const reprinting = ref(false)

const snackbar = reactive({
  show: false,
  message: "",
  color: "success",
  timeout: 3000,
})

const showToast = (message, color = "success") => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

/* Safe paginator */
const transactionsSafe = computed(() => {
  const t = props.transactions ?? {}
  return {
    data: Array.isArray(t.data) ? t.data : [],
    current_page: t.current_page ?? 1,
    last_page: t.last_page ?? 1,
    per_page: t.per_page ?? 15,
    total: t.total ?? (Array.isArray(t.data) ? t.data.length : 0),
  }
})

const hasData = computed(() => transactionsSafe.value.data.length > 0)

/* Verificar si es la transacción más reciente */
const isMostRecentTransaction = (transaction) => {
  const data = transactionsSafe.value.data
  if (!data || data.length === 0) return false
  return data[0]?.id === transaction?.id
}

/* Table headers (Vuetify v-data-table) */
const headers = [
  { title: 'ID', key: 'id', value: 'id', align: 'start' },
  { title: 'Usuario', key: 'user', value: 'user' },
  { title: 'Estación', key: 'station', value: 'station.station_name' },
  { title: 'Monto', key: 'amount', value: 'amount' },
  { title: 'Método Pago', key: 'payment_method', value: 'payment_method' },
  { title: 'T. Transacción', key: 'transaction_type_id', value: 'transaction_type_id' },
  { title: 'Estado', key: 'status', value: 'status.status' },
  { title: 'Fecha', key: 'created_at', value: 'created_at' },
  { title: 'Acciones', key: 'actions', sortable: false }
]

/* Helpers — defensas frente a distintas estructuras del objeto */
function getUserName(tx) {
  return tx?.user?.name ?? tx?.user_name ?? 'N/A'
}
function getStationName(tx) {
  return tx?.station?.station_name ?? tx?.station_name ?? 'N/A'
}
function getPaymentTerminalOpeningId(tx) {
  return tx?.payment_terminal_opening_id ?? 'N/A'
}
function getPaymentMethodLabel(tx) {
  const pm = tx?.payment_method ?? tx?.paymentMethod
  if (pm && (pm.payment_method || pm.name)) return pm.payment_method ?? pm.name
  return tx?.payment_method_label ?? tx?.paymentMethodLabel ?? 'N/A'
}
// Venta con pago mixto (método 4): tiene desglose efectivo/tarjeta.
function isMixed(tx) {
  return Number(tx?.payment_method_id) === 4
}
function getStatusName(tx) {
  return tx?.status?.status ?? tx?.status_name ?? (tx.status_id === 4 ? 'Devolución' : 'N/A')
}
function getStatusColor(tx) {
  return tx?.status?.color ?? tx?.status_color ?? (tx.status_id === 4 ? 'grey' : 'primary')
}
function getAmount(tx) {
  return tx?.amount ?? tx?.total ?? 0
}
function getDate(tx) {
  return tx?.transaction_date ?? tx?.created_at ?? tx?.date ?? null
}

function getTransactionTypeName(tx) {
  return tx?.transaction_type?.name
    ?? tx?.transactionType?.name
    ?? tx?.transaction_type_name
    ?? tx?.transactionTypeName
    ?? tx?.transaction_type_id
    ?? tx?.transaction_type_id ?? (tx?.transaction_type_id ? String(tx.transaction_type_id) : null)
    ?? 'N/A'
}

const onPageChange = (newPage) => {
  if (!newPage) return
  loading.value = true
  page.value = newPage
  router.get('/historial-ventas', { page: newPage }, {
    preserveState: false,
    onFinish: () => {
      loading.value = false
    }
  })
}

/* Reimpresión de ticket */
const openReprintDialog = (transaction) => {
  selectedTransaction.value = transaction
  showReprintDialog.value = true
}

const closeReprintDialog = () => {
  showReprintDialog.value = false
  selectedTransaction.value = null
}

const confirmReprint = async () => {
  if (!selectedTransaction.value) return

  reprinting.value = true

  try {
    const response = await axios.post(`/transactions/${selectedTransaction.value.id}/reprint`)

    if (response.data.success) {
      showToast('Ticket enviado a impresión correctamente', 'success')
      closeReprintDialog()
    } else {
      showToast(response.data.message || 'Error al enviar a impresión', 'error')
    }
  } catch (error) {
    console.error('Error al reimprimir:', error)
    showToast('Error al enviar el ticket a impresión', 'error')
  } finally {
    reprinting.value = false
  }
}

/* Formatters */
const formatDate = (iso) => {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    if (isNaN(d.getTime())) return String(iso)
    return d.toLocaleString()
  } catch {
    return String(iso)
  }
}
const formatCurrency = (v) => {
  if (v == null) return '-'
  const n = Number(v)
  if (Number.isNaN(n)) return String(v)
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.pa-6 { padding: 24px; }
.text-center { text-align: center; }
</style>
