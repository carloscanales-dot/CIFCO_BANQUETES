<script setup>
import { storeToRefs } from 'pinia'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ScannerCodeQR from '@/Components/ScannerCodeQR.vue'
import { useQrStore } from '@/Stores/Ticket/qrStore'

const qrStore = useQrStore()
const { form, alert, isLoading } = storeToRefs(qrStore)

const onScanResult = async (decodeText) => {
  await qrStore.validate(decodeText)
}

const submit = () => {
  qrStore.store()
}

// ===============================
//  COMPUTED para manejar los 3 modales
// ===============================

const modalDisponible = computed({
  get: () => form.value.status === 1,
  set: (val) => {
    if (!val) qrStore.reset()
  }
})

const modalNoDisponible = computed({
  get: () => form.value.status === 0,
  set: (val) => {
    if (!val) qrStore.reset()
  }
})

const modalExito = computed({
  get: () => form.value.status === 2,
  set: (val) => {
    if (!val) qrStore.reset()
  }
})

// Modal para producto no asignado a la estación
const modalNoAsignado = computed({
  get: () => form.value.status === 3,
  set: (val) => {
    if (!val) qrStore.reset()
  }
})
</script>

<template>
  <Head title="Lector-QR" />

  <AuthenticatedLayout>
    <VCard title="Escaneo de códigos">
      <VCardText>
        <ScannerCodeQR
          :fps="10"
          :qrbox="150"
          :reader-on="form.status === null"
          @result="onScanResult"
        />
      </VCardText>
    </VCard>

    <!-- ==========================
         MODAL — TICKET DISPONIBLE
    ============================ -->
    <VDialog v-model="modalDisponible" persistent max-width="450">
      <VCard>
        <VCardTitle class="text-info text-h6">
          Ticket disponible
        </VCardTitle>

        <VCardText>
          {{ alert }}
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="primary"
            variant="tonal"
            prepend-icon="mdi-database"
            :loading="isLoading"
            @click="submit"
          >
            Canjear
          </VBtn>
          <VBtn
            color="secondary"
            variant="tonal"
            @click="modalDisponible = false"
          >
            Cerrar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ==========================
         MODAL — YA CANJEADO (NO DISPONIBLE)
    ============================ -->
    <VDialog v-model="modalNoDisponible" persistent max-width="450">
      <VCard>
        <VCardTitle class="text-error text-h6">
          Ticket no disponible
        </VCardTitle>

        <VCardText>
          {{ alert }}
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="modalNoDisponible = false"
          >
            Cerrar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ==========================
         MODAL — CANJE EXITOSO
    ============================ -->
    <VDialog v-model="modalExito" persistent max-width="450">
      <VCard>
        <VCardTitle class="text-success text-h6">
          ¡Canje exitoso!
        </VCardTitle>

        <VCardText>
          {{ alert }}
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="success"
            variant="tonal"
            @click="modalExito = false"
          >
            Aceptar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ==========================
         MODAL — PRODUCTO NO ASIGNADO A ESTA ESTACIÓN
    ============================ -->
    <VDialog v-model="modalNoAsignado" persistent max-width="520">
      <VCard>
        <VCardTitle class="text-error text-h6">
          Producto no asignado
        </VCardTitle>

        <VCardText>
          {{ alert }}
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="tonal" @click="modalNoAsignado = false">
            Cerrar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </AuthenticatedLayout>
</template>
