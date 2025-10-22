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

  const redirect = () => {
    router.visit(api_url)
  }

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
      preserveState: true,
      onSuccess: () => {
        form.reset()
        redirect()
      },
      onError: (error) => {
        errors.value = error
        toast.error('❌ Verifica los campos del formulario')
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
        redirect()
      },
      onError: (err) => (errors.value = err),
      onFinish: () => (isLoading.value = false),
    })
  }

  const destroy = (id) => {
    isLoading.value = true
    router.delete(`${api_url}/${id}`, {
      onSuccess: () => redirect(),
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
    destroy,
    resetForm,
  }
})
