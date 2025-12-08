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

      // Asignar explícitamente campos al form para evitar problemas reactivos
      form.ticket_id = ticket.ticket_id ?? null
      form.uuid = ticket.uuid ?? null
      form.product_name = ticket.product_name ?? null
      form.status = null

      // Normalizar y establecer form.status según ticket.status y si está asignado
      // status mapping: 1 = disponible, 0 = ya canjeado, 3 = no asignado a la estación
      if (ticket.status === 0) {
        form.status = 0
      } else if (ticket.status === 1 && ticket.assigned === false) {
        form.status = 3
      } else {
        form.status = 1
      }

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
      // Enviar solo los campos necesarios (ReaderRequest valida ticket_id)
      const payload = {
        ticket_id: form.ticket_id,
      }

      const response = await window.axios.post(`${api_url}/store`, payload)

      // ⛳ Ahora sí el modal exito aparece
      form.status = 2
      alert.value = `El producto ${form.product_name}, ha sido CANJEADO exitosamente.`

    } catch (error) {
      if (error.response) {
        if (error.response.status === 403) {
          toast.error(error.response.data.message)
        } else if (error.response.status === 422) {
          // Mostrar errores de validación (si existen) o el mensaje
          const data = error.response.data
          if (data && data.errors) {
            const first = Object.values(data.errors)[0]
            toast.error(Array.isArray(first) ? first[0] : first)
          } else if (data && data.message) {
            toast.error(data.message)
          } else {
            toast.error('Error de validación en los datos enviados.')
          }
        } else {
          toast.error("Error inesperado al canjear el ticket.")
        }
      } else {
        toast.error('Error de red o servidor.')
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
