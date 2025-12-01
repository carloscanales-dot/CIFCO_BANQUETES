<template>
  <v-dialog v-model="localShow" max-width="520">
    <v-card>
      <v-card-title class="text-h6 font-weight-bold">
        {{ titleText }}
      </v-card-title>

      <v-divider class="my-3" />

      <v-card-text class="text-body-2">
        <div class="mb-3">
            <p class="mb-1"><strong>Terminal:</strong> {{ terminal?.terminal_name || 'N/A' }}</p>
            <p class="mb-1"><strong>Cajero:</strong> {{ terminal?.user?.name || 'Sin asignar' }}</p>
        </div>

        <div v-if="showTotals">
          <v-divider class="my-3" />
          <p class="font-weight-bold mb-2">Totales por Método de Pago</p>
          <div class="mb-2">
            <p class="mb-1">Efectivo: <strong>${{ opening?.total_cash ?? 0 }}</strong></p>
            <p class="mb-1">Tarjeta: <strong>${{ opening?.total_card ?? 0 }}</strong></p>
            <p class="mb-1">Chivo Wallet: <strong>${{ opening?.total_chivo ?? 0 }}</strong></p>
          </div>
          <v-divider class="my-3" />
          <p><strong>Total transaccionado:</strong> ${{ opening?.total_transacted ?? 0 }}</p>
          <v-divider class="my-3" />
        </div>

        <!-- Si es mode open: mostrar apertura -->
        <v-text-field
          v-if="mode === 'open'"
          v-model.number="openingAmount"
          label="Monto de apertura"
          type="number"
          prefix="$"
          variant="solo"
          density="compact"
        />

        <!-- Si es mode close: mostrar inputs de cierre -->
        <div v-else-if="mode === 'close'">
          <v-text-field
            v-model.number="closingReal"
            label="Monto recibido"
            type="number"
            prefix="$"
            variant="solo"
            density="compact"
            class="mb-2"
          />
          <v-textarea
            v-model="closingNotes"
            label="Observaciones"
            variant="solo"
            density="compact"
            rows="2"
          />
        </div>

        <!-- Si es preclose: NO mostrar inputs, solo explicar al cajero -->
        <div v-else-if="mode === 'preclose'">
          <p class="mb-1">Al confirmar, la terminal pasará a <strong>PRE-CIERRE</strong>. El cierre definitivo lo realizará el administrador desde la vista de Apertura y Cierres.</p>
        </div>

      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text @click="closeDialog">Cancelar</v-btn>
        <v-btn :loading="processing" color="black" variant="elevated" @click="onConfirm">
          {{ primaryButtonText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mode: { type: String, default: 'close' }, // 'open'|'close'|'preclose'
  terminal: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'done'])

const localShow = ref(props.modelValue)
watch(() => props.modelValue, v => localShow.value = v)
watch(localShow, v => emit('update:modelValue', v))

const openingAmount = ref(0)
const closingReal = ref(0)
const closingNotes = ref('')
const processing = ref(false)
const opening = ref(null)

onMounted(() => loadOpening())
watch(() => props.terminal, () => loadOpening())

function closeDialog() {
  emit('update:modelValue', false)
}

const mode = computed(() => props.mode || 'close')
const titleText = computed(() => mode.value === 'open' ? 'Aperturar Caja' : (mode.value === 'preclose' ? 'Pre-Cierre' : 'Cerrar Caja'))
const primaryButtonText = computed(() => mode.value === 'open' ? 'Confirmar Apertura' : (mode.value === 'preclose' ? 'Confirmar Pre-Cierre' : 'Confirmar Cierre'))
const showTotals = computed(() => (props.terminal?.status_id === 5 || props.terminal?.status_id === 7))

async function loadOpening() {
  opening.value = null
  if (!props.terminal) return
  opening.value = props.terminal.openings?.[0] ?? null
}

async function onConfirm() {
  if (processing.value) return
  processing.value = true

  try {
    if (mode.value === 'open') {
      await axios.post('/terminal-sessions/open', {
        payment_terminal_id: props.terminal?.id,
        user_id: props.terminal?.user?.id ?? null,
        opening_amount: openingAmount.value
      })
      toast.success('Terminal aperturada.')
      emit('done', { action: 'open' })
      closeDialog()
      return
    }

    const openingId = opening.value?.id
    if (!openingId) {
      toast.error('No se encontró la apertura asociada.')
      processing.value = false
      return
    }

    if (mode.value === 'close') {
      const response = await axios.post(`/terminal-sessions/${openingId}/close`, {
        expected_amount: opening.value.opening_amount,
        real_amount: closingReal.value,
        closing_balance: closingReal.value - opening.value.opening_amount,
        user_id: props.terminal?.user?.id ?? null,
        notes: closingNotes.value,
      })
      if (response.data.success) {
        toast.success('Terminal cerrada.')
        emit('done', { action: 'close', payload: response.data })
        closeDialog()
      } else {
        toast.error(response.data.message || 'Error al cerrar.')
      }
      processing.value = false
      return
    }

    if (mode.value === 'preclose') {
      // LLAMADA DE PRE-CIERRE: solo actualiza estado a 7 y devuelve totales/details
      const response = await axios.post(`/terminal-sessions/${openingId}/preclose`, {
        user_id: props.terminal?.user?.id ?? null,
      })

      if (response.data.success) {
        toast.success('Pre-cierre realizado.')
        emit('done', { action: 'preclose', payload: response.data })
        closeDialog()
      } else {
        toast.error(response.data.message || 'Error al realizar pre-cierre.')
      }
      processing.value = false
      return
    }

  } catch (err) {
    console.error('TerminalClosingModal error', err)
    toast.error('Ocurrió un error en la operación.')
    processing.value = false
  } finally {
    processing.value = false
  }
}
</script>
