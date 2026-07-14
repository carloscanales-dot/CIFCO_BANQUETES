<template>
  <Head title="Reportes" />
  <AdminLayout>
    <v-container fluid class="pa-6">
      <div class="mb-5">
        <h5 class="text-h5 font-weight-bold">Reportes de Ventas</h5>
        <p class="text-medium-emphasis">Genera y descarga reportes personalizados de ventas y tickets</p>
      </div>

      <!-- Filtros -->
      <v-card class="mb-4">
        <v-card-title class="text-h6 font-weight-bold py-3">
          <v-icon class="mr-2">mdi-filter</v-icon>
          Filtros
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-row dense>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.fecha_inicio"
                label="Fecha Inicio"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.fecha_fin"
                label="Fecha Fin"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="3">
              <v-select
                v-model="filters.station_id"
                :items="stations"
                item-title="station_name"
                item-value="id"
                label="Estación"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              ></v-select>
            </v-col>

            <v-col cols="12" md="3">
              <v-select
                v-model="filters.product_id"
                :items="products"
                item-title="product_name"
                item-value="id"
                label="Producto"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              ></v-select>
            </v-col>
          </v-row>

          <v-row dense class="mt-2">
            <v-col cols="12">
              <v-btn color="black" variant="flat" class="text-white" @click="loadReport" :loading="loading" prepend-icon="mdi-magnify">
                Generar Reporte
              </v-btn>
              <v-btn variant="text" @click="clearFilters" class="ml-2">
                Limpiar Filtros
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Resumen -->
      <v-row dense class="mb-4" v-if="reportData">
        <v-col cols="12" md="3">
          <v-card variant="outlined">
            <v-card-text>
              <div class="text-h5 font-weight-bold">
                ${{ parseFloat(reportData.total_monto || 0).toFixed(2) }}
              </div>
              <div class="text-caption text-medium-emphasis">Total en Ventas</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card variant="outlined">
            <v-card-text>
              <div class="text-h5 font-weight-bold">
                {{ reportData.total_transacciones || 0 }}
              </div>
              <div class="text-caption text-medium-emphasis">Transacciones</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card variant="outlined">
            <v-card-text>
              <div class="text-h5 font-weight-bold">
                {{ reportData.productos_vendidos || 0 }}
              </div>
              <div class="text-caption text-medium-emphasis">Productos Vendidos</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card variant="outlined">
            <v-card-text>
              <div class="text-h5 font-weight-bold">
                ${{ parseFloat(reportData.promedio_ticket || 0).toFixed(2) }}
              </div>
              <div class="text-caption text-medium-emphasis">Promedio por Transacción</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Tabla de Datos -->
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center text-h6 font-weight-bold py-3">
          <span>
            <v-icon class="mr-2">mdi-table</v-icon>
            Detalle de Transacciones
          </span>
          <div v-if="reportData && reportData.transactions?.length > 0">
            <v-btn
              color="grey-darken-3"
              variant="outlined"
              prepend-icon="mdi-file-pdf-box"
              @click="downloadPDF"
              :loading="downloadingPDF"
              class="mr-2"
            >
              PDF
            </v-btn>
            <v-btn
              color="grey-darken-3"
              variant="outlined"
              prepend-icon="mdi-file-excel"
              @click="downloadExcel"
              :loading="downloadingExcel"
            >
              Excel
            </v-btn>
          </div>
        </v-card-title>

        <v-divider />

        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="reportData?.transactions || []"
            :loading="loading"
            items-per-page="10"
            class="elevation-0"
            show-expand
          >
            <template v-slot:item.transaction_date="{ item }">
              {{ formatDate(item.transaction_date) }}
            </template>

            <template v-slot:item.amount="{ item }">
              ${{ parseFloat(item.amount || 0).toFixed(2) }}
            </template>

            <template v-slot:item.station="{ item }">
              {{ item.station_name || 'N/A' }}
            </template>

            <template v-slot:item.user="{ item }">
              {{ item.user_name || 'N/A' }}
            </template>

            <!-- Detalles expandibles de productos -->
            <template v-slot:expanded-row="{ item }">
              <tr>
                <td :colspan="headers.length + 1" class="pa-0">
                  <v-card flat class="ma-2">
                    <v-card-title class="text-subtitle-2 font-weight-bold py-2">
                      <v-icon class="mr-2" size="small">mdi-package-variant</v-icon>
                      Productos de la Transacción #{{ item.id }}
                    </v-card-title>
                    <v-divider />
                    <v-card-text>
                      <v-table density="compact">
                        <thead>
                          <tr>
                            <th>Producto</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Precio Unitario</th>
                            <th class="text-right">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="detail in item.details" :key="detail.product_id">
                            <td>{{ detail.product_name }}</td>
                            <td class="text-right">{{ detail.quantity }}</td>
                            <td class="text-right">${{ parseFloat(detail.unit_price || 0).toFixed(2) }}</td>
                            <td class="text-right font-weight-bold">${{ parseFloat(detail.total || 0).toFixed(2) }}</td>
                          </tr>
                          <tr v-if="!item.details || item.details.length === 0">
                            <td colspan="4" class="text-center text-medium-emphasis">
                              No hay detalles de productos
                            </td>
                          </tr>
                        </tbody>
                      </v-table>
                    </v-card-text>
                  </v-card>
                </td>
              </tr>
            </template>

            <template v-slot:no-data>
              <div class="text-center pa-4">
                <v-icon size="64" color="grey">mdi-file-document-outline</v-icon>
                <p class="text-h6 text-medium-emphasis mt-2">No hay datos para mostrar</p>
                <p class="text-caption">Selecciona los filtros y genera un reporte</p>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-container>

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

const props = defineProps({
  stations: Array,
  products: Array,
})

const filters = reactive({
  fecha_inicio: null,
  fecha_fin: null,
  station_id: null,
  product_id: null,
})

const reportData = ref(null)
const loading = ref(false)
const downloadingPDF = ref(false)
const downloadingExcel = ref(false)

const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
})

const stations = ref(props.stations || [])
const products = ref(props.products || [])

const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Fecha', key: 'transaction_date', sortable: true },
  { title: 'Estación', key: 'station', sortable: true },
  { title: 'Usuario', key: 'user', sortable: true },
  { title: 'Monto', key: 'amount', sortable: true, align: 'end' },
]

onMounted(async () => {
  // Cargar datos iniciales
  await loadStations()
  await loadProducts()

  // Cargar reporte automáticamente al iniciar (últimos 30 días)
  const today = new Date()
  const thirtyDaysAgo = new Date(today)
  thirtyDaysAgo.setDate(today.getDate() - 30)

  filters.fecha_inicio = thirtyDaysAgo.toISOString().split('T')[0]
  filters.fecha_fin = today.toISOString().split('T')[0]

  await loadReport()
})

const loadStations = async () => {
  try {
    const response = await axios.get('/api/ticket/stations')
    stations.value = response.data
  } catch (error) {
    console.error('Error cargando estaciones:', error)
  }
}

const loadProducts = async () => {
  try {
    const response = await axios.get('/api/ticket/products')
    products.value = response.data
  } catch (error) {
    console.error('Error cargando productos:', error)
  }
}

const loadReport = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/reportes/data', { params: filters })
    reportData.value = response.data
    showSnackbar('Reporte generado exitosamente', 'success')
  } catch (error) {
    console.error('Error generando reporte:', error)
    showSnackbar('Error al generar el reporte', 'error')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.fecha_inicio = null
  filters.fecha_fin = null
  filters.station_id = null
  filters.product_id = null
  reportData.value = null
}

const downloadPDF = async () => {
  downloadingPDF.value = true
  try {
    const response = await axios.get('/api/reportes/download/pdf', {
      params: filters,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `reporte_ventas_${new Date().getTime()}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    showSnackbar('PDF descargado exitosamente', 'success')
  } catch (error) {
    console.error('Error descargando PDF:', error)
    showSnackbar('Error al descargar PDF', 'error')
  } finally {
    downloadingPDF.value = false
  }
}

const downloadExcel = async () => {
  downloadingExcel.value = true
  try {
    const response = await axios.get('/api/reportes/download/excel', {
      params: filters,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `reporte_ventas_${new Date().getTime()}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    showSnackbar('Excel descargado exitosamente', 'success')
  } catch (error) {
    console.error('Error descargando Excel:', error)
    showSnackbar('Error al descargar Excel', 'error')
  } finally {
    downloadingExcel.value = false
  }
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
