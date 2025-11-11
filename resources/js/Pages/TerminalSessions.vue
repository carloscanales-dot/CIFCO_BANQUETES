<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const page = usePage()
const terminals = computed(() => page.props.terminals?.data ?? [])

// ✅ Props que vienen desde Laravel
const openings = computed(() => page.props.openings?.data ?? [])
const filters = computed(() => page.props.filters ?? { q: '', perPage: 10 })

// 🔍 Estados locales
const closeDialog = ref(false)
const selectedOpening = ref(null)
const isProcessing = ref(false)

// 🔎 Abrir modal para cierre
function openCloseDialog(opening) {
    selectedOpening.value = opening
    closeDialog.value = true
}

// 🔐 Confirmar cierre
async function closeTerminal() {
    if (!selectedOpening.value) return

    isProcessing.value = true
    try {
        const response = await axios.post(`/terminal-sessions/${selectedOpening.value.id}/close`, {
            expected_amount: selectedOpening.value.opening_amount,
            real_amount: selectedOpening.value.opening_amount,
            closing_balance: 0,
            user_id: selectedOpening.value.user?.id || null,
            notes: 'Cierre de caja normal',
        })

        if (response.data.success) {
            toast.success('Terminal cerrada correctamente.')
            closeDialog.value = false
            router.reload({ only: ['openings'] })
        } else {
            toast.error(response.data.message || 'No se pudo cerrar la terminal.')
        }
    } catch (error) {
        console.error(error)
        toast.error('Error al procesar el cierre.')
    } finally {
        isProcessing.value = false
    }
}
</script>

<template>
    <AdminLayout>
        <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
            <v-card flat class="pa-4 elevation-1" style="background-color: white;">
                <v-card-title class="text-h6 font-weight-bold">Aperturas y Cierres de Terminales</v-card-title>
                <v-divider class="my-3" />
                <v-data-table :items="terminals" class="elevation-0" dense :headers="[
                    { title: 'Terminal', key: 'terminal_name' },
                    { title: 'Estado', key: 'status' },
                    { title: 'Monto en Caja', key: 'amount' },
                    { title: 'Estación', key: 'station' },
                    { title: 'Cajero', key: 'user' },
                    { title: 'Acciones', key: 'actions', align: 'center' }
                ]">
                    <!-- ✅ Nombre de terminal -->
                    <template #item.terminal_name="{ item }">
                        {{ item.terminal_name }}
                    </template>

                    <!-- ✅ Estado -->
                    <template #item.status="{ item }">
                        <v-chip size="small" :color="item.status === 2 ? 'red' : (item.status === 1 ? 'green' : 'grey')"
                            variant="flat" text-color="white">
                            {{ item.status === 1 ? 'Abierta' : (item.status === 2 ? 'Cerrada' : 'Desconocido') }}
                        </v-chip>
                    </template>


                    <!-- ✅ Monto en caja -->
                    <template #item.amount="{ item }">
                        ${{ item.openings[0]?.opening_amount ?? 0 }}
                    </template>

                    <!-- ✅ Estación -->
                    <template #item.station="{ item }">
                        {{ item.station?.station_name || 'Sin estación' }}
                    </template>

                    <!-- ✅ Cajero -->
                    <template #item.user="{ item }">
                        {{ item.user?.name || 'Sin asignar' }}
                    </template>

                    <!-- ✅ Acciones -->
                    <template #item.actions="{ item }">
                        <v-btn v-if="item.status === 2" color="green" variant="elevated" size="small"
                            @click="openOpenDialog(item)">
                            Aperturar Caja
                        </v-btn>

                        <v-btn v-else-if="item.status === 1" color="black" variant="elevated" size="small"
                            @click="openCloseDialog(item)">
                            Cerrar Caja
                        </v-btn>

                        <v-chip v-else size="small" color="grey" text-color="white">
                            Inactiva
                        </v-chip>
                    </template>
                </v-data-table>



            </v-card>

            <!-- Modal de Apertura -->
            <v-dialog v-model="openDialog" max-width="420">
                <v-card>
                    <v-card-title class="text-h6">Aperturar Caja</v-card-title>
                    <v-card-text>
                        <p>Terminal: <strong>{{ selectedTerminal?.terminal_name }}</strong></p>
                        <v-text-field v-model="openingAmount" label="Monto de Apertura" type="number" prefix="$"
                            variant="outlined" density="comfortable" />
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn text @click="openDialog = false">Cancelar</v-btn>
                        <v-btn color="green" @click="openTerminal">Confirmar Apertura</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Modal de Cierre -->
            <v-dialog v-model="closeDialog" max-width="420">
                <v-card>
                    <v-card-title class="text-h6">Cerrar Caja</v-card-title>
                    <v-card-text>
                        <p>Terminal: <strong>{{ selectedTerminal?.terminal_name }}</strong></p>
                        <v-text-field v-model="closingReal" label="Monto Real" type="number" prefix="$"
                            variant="outlined" density="comfortable" />
                        <v-textarea v-model="closingNotes" label="Observaciones" variant="outlined" rows="2" />
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn text @click="closeDialog = false">Cancelar</v-btn>
                        <v-btn color="black" @click="closeTerminal">Confirmar Cierre</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </v-container>
    </AdminLayout>
</template>

<style scoped>
.v-data-table th {
    font-weight: 700 !important;
    color: black !important;
    background-color: #f7f7f7 !important;
}
</style>
