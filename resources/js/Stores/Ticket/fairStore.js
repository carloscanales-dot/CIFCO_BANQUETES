import { ref } from 'vue'
import { defineStore } from 'pinia'
import { useToast } from "vue-toastification"
import { router, useForm } from '@inertiajs/vue3'

const toast = useToast()
const api_url = '/ticket/fair'

export const useFairStore = defineStore('fairStore', () => {
  const items = ref([])
  const fairs = ref([])
  const errors = ref({})
  const totalItems = ref(0)
  const isLoading = ref(false)

  const form = useForm({
    fair_name: '',
    start_date: '',
    end_date: '',
    status: 1, // por defecto "Programada"
  })

  const index = (filters) => {
    isLoading.value = true
    router.get(`${api_url}`, filters, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        const { data, total } = page.props.result
        items.value = data
        totalItems.value = total
      },
      onError: (error) => (errors.value = error),
      onFinish: () => (isLoading.value = false),
    })
  }

  const store = () => {
    isLoading.value = true

    router.post(`${api_url}`, form.data(), {
      onSuccess: (page) => {
        const fair = page.props.flash?.fair || null

        if (fair?.fair_id) {
          router.visit(`/ticket/fair/${fair.fair_id}/edit`)
        } else {
          router.visit(api_url) // fallback si no devuelve el ID
        }

        toast.success(' Feria creada correctamente.')
      },
      onError: (error) => {
        errors.value = error
        toast.error(' Verifica los campos del formulario.')
      },
      onFinish: () => (isLoading.value = false),
    })
  }


  const ajaxList = async (status) => {
    try {
      const response = await window.axios.get(`${api_url}/list/${status}`)
      fairs.value = response.data.fairs
    } catch (ex) {
      toast.error(ex.message)
    }
  }

  const update = (id) => {
    isLoading.value = true
    form.patch(`${api_url}/${id}`, {
      preserveState: true,
      onSuccess: () => {
        form.reset()
        router.visit(api_url) // Redirigir al index después de actualizar
      },
      onError: (err) => (errors.value = err),
      onFinish: () => (isLoading.value = false),
    })
  }

  const clone = (payload) => {
    isLoading.value = true
    router.post(`${api_url}/clone`, payload, {
      onSuccess: () => {
        toast.success('Feria clonada correctamente.')
        // el backend redirige a la edición de la feria nueva
      },
      onError: (error) => {
        errors.value = error
        toast.error('No se pudo clonar la feria. Verifica los datos.')
      },
      onFinish: () => (isLoading.value = false),
    })
  }

  const changeStatus = (id, status) => {
    isLoading.value = true
    router.patch(`${api_url}/${id}/status`, { status }, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Estado de la feria actualizado.')
        router.visit(api_url) // recarga el listado con el estado nuevo
      },
      onError: (error) => {
        errors.value = error
        toast.error('No se pudo cambiar el estado de la feria.')
      },
      onFinish: () => (isLoading.value = false),
    })
  }

  const destroy = (id) => {
    isLoading.value = true
    router.delete(`${api_url}/${id}`, {
      onSuccess: () => router.visit(api_url), // Redirigir al index después de eliminar
      onError: (error) => (errors.value = error),
      onFinish: () => (isLoading.value = false),
    })
  }

  // fairStore.js
  const resetForm = () => {
    form.reset() // useForm de Inertia tiene reset()
    errors.value = []
  }


  return {
    items,
    fairs,
    form,
    errors,
    totalItems,
    isLoading,
    index,
    store,
    ajaxList,
    update,
    clone,
    changeStatus,
    destroy,
    resetForm,
  }
})