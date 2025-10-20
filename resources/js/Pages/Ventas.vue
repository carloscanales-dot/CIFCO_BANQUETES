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
    { product_id: 1, product_name: 'Elotes Locos', unit_price: 1.50, icon: 'mdi-food' },
    { product_id: 2, product_name: 'Churros Españoles', unit_price: 2.00, icon: 'mdi-food' },
    { product_id: 3, product_name: 'Papitas', unit_price: 1.00, icon: 'mdi-food' },
    { product_id: 4, product_name: 'Refresco de Cola', unit_price: 1.25, icon: 'mdi-beer-outline' },
    { product_id: 5, product_name: 'Agua', unit_price: 1.00, icon: 'mdi-beer-outline' },
])
const cart = useCartStore()

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

    const width = 512;
    const height = 288;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(width, height);

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const byteIndex = Math.floor(x / 8) + y * Math.ceil(width / 8);
            const bit = 7 - (x % 8);
            const isBlack = (logoBitmap[byteIndex] >> bit) & 1;
            const idx = (y * width + x) * 4;
            imageData.data[idx] = imageData.data[idx+1] = imageData.data[idx+2] = isBlack ? 0 : 255;
            imageData.data[idx+3] = 255;
        }
    }
    ctx.putImageData(imageData, 0, 0);

    printer.addImage(ctx, 0, 0, width, height, printer.COLOR_1, printer.MODE_MONO);

    printer.addFeedLine(1);
    printer.addText("GAMER CIFCO 2\n");
    printer.addText("https://cifco.gob.sv/\n");
    printer.addText("-----------------------------\n");

    printer.addTextAlign(printer.ALIGN_LEFT);
    printer.addText(`PEDIDO N.º ${Math.floor(Math.random() * 1000)}\n`);
    printer.addText(`${new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}\n`);
    printer.addText("USUARIO: Pruebas\n");
    printer.addText("-----------------------------\n");

    printer.addText("CANT   ARTÍCULO                PRECIO\n");
    cart.cartItems.forEach(item => {
        const price = (item.unit_price * item.quantity).toFixed(2);
        const name = item.product_name.padEnd(23, ' ');
        const quantity = item.quantity.toString().padStart(2, ' ');
        printer.addText(`${quantity}   ${name}${price}\n`);
    });
    printer.addText("-----------------------------\n");

    printer.addText(`Recuento de artículos: ${cart.itemCount}\n`);
    printer.addText(`TOTAL: ${cart.cartTotal.toFixed(2)}\n`);
    printer.addText("-----------------------------\n");

    printer.addTextAlign(printer.ALIGN_CENTER);
    printer.addText("GRACIAS\n");

    printer.addBarcode("123456789012", printer.BARCODE_CODE39, printer.HRI_BELOW, printer.FONT_A, 2, 50);

    printer.addFeedLine(3);
    printer.addCut(printer.CUT_FEED);

    printer.send();
    cart.clearCart();
}
</script>
