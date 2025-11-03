<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from "vue-toastification";

const props = defineProps({
    users: Array,
    roles: Array,
})

const modal = ref(false)
const isEdit = ref(false)
const resetModal = ref(false)
const selectedUser = ref(null)
const toast = useToast();

function openResetModal(user) {
    selectedUser.value = user
    resetModal.value = true
}

function closeResetModal() {
    resetModal.value = false
    selectedUser.value = null
}

const form = useForm({
    id: null,
    name: '',
    email: '',
    roles: []
})

const headers = ref([
    { title: 'Nombre', key: 'name' },
    { title: 'Email', key: 'email' },
    { title: 'Roles', key: 'roles', align: 'start' },
    { title: 'Acciones', key: 'actions', align: 'center', width: '100px' }
])

function openModal(user = null) {
    if (user) {
        isEdit.value = true
        form.id = user.id
        form.name = user.name
        form.email = user.email
        form.roles = user.roles.map(r => r.name) // ← cargar roles del usuario

    } else {
        isEdit.value = false
        form.reset()
    }
    modal.value = true
}

function closeModal() {
    modal.value = false
    form.reset()
}

function submitForm() {
    const options = {
        onSuccess: () => closeModal(),
    }

    if (isEdit.value) {
        router.put(`/users/${form.id}`, form, options)
    } else {
        router.post('/users', form, options)
    }
}

function confirmResetPassword() {
    if (!selectedUser.value) return

    router.post(`/users/${selectedUser.value.id}/reset-password`, {}, {
        onSuccess: (page) => {
            closeResetModal()
        },
        onError: (errors) => {
            toast.error("Hubo un error al resetear la contraseña.");
        }
    })
}
</script>

<template>
    <AdminLayout>
        <v-container fluid class="pa-4" style="background-color: #f8f8f8; min-height: 100vh;">
            <!-- Tabla de usuarios -->
            <v-card flat class="pa-4 elevation-1" style="background-color: white;">
                <!-- Título + botón alineados -->
                <v-row class="align-center justify-space-between mb-2">
                    <v-col cols="12" sm="6">
                        <v-card-title class="text-h6 font-weight-bold text-left pa-0">
                            Usuarios
                        </v-card-title>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end">
                        <v-btn color="black" variant="elevated" class="add-user-btn" @click="openModal()">
                            <v-icon left>mdi-plus</v-icon>
                            Agregar Usuario
                        </v-btn>
                    </v-col>
                </v-row>

                <v-divider></v-divider>

                <!-- Tabla de usuarios -->
                <v-data-table :items="users" :headers="headers" class="elevation-0" dense>
                    <!-- Mostrar roles -->
                    <template #item.roles="{ item }">
                        <div>
                            <v-chip v-for="role in item.roles" :key="role.id" color="black" text-color="white"
                                size="small" class="ma-1">
                                {{ role.name }}
                            </v-chip>
                        </div>
                    </template>

                    <!-- Acciones -->
                    <template #item.actions="{ item }">
                        <div class="action-buttons">
                            <v-btn icon color="black" variant="text" density="compact" @click="openModal(item)">
                                <v-icon size="18">mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon color="black" variant="text" density="compact" @click="openResetModal(item)">
                                <v-icon size="18">mdi-lock-reset</v-icon>
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-card>


            <!-- Modal para crear/editar usuario -->
            <v-dialog v-model="modal" max-width="520">
                <v-card class="rounded-lg" style="background-color: #ffffff; color: black;">
                    <v-card-title class="text-h6 font-weight-bold text-center pb-2">
                        {{ isEdit ? 'Editar Usuario' : 'Agregar Usuario' }}
                    </v-card-title>

                    <v-divider></v-divider>

                    <v-card-text class="pt-6 pb-0">
                        <v-form @submit.prevent="submitForm">
                            <v-text-field v-model="form.name" label="Nombre" variant="outlined" color="black"
                                density="comfortable" :error-messages="form.errors.name" class="mb-3" required />

                            <v-text-field v-model="form.email" label="Email" variant="outlined" color="black"
                                density="comfortable" :error-messages="form.errors.email" class="mb-5" required />

                            <!-- 🔹 Select elegante para roles -->
                            <v-select v-model="form.roles" :items="props.roles.map(r => r.name)"
                                label="Roles del usuario" multiple variant="outlined" density="comfortable" chips
                                color="black" class="mb-4" :error-messages="form.errors.roles">
                                <template #selection="{ item, index }">
                                    <v-chip color="black" text-color="white" size="small" class="me-1 rounded-pill">
                                        {{ item.title }}
                                    </v-chip>
                                </template>
                                <template #item="{ props, item }">
                                    <v-list-item v-bind="props" class="hover:bg-black/5 rounded-lg"
                                        title-class="font-medium text-black">
                                        <template #prepend>
                                            <v-icon size="16" color="black">mdi-shield-account-outline</v-icon>
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-select>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="justify-end py-3 px-4">
                        <v-btn text color="red" variant="elevated"  @click="closeModal()">
                            Cancelar
                        </v-btn>
                        <v-btn color="black" variant="elevated"  @click="submitForm()">
                            {{ isEdit ? 'Actualizar' : 'Crear' }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Modal de confirmación de reseteo -->
            <v-dialog v-model="resetModal" max-width="450">
                <v-card style="background-color: #ffffff; color: black;">
                    <v-card-title class="text-h6 font-weight-bold text-center">
                        Confirmar reseteo
                    </v-card-title>

                    <v-divider></v-divider>

                    <v-card-text class="pt-4 text-center">
                        ¿Estás seguro de que deseas resetear la contraseña de
                        <strong>{{ selectedUser?.name }}</strong>?
                        <div class="mt-2 text-caption text-grey-darken-1">
                            Se generará una nueva contraseña aleatoria y se enviará por correo.
                        </div>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="justify-end">
                        <v-btn text color="red" @click="closeResetModal()" variant="elevated">Cancelar</v-btn>
                        <v-btn color="black" variant="elevated" @click="confirmResetPassword()">Aceptar</v-btn>
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

.v-data-table th:nth-child(3) {
    width: 100px !important;
}

.v-data-table td:nth-child(3) {
    width: 100px !important;
    text-align: center;
    padding: 0 !important;
}

:deep(.v-data-table thead th) {
    font-weight: 700 !important;
    color: black !important;
    background-color: #f7f7f7 !important;
}

.v-card-text strong {
    font-weight: 700;
    color: black;
}
.v-chip {
  font-weight: 500;
  border-radius: 9999px;
}

.v-list-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>