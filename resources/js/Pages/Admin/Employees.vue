<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import EmployeeModal from '@/Components/Admin/EmployeeModal.vue'

const props = defineProps({
    employees: Array,
})

const modal = ref(false)
const searchQuery = ref('')
const selectedEmployee = ref(null)

const headers = ref([
    { title: 'ID', key: 'employee_id', align: 'center', width: '80px' },
    { title: 'Nombre', key: 'employee_name' },
    { title: 'Área', key: 'employee_area' },
    { title: 'Estado', key: 'status', align: 'center', width: '100px' },
    { title: 'Acciones', key: 'actions', align: 'center', width: '100px' },
])

const filteredEmployees = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.employees
    }
    const query = searchQuery.value.toLowerCase()
    return props.employees.filter(employee =>
        employee.employee_name.toLowerCase().includes(query) ||
        employee.employee_area.toLowerCase().includes(query)
    )
})

const openModal = (employee = null) => {
    selectedEmployee.value = employee
    // Forzar un pequeño delay para asegurar que el valor se actualice
    setTimeout(() => {
        modal.value = true
    }, 0)
}

const handleModalClose = (value) => {
    modal.value = value
    if (!value) {
        selectedEmployee.value = null
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Empleados" />
        <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
            <!-- Tabla de empleados -->
            <v-card flat class="pa-4 elevation-1" style="background-color: white;">
                <!-- Título y búsqueda -->
                <v-row class="align-center justify-space-between mb-4">
                    <v-col cols="12" sm="6">
                        <v-card-title class="text-h6 font-weight-bold text-left pa-0">
                            Empleados
                        </v-card-title>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end gap-2">
                        <v-text-field
                            v-model="searchQuery"
                            placeholder="Buscar por nombre o área..."
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            color="black"
                            class="flex-grow-1"
                            style="max-width: 300px;"
                        />
                        <v-btn color="black" variant="elevated" class="add-employee-btn" @click="openModal">
                            <v-icon left>mdi-plus</v-icon>
                            Agregar Empleado
                        </v-btn>
                    </v-col>
                </v-row>

                <v-divider></v-divider>

                <!-- Tabla de empleados -->
                <v-data-table
                    :items="filteredEmployees"
                    :headers="headers"
                    class="elevation-0"
                    dense
                    items-per-page="10"
                    :items-per-page-options="[10, 25, 50, 100]"
                >
                    <!-- Estado -->
                    <template #item.status="{ item }">
                        <v-chip
                            :color="item.status ? 'green' : 'red'"
                            text-color="white"
                            size="small"
                        >
                            {{ item.status ? 'Activo' : 'Inactivo' }}
                        </v-chip>
                    </template>

                    <!-- Acciones -->
                    <template #item.actions="{ item }">
                        <div class="action-buttons">
                            <v-btn icon color="black" variant="text" density="compact" @click="openModal(item)">
                                <v-icon size="18">mdi-pencil</v-icon>
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-card>

            <!-- Modal para crear/editar empleado -->
            <EmployeeModal v-model="modal" :employee="selectedEmployee" @update:modelValue="handleModalClose" />
        </v-container>
    </AdminLayout>
</template>

<style scoped>
:deep(.v-data-table thead th) {
    font-weight: 700 !important;
    color: black !important;
    background-color: #f7f7f7 !important;
}

.v-chip {
    font-weight: 500;
    border-radius: 9999px;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 4px;
}
</style>
