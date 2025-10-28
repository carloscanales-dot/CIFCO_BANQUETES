<template>
    <Head title="Créditos" />
    <AdminLayout>
        <div class="mb-5">
            <h5 class="text-h5 font-weight-bold">Administrar</h5>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
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
