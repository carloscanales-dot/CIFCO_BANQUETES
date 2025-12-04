<template>
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
              {{ getPaymentMethodLabel(item) }}
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

  </CajaLayout>
</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

/* Props */
const props = defineProps({
  transactions: Object,
})

/* State */
const loading = ref(false)
const page = ref(props.transactions?.current_page ?? 1)

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

/* Table headers (Vuetify v-data-table) */
const headers = [
  { title: 'ID', key: 'id', value: 'id', align: 'start' },
  { title: 'Usuario', key: 'user', value: 'user' },
  { title: 'Estación', key: 'station', value: 'station.station_name' },
  { title: 'Monto', key: 'amount', value: 'amount' },
  { title: 'Método Pago', key: 'payment_method', value: 'payment_method' },
  { title: 'Estado', key: 'status', value: 'status.status' },
  { title: 'Fecha', key: 'created_at', value: 'created_at' },
]

/* Helpers — defensas frente a distintas estructuras del objeto */
function getUserName(tx) {
  return tx?.user?.name ?? tx?.user_name ?? 'N/A'
}
function getStationName(tx) {
  return tx?.station?.station_name ?? tx?.station_name ?? 'N/A'
}
function getPaymentMethodLabel(tx) {
  const pm = tx?.payment_method ?? tx?.paymentMethod
  if (pm && (pm.payment_method || pm.name)) return pm.payment_method ?? pm.name
  return tx?.payment_method_label ?? tx?.paymentMethodLabel ?? 'N/A'
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
