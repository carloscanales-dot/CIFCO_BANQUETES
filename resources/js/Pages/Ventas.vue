<template>

    <Head title="Ventas" />
    <CajaLayout>
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
                        <v-btn color="primary" block @click="printReceipt">Pagar</v-btn>
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
const cart = useCartStore()
const pedidoCounter = ref(1);
const props = defineProps({
    printer_ip: String,
});

let epos = null;
let printer = null;

onMounted(async () => {
    if (!props.printer_ip) {
        alert(" No hay una impresora activa asignada a esta estación.");
        return;
    }

    console.log("Conectando a impresora con IP:", props.printer_ip);

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

    // Cargar productos de la estación del usuario
    await loadStationProducts();
});


const loadStationProducts = async () => {
    try {
        // Llamada a la API: asegúrate de usar la ruta correcta según tu web.php
        const response = await axios.get('/ticket/cajas/products');

        // Mapear los productos y asegurar que unit_price sea un número
        products.value = response.data.products.map(p => ({
            product_id: p.id,
            product_name: p.product_name,
            unit_price: Number(p.unit_price) || 0, // Convertir a número, fallback a 0
            icon: p.icon || 'mdi-food'
        }));
    } catch (error) {
        console.error('Error cargando productos de la estación:', error);

        // Vaciar lista para evitar errores de renderizado
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
        // 1️⃣ Guardar la transacción en el backend
        const response = await axios.post('/ticket/cajas/transactions/store', {
            cartItems: cart.cartItems,
            total: cart.cartTotal,
            station_id: 1 // aquí puedes usar la estación real si la tienes
        });

        if (!response.data.success) {
            alert("Error al guardar la transacción: " + response.data.message);
            return;
        }

        const transactionId = response.data.transaction_id;
        console.log("Transacción guardada con ID:", transactionId);

        // 2️⃣ Imprimir recibo
        const printOnce = () => {
            const width = 512; // 80 mm
            const height = 288;
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            const imageData = ctx.createImageData(width, height);

            // Render del logo
            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const byteIndex = Math.floor(x / 8) + y * Math.ceil(width / 8);
                    const bit = 7 - (x % 8);
                    const isBlack = (logoBitmap[byteIndex] >> bit) & 1;
                    const idx = (y * width + x) * 4;
                    imageData.data[idx] = imageData.data[idx + 1] = imageData.data[idx + 2] = isBlack ? 0 : 255;
                    imageData.data[idx + 3] = 255;
                }
            }
            ctx.putImageData(imageData, 0, 0);

            printer.addTextAlign(printer.ALIGN_CENTER);
            printer.addImage(ctx, 0, 0, width, height, printer.COLOR_1, printer.MODE_MONO);
            printer.addFeedLine(1);

            printer.addTextStyle(false, false, true, printer.COLOR_1);
            printer.addText("COMIDA ITALIANA\n");
            printer.addTextStyle(false, false, false, printer.COLOR_1);
            printer.addText("https://cifco.gob.sv/\n");
            printer.addText("-----------------------------\n");

            // Fecha y hora
            const now = new Date();
            const fecha = now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const hora = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

            printer.addText(`PEDIDO N.º ${transactionId}\n`); // mostramos el ID real
            printer.addText(`${fecha} - ${hora}\n`);
            printer.addText("USUARIO: Alejandra Portillo\n");
            printer.addText("-----------------------------\n");

            printer.addTextAlign(printer.ALIGN_LEFT);
            printer.addText("CANT  ARTÍCULO                          PRECIO\n");

            cart.cartItems.forEach(item => {
                const name = item.product_name.trim();
                const price = `$${(item.unit_price * item.quantity).toFixed(2)}`;
                const quantity = item.quantity.toString().padEnd(5);

                const maxLength = 26;
                const lines = [];
                for (let i = 0; i < name.length; i += maxLength) lines.push(name.slice(i, i + maxLength));

                printer.addText(`${quantity}${lines[0].padEnd(30)}${price}\n`);
                for (let i = 1; i < lines.length; i++) {
                    printer.addText(`     ${lines[i]}\n`);
                }
            });

            printer.addTextAlign(printer.ALIGN_CENTER);
            printer.addText("-----------------------------\n");
            printer.addText(`Subtotal: $${cart.cartTotal.toFixed(2)}\n`);
            printer.addTextStyle(false, false, true, printer.COLOR_1);
            printer.addText(`TOTAL: $${cart.cartTotal.toFixed(2)}\n`);
            printer.addTextStyle(false, false, false, printer.COLOR_1);
            printer.addText("-----------------------------\n");
            printer.addText("¡GRACIAS POR SU COMPRA!\n");
            printer.addFeedLine(1);
            printer.addBarcode("123456789012", printer.BARCODE_CODE39, printer.HRI_BELOW, printer.FONT_A, 2, 50);
            printer.addFeedLine(3);
            printer.addCut(printer.CUT_FEED);
        };

        printOnce();
        printOnce();
        printer.send();

        // 3️⃣ Limpiar carrito y actualizar contador
        cart.clearCart();
        pedidoCounter.value++;

    } catch (error) {
        console.error("Error procesando la transacción:", error);
        alert("Ocurrió un error al procesar la transacción");
    }
};

</script>
