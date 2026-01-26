<template>
  <v-dialog v-model="localDialog" max-width="520">
    <v-card class="rounded-lg" style="background-color: #ffffff; color: black;">
      <v-card-title class="text-h6 font-weight-bold text-center pb-2">
        {{ isEdit ? 'Editar Empleado' : 'Agregar Empleado' }}
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pt-6 pb-0">
        <v-form @submit.prevent="submitForm">
          <v-text-field
            v-model="form.employee_name"
            label="Nombre del empleado"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="errors.employee_name"
            class="mb-3"
            required
          />

          <v-text-field
            v-model="form.employee_area"
            label="Área"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="errors.employee_area"
            class="mb-3"
            required
          />

          <v-select
            v-model="form.status"
            :items="statusOptions"
            item-title="title"
            item-value="value"
            label="Estado"
            variant="outlined"
            color="black"
            density="comfortable"
            :error-messages="errors.status"
            class="mb-4"
          />
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="justify-end py-3 px-4">
        <v-btn text color="red" variant="elevated" @click="closeDialog">
          Cancelar
        </v-btn>
        <v-btn color="black" variant="elevated" @click="submitForm" :loading="loading">
          {{ isEdit ? 'Actualizar' : 'Crear' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  modelValue: Boolean,
  employee: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const localDialog = ref(false)
const loading = ref(false)

const isEdit = computed(() => {
  return props.employee !== null && props.employee !== undefined && props.employee?.employee_id
})

const form = ref({
  employee_name: '',
  employee_area: '',
  status: 1,
})

const errors = ref({
  employee_name: '',
  employee_area: '',
  status: '',
})

const statusOptions = [
  { title: 'Activo', value: 1 },
  { title: 'Inactivo', value: 0 },
]

// Definir resetForm antes de los watchers
const resetForm = () => {
  form.value = {
    employee_name: '',
    employee_area: '',
    status: 1,
  }
  errors.value = {
    employee_name: '',
    employee_area: '',
    status: '',
  }
}

// Watch para cambios en el empleado seleccionado
watch(() => props.employee, (newEmployee) => {
  if (newEmployee) {
    // Cargar datos del empleado para editar
    form.value = {
      employee_name: newEmployee.employee_name,
      employee_area: newEmployee.employee_area,
      status: newEmployee.status ? 1 : 0,
    }
  } else {
    // Reset form para nuevo empleado
    resetForm()
  }
})

watch(() => props.modelValue, (val) => {
  localDialog.value = val
  if (val) {
    if (props.employee) {
      // Cargar datos del empleado para editar
      form.value = {
        employee_name: props.employee.employee_name,
        employee_area: props.employee.employee_area,
        status: props.employee.status ? 1 : 0,
      }
    } else {
      // Reset form for new employee
      resetForm()
    }
  }
})

watch(localDialog, (val) => {
  emit('update:modelValue', val)
  if (!val) {
    resetForm()
  }
})

const closeDialog = () => {
  localDialog.value = false
}

const submitForm = () => {
  loading.value = true
  errors.value = {
    employee_name: '',
    employee_area: '',
    status: '',
  }

  if (isEdit.value) {
    // Actualizar empleado existente
    router.put(`/admin/employees/${props.employee.employee_id}`, form.value, {
      onSuccess: () => {
        closeDialog()
        loading.value = false
      },
      onError: (err) => {
        errors.value = err
        loading.value = false
      },
    })
  } else {
    // Crear nuevo empleado
    router.post('/admin/employees', form.value, {
      onSuccess: () => {
        closeDialog()
        loading.value = false
      },
      onError: (err) => {
        errors.value = err
        loading.value = false
      },
    })
  }
}
</script>

<style scoped>
.v-card-text strong {
  font-weight: 700;
  color: black;
}
</style>
