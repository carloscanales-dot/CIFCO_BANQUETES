<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
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

// Cierre
async function confirmClose() {
    const opening = selectedTerminal.value.openings?.[0]
    if (!opening) return

    try {
        const response = await axios.post(`/terminal-sessions/${opening.id}/close`, {
            expected_amount: opening.opening_amount,
            real_amount: closingReal.value,
            closing_balance: closingReal.value - opening.opening_amount,
            user_id: selectedTerminal.value.user?.id ?? null,
            notes: closingNotes.value,
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
</script>

<template>
    <AdminLayout>
        <v-container fluid class="pa-4">

            <v-card flat class="pa-4 elevation-1">

                <v-card-title class="text-h6 font-weight-bold">Aperturas y Cierres</v-card-title>
                <v-divider class="my-3" />

                <!-- HEADERS con columnas virtuales -->
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
                        ${{ item.openings?.[0]?.opening_amount ?? 0 }}
                    </template>

                    <!-- Estado -->
                    <template #item.status_id="{ item }">
                        <v-chip size="small" :color="item.status_id === 5 ? 'green' :
                            item.status_id === 6 ? 'red' :
                                item.status_id === 7 ? 'orange' : 'grey'" text-color="white">
                            {{
                                item.status_id === 5 ? 'Abierta' :
                                    item.status_id === 6 ? 'Cerrada' :
                                        item.status_id === 7 ? 'Pre-Cierre' :
                                            'Desconocido'
                            }}
                        </v-chip>
                    </template>

                    <!-- Estación (FIX JSON) -->
                    <template #item.station_display="{ item }">
                        {{ item.station?.station_name || 'Sin estación' }}
                    </template>

                    <!-- Cajero (FIX JSON) -->
                    <template #item.user_display="{ item }">
                        {{ item.user?.name || 'Sin asignar' }}
                    </template>

                    <!-- Acciones -->
                    <template #item.actions="{ item }">
                        <v-btn v-if="item.status_id === 6" color="black" variant="elevated" size="small"
                            @click="openModalOpen(item)">
                            Aperturar
                        </v-btn>

                        <v-btn v-else-if="item.status_id === 5" color="black" variant="elevated" size="small"
                            @click="openModalClose(item)">
                            Cerrar
                        </v-btn>
                    </template>

                </v-data-table>
            </v-card>

            <!-- MODAL ÚNICO -->
            <v-dialog v-model="dialog" max-width="420">
                <v-card>

                    <v-card-title class="text-h6">
                        {{ modalMode === 'open' ? 'Aperturar Caja' : 'Cerrar Caja' }}
                    </v-card-title>

                    <v-card-text>
                        <p>
                            Terminal:
                            <strong>{{ selectedTerminal?.terminal_name }}</strong>
                        </p>
                        <p>
                            Total transaccionado:
                            <strong>{{ selectedTerminal?.openings?.[0]?.total_transacted ?? 0 }}
</strong>
                        </p>
                        <v-divider class="my-3" />

                        <v-text-field v-if="modalMode === 'open'" v-model="openingAmount" label="Monto de apertura"
                            type="number" prefix="$" variant="solo" density="compact" />

                        <!-- Inputs SOLO para cierre -->
                        <div v-else>
                            <v-text-field v-model="closingReal" label="Monto recibido" type="number" prefix="$"
                                variant="outlined" density="compact" />

                            <v-textarea v-model="closingNotes" label="Observaciones" variant="outlined" rows="2"
                                density="compact" />
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

.text-grey {
    color: #9e9e9e !important;
}
</style>
