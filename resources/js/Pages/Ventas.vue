<template>
    <Head title="Ventas" />
    <CajaLayout>
    <!-- Estado: CERRADA -->
    <div v-if="props.terminal_status === 6">
      <v-card color="error" border="start" elevation="2">
        <v-card-title class="text-h5 font-weight-bold">CAJA CERRADA</v-card-title>
        <v-card-text class="text-subtitle-1">
          No se pueden realizar ventas en esta terminal porque está cerrada.
        </v-card-text>
      </v-card>
    </div>

    <!-- Estado: PRE-CIERRE -->
    <div v-else-if="props.terminal_status === 7">
      <v-card color="orange" elevation="2">
        <v-card-title class="text-h6 font-weight-bold">CAJA EN PRE-CIERRE</v-card-title>
        <v-card-text>
          <p class="mb-2">
            La caja está en <strong>PRE-CIERRE</strong>. El cierre definitivo lo realizará el administrador.
          </p>

          <v-divider class="my-2" />

          <div v-if="precloseOpening">
            <p class="mb-1">
              <strong>Estación:</strong>
              {{ props.station_name || currentTerminal?.station?.station_name || 'N/A' }}
            </p>
            <p class="mb-1"><strong>Cajero:</strong> {{ currentTerminal?.user?.name || 'N/A' }}</p>

            <v-divider class="my-2" />

            <p class="mb-1">Efectivo: <strong>${{ precloseTotals.total_cash.toFixed(2) }}</strong></p>
            <p class="mb-1">Tarjeta:  <strong>${{ precloseTotals.total_card.toFixed(2) }}</strong></p>
            <p class="mb-1">Chivo:    <strong>${{ precloseTotals.total_chivo.toFixed(2) }}</strong></p>

            <v-divider class="my-2" />

            <p class="font-weight-bold">Total: ${{ precloseTotals.total_transacted.toFixed(2) }}</p>

            <!-- DETALLE DE ITEMS -->
            <v-divider class="my-2" />
            <div v-if="precloseDetails.length">
              <v-list density="compact" lines="two">
                <v-list-item v-for="(item, idx) in precloseDetails" :key="idx">
                  <v-list-item-title>{{ item.product_name }}</v-list-item-title>
                  <v-list-item-subtitle>Cantidad: {{ item.quantity }}</v-list-item-subtitle>

                  <template v-slot:append>
                    <div style="text-align:right; min-width:120px">
                      <div class="text-caption">P. unit: ${{ Number(item.unit_price).toFixed(2) }}</div>
                      <div class="font-weight-bold">${{ Number(item.total).toFixed(2) }}</div>
                    </div>
                  </template>
                </v-list-item>
              </v-list>
            </div>

            <div v-else class="text-white">
              No hay detalle de items disponible.
            </div>
          </div>

          <div v-else class="text-white">
            No hay resumen disponible. Actualiza o presiona "Obtener resumen".
          </div>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn text @click="fetchTerminalForStation">Obtener resumen</v-btn>
          <v-btn color="primary" @click="reprintPreclose">
            <v-icon left>mdi-printer</v-icon>
            Reimprimir Voucher
          </v-btn>
        </v-card-actions>
      </v-card>
    </div>

<!-- Estado: ABIERTA u otros (sigue mostrando la UI normal) -->

        <div v-else>
            <v-row class="mb-2">
                <v-col cols="12" class="d-flex justify-end">
                    <v-btn color="info" class="mr-2" @click="reconnectPrinter">Reconectar Impresora</v-btn>
                    <v-btn color="secondary" @click="openPrecloseModal">Pre-cierre</v-btn>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="4">
                    <v-card>
                        <v-card-title class="d-flex justify-space-between align-center">
                            <span>Detalle de Venta</span>
                            <v-tooltip location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn v-bind="props" icon size="small" @click="clearCartWithConfirmation"
                                        color="warning" :disabled="cart.cartItems.length === 0">
                                        <v-icon>mdi-delete-sweep-outline</v-icon>
                                    </v-btn>
                                </template>
                                <span>Limpiar Carrito</span>
                            </v-tooltip>
                        </v-card-title>
                        <v-list>
                            <v-list-item v-for="item in cart.cartItems" :key="item.product_id">
                                <v-list-item-title>{{ item.product_name }}</v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ item.quantity }} x ${{ item.unit_price.toFixed(2) }} = ${{ (item.quantity *
                                    item.unit_price).toFixed(2) }}
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-btn icon size="small" @click="cart.incrementItem(item.product_id)" color="green">
                                        <v-icon>mdi-plus</v-icon>
                                    </v-btn>
                                    <v-btn icon size="small" @click="cart.decrementItem(item.product_id)" color="red">
                                        <v-icon>mdi-minus</v-icon>
                                    </v-btn>
                                </template>
                            </v-list-item>
                        </v-list>
                        <v-divider></v-divider>
                        <v-card-text>
                            <div class="d-flex justify-space-between">
                                <span>Subtotal</span>
                                <span>${{ cart.cartTotal.toFixed(2) }}</span>
                            </div>
                            <div class="d-flex justify-space-between font-weight-bold">
                                <span>Total</span>
                                <span>${{ cart.cartTotal.toFixed(2) }}</span>
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn color="primary" block @click="abrirModalPago">Pagar</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
                <v-col cols="12" md="8">
                    <v-row>
                        <v-col v-for="product in products" :key="product.product_id" cols="6" sm="4" lg="3">
                            <v-card @click="cart.addItem(product)" style="background-color: #32385d; color: white;"
                                theme="dark" class="text-center" height="180px" fill-height>
                                <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                                    <div>
                                        <v-icon size="x-large">{{ product.icon }}</v-icon>
                                    </div>
                                    <div class="text-subtitle-1 my-2">{{ product.product_name }}</div>
                                    <div>${{ product.unit_price.toFixed(2) }}</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
        </div>
    </CajaLayout>

    <v-dialog v-model="showPaymentModal" width="450" persistent>
  <v-card>
    <v-card-title class="font-weight-bold">Forma de Pago</v-card-title>

    <v-card-text>
      <v-radio-group v-model.number="paymentMethod">

        <v-radio :value="1" label="Efectivo"></v-radio>

        <v-radio :value="2" label="Tarjeta"></v-radio>

        <v-radio :value="3" label="Chivo Wallet" disabled></v-radio>

      </v-radio-group>

      <!-- Campo efectivo recibido -->
      <div v-if="paymentMethod === 1" class="mt-4">
        <v-text-field
          label="Efectivo recibido"
          type="number"
          v-model.number="efectivoRecibido"
        />
        <div class="mt-2 d-flex justify-space-between font-weight-bold">
          <span>Cambio:</span>
          <span>${{ cambio }}</span>
        </div>
      </div>
    </v-card-text>

    <v-card-actions>
      <v-btn variant="text" @click="cancelarPago">Cancelar</v-btn>
      <v-btn :disabled="processing" color="primary" @click="confirmarPago">
        <span v-if="!processing">Confirmar Pago</span>
        <span v-else>Procesando...</span>
      </v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>
    <v-dialog v-model="showClearCartDialog" max-width="400" persistent>
        <v-card>
            <v-card-title class="text-h5">Confirmar Limpieza</v-card-title>
            <v-card-text>
                ¿Está seguro de que desea limpiar el carrito por completo? Esta acción no se puede deshacer.
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn text @click="showClearCartDialog = false">Cancelar</v-btn>
                <v-btn color="warning" text @click="executeClearCart">Aceptar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

  <TerminalClosingModal
    v-model="showClosingModal"
    :mode="closingModalMode"
    :terminal="currentTerminal"
    @done="onModalDone"
  />
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
</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, onMounted, computed, reactive, watch } from 'vue'
import { useCartStore } from '@/Stores/cart'
import { logoBitmap } from '../logoBitmap.js'
import TerminalClosingModal from '@/Components/TerminalClosingModal.vue'
import axios from 'axios'

const page = usePage();

const products = ref([])
const cart = useCartStore()
const pedidoCounter = ref(1);
const showClosingModal = ref(false)
const closingModalMode = ref('preclose') // 'open' | 'close' | 'preclose'
const currentTerminal = ref(null)
const loadingTerminal = ref(false) // inicialización correcta

// computed para abrir/resumen del pre-cierre
const precloseOpening = computed(() => {
  return currentTerminal.value?.openings?.[0] ?? null
})

const precloseTotals = computed(() => {
  const o = currentTerminal.value?.openings?.[0] ?? {}
  return {
    total_cash: Number(o.total_cash ?? o.total_cash_amount ?? 0),
    total_card: Number(o.total_card ?? o.total_card_amount ?? 0),
    total_chivo: Number(o.total_chivo ?? o.total_chivo_amount ?? 0),
    total_transacted: Number(o.total_transacted ?? o.total_amount ?? 0),
  }
})

const precloseDetails = computed(() => {
  const o = currentTerminal.value?.openings?.[0] ?? {}
  return Array.isArray(o.details) ? o.details : []
})



// abrir modal preclose (asegura que currentTerminal esté cargada)
const openPrecloseModal = async () => {
  if (loadingTerminal.value) return
  loadingTerminal.value = true

  try {
    if (!currentTerminal.value) {
      const found = await fetchTerminalForStation()
      if (!found) {
        showToast('No se encontró la terminal o no hay apertura activa en esta estación.', 'error')
        return
      }
    }

    // Si la terminal ya está en PRE-CIERRE (7) no permitir abrir modal otra vez
    if (currentTerminal.value?.status_id === 7 || props.terminal_status === 7) {
      showToast('La terminal ya está en Pre-cierre.', 'warning')
      return
    }

    closingModalMode.value = 'preclose'
    showClosingModal.value = true
  } finally {
    loadingTerminal.value = false
  }
}

// intenta resolver la terminal del backend a partir de la estación
const fetchTerminalForStation = async () => {
  const stationId = getStationId()
  if (!stationId) return null

  try {
    // RUTA ASUMIDA: backend que devuelve la terminal / apertura actual para la estación
    const resp = await axios.get('/terminal-sessions/current', { params: { station_id: stationId } })
    if (resp.data && resp.data.terminal) {
      currentTerminal.value = resp.data.terminal
      return currentTerminal.value
    }
    return null
  } catch (err) {
    console.error('Error fetching terminal', err)
    return null
  }
}

// handler cuando el modal emite done
const onModalDone = async (evt) => {
  if (evt.action === 'preclose' && evt.payload) {
    // normalizar totals
    const totals = evt.payload.totals ?? evt.payload ?? {}
    const totalsNormalized = {
      total_cash: Number(totals.total_cash ?? totals.cash ?? 0),
      total_card: Number(totals.total_card ?? totals.card ?? 0),
      total_chivo: Number(totals.total_chivo ?? totals.chivo ?? 0),
      total_transacted: Number(totals.total_transacted ?? totals.total ?? 0),
    }

    // dentro de onModalDone, después de totalsNormalized...
    const details = evt.payload.details ?? [];

    // Guardar detalles en sessionStorage para persistir a través de la recarga de página.
    if (details.length && currentTerminal.value?.id) {
      sessionStorage.setItem(`precloseDetails_${currentTerminal.value.id}`, JSON.stringify(details));
    }

    // si tenemos currentTerminal, guarda los totales y detalles ahí para que la card muestre inmediatamente
    if (currentTerminal.value) {
      currentTerminal.value.openings = currentTerminal.value.openings || []
      const opening = currentTerminal.value.openings[0] ?? {}
      opening.total_cash = totalsNormalized.total_cash
      opening.total_card = totalsNormalized.total_card
      opening.total_chivo = totalsNormalized.total_chivo
      opening.total_transacted = totalsNormalized.total_transacted

      // <- Guarda el detalle por producto (esperamos objetos con product_name, unit_price, quantity, total)
      opening.details = details.map(d => ({
        product_name: d.product_name ?? d.name ?? 'N/A',
        unit_price: Number(d.unit_price ?? d.unit_price_amount ?? 0),
        quantity: Number(d.quantity ?? d.qty ?? 0),
        total: Number(d.total ?? d.subtotal ?? 0),
      }))

      currentTerminal.value.openings[0] = opening
    }

    // si tenemos currentTerminal, guarda los totales ahí para que la card muestre inmediatamente
    if (currentTerminal.value) {
      currentTerminal.value.openings = currentTerminal.value.openings || []
      // Si ya existe opening[0], mergea los totales; si no, crea un objeto básico
      const opening = currentTerminal.value.openings[0] ?? {}
      opening.total_cash = totalsNormalized.total_cash
      opening.total_card = totalsNormalized.total_card
      opening.total_chivo = totalsNormalized.total_chivo
      opening.total_transacted = totalsNormalized.total_transacted
      currentTerminal.value.openings[0] = opening
    }

    // Intenta imprimir si hay impresora
    if (printer && totalsNormalized.total_transacted > 0) {
      try {
        await printPrecloseReceipt(totalsNormalized)
        showToast('Pre-cierre realizado e impreso.', 'success')
      } catch (e) {
        console.error(e)
        showToast('Pre-cierre realizado, pero falló la impresión.', 'warning')
      }
    } else {
      showToast('Pre-cierre realizado.', 'success')
    }

  }

  if (['preclose', 'close', 'open'].includes(evt.action)) {
    // Forzamos la recarga para que Inertia actualice la prop `terminal_status`
    // y se muestre la vista correcta (ej. PRE-CIERRE).
    router.reload()
  }
}

// función de impresión del PRE-CIERRE (ejemplo basado en tu printReceipt)
const printPrecloseReceipt = async (closing) => {
  // closing puede ser el objeto totalsNormalized o incluir .details (array)
  const details = closing.details ?? currentTerminal.value?.openings?.[0]?.details ?? []

  const printOncePreclose = () => {
    printer.addTextAlign(printer.ALIGN_CENTER)
    printer.addTextStyle(false, false, true, printer.COLOR_1)
    printer.addText(`${props.fair_name || 'CIFCO'}\n`)
    printer.addTextStyle(false, false, false, printer.COLOR_1)
    printer.addText('------ RESUMEN PRE-CIERRE ------\n')
    const now = new Date()
    printer.addText(`${now.toLocaleDateString('es-ES')} ${now.toLocaleTimeString('es-ES')}\n`)
    printer.addText(`ESTACIÓN: ${props.station_name || 'N/A'}\n`)
    printer.addText(`CAJERO: ${page.props.auth.user.name || 'N/A'}\n`)
    printer.addText('-----------------------------\n')
    printer.addText(`EFECTIVO: $${(closing.total_cash ?? 0).toFixed(2)}\n`)
    printer.addText(`TARJETA: $${(closing.total_card ?? 0).toFixed(2)}\n`)
    printer.addText(`CHIVO:   $${(closing.total_chivo ?? 0).toFixed(2)}\n`)
    printer.addText('-----------------------------\n')

    // cabecera detalle
    if (details.length) {
      printer.addText("CANT  ARTÍCULO            P.UNIT  SUBTOTAL\n")
      details.forEach(item => {
        const qty = String(item.quantity).padEnd(4)
        const name = (item.product_name || '').trim()
        const unit = Number(item.unit_price || 0).toFixed(2).padStart(6)
        const subtotal = Number(item.total || 0).toFixed(2).padStart(8)
        const max = 18
        const firstLine = name.slice(0, max).padEnd(max)
        printer.addText(`${qty} ${firstLine} ${unit} ${subtotal}\n`)
        // si el nombre es largo, imprime siguientes líneas
        for (let i = max; i < name.length; i += max) {
          printer.addText(`     ${name.slice(i, i + max)}\n`)
        }
      })
      printer.addText('-----------------------------\n')
    }

    printer.addTextStyle(false, false, true, printer.COLOR_1)
    printer.addText(`TOTAL: $${(closing.total_transacted ?? 0).toFixed(2)}\n`)
    printer.addTextStyle(false, false, false, printer.COLOR_1)
    printer.addText('-----------------------------\n')
    printer.addText('PRE-CIERRE\n')
    printer.addText('GRACIAS\n')
    printer.addFeedLine(2)
    printer.addCut(printer.CUT_FEED)
  }

  printOncePreclose()
  printer.send()
}


const props = defineProps({
    printer_ip: String,
    station_name: String,
    terminal_status: Number,
    fair_name: String,
})

let epos = null;
let printer = null;

// Helper para conectar a la impresora usando proxy HTTPS
const connectPrinterWithProxy = (printerIp) => {
    return new Promise((resolve, reject) => {
        if (!printerIp) {
            reject(new Error('No hay IP de impresora configurada'));
            return;
        }

        try {
            // Crear instancia de ePOS
            epos = new window.epson.ePOSDevice();

            // En producción (HTTPS), usamos un proxy en el servidor
            // En desarrollo (HTTP), conectamos directamente
            const isProduction = window.location.protocol === 'https:';

            if (isProduction) {
                // Usar proxy del servidor Laravel
                // El proxy acepta: printer_ip, path, method
                const proxyHandler = {
                    request: (req) => {
                        return axios.post('/printer-proxy', {
                            printer_ip: printerIp,
                            path: req.path || '',
                            method: req.method || 'GET'
                        });
                    }
                };
                // Conectar al proxy local (HTTPS)
                epos.connect('localhost', 443, (result) => {
                    if (result === 'OK') {
                        epos.createDevice('local_printer', epos.DEVICE_TYPE_PRINTER,
                            { crypto: false, buffer: false },
                            (printerDevice, code) => {
                                if (printerDevice) {
                                    printer = printerDevice;
                                    resolve({ printer, epos });
                                } else {
                                    reject(new Error('Error creando dispositivo: ' + code));
                                }
                            }
                        );
                    } else {
                        reject(new Error('Error conectando al proxy: ' + result));
                    }
                });
            } else {
                // En desarrollo, conexión directa
                epos.connect(printerIp, 8008, (result) => {
                    if (result === 'OK') {
                        epos.createDevice('local_printer', epos.DEVICE_TYPE_PRINTER,
                            { crypto: false, buffer: false },
                            (printerDevice, code) => {
                                if (printerDevice) {
                                    printer = printerDevice;
                                    resolve({ printer, epos });
                                } else {
                                    reject(new Error('Error creando dispositivo: ' + code));
                                }
                            }
                        );
                    } else {
                        reject(new Error('Error conectando a impresora: ' + result));
                    }
                });
            }
        } catch (error) {
            reject(error);
        }
    });
};

// Modal Pago
const showPaymentModal = ref(false)
const showClearCartDialog = ref(false)
const paymentMethod = ref(null)   // 1=Efectivo, 2=Tarjeta, 3=Chivo
const efectivoRecibido = ref(null)
const processing = ref(false)

const snackbar = reactive({
  show: false,
  message: "",
  color: "success",
  timeout: 3000,
});

const showToast = (message, color = "success") => {
  snackbar.message = message;
  snackbar.color = color;
  snackbar.show = true;
};

// reimprimir preclose
const reprintPreclose = async () => {
  try {
    // Asegúrate de tener la apertura/resumen.
    if (!currentTerminal.value?.openings?.[0]) {
      const found = await fetchTerminalForStation();
      if (!found) {
        showToast('No se encontró la apertura/pre-cierre para esta estación.', 'error');
        return;
      }
    }

    if (!printer) {
      showToast('Impresora no conectada.', 'error');
      return;
    }

    // `precloseDetails` computado puede estar vacío si los detalles no vienen del backend.
    let details = precloseDetails.value;

    // Si está vacío, intenta recuperarlo de sessionStorage como fallback.
    if ((!details || details.length === 0) && currentTerminal.value?.id) {
      const storedDetails = sessionStorage.getItem(`precloseDetails_${currentTerminal.value.id}`);
      if (storedDetails) {
        try {
          details = JSON.parse(storedDetails);
        } catch (e) {
          console.error("Error al parsear detalles desde sessionStorage:", e);
          details = []; // Reset in case of invalid JSON
        }
      }
    }

    const dataToPrint = {
      ...precloseTotals.value,
      details: details || [], // Asegura que details sea siempre un array
    };

    // Una última comprobación por si no hay ni totales ni detalles
    if (dataToPrint.total_transacted <= 0 && dataToPrint.details.length === 0) {
      showToast('No hay nada para imprimir.', 'warning')
      return
    }

    await printPrecloseReceipt(dataToPrint);
    showToast('Voucher reimpreso correctamente.', 'success');
  } catch (err) {
    console.error('Error reimprimiendo pre-cierre:', err);
    showToast('Ocurrió un error al reimprimir.', 'error');
  }
};

// helper: station id (en scope global del componente)
const getStationId = () => {
  const p = page.props || {}

  if (p.station_id) return Number(p.station_id)
  if (p.station && p.station.id) return Number(p.station.id)

  // si el usuario tiene estaciones en props.auth.user.stations
  const user = p.auth?.user
  if (user && Array.isArray(user.stations) && user.stations.length) {
    const s = user.stations[0]
    return Number(s.id ?? s.station_id ?? s.stationId) || null
  }

  // fallback: null (el backend debe validar y rechazar si es inválido)
  return null
}

// Cálculo del cambio solo si es efectivo
const cambio = computed(() => {
    if (paymentMethod.value === 1 && efectivoRecibido.value) {
        return (efectivoRecibido.value - cart.cartTotal).toFixed(2)
    }
    return "0.00"
})

const abrirModalPago = () => {
    showPaymentModal.value = true
}

const resetModalPago = () => {
    paymentMethod.value = null
    efectivoRecibido.value = null
}

const cancelarPago = () => {
    resetModalPago()
    showPaymentModal.value = false
}

const clearCartWithConfirmation = () => {
  if (cart.cartItems.length === 0) {
    showToast("El carrito ya está vacío.", "info");
    return;
  }
  showClearCartDialog.value = true;
};

const executeClearCart = () => {
    cart.clearCart();
    showToast("Carrito limpiado.", "success");
    showClearCartDialog.value = false;
};

// confirmar pago: valida, guarda selección local y llama a printReceipt
const confirmarPago = async () => {
  if (processing.value) return
  if (!paymentMethod.value) {
    showToast("Seleccione una forma de pago", "warning");
    return
  }
  if (paymentMethod.value === 1) {
    if (!efectivoRecibido.value || efectivoRecibido.value < cart.cartTotal) {
      showToast("El efectivo recibido es insuficiente", "error");
      return
    }
  }

  const selectedPaymentMethod = Number(paymentMethod.value)
  const selectedEfectivo = efectivoRecibido.value ?? 0

  showPaymentModal.value = false
  processing.value = true

    try {
    await printReceipt(selectedPaymentMethod, selectedEfectivo)
  } finally {
    processing.value = false
  }
}

const performFullReconnect = async () => {
    if (!props.printer_ip) {
        showToast("No hay una impresora activa asignada.", "error");
        return;
    }
    if (epos) {
        epos.disconnect();
    }
    printer = null;

    showToast('Reconectando impresora...', 'info');

    try {
        const { printer: newPrinter, epos: newEpos } = await connectPrinterWithProxy(props.printer_ip);
        printer = newPrinter;
        epos = newEpos;

        // Imprimir voucher de prueba
        try {
            printer.addTextAlign(printer.ALIGN_CENTER);
            printer.addTextStyle(false, false, true, printer.COLOR_1);
            printer.addText('Impresora Reconectada Exitosamente\n');
            printer.addTextStyle(false, false, false, printer.COLOR_1);
            printer.addFeedLine(1);
            printer.addCut(printer.CUT_FEED);
            printer.send();
            showToast('Impresora reconectada y voucher de prueba enviado.', 'success');
        } catch (e) {
            console.error('Error al imprimir voucher de prueba post-reconexión:', e);
            showToast('Impresora reconectada pero error al enviar voucher de prueba.', 'warning');
        }
    } catch (error) {
        console.error('Error reconectando a impresora:', error);
        showToast(`Error al reconectar impresora: ${error.message}`, 'error');
    }
};

const reconnectPrinter = () => {
    if (!printer || !epos) {
        performFullReconnect();
        return;
    }

    showToast('Verificando conexión de impresora...', 'info');

    let printFailed = false;
    const originalOnError = epos.onerror;

    epos.onerror = (err) => {
        if (!printFailed) { // Prevent multiple calls
            printFailed = true;
            console.error('Fallo de impresión detectado, iniciando reconexión completa.', err);
            epos.onerror = originalOnError;
            performFullReconnect();
        }
    };

    try {
        printer.addTextAlign(printer.ALIGN_CENTER);
        printer.addText('-----------------------------\n');
        printer.addText('Actualmente Conectada\n');
        printer.addText('-----------------------------\n');
        printer.addFeedLine(1);
        printer.addCut(printer.CUT_FEED);
        printer.send();
    } catch (e) {
        if (!printFailed) {
            printFailed = true;
            console.error('Fallo de impresión (síncrono), iniciando reconexión completa.', e);
            epos.onerror = originalOnError;
            performFullReconnect();
        }
        return;
    }

    setTimeout(() => {
        epos.onerror = originalOnError;
        if (!printFailed) {
            showToast('La impresora ya está conectada.', 'success');
        }
    }, 2000); // 2 seconds should be enough to catch a connection error
};

// Cargar productos + conectar impresora
onMounted(async () => {
    if (props.terminal_status === 6) {
      showToast(
        "No se pueden realizar ventas en esta terminal porque está cerrada.",
        "error"
      );
    }

    // Si la terminal ya está en pre-cierre al montar, carga el resumen
    if (props.terminal_status === 7) {
      await fetchTerminalForStation()
    }

    await loadStationProducts();

    if (!props.printer_ip) {
        showToast("No hay una impresora activa asignada.", "error");
        return;
    }

    try {
        await connectPrinterWithProxy(props.printer_ip);
        // La conexión fue exitosa, printer y epos ya están asignados por la función
    } catch (error) {
        console.error('Error al conectar impresora en onMounted:', error);
        showToast("No se pudo conectar a la impresora: " + error.message, "error");
    }
});

// refrescar cuando cambie el estado de la terminal (ej: a pre-cierre)
watch(() => props.terminal_status, async (newVal) => {
  if (newVal === 7) {
    await fetchTerminalForStation()
  }
})

const loadStationProducts = async () => {
    try {
        const response = await axios.get('/ticket/cajas/products');

        products.value = response.data.products.map(p => ({
            product_id: p.product_id,
            product_name: p.product_name,
            unit_price: Number(p.unit_price) || 0,
            icon: p.icon || 'mdi-food'
        }));

    } catch (error) {
        console.error('Error cargando productos:', error);
        products.value = [];
    }
};

const printReceipt = async (paymentMethodValue, efectivoValue) => {
    if (!printer) {
        showToast("Impresora no conectada", "error");
        return;
    }

    if (!cart.cartItems.length) {
        showToast("El carrito está vacío", "warning");
        return;
    }

    // obtener y validar stationId
    const stationId = getStationId()
    if (!stationId) {
      showToast('Estación inválida. Contacte al administrador.', 'error')
      return
    }

    try {
        // payload para la creación de la transacción (sin logs en producción)
        const payload = {
          cartItems: cart.cartItems,
          total: cart.cartTotal,
          station_id: stationId,
          employee_id: 103,
          payment_method: Number(paymentMethodValue)
        }

        const response = await axios.post('/ticket/cajas/transactions/store', {
          ...payload,
          transaction_type_id: 1, // 1 = Venta normal
        });

        if (!response.data.success) {
            showToast("Error al guardar la transacción: " + response.data.message, "error");
            return;
        }

        const transactionId = response.data.transaction_id;

        const cambioLocal = (paymentMethodValue === 1)
      ? (Number(efectivoValue) - Number(cart.cartTotal)).toFixed(2)
      : "0.00";

        const printOnce = () => {
            const sourceWidth = 512;
            const sourceHeight = 288;
            const width = 256;
            const height = 144;

            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = sourceWidth;
            tempCanvas.height = sourceHeight;
            const tempCtx = tempCanvas.getContext('2d');
            const tempData = tempCtx.createImageData(sourceWidth, sourceHeight);

            for (let y = 0; y < sourceHeight; y++) {
                for (let x = 0; x < sourceWidth; x++) {
                    const byteIndex = Math.floor(x / 8) + y * Math.ceil(sourceWidth / 8);
                    const bit = 7 - (x % 8);
                    const isBlack = (logoBitmap[byteIndex] >> bit) & 1;
                    const idx = (y * sourceWidth + x) * 4;
                    tempData.data[idx] = tempData.data[idx + 1] = tempData.data[idx + 2] = isBlack ? 0 : 255;
                    tempData.data[idx + 3] = 255;
                }
            }
            tempCtx.putImageData(tempData, 0, 0);

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(tempCanvas, 0, 0, sourceWidth, sourceHeight, 0, 0, width, height);

            printer.addTextAlign(printer.ALIGN_CENTER);
            printer.addImage(ctx, 0, 0, width, height, printer.COLOR_1, printer.MODE_MONO);
            printer.addFeedLine(1);

            printer.addTextStyle(false, false, true, printer.COLOR_1);
            printer.addText(`${props.fair_name || 'CIFCO'}\n`);
            printer.addTextStyle(false, false, false, printer.COLOR_1);
            printer.addText("-----------------------------\n");

            const now = new Date();
            const fecha = now.toLocaleDateString('es-ES');
            const hora = now.toLocaleTimeString('es-ES');

            printer.addText(`VENTA N.º ${transactionId}\n`);
            printer.addText(`${fecha} - ${hora}\n`);
            printer.addText(`ESTACIÓN: ${props.station_name || 'N/A'}\n`);
            printer.addText(`CAJERO: ${page.props.auth.user.name || 'N/A'}\n`);
            printer.addText("-----------------------------\n");

            printer.addText("CANT  ARTÍCULO            P.UNIT  SUBTOTAL\n");

            cart.cartItems.forEach(item => {
                const qty = item.quantity.toString().padEnd(4);
                const name = item.product_name.trim();
                const unit = item.unit_price.toFixed(2).padStart(5);
                const total = (item.unit_price * item.quantity).toFixed(2).padStart(7);
                const max = 18;
                const firstLine = name.slice(0, max).padEnd(max);
                printer.addText(`${qty} ${firstLine} ${unit} ${total}\n`);
                for (let i = max; i < name.length; i += max) {
                    printer.addText(`     ${name.slice(i, i + max)}\n`);
                }
            });

            printer.addText("-----------------------------\n");
            printer.addTextStyle(false, false, true, printer.COLOR_1);
            printer.addText(`TOTAL: $${cart.cartTotal.toFixed(2)}\n`);
            printer.addTextStyle(false, false, false, printer.COLOR_1);

            if (paymentMethodValue === 1) {
              printer.addText(`EFECTIVO: $${Number(efectivoValue).toFixed(2)}\n`);
              printer.addText(`CAMBIO:   $${cambioLocal}\n`);
            } else if (paymentMethodValue === 2) {
              printer.addText("PAGO CON TARJETA\n");
            } else if (paymentMethodValue === 3) {
              printer.addText("PAGO CHIVO\n");
            }

            printer.addText("-----------------------------\n");
            printer.addText("GRACIAS POR SU PREFERENCIA\n");
            printer.addFeedLine(1);
            printer.addBarcode("123456789012", printer.BARCODE_CODE39, printer.HRI_BELOW, printer.FONT_A, 2, 50);
            printer.addFeedLine(3);
            printer.addCut(printer.CUT_FEED);
        };

        printOnce();
        printOnce();
        printer.send();

        cart.clearCart();
        pedidoCounter.value++;

        resetModalPago();
        showToast('Transacción procesada exitosamente', 'success');

    } catch (error) {
        console.error("Error procesando la transacción:", error);
        showToast("Ocurrió un error al procesar la transacción", "error");
    }
};
</script>
