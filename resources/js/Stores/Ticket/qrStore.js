import { ref } from "vue"
import { defineStore } from "pinia"
import { useToast } from "vue-toastification"
import { useForm } from "@inertiajs/vue3"

const toast = useToast()
const api_url = '/ticket/reader'

export const useQrStore = defineStore('qrStore', () => {
  const alert = ref(null)
  const isLoading = ref(false)

  const form = useForm({
    ticket_id: null,
    status: null,
    uuid: null,
    product_name: null,
  })

  const validate = async (uuid) => {
    isLoading.value = true

    try {
      const response = await window.axios.get(`${api_url}/ticket/${uuid}`)
      const { success, message, ticket } = response.data

      // 🔥 IMPORTANTE: asignar a form.data()
      Object.assign(form.data(), ticket)

      alert.value = message
    } catch (error) {
      toast.error(error.message)
    } finally {
      isLoading.value = false
    }
  }

  const store = async () => {
    isLoading.value = true

    try {
      const response = await window.axios.post(`${api_url}/store`, form.data())

      // ⛳ Ahora sí el modal exito aparece
      form.status = 2
      alert.value = `El producto ${form.product_name}, ha sido CANJEADO exitosamente.`

    } catch (error) {
      if (error.response && error.response.status === 403) {
        toast.error(error.response.data.message)
      } else {
        toast.error("Error inesperado al canjear el ticket.")
      }
    } finally {
      isLoading.value = false
    }
  }

  const reset = () => {
    form.ticket_id = null
    form.status = null
    form.uuid = null
    form.product_name = null
    alert.value = null
  }

  return {
    form,
    alert,
    isLoading,
    validate,
    store,
    reset,
  }
})
