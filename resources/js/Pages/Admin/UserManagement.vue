<template>

    <Head title="Administrar Usuarios" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl leading-tight" style="color: #ffffff;">
                Administrar Usuarios
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <v-card>
                    <v-card-title>
                        <v-row class="w-100" align="center" justify="space-between">
                            <!-- Título a la izquierda -->
                            <v-col cols="12" md="6" class="d-flex align-center">
                                <div class="text-h6 font-weight-bold ">Usuarios</div>
                            </v-col>

                            <!-- Botón a la derecha -->
                            <v-col cols="12" md="6" class="d-flex justify-end">
                                <v-btn @click="openCreateDialog" prepend-icon="mdi-account-plus" color="primary">
                                    Crear Usuario
                                </v-btn>
                            </v-col>

                            <!-- Campo de búsqueda debajo -->
                            <v-col cols="12" class="mt-2">
                                <v-text-field v-model="search" append-inner-icon="mdi-magnify" label="Buscar"
                                    single-line hide-details variant="solo"></v-text-field>
                            </v-col>
                        </v-row>
                    </v-card-title>
                    <v-dialog v-model="createDialog" max-width="600px">
                        <v-card>
                            <v-card-title>
                                <v-icon start icon="mdi-account-plus-outline"></v-icon>
                                <span class="text-h6 font-weight-bold">Crear Nuevo Usuario</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <v-form ref="createFormRef" v-model="isCreateFormValid" @submit.prevent="saveCreate">
                                    <v-container>
                                        <v-row>
                                            <v-col cols="12">
                                                <v-text-field v-model="newUser.name" label="Nombre Completo"
                                                    variant="outlined" density="compact" :rules="[rules.required]"
                                                    required></v-text-field>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-text-field v-model="newUser.email" label="Correo Electrónico"
                                                    variant="outlined" density="compact"
                                                    :rules="[rules.required, rules.email]" required></v-text-field>
                                            </v-col>
                                            <v-col cols="12" sm="6">
                                                <v-text-field v-model="newUser.telefono" label="Teléfono"
                                                    variant="outlined" density="compact" type="tel"></v-text-field>
                                            </v-col>
                                            <v-col cols="12" sm="6">
                                                <v-select v-model="newUser.sexo" :items="sexoOptions" item-title="text"
                                                    item-value="value" label="Sexo" variant="outlined" density="compact"
                                                    :rules="[rules.required]" required></v-select>
                                            </v-col>
                                            <v-col cols="12" sm="6">
                                                <v-select v-model="newUser.area_id" :items="allAreas"
                                                    item-title="nombre" item-value="id" label="Área" variant="outlined"
                                                    density="compact" :rules="[rules.required]" required></v-select>
                                            </v-col>
                                            <v-col cols="12" sm="6">
                                                <v-select v-model="newUser.cargo_id" :items="allCargos"
                                                    item-title="nombre" item-value="id" label="Cargo" variant="outlined"
                                                    density="compact" :rules="[rules.required]" required></v-select>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-text-field v-model="newUser.fecha_nacimiento"
                                                    label="Fecha de Nacimiento" type="date" variant="outlined"
                                                    density="compact"></v-text-field>
                                            </v-col>
                                            <v-col cols="12">
                                                <!-- Contraseña genérica -->
                                                <v-text-field v-model="newUser.password" label="Contraseña Inicial"
                                                    type="password" variant="outlined" density="compact" readonly
                                                    hint="Se recomienda cambiarla en el primer inicio de sesión."></v-text-field>
                                            </v-col>
                                        </v-row>
                                    </v-container>
                                </v-form>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="closeCreateDialog">Cancelar</v-btn>
                                <v-btn color="primary" @click="saveCreate" :disabled="!isCreateFormValid">Crear</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>


                    <v-data-table :headers="headers" :items="users" :search="search">
                        <template v-slot:item.roles="{ item }">
                            <v-chip v-for="role in item.roles" :key="role.id" color="primary" dark>
                                {{ role.name }}
                            </v-chip>
                        </template>

                        <template v-slot:item.actions="{ item }">
                            <v-icon small class="mr-2" @click="editItem(item)">
                                mdi-pencil
                            </v-icon>
                            <v-icon small @click="resetPassword(item)">
                                mdi-lock-reset
                            </v-icon>
                        </template>
                    </v-data-table>
                </v-card>
            </div>
        </div>

        <!-- Dialogo para editar usuario -->
        <v-dialog v-model="dialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="text-h6">Editar Usuario</span>
                </v-card-title>

                <v-card-text>
                    <v-text-field v-model="editedItem.name" label="Nombre" variant="solo" readonly></v-text-field>
                    <v-text-field v-model="editedItem.email" label="Email" variant="solo" readonly></v-text-field>
                    <v-text-field v-model="editedItem.telefono" label="Teléfono" variant="solo"></v-text-field>

                    <v-select v-model="editedItem.roles" :items="allRoles" item-title="name" item-value="id"
                        label="Roles" variant="solo" multiple chips closable-chips />

                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="closeDialog">Cancelar</v-btn>
                    <v-btn variant="elevated" color="primary" @click="saveEdit">Guardar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialogo para resetear contraseña -->
        <v-dialog v-model="resetDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="text-h6">Resetear Contraseña</span>
                </v-card-title>
                <v-card-text>
                    <p class="mb-4">¿Estás seguro de que deseas resetear la contraseña para el usuario?</p>
                    <v-text-field :model-value="itemToReset?.name" label="Nombre" variant="solo"
                        readonly></v-text-field>
                    <v-text-field :model-value="itemToReset?.email" label="Email" variant="solo"
                        readonly></v-text-field>
                    <p>Se generará una nueva contraseña aleatoria y se le enviará por correo electrónico.</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="closeResetDialog">Cancelar</v-btn>
                    <v-btn variant="elevated" color="primary" @click="confirmResetPassword" :loading="isResetting">Resetear</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue'

const props = defineProps({
    users: Array,
    roles: Array, // Roles disponibles para asignar
    areas: Array,
    cargos: Array,
})

// Estado
const search = ref('')
const dialog = ref(false)
const resetDialog = ref(false)
const itemToReset = ref(null)
const allRoles = ref(props.roles) // aquí cargo los roles de Spatie
const allAreas = ref(props.areas);
const allCargos = ref(props.cargos);
const isResetting = ref(false)
const createDialog = ref(false);
const createFormRef = ref(null); // Referencia para el v-form de creación
const isCreateFormValid = ref(false);

const sexoOptions = [
    { text: 'Masculino', value: 'M' },
    { text: 'Femenino', value: 'F' },
];


// Usamos useForm para manejar el estado del formulario y el envío
const editedItem = useForm({
    id: null,
    name: '',
    email: '',
    telefono: '',
    roles: [], // Usamos un array para los roles
})

const newUser = reactive({
    name: '',
    email: '',
    area_id: null,
    cargo_id: null,
    sexo: '',
    fecha_nacimiento: '',
    telefono: '',
    password: 'Password123!', // genérica
});

const rules = {
    required: value => !!value || 'Este campo es requerido.',
    email: value => /.+@.+\..+/.test(value) || 'Debe ser un correo electrónico válido.',
};


const headers = ref([
    { title: 'Nombre', key: 'name' },
    { title: 'Email', key: 'email' },
    { title: 'Roles', key: 'roles', value: 'roles' }, // Añadimos 'value' para que funcione el ordenamiento
    { title: 'Acciones', key: 'actions', sortable: false },
])

// Funciones
const editItem = (item) => {
    editedItem.id = item.id;
    editedItem.name = item.name;
    editedItem.email = item.email;
    editedItem.telefono = item.telefono;
    // Mapeamos los roles del usuario a un array de IDs para el v-select
    editedItem.roles = item.roles.map(role => role.id);
    dialog.value = true
}

const closeDialog = () => {
    dialog.value = false
    editedItem.reset(); // Limpiamos el formulario al cerrar
}

const saveEdit = () => {
    // Aquí envías los datos al backend con Inertia
    editedItem.put(route('users.update', editedItem.id), {
        onSuccess: () => closeDialog(), // Cierra el diálogo solo si la petición es exitosa
        // Opcional: manejo de errores
        onError: (errors) => console.error('Error al guardar:', errors),
    });
}

const closeResetDialog = () => {
    resetDialog.value = false
    itemToReset.value = null
    isResetting.value = false
}

const resetPassword = (item) => {
    itemToReset.value = item
    resetDialog.value = true
}

const confirmResetPassword = () => {
    if (!itemToReset.value) return;
    isResetting.value = true;
    router.post(route('users.reset-password', itemToReset.value.id), {}, {
        onSuccess: () => {
            closeResetDialog();
        },
        onFinish: () => { isResetting.value = false; }
    });
}

const openCreateDialog = () => { createDialog.value = true }
const closeCreateDialog = () => {
    createDialog.value = false
    Object.assign(newUser, {
        name: '',
        email: '',
        area_id: null,
        cargo_id: null,
        sexo: '',
        fecha_nacimiento: '',
        telefono: '',
        password: 'Password123!',
    })
}

const saveCreate = () => {
    // Si el formulario no es válido (controlado por el v-model), no hacemos nada.
    if (!isCreateFormValid.value) {
        return;
    }
    // Enviar datos al backend
    router.post(route('users.store'), newUser, {
        onSuccess: () => closeCreateDialog(),
        onError: (errors) => console.error(errors),
    })
}
</script>
