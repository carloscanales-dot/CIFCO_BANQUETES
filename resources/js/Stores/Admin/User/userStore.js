import { ref } from 'vue'
import { defineStore } from 'pinia'
import { useToast } from "vue-toastification"
import { router, useForm } from '@inertiajs/vue3'

const toast = useToast()
const api_url = '/admin/user'

export const useUserStore = defineStore('userStore', () => {
  const items = ref([])
  const users = ref([])
  const errors = ref([])
  const totalItems = ref(0)
  const isLoading = ref(false)

  const form = useForm({
    name: null,
    email: null,
  })

  const redirect = () => {
    window.location.href = `${api_url}`
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
      onError: (error) => {
        errors.value = error
      },
      onFinish: () => {
        isLoading.value = false
      },
    })
  }

  const ajaxList = async () => {
    try {
      const response = await window.axios.get(`${api_url}/list`)
      users.value = response.data.users
    } catch (ex) {
      toast.error(ex.message)
    }
  }

  const store = () => {
    isLoading.value = true

    router.post(`${api_url}`, form.data(), {
      preserveState: true,
      onSuccess: () => {
        redirect()
      },
      onError: (error) => {
        errors.value = error
      },
      onFinish: () => {
        isLoading.value = false
      },
    })
  }

  const ajaxStore = async () => {
    isLoading.value = true

    try {
      const response = await window.axios.post(`${api_url}`, form.data())
      const { message, fund } = response.data
      users.value.push(fund)
      toast.success(message)
    } catch (ex) {
      if (ex.response) {
        errors.value = ex.response.data.errors
        throw ex
      }
    } finally {
      isLoading.value = false
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
      onError: (err) => {
        errors.value = err
      },
      onFinish: () => {
        isLoading.value = false
      }
    })
  }

  const destroy = (id) => {
    isLoading.value = true

    router.delete(`${api_url}/${id}`, {
      onSuccess: () => {
        redirect()
      },
      onError: (error) => {
        errors.value = error
      },
      onFinish: () => {
        isLoading.value = false
      }
    })
  }

  return {
    items,
    users,
    form,
    errors,
    totalItems,
    isLoading,
    index,
    store,
    ajaxList,
    ajaxStore,
    update,
    destroy,
  }
})
