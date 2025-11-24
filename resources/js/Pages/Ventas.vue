<template>
    <Head title="Ventas" />
    <CajaLayout>
        <div v-if="props.terminal_status === 6">
            <v-card
                color="error"
                border="start"
                elevation="2"
            >
                <v-card-title class="text-h5 font-weight-bold">
                    CAJA CERRADA
                </v-card-title>
                <v-card-text class="text-subtitle-1">
                    No se pueden realizar ventas en esta terminal porque está cerrada.
                </v-card-text>
            </v-card>
        </div>
        <div v-else>
            <v-row class="mb-2">
                <v-col cols="12" class="d-flex justify-end">
                    <v-btn color="secondary">Pre-cierre</v-btn>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="4">
                    <v-card>
                        <v-card-title>Detalle de Venta</v-card-title>
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
      <v-radio-group v-model="paymentMethod">

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
      <v-btn color="primary" @click="confirmarPago">Confirmar Pago</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import { useCartStore } from '@/Stores/cart'
import { logoBitmap } from '../logoBitmap.js'
import axios from 'axios'

const products = ref([])
const cart = useCartStore()
const pedidoCounter = ref(1);

const props = defineProps({
    printer_ip: String,
    station_name: String,
    terminal_status: Number
})

let epos = null;
let printer = null;

// Modal Pago
const showPaymentModal = ref(false)
const paymentMethod = ref(null)   // 1=Efectivo, 2=Tarjeta, 3=Chivo
const efectivoRecibido = ref(null)

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

const confirmarPago = () => {
    if (!paymentMethod.value) {
        alert("Seleccione una forma de pago")
        return
    }

    if (paymentMethod.value === 1) {
        if (!efectivoRecibido.value || efectivoRecibido.value < cart.cartTotal) {
            alert("El efectivo recibido es insuficiente")
            return
        }
    }

    showPaymentModal.value = false
    resetModalPago()
    printReceipt()
}

// Cargar productos
onMounted(async () => {
    await loadStationProducts();

    if (!props.printer_ip) {
        alert("No hay una impresora activa asignada.");
        return;
    }

    epos = new window.epson.ePOSDevice();
    epos.connect(props.printer_ip, 8008, (result) => {
        if (result !== 'OK') {
            alert("No se pudo conectar a la impresora: " + result);
            return;
        }

        epos.createDevice('local_printer', epos.DEVICE_TYPE_PRINTER, { crypto: false, buffer: false }, (printerDevice, code) => {
            if (!printerDevice) {
                alert("Error creando dispositivo: " + code);
                return;
            }
            printer = printerDevice;
        });
    });
});

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

const printReceipt = async () => {
    if (!printer) {
        alert("Impresora no conectada");
        return;
    }

    if (!cart.cartItems.length) {
        alert("El carrito está vacío");
        return;
    }

    try {
        const response = await axios.post('/ticket/cajas/transactions/store', {
            cartItems: cart.cartItems,
            total: cart.cartTotal,
            station_id: 1,
            payment_method: paymentMethod.value
        });

        if (!response.data.success) {
            alert("Error al guardar la transacción: " + response.data.message);
            return;
        }

        const transactionId = response.data.transaction_id;

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
            printer.addText("COMIDA CHINA\n");
            printer.addTextStyle(false, false, false, printer.COLOR_1);
            printer.addText("-----------------------------\n");

            const now = new Date();
            const fecha = now.toLocaleDateString('es-ES');
            const hora = now.toLocaleTimeString('es-ES');

            printer.addText(`VENTA N.º ${transactionId}\n`);
            printer.addText(`${fecha} - ${hora}\n`);
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

            if (paymentMethod.value === 1) {
                printer.addText(`EFECTIVO: $${efectivoRecibido.value}\n`);
                printer.addText(`CAMBIO:   $${cambio.value}\n`);
            } else if (paymentMethod.value === 2) {
                printer.addText("PAGO CON TARJETA\n");
            } else if (paymentMethod.value === 3) {
                printer.addText("PAGO CHIVO\n");
            }

            printer.addText("-----------------------------\n");
            printer.addText("GRACIAS POR SU PREFERENCIA\n");
            printer.addFeedLine(3);
            printer.addCut(printer.CUT_FEED);
        };

        printOnce();
        printOnce();
        printer.send();

        cart.clearCart();
        pedidoCounter.value++;

    } catch (error) {
        console.error("Error procesando la transacción:", error);
        alert("Ocurrió un error al procesar la transacción");
    }
};
</script>

