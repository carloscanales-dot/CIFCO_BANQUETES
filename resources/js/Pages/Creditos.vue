<template>
    <Head title="Créditos" />
    <CreditoLayout>
        <!-- Estado: FERIA CERRADA / NO ABIERTA (cierre real) -->
        <div v-if="fairClosed">
            <v-card color="error" border="start" elevation="2">
                <v-card-title class="text-h5 font-weight-bold">FERIA FINALIZADA</v-card-title>
                <v-card-text class="text-subtitle-1">
                    La feria <strong>{{ props.fair_name }}</strong> no está abierta. Es un evento finalizado,
                    por lo que no se pueden registrar créditos en esta estación.
                </v-card-text>
            </v-card>
        </div>

        <!-- Estado: CERRADA -->
        <div v-else-if="props.terminal_status === 6">
            <v-card color="error" border="start" elevation="2">
                <v-card-title class="text-h5 font-weight-bold">CAJA CERRADA</v-card-title>
                <v-card-text class="text-subtitle-1">
                    No se pueden realizar ventas en esta terminal porque está cerrada.
                </v-card-text>
            </v-card>
        </div>

        <!-- Estado: PRE-CIERRE (Simplificado) -->
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
                    </div>
                    <div v-else>
                        <p>Cargando información de la terminal...</p>
                    </div>
                </v-card-text>
            </v-card>
        </div>

        <div v-else>
            <v-row>
                <!-- Panel lateral: detalle del crédito -->
                <v-col cols="12" md="4">
                    <v-card>
                        <v-card-title>Detalle de Crédito</v-card-title>
                        <!-- Select de empleado -->
                        <v-card-text>
                            <v-combobox v-model="selectedEmployee" :items="employees" item-title="name" item-value="id" label="Seleccionar empleado" outlined dense prepend-icon="mdi-account" clearable></v-combobox>
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
                            <v-btn
                                color="primary"
                                block
                                @click="printCreditReceipt"
                                :disabled="processing"
                                :loading="processing"
                            >
                                Guardar Crédito
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
                <!-- Panel derecho: productos -->
                <v-col cols="12" md="8">
                    <v-row>
                        <v-col v-for="product in products" :key="product.product_id" cols="6" sm="4" lg="3">
                            <v-card @click="cart.addItem(product)" style="background-color: #32385d; color: white;" class="text-center" height="180px" fill-height>
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

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="snackbar.timeout" location="top right">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn color="white" variant="text" @click="snackbar.show = false">
                    X
                </v-btn>
            </template>
        </v-snackbar>
    </CreditoLayout>
</template>

<script setup>
import CreditoLayout from '@/Layouts/CreditoLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref, onMounted, computed, reactive, watch } from 'vue'
import { useCartStore } from '@/Stores/cart'
import axios from 'axios'

const page = usePage()

const products = ref([])
const employees = ref([])
const selectedEmployee = ref(null)
const cart = useCartStore()
const pedidoCounter = ref(1)
const processing = ref(false) // Para evitar múltiples clics

// -- PRE-CIERRE VARS --
const currentTerminal = ref(null)
const snackbar = reactive({
    show: false,
    message: "",
    color: "success",
    timeout: 3000,
});
// -- END PRE-CIERRE VARS --

const props = defineProps({
    printer_ip: String,
    station_name: String,
    terminal_status: Number,
    fair_name: String,
    fair_status: Number,
})

// La feria solo permite créditos si está ABIERTA (status = 2).
const fairClosed = computed(() => props.fair_status != null && props.fair_status !== 2)

// -- PRE-CIERRE COMPUTED --
const precloseOpening = computed(() => {
    return currentTerminal.value?.openings?.[0] ?? null
})
// -- END PRE-CIERRE COMPUTED --


onMounted(async () => {
    // Si la terminal ya está en pre-cierre al montar, carga el resumen
    if (props.terminal_status === 7) {
        await fetchTerminalForStation()
    }

    await loadEmployees();
    await loadStationProducts();
});

watch(() => props.terminal_status, async (newVal) => {
    if (newVal === 7) {
        await fetchTerminalForStation()
    }
})


// -- PRE-CIERRE METHODS --
const showToast = (message, color = "success") => {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.show = true;
};

const fetchTerminalForStation = async () => {
    const stationId = getStationId()
    if (!stationId) return null
    try {
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

const getStationId = () => {
    const p = page.props || {}
    if (p.station_id) return Number(p.station_id)
    if (p.station && p.station.id) return Number(p.station.id)
    const user = p.auth?.user
    if (user && Array.isArray(user.stations) && user.stations.length) {
        const s = user.stations[0]
        return Number(s.id ?? s.station_id ?? s.stationId) || null
    }
    return null
}
// -- END PRE-CIERRE METHODS --


/* Cargar productos asignados a la estación actual */
const loadStationProducts = async () => {
    try {
        const response = await axios.get('/ticket/stations/my-products');
        products.value = response.data.products.map(p => ({
            product_id: p.product_id ?? p.id,
            product_name: p.product_name,
            unit_price: Number(p.unit_price),
            icon: p.icon ?? 'mdi-food'
        }));
        if (products.value.length === 0) {
            showToast("Esta estación no tiene productos asignados.", "warning");
        }
    } catch (error) {
        console.error("Error cargando productos de la estación:", error);
        products.value = [];
    }
};


/* Cargar empleados */
const loadEmployees = async () => {
    try {
        const response = await axios.get('/ticket/empleados/list')
        employees.value = response.data.data
    } catch (error) {
        console.error('Error cargando empleados:', error)
        employees.value = []
    }
}

/* Guardar crédito e imprimir ticket */
const printCreditReceipt = async () => {
    // Prevenir múltiples clics
    if (processing.value) return

    // Validaciones
    if (fairClosed.value) {
        showToast("La feria está cerrada. No se pueden registrar créditos.", "error")
        return
    }

    if (!selectedEmployee.value) {
        showToast("Debe seleccionar un empleado antes de guardar el crédito", "warning")
        return
    }

    if (!cart.cartItems.length) {
        showToast("Debe agregar al menos un producto", "warning")
        return
    }

    // Establecer procesando INMEDIATAMENTE para deshabilitar el botón
    processing.value = true

    try {
        const response = await axios.post('/ticket/cajas/transactions/store', {
            employee_id: selectedEmployee.value.id,
            cartItems: cart.cartItems,
            total: cart.cartTotal,
            station_id: getStationId(),
            transaction_type_id: 2, // 2 = Venta a empleado
        })

        if (!response.data.success) {
            showToast("Error al guardar crédito: " + response.data.message, "error")
            throw new Error("Error al guardar crédito")
        }

        // Limpiar el carrito y el empleado seleccionado
        cart.clearCart()
        selectedEmployee.value = null
        pedidoCounter.value++
        showToast("Crédito guardado correctamente. El ticket se imprimirá automáticamente.", "success")
    } catch (error) {
        console.error("Error procesando crédito:", error)
        showToast("Ocurrió un error al procesar el crédito", "error")
        throw error // Re-lanzar para mantener consistencia
    } finally {
        processing.value = false
    }
}
</script>
