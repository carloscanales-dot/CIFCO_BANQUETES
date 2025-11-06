<template>
    <Head title="Créditos" />
    <CajaLayout>
        <v-row>
            <!-- Panel lateral: detalle del crédito -->
            <v-col cols="12" md="4">
                <v-card>
                    <v-card-title>Detalle de Crédito</v-card-title>

                    <!-- Select de empleado -->
                    <v-card-text>
                        <v-combobox
                            v-model="selectedEmployee"
                            :items="employees"
                            item-title="name"
                            item-value="id"
                            label="Seleccionar empleado"
                            outlined
                            dense
                            prepend-icon="mdi-account"
                            clearable
                        ></v-combobox>
                    </v-card-text>

                    <!-- Lista de productos seleccionados -->
                    <v-list>
                        <v-list-item v-for="item in cart.cartItems" :key="item.product_id">
                            <v-list-item-title>{{ item.product_name }}</v-list-item-title>
                            <v-list-item-subtitle>
                                {{ item.quantity }} x ${{ item.unit_price.toFixed(2) }} =
                                ${{ (item.quantity * item.unit_price).toFixed(2) }}
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
                        <v-btn color="primary" block @click="printCreditReceipt">
                            Guardar Crédito
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <!-- Panel derecho: productos -->
            <v-col cols="12" md="8">
                <v-row>
                    <v-col v-for="product in products" :key="product.product_id" cols="6" sm="4" lg="3">
                        <v-card
                            @click="cart.addItem(product)"
                            style="background-color: #32385d; color: white;"
                            class="text-center"
                            height="180px"
                            fill-height
                        >
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
    </CajaLayout>
</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { useCartStore } from '@/Stores/cart'
import { logoBitmap } from '../logoBitmap.js'
import axios from 'axios'

const products = ref([])
const employees = ref([])
const selectedEmployee = ref(null)
const cart = useCartStore()
const pedidoCounter = ref(1)
const props = defineProps({
    printer_ip: String,
    station_name: String
})

let epos = null
let printer = null

onMounted(async () => {
      /* 👇 Aquí cargamos empleados y productos correctamente */
    await loadEmployees();
    await loadStationProducts();
    console.log("Conectando a impresora con IP:", props.printer_ip);
    if (!props.printer_ip) {
        alert(" No hay una impresora activa asignada a esta estación.");
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
            // console.log("Impresora conectada correctamente a:", props.printer_ip);
        });
    });

});

/* Cargar productos asignados a la estación actual */
const loadStationProducts = async () => {
    try {
        console.log("📡 Solicitando productos de la estación...");
        const response = await axios.get('/ticket/stations/my-products');

        console.log("✅ Respuesta cruda del backend:", response.data);

        products.value = response.data.products.map(p => ({
            product_id: p.product_id ?? p.id,  // 👈 Aseguramos compatibilidad
            product_name: p.product_name,
            unit_price: Number(p.unit_price),
            icon: p.icon ?? 'mdi-food'
        }));

        console.log("🎯 Productos formateados para la vista:", products.value);

        if (products.value.length === 0) {
            alert("⚠ Esta estación no tiene productos asignados.");
        }

    } catch (error) {
        console.error("❌ Error cargando productos de la estación:", error);
        products.value = [];
    }
};


/* Cargar empleados */
const loadEmployees = async () => {
    try {
        const response = await axios.get('/ticket/empleados/list')
        // 👇 Ajuste: accedemos correctamente al array devuelto desde el backend
        employees.value = response.data.data
    } catch (error) {
        console.error('Error cargando empleados:', error)
        employees.value = []
    }
}

/* Guardar crédito e imprimir ticket */
const printCreditReceipt = async () => {
    if (!selectedEmployee.value) {
        alert("Debe seleccionar un empleado antes de guardar el crédito")
        return
    }

    if (!cart.cartItems.length) {
        alert("Debe agregar al menos un producto")
        return
    }

    if (!printer) {
        alert("Impresora no conectada")
        return
    }

    try {
        // Enviar crédito al backend
        const response = await axios.post('/ticket/cajas/transactions/store', {
            employee_id: selectedEmployee.value.id,
            cartItems: cart.cartItems,
            total: cart.cartTotal,
            station_id: 1
        })

        if (!response.data.success) {
            alert("Error al guardar crédito: " + response.data.message)
            return
        }

        const transactionId = response.data.transaction_id
        const empleado = selectedEmployee.value

        // --- Imprimir recibo ---
        const printOnce = () => {
            const width = 512
            const height = 288
            const canvas = document.createElement('canvas')
            canvas.width = width
            canvas.height = height
            const ctx = canvas.getContext('2d')
            const imageData = ctx.createImageData(width, height)

            // Logo
            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const byteIndex = Math.floor(x / 8) + y * Math.ceil(width / 8)
                    const bit = 7 - (x % 8)
                    const isBlack = (logoBitmap[byteIndex] >> bit) & 1
                    const idx = (y * width + x) * 4
                    imageData.data[idx] = imageData.data[idx + 1] = imageData.data[idx + 2] = isBlack ? 0 : 255
                    imageData.data[idx + 3] = 255
                }
            }
            ctx.putImageData(imageData, 0, 0)

            printer.addTextAlign(printer.ALIGN_CENTER)
            printer.addImage(ctx, 0, 0, width, height, printer.COLOR_1, printer.MODE_MONO)
            printer.addFeedLine(1)

            printer.addTextStyle(false, false, true, printer.COLOR_1)
            printer.addText("COMIDA CHINA\n")
            printer.addTextStyle(false, false, false, printer.COLOR_1)
            printer.addText("https://cifco.gob.sv/\n")
            printer.addText("-----------------------------\n")

            const now = new Date()
            const fecha = now.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
            const hora = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            })

            printer.addTextAlign(printer.ALIGN_CENTER)
            printer.addText(`VENTA N.º ${transactionId}\n`)
            printer.addText(`${fecha} - ${hora}\n`)
            printer.addText(`EMPLEADO: ${empleado?.name || 'N/A'}\n`)
            printer.addText("-----------------------------\n")

            printer.addText("CANT  ARTÍCULO                  PRECIO\n")
            printer.addTextAlign(printer.ALIGN_CENTER)
            cart.cartItems.forEach(item => {
                const name = item.product_name.trim()
                const price = `$${(item.unit_price * item.quantity).toFixed(2)}`
                const quantity = item.quantity.toString().padEnd(5)

                const maxLength = 26
                const lines = []
                for (let i = 0; i < name.length; i += maxLength) lines.push(name.slice(i, i + maxLength))

                printer.addText(`${quantity}${lines[0].padEnd(30)}${price}\n`)
                for (let i = 1; i < lines.length; i++) {
                    printer.addText(`     ${lines[i]}\n`)
                }
            })

            printer.addTextAlign(printer.ALIGN_CENTER)
            printer.addText("-----------------------------\n")
            printer.addText(`Subtotal: $${cart.cartTotal.toFixed(2)}\n`)
            printer.addTextStyle(false, false, true, printer.COLOR_1)
            printer.addText(`TOTAL: $${cart.cartTotal.toFixed(2)}\n`)
            printer.addTextStyle(false, false, false, printer.COLOR_1)
            printer.addText("-----------------------------\n")
            printer.addText("¡GRACIAS POR SU CONFIANZA!\n")
            printer.addFeedLine(1)
            printer.addBarcode("987654321098", printer.BARCODE_CODE39, printer.HRI_BELOW, printer.FONT_A, 2, 50)
            printer.addFeedLine(3)
            printer.addCut(printer.CUT_FEED)
        }

        printOnce()
        printOnce()
        printer.send()

        // Limpiar carrito y selección
        cart.clearCart()
        selectedEmployee.value = null
        pedidoCounter.value++
        alert("Crédito guardado e impreso correctamente")

    } catch (error) {
        console.error("Error procesando crédito:", error)
        alert("Ocurrió un error al procesar el crédito")
    }
}
</script>

