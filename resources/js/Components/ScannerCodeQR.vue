<script setup>
import { onMounted } from 'vue'
import { Html5QrcodeScanner, Html5QrcodeScanType } from 'html5-qrcode'

const props = defineProps({
  fps: { type: Number, default: 10 },
  qrbox: { type: Number, default: 150 },
  readerOn: { type: Boolean, required: true },
})

const emits = defineEmits(['result'])

const createScan = () => {
  const config = {
    fps: props.fps,
    qrbox: props.qrbox,
    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
  }

  const html5QrcodeScanner = new Html5QrcodeScanner('qrcode', config)
  html5QrcodeScanner.render(onScanSuccess)
}

const onScanSuccess = (decodedText, decodedResult) => {
  emits('result', decodedText, decodedResult)
}

onMounted(() => {
  createScan()
})
</script>
<template>
  <div id="qrcode" class="scanner-wrapper"></div>
</template>

<style scoped>
.scanner-wrapper {
  max-width: 350px;
  margin: 0 auto;
}
.scanner-wrapper video {
  max-width: 100% !important;
  height: auto !important;
}
</style>
