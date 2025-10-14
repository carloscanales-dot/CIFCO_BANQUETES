<script setup>
import { storeToRefs } from 'pinia'
import { Head } from '@inertiajs/vue3'
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
</script>
<template>
  <Head title="Lector-QR" />
  <AuthenticatedLayout>
    <VCard title="Escaneo de codigos">
      <VCardText>
        <ScannerCodeQR :fps="10" :qrbox="150" :reader-on="true" @result="onScanResult"></ScannerCodeQR>
      </VCardText>
      <VCardText>
        <VForm @submit.prevent="submit">
          <VRow dense>
            <VCol cols="12" md="12" sm="12">
              <v-banner v-if="form.status === 'D'" color="info" icon="$info" :text="alert" stacked>
                <template v-slot:actions>
                  <VBtn
                    prepend-icon="mdi-database"
                    type="submit"
                    text="Canjear"
                    variant="tonal"
                    color="primary"
                    :disabled="form.processing"
                  ></VBtn>
                </template>
              </v-banner>
              <v-banner v-else-if="form.status === 'C'" color="warning" icon="$warning" :text="alert" stacked>
              </v-banner>
              <v-banner v-else-if="form.status === 'A'" color="error" icon="$error" :text="alert" stacked> </v-banner>
              <v-banner v-else-if="form.status === 'S'" color="success" icon="$success" :text="alert" stacked>
              </v-banner>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </AuthenticatedLayout>
</template>
