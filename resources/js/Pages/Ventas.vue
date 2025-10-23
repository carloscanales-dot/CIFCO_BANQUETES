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
                                {{ item.quantity }} x ${{ item.unit_price.toFixed(2) }} = ${{ (item.quantity * item.unit_price).toFixed(2) }}
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
                        <v-card @click="cart.addItem(product)" style="background-color: #32385d; color: white;" theme="dark" class="text-center" height="180px" fill-height>
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

const products = ref([
    { product_id: 1, product_name: 'Minestrone', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 2, product_name: 'Spaghetti a la Carbonara', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 3, product_name: 'Porcion Pizza Suprema', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 4, product_name: 'Porcion Pizza Pepperoni', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 5, product_name: 'Gnochi a la sorrentina', unit_price: 2.50, icon: 'mdi-food' },
    { product_id: 6, product_name: 'Canelones', unit_price: 2.50, icon: 'mdi-food' },
    { product_id: 7, product_name: 'Bruschetta capresa', unit_price: 1.50, icon: 'mdi-food' },
    { product_id: 8, product_name: 'Tiramisu', unit_price: 2.75, icon: 'mdi-food' },
    { product_id: 9, product_name: 'Canoli de ricotta', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 10, product_name: 'Soda de lata', unit_price: 1.00, icon: 'mdi-beer-outline' },
    { product_id: 11, product_name: 'Soda de botella', unit_price: 0.35, icon: 'mdi-beer-outline' },
])
const cart = useCartStore()
const pedidoCounter = ref(1);


let epos = null;
let printer = null;

onMounted(() => {
    epos = new window.epson.ePOSDevice();
    epos.connect('10.0.0.172', 8008, (result) => {
        if (result !== 'OK') {
            alert("No se pudo conectar: " + result);
            return;
        }
        epos.createDevice('local_printer', epos.DEVICE_TYPE_PRINTER, {crypto:false, buffer:false}, (printerDevice, code) => {
            if (!printerDevice) {
                alert("Error creando dispositivo: " + code);
                return;
            }
            printer = printerDevice;
        });
    });
});

const printReceipt = () => {
    if (!printer) {
        alert("Impresora no conectada");
        return;
    }

    const printOnce = () => {
        const width = 512; // 80 mm
        const height = 288;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        const imageData = ctx.createImageData(width, height);

        // === Render del logo ===
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

        // === Encabezado centrado ===
        printer.addTextAlign(printer.ALIGN_CENTER);
        printer.addImage(ctx, 0, 0, width, height, printer.COLOR_1, printer.MODE_MONO);
        printer.addFeedLine(1);

        printer.addTextStyle(false, false, true, printer.COLOR_1);
        printer.addText("COMIDA ITALIANA\n");
        printer.addTextStyle(false, false, false, printer.COLOR_1);
        printer.addText("https://cifco.gob.sv/\n");
        printer.addText("-----------------------------\n");

        // === Fecha y hora ===
        const now = new Date();
        const fecha = now.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const hora = now.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });

        printer.addText(`PEDIDO N.º ${pedidoCounter.value}\n`);
        printer.addText(`${fecha} - ${hora}\n`);
        printer.addText("USUARIO: Alejandra Portillo\n");
        printer.addText("-----------------------------\n");

        // === Tabla de productos centrada ===
        printer.addTextAlign(printer.ALIGN_CENTER);
        printer.addText("<DETALLE DE PRODUCTOS>\n");

        printer.addTextAlign(printer.ALIGN_LEFT);
        printer.addText("CANT  ARTÍCULO                          PRECIO\n");

        cart.cartItems.forEach(item => {
            const name = item.product_name.trim();
            const price = `$${(item.unit_price * item.quantity).toFixed(2)}`;
            const quantity = item.quantity.toString();

            const maxLength = 26;
            const lines = [];
            for (let i = 0; i < name.length; i += maxLength) {
                lines.push(name.slice(i, i + maxLength));
            }

            const firstLine = `${quantity.padEnd(5)}${lines[0].padEnd(30)}${price}\n`;

            printer.addTextAlign(printer.ALIGN_CENTER);
            printer.addText(firstLine);

            for (let i = 1; i < lines.length; i++) {
                printer.addTextAlign(printer.ALIGN_CENTER);
                printer.addText(`     ${lines[i]}\n`);
            }
        });

        printer.addTextAlign(printer.ALIGN_CENTER);
        printer.addText("-----------------------------\n");

        // === Totales centrados ===
        printer.addText(`Subtotal: $${cart.cartTotal.toFixed(2)}\n`);
        printer.addTextStyle(false, false, true, printer.COLOR_1);
        printer.addText(`TOTAL: $${cart.cartTotal.toFixed(2)}\n`);
        printer.addTextStyle(false, false, false, printer.COLOR_1);
        printer.addText("-----------------------------\n");

        printer.addText("¡GRACIAS POR SU COMPRA!\n");
        printer.addFeedLine(1);

        // === Código de barras centrado ===
        printer.addBarcode("123456789012", printer.BARCODE_CODE39, printer.HRI_BELOW, printer.FONT_A, 2, 50);

        printer.addFeedLine(3);
        printer.addCut(printer.CUT_FEED);
    };

    // === Imprimir dos copias ===
    printOnce();
    printOnce();

    printer.send();
    cart.clearCart();
    pedidoCounter.value++;
};






</script>
