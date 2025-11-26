<script setup>
import { ref, computed } from 'vue'
import { usePage, router, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const page = usePage()

const terminals = computed(() => page.props.terminals?.data ?? [])

// Modal
const dialog = ref(false)
const modalMode = ref('open') // 'open' | 'close'
const selectedTerminal = ref(null)

// Campos apertura
const openingAmount = ref(0)

// Campos cierre
const closingReal = ref(0)
const closingNotes = ref('')

// Monto esperado = apertura + total_cash
const expectedAmount = computed(() => {
    const opening = selectedTerminal.value?.openings?.[0]
    if (!opening) return 0

    const openingAmount = opening.opening_amount ?? 0
    const totalCash = opening.total_cash ?? 0

    return openingAmount + totalCash
})

function openModalOpen(terminal) {
    modalMode.value = 'open'
    selectedTerminal.value = terminal
    openingAmount.value = 0
    dialog.value = true
}

function openModalClose(terminal) {
    modalMode.value = 'close'
    selectedTerminal.value = terminal
    closingReal.value = 0
    closingNotes.value = ''
    dialog.value = true
}

// Apertura
async function confirmOpen() {
    try {
        const response = await axios.post('/terminal-sessions/open', {
            payment_terminal_id: selectedTerminal.value.id,
            user_id: selectedTerminal.value.user?.id ?? null,
            opening_amount: openingAmount.value,
        })

        if (response.data.success) {
            toast.success("Terminal aperturada.")
            dialog.value = false
            router.reload({ only: ['terminals'] })
        }
    } catch {
        toast.error("Error al aperturar.")
    }
}

// Cierre — el backend ahora calcula expected_amount y closing_balance
async function confirmClose() {
    const opening = selectedTerminal.value.openings?.[0]
    if (!opening) return

    try {
        const response = await axios.post(`/terminal-sessions/${opening.id}/close`, {
            real_amount: closingReal.value,
            notes: closingNotes.value,
            user_id: selectedTerminal.value.user?.id ?? null,
        })

        if (response.data.success) {
            toast.success("Terminal cerrada.")
            dialog.value = false
            router.reload({ only: ['terminals'] })
        }
    } catch {
        toast.error("Error al cerrar.")
    }
}

function confirmAction() {
    modalMode.value === 'open'
        ? confirmOpen()
        : confirmClose()
}

function exportClosing(item) {
    const closingId = item.openings?.[0]?.closing?.payment_terminal_closing_id;

    if (!closingId) {
        return toast.error("No existe un cierre registrado para esta terminal.");
    }

    window.open(`/terminal-sessions/closing/${closingId}/export`, '_blank');
}

</script>

<template>

    <Head title="Aperturas y Cierres" />
    <AdminLayout>
        <v-container fluid class="pa-4">

            <v-card flat class="pa-4 elevation-1">

                <v-card-title class="text-h6 font-weight-bold">Aperturas y Cierres</v-card-title>
                <v-divider class="my-3" />

                <v-data-table :items="terminals" dense :headers="[
                    { title: 'Terminal', key: 'terminal_name' },
                    { title: 'Estado', key: 'status_id' },
                    { title: 'Monto', key: 'amount' },
                    { title: 'Estación', key: 'station_display', sortable: false },
                    { title: 'Cajero', key: 'user_display', sortable: false },
                    { title: 'Acciones', key: 'actions', align: 'center', sortable: false }
                ]">

                    <!-- Monto -->
                    <template #item.amount="{ item }">
                        <span v-if="item.status_id === 5">
                            ${{ item.openings?.[0]?.opening_amount ?? 0 }}
                        </span>
                        <span v-else>$0.00</span>
                    </template>

                    <!-- Estado -->
                    <template #item.status_id="{ item }">
                        <v-chip size="small" :color="item.status_id === 5 ? 'green'
                            : item.status_id === 6 ? 'red'
                                : item.status_id === 7 ? 'orange'
                                    : 'grey'" text-color="white">
                            {{
                                item.status_id === 5 ? 'Abierta'
                                    : item.status_id === 6 ? 'Cerrada'
                                        : item.status_id === 7 ? 'Pre-Cierre'
                                            : 'Desconocido'
                            }}
                        </v-chip>
                    </template>

                    <!-- Estación -->
                    <template #item.station_display="{ item }">
                        {{ item.station?.station_name || 'Sin estación' }}
                    </template>

                    <!-- Cajero -->
                    <template #item.user_display="{ item }">
                        {{ item.user?.name || 'Sin asignar' }}
                    </template>

                    <!-- Acciones -->
                    <template #item.actions="{ item }">

                        <v-btn v-if="item.status_id === 6" color="black" variant="elevated" size="small"
                            @click="openModalOpen(item)">
                            Aperturar
                        </v-btn>

                        <v-btn v-else-if="item.status_id === 5 || item.status_id === 7" color="black" variant="elevated"
                            size="small" @click="openModalClose(item)">
                            Cerrar
                        </v-btn>

                        <v-btn v-if="item.status_id === 6 && item.openings?.[0]?.closing" color="black"
                            variant="outlined" size="small" class="ml-2" @click="exportClosing(item)">
                            <v-icon small class="mr-1">mdi-file-pdf-box</v-icon>
                            PDF
                        </v-btn>

                    </template>
                </v-data-table>
            </v-card>

            <!-- ========================== -->
            <!-- MODAL -->
            <!-- ========================== -->
            <v-dialog v-model="dialog" max-width="420">
                <v-card>

                    <v-card-title class="text-h6 font-weight-bold">
                        {{ modalMode === 'open' ? 'Aperturar Caja' : 'Cerrar Caja' }}
                    </v-card-title>

                    <v-divider class="my-3" />

                    <v-card-text class="text-body-2">

                        <!-- INFORMACIÓN PRINCIPAL -->
                        <div class="mb-3">
                            <p class="mb-1">
                                <strong>Terminal:</strong> {{ selectedTerminal?.terminal_name }}
                            </p>
                            <p class="mb-1">
                                <strong>Cajero:</strong> {{ selectedTerminal?.user?.name ?? 'Sin asignar' }}
                            </p>
                        </div>

                        <!-- TOTALES ANTES DE CIERRE -->
                        <div v-if="modalMode === 'close' && selectedTerminal?.openings?.[0]">
                            <v-divider class="my-3" />

                            <p class="font-weight-bold mb-2">Totales por Método de Pago</p>

                            <p class="mb-1">Efectivo: <strong>${{ selectedTerminal?.openings?.[0]?.total_cash ?? 0
                                    }}</strong></p>
                            <p class="mb-1">Tarjeta: <strong>${{ selectedTerminal?.openings?.[0]?.total_card ?? 0
                                    }}</strong></p>
                            <p class="mb-1">Chivo Wallet: <strong>${{ selectedTerminal?.openings?.[0]?.total_chivo ?? 0
                                    }}</strong>
                            </p>

                            <v-divider class="my-3" />

                            <!-- Monto esperado -->
                            <p class="text-h6 mb-2">
                                <strong>Efectivo esperado: </strong> ${{ expectedAmount }}
                            </p>

                            <v-divider class="my-3" />
                        </div>

                        <!-- Apertura -->
                        <v-text-field v-if="modalMode === 'open'" v-model="openingAmount" label="Monto de apertura"
                            type="number" prefix="$" variant="solo" density="compact" />

                        <!-- Cierre -->
                        <div v-else>
                            <v-text-field v-model="closingReal" label="Monto recibido" type="number" prefix="$"
                                variant="solo" density="compact" class="mb-2" />

                            <v-textarea v-model="closingNotes" label="Observaciones" variant="solo" density="compact"
                                rows="2" />
                        </div>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer />
                        <v-btn text @click="dialog = false">Cancelar</v-btn>
                        <v-btn color="black" variant="elevated" @click="confirmAction">
                            {{ modalMode === 'open' ? 'Confirmar Apertura' : 'Confirmar Cierre' }}
                        </v-btn>
                    </v-card-actions>

                </v-card>
            </v-dialog>
        </v-container>
    </AdminLayout>
</template>

<style scoped>
:deep(.v-data-table thead th) {
    font-weight: 700 !important;
    color: black !important;
    background-color: #f7f7f7 !important;
}
</style>
