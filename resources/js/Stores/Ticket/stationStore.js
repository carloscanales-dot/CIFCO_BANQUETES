import { ref } from 'vue'
import { defineStore } from 'pinia'
import { useToast } from "vue-toastification"
import { router, useForm } from '@inertiajs/vue3'

const toast = useToast()
const api_url = '/ticket/station'

export const useStationStore = defineStore('stationStore', () => {
  const items = ref([])
  const stations = ref([])
  const errors = ref([])
  const totalItems = ref(0)
  const isLoading = ref(false)

  const form = useForm({
    station_name: null,
    status: 1, // Default status
    location_id: null,
    product_ids: [],
    user_ids: null,
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

  const load = async (fairId) => {
    if (!fairId) return;
    try {
      const response = await window.axios.get(`${api_url}/list-by-fair/${fairId}`);
      stations.value = response.data.stations;
    } catch (ex) {
      toast.error(ex.message);
    }
  };

  const ajaxList = async (status) => {
    try {
      const response = await window.axios.get(`${api_url}/list/${status}`)
      stations.value = response.data.stations
    } catch (ex) {
      toast.error(ex.message)
    }
  }

  const store = (fairId) => {
    if (!fairId) {
      toast.error('ID de la feria es requerido.');
      return;
    }
    isLoading.value = true;

    form.transform(data => ({
      ...data,
      fair_id: fairId
    })).post(`${api_url}`, {
      preserveState: true,
      onSuccess: () => {
        toast.success('Estación creada exitosamente.');
        router.reload({ only: ['stations'] });
        form.reset('station_name', 'location_id');
      },
      onError: (error) => {
        errors.value = error;
      },
      onFinish: () => {
        isLoading.value = false;
      },
    });
  }

  const ajaxStore = async () => {
    isLoading.value = true

    try {
      const response = await window.axios.post(`${api_url}`, form.data())
      const { message, fund } = response.data
      stations.value.push(fund)
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

  const destroy = async (id) => {
    isLoading.value = true
    try {
      const response = await window.axios.delete(`${api_url}/${id}`)
      toast.success(response.data.message)

      // ✅ Eliminar visualmente de la lista reactiva sin recargar la página
      stations.value = stations.value.filter(station => station.id !== id)

    } catch (error) {
      console.error(error)
      toast.error('Error al eliminar la estación.')
    } finally {
      isLoading.value = false
    }
  }

  return {
    items,
    stations,
    form,
    errors,
    totalItems,
    isLoading,
    index,
    load,
    store,
    ajaxList,
    ajaxStore,
    update,
    destroy,
  }
})
