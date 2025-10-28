<template>
    <Head title="Créditos" />
    <CajaLayout>
        <div class="mb-5">
            <h5 class="text-h5 font-weight-bold">Créditos Empleados</h5>
        </div>

        <v-card class="pa-5" elevation="4">
            <v-form ref="form" v-model="valid" lazy-validation>
                <v-row dense>
                    <!-- Nombre del empleado -->
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="formData.empleado"
                            label="Nombre del Empleado"
                            outlined
                            clearable
                            :rules="[rules.required]"
                        ></v-text-field>
                    </v-col>

                    <!-- Producto -->
                    <v-col cols="12" md="6">
                        <v-select
                            v-model="formData.producto"
                            :items="productos"
                            label="Producto"
                            outlined
                            clearable
                            :rules="[rules.required]"
                        ></v-select>
                    </v-col>

                    <!-- Cantidad -->
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model.number="formData.cantidad"
                            type="number"
                            label="Cantidad"
                            outlined
                            clearable
                            min="1"
                            :rules="[rules.required, rules.min]"
                        ></v-text-field>
                    </v-col>
                </v-row>

                <v-divider class="my-4"></v-divider>

                <!-- Botones -->
                <v-row justify="end" class="mt-3">
                    <v-btn color="grey" variant="outlined" class="mr-3" @click="limpiarCampos">
                        Cancelar
                    </v-btn>

                    <v-btn color="primary" :disabled="!valid" @click="guardarCredito">
                        Guardar
                    </v-btn>
                </v-row>
            </v-form>
        </v-card>
    </CajaLayout>
</template>

<script setup>
import CajaLayout from '@/Layouts/CajaLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'

const valid = ref(true)
const form = ref(null)

const formData = reactive({
    empleado: '',
    producto: '',
    cantidad: null,
})

// Lista de productos (puedes reemplazar esto por una llamada a la API)
const productos = ['Café', 'Refresco', 'Snack', 'Sandwich', 'Agua']

// Reglas de validación
const rules = {
    required: (v) => !!v || 'Campo requerido',
    min: (v) => v > 0 || 'Debe ser mayor a 0',
}

// Acción Guardar
const guardarCredito = () => {
    if (form.value.validate()) {
        console.log('Datos guardados:', { ...formData })
        // Aquí puedes hacer tu llamada a la API con Inertia.post(...)
        limpiarCampos()
    }
}

// Acción Cancelar (limpiar campos)
const limpiarCampos = () => {
    formData.empleado = ''
    formData.producto = ''
    formData.cantidad = null
    form.value.resetValidation()
}
</script>

<style scoped>
.v-card {
    max-width: 800px;
    margin: auto;
}
</style>
