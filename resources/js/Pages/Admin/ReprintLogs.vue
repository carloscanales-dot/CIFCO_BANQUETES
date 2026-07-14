<template>
  <Head title="Historial de Reimpresiones" />
  <AdminLayout>
    <v-container>
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Historial de Reimpresiones</span>
          <v-chip color="info" variant="tonal">
            <v-icon start>mdi-printer-check</v-icon>
            Total: {{ reprintsSafe.total }}
          </v-chip>
        </v-card-title>

        <!-- Filtros -->
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filterForm.start_date"
                label="Fecha Inicio"
                type="date"
                density="compact"
                variant="outlined"
                clearable
                prepend-inner-icon="mdi-calendar"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filterForm.end_date"
                label="Fecha Fin"
                type="date"
                density="compact"
                variant="outlined"
                clearable
                prepend-inner-icon="mdi-calendar"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="filterForm.station_id"
                :items="stationOptions"
                item-title="station_name"
                item-value="id"
                label="Estación"
                density="compact"
                variant="outlined"
                clearable
                prepend-inner-icon="mdi-map-marker"
              />
            </v-col>
          </v-row>
          <v-row dense>
            <v-col cols="12" md="6">
              <v-btn
                color="primary"
                @click="applyFilters"
                :loading="loading"
                block
              >
                <v-icon start>mdi-filter</v-icon>
                Filtrar
              </v-btn>
            </v-col>
            <v-col cols="12" md="6">
              <v-btn
                color="secondary"
                variant="outlined"
                @click="clearFilters"
                :disabled="loading"
                block
              >
                <v-icon start>mdi-filter-off</v-icon>
                Limpiar
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />

        <v-card-text>
          <v-data-table
            v-if="hasData"
            :items="reprintsSafe.data"
            :headers="headers"
            item-key="id"
            density="comfortable"
            class="elevation-0"
            hide-default-footer
          >
            <!-- Transaction ID column -->
            <template #item.transaction_id="{ item }">
              <v-chip size="small" color="primary" variant="tonal">
                #{{ item.transaction_id }}
              </v-chip>
            </template>

            <!-- Usuario column -->
            <template #item.user="{ item }">
              <div class="d-flex align-center">
                <v-icon size="small" class="mr-2">mdi-account</v-icon>
                {{ getUserName(item) }}
              </div>
            </template>

            <!-- Estación column -->
            <template #item.station="{ item }">
              <div class="d-flex align-center">
                <v-icon size="small" class="mr-2">mdi-map-marker</v-icon>
                {{ getStationName(item) }}
              </div>
            </template>

            <!-- Tipo Transacción column -->
            <template #item.transaction_type="{ item }">
              <v-chip
                size="small"
                :color="getTransactionTypeColor(item)"
                variant="tonal"
              >
                {{ getTransactionTypeName(item) }}
              </v-chip>
            </template>

            <!-- Monto column -->
            <template #item.amount="{ item }">
              <span class="font-weight-bold">
                {{ formatCurrency(getAmount(item)) }}
              </span>
            </template>

            <!-- Empleado column (si aplica) -->
            <template #item.employee="{ item }">
              <span v-if="getEmployeeName(item) !== 'N/A'">
                <v-icon size="small" class="mr-1">mdi-account-tie</v-icon>
                {{ getEmployeeName(item) }}
              </span>
              <span v-else class="text-medium-emphasis">-</span>
            </template>

            <!-- Fecha de reimpresión column -->
            <template #item.reprinted_at="{ item }">
              <div class="text-caption">
                {{ formatDate(item.reprinted_at) }}
              </div>
            </template>
          </v-data-table>

          <div v-else class="text-center pa-6">
            <div v-if="loading">
              <v-progress-circular indeterminate color="primary" class="mb-3"></v-progress-circular>
              <div>Cargando...</div>
            </div>
            <div v-else>
              <v-icon size="64" color="grey-lighten-1" class="mb-3">mdi-printer-off</v-icon>
              <div class="text-h6 text-medium-emphasis">No hay reimpresiones registradas</div>
            </div>
          </div>

          <div v-if="hasData" class="d-flex justify-end mt-4">
            <v-pagination
              v-model="page"
              :length="reprintsSafe.last_page"
              @update:model-value="onPageChange"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, reactive } from 'vue'
import { router, Head } from '@inertiajs/vue3'

/* Props */
const props = defineProps({
  reprints: Object,
  stations: Array,
  filters: Object,
})

/* State */
const loading = ref(false)
const page = ref(props.reprints?.current_page ?? 1)

/* Filtros */
const filterForm = reactive({
  start_date: props.filters?.start_date ?? null,
  end_date: props.filters?.end_date ?? null,
  station_id: props.filters?.station_id ?? null,
})

const stationOptions = computed(() => {
  return props.stations ?? []
})

/* Safe paginator */
const reprintsSafe = computed(() => {
  const r = props.reprints ?? {}
  return {
    data: Array.isArray(r.data) ? r.data : [],
    current_page: r.current_page ?? 1,
    last_page: r.last_page ?? 1,
    per_page: r.per_page ?? 15,
    total: r.total ?? (Array.isArray(r.data) ? r.data.length : 0),
  }
})

const hasData = computed(() => reprintsSafe.value.data.length > 0)

/* Filtros */
const applyFilters = () => {
  loading.value = true
  const params = {}

  if (filterForm.start_date) params.start_date = filterForm.start_date
  if (filterForm.end_date) params.end_date = filterForm.end_date
  if (filterForm.station_id) params.station_id = filterForm.station_id

  router.get('/admin/reprint-logs', params, {
    preserveState: false,
    onFinish: () => {
      loading.value = false
    }
  })
}

const clearFilters = () => {
  filterForm.start_date = null
  filterForm.end_date = null
  filterForm.station_id = null
  applyFilters()
}

/* Table headers */
const headers = [
  { title: 'Transacción', key: 'transaction_id', value: 'transaction_id', align: 'start' },
  { title: 'Usuario', key: 'user', value: 'user' },
  { title: 'Estación', key: 'station', value: 'station' },
  { title: 'Tipo', key: 'transaction_type', value: 'transaction_type' },
  { title: 'Monto', key: 'amount', value: 'amount' },
  { title: 'Empleado', key: 'employee', value: 'employee' },
  { title: 'Fecha de Reimpresión', key: 'reprinted_at', value: 'reprinted_at' }
]

/* Helpers */
function getUserName(reprint) {
  return reprint?.user?.name ?? 'N/A'
}

function getStationName(reprint) {
  return reprint?.station?.station_name ?? 'N/A'
}

function getTransactionTypeName(reprint) {
  const tx = reprint?.transaction
  return tx?.transaction_type?.transaction_type
    ?? tx?.transaction_type?.name
    ?? tx?.transaction_type_name
    ?? 'N/A'
}

function getTransactionTypeColor(reprint) {
  const tx = reprint?.transaction
  const typeId = tx?.transaction_type_id

  if (typeId === 1) return 'success' // Venta
  if (typeId === 2) return 'warning' // Empleado
  if (typeId === 3) return 'info'    // Ticket QR
  return 'default'
}

function getAmount(reprint) {
  return reprint?.transaction?.amount ?? reprint?.transaction?.total ?? 0
}

function getEmployeeName(reprint) {
  const tx = reprint?.transaction
  return tx?.employee?.employee_name ?? tx?.employee_name ?? 'N/A'
}

const onPageChange = (newPage) => {
  if (!newPage) return
  loading.value = true
  page.value = newPage

  const params = { page: newPage }
  if (filterForm.start_date) params.start_date = filterForm.start_date
  if (filterForm.end_date) params.end_date = filterForm.end_date
  if (filterForm.station_id) params.station_id = filterForm.station_id

  router.get('/admin/reprint-logs', params, {
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
    return d.toLocaleString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    })
  } catch {
    return String(iso)
  }
}

const formatCurrency = (v) => {
  if (v == null) return '-'
  const n = Number(v)
  if (Number.isNaN(n)) return String(v)
  return '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.pa-6 { padding: 24px; }
.text-center { text-align: center; }
.gap-2 { gap: 8px; }
</style>
