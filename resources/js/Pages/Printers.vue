<script setup>
import { ref } from 'vue'
import { router, useForm, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
    printers: Array,
    stations: Array
})

const toast = useToast()

const modal = ref(false)
const isEdit = ref(false)
const deleteModal = ref(false)
const printerToDelete = ref(null)

const form = useForm({
    printer_id: null,
    printer_name: '',
    station_id: '',
    ip_adress: '',
    status: false
})

const headers = ref([
    { title: 'Nombre', key: 'printer_name' },
    { title: 'Estación', key: 'station.station_name' },
    { title: 'Dirección IP', key: 'ip_adress' },
    { title: 'Estado', key: 'status', align: 'center', width: '100px' },
    { title: 'Acciones', key: 'actions', align: 'center', width: '100px' }
])

function openModal(printer = null) {
    if (printer) {
        isEdit.value = true
        form.printer_id = printer.printer_id
        form.printer_name = printer.printer_name
        form.station_id = printer.station_id
        form.ip_adress = printer.ip_adress
        form.status = printer.status
    } else {
        isEdit.value = false
        form.reset()
        form.status = false
    }
    modal.value = true
}

function closeModal() {
    modal.value = false
    form.reset()
}

function submitForm() {
    const options = {
        onSuccess: () => {
            toast.success(isEdit.value ? 'Impresora actualizada.' : 'Impresora creada.')
            closeModal()
        },
        onError: () => {
            toast.error('Verifica los campos e inténtalo nuevamente.')
        }
    }

    if (isEdit.value) {
        router.put(`/printers/${form.printer_id}`, form, options)
    } else {
        router.post('/printers', form, options)
    }
}

function openDeleteModal(printer) {
    printerToDelete.value = printer
    deleteModal.value = true
}

function closeDeleteModal() {
    printerToDelete.value = null
    deleteModal.value = false
}

function confirmDeletePrinter() {
    if (!printerToDelete.value) return

    router.delete(`/printers/${printerToDelete.value.printer_id}`, {
        onSuccess: () => {
            toast.success('Impresora eliminada correctamente.')
            closeDeleteModal()
        },
        onError: () => {
            toast.error('No se pudo eliminar la impresora.')
        }
    })
}
// Actualiza el estado directamente desde el switch
function toggleStatus(printer) {
    const newStatus = !printer.status

    router.put(`/printers/${printer.printer_id}`, {
        ...printer,
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Impresora ${newStatus ? 'activada' : 'desactivada'}.`)
            printer.status = newStatus
        },
        onError: () => {
            toast.error('No se pudo actualizar el estado.')
        }
    })
}
</script>

<template>
  <Head title="Impresoras" />
    <AdminLayout>
        <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
            <v-card flat class="pa-4 elevation-1" style="background-color: white;">
                <!-- Título + botón -->
                <v-row class="align-center justify-space-between mb-2">
                    <v-col cols="12" sm="6">
                        <v-card-title class="text-h6 font-weight-bold text-left pa-0">
                            Impresoras
                        </v-card-title>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end">
                        <v-btn color="black" variant="elevated" class="add-printer-btn" @click="openModal()">
                            <v-icon left>mdi-plus</v-icon>
                            Agregar Impresora
                        </v-btn>
                    </v-col>
                </v-row>

                <v-divider></v-divider>

                <!-- Tabla -->
                <v-data-table :items="props.printers" :headers="headers" class="elevation-0" dense>
                    <!-- Estado (switch interactivo) -->
                    <template #item.status="{ item }">
                        <v-switch v-model="item.status" :color="item.status ? '#4CAF50' : 'grey'" inset hide-details
                            density="compact" class="custom-switch" @click.stop="toggleStatus(item)" />
                    </template>

                    <!-- Acciones -->
                    <template #item.actions="{ item }">
                        <div class="action-buttons">
                            <v-btn icon color="black" variant="text" density="compact" @click="openModal(item)">
                                <v-icon size="18">mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon color="red" variant="text" density="compact" @click="openDeleteModal(item)">
                                <v-icon size="18">mdi-delete</v-icon>
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-card>

            <!-- Modal crear/editar -->
            <v-dialog v-model="modal" max-width="500">
                <v-card style="background-color: #ffffff; color: black;">
                    <v-card-title class="text-h6 font-weight-bold text-center">
                        {{ isEdit ? 'Editar Impresora' : 'Agregar Impresora' }}
                    </v-card-title>

                    <v-divider></v-divider>

                    <v-card-text class="pt-4">
                        <v-form @submit.prevent="submitForm">
                            <v-text-field v-model="form.printer_name" label="Nombre" variant="outlined" color="black"
                                density="comfortable" :error-messages="form.errors.printer_name" required
                                class="mb-3" />

                            <v-select v-model="form.station_id" :items="props.stations" item-title="station_name"
                                item-value="id" label="Estación" variant="outlined" color="black" density="comfortable"
                                :error-messages="form.errors.station_id" required class="mb-3" />

                            <v-text-field v-model="form.ip_adress" label="Dirección IP" variant="outlined" color="black"
                                density="comfortable" :error-messages="form.errors.ip_adress" class="mb-3" />
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="justify-end">
                        <v-btn text color="red" @click="closeModal()" variant="elevated">Cancelar</v-btn>
                        <v-btn color="black" variant="elevated" @click="submitForm()">
                            {{ isEdit ? 'Actualizar' : 'Crear' }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-dialog v-model="deleteModal" max-width="450">
                <v-card style="background-color: #ffffff; color: black;">
                    <v-card-title class="text-h6 font-weight-bold text-center">
                        Confirmar eliminación
                    </v-card-title>

                    <v-divider></v-divider>

                    <v-card-text class="pt-4 text-center">
                        ¿Seguro que deseas eliminar la impresora con IP
                        <strong>{{ printerToDelete?.ip_adress }}</strong>?
                        <div class="mt-2 text-caption text-grey-darken-1">
                            Esta acción no se puede deshacer.
                        </div>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="justify-end">
                        <v-btn text color="red" variant="elevated" @click="closeDeleteModal()">Cancelar</v-btn>
                        <v-btn color="black" variant="elevated" @click="confirmDeletePrinter()">Eliminar</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </AdminLayout>
</template>

<style scoped>
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 4px;
}

.v-data-table th:nth-child(5) {
    width: 100px !important;
}

.v-data-table td:nth-child(5) {
    width: 100px !important;
    text-align: center;
    padding: 0 !important;
}

:deep(.v-data-table thead th) {
    font-weight: 700 !important;
    color: black !important;
    background-color: #f7f7f7 !important;
}

.custom-switch :deep(.v-selection-control) {
    transform: scale(0.8);
    /* reduce tamaño total */
}

.v-card-text strong {
    font-weight: 700;
    color: black;
}
</style>
