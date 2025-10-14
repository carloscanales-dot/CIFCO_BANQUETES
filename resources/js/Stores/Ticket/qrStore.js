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
    uuid: null
  })

  const validate = async (uuid) => {
    isLoading.value = true

    try {
      const response = await window.axios.get(`${api_url}/ticket/${uuid}`)
      const { success, message, ticket } = response.data
      Object.assign(form, ticket)
      alert.value = message
    } catch (error) {
      toast.error(error.message)
    } finally {
      isLoading.value = false
    }
  }

  const store = () => {
    isLoading.value = true

    form.post(`${api_url}/store`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        form.status = 'S'
        alert.value = `El producto ${form.product_name}, ha sido CANJEADO exitosamente.`
      },
      onError: (error) => {
        toast.error(error.message)
      },
      onFinish: () => {
        isLoading.value = false
      }
    })
  }

  return {
    form,
    alert,
    isLoading,
    validate,
    store,
  }

})
