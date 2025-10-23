import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

export const useLocationStore = defineStore('locationStore', () => {
  const locations = ref([]) // lista de locations cargadas
  const locationForm = ref({
    location_name: '',
  })
  const locationErrors = ref({})

  // Cargar locations de una feria
  const load = async (fairId) => {
    try {
      const { data } = await axios.get(`/ticket/location/list/${fairId}`)
      locations.value = data.locations
    } catch (error) {
      console.error(error)
    }
  }

  const loadAll = async () => {
    try {
        const { data } = await axios.get('/ticket/location/list-all');
        locations.value = data.locations;
    } catch (error) {
        console.error(error);
    }
  };

  // Guardar location
  const store = async (fairId) => {
    locationErrors.value = {}
    try {
      const { data } = await axios.post(`/ticket/location/store`, {
        ...locationForm.value,
        fair_id: fairId,
      })
      locations.value.push(data.location)
      locationForm.value.location_name = '' // limpiar input
    } catch (error) {
      if (error.response?.data?.errors) {
        locationErrors.value = error.response.data.errors
      }
    }
  }

  // Eliminar location
  const destroy = async (id) => {
    try {
      await axios.delete(`/ticket/location/${id}`)
      locations.value = locations.value.filter(l => l.id !== id)
    } catch (error) {
      console.error(error)
    }
  }

  // Reset formulario
  const resetForm = () => {
    locationForm.value = { location_name: '' }
    locationErrors.value = {}
  }

  return {
    locations,
    locationForm,
    locationErrors,
    load,
    loadAll,
    store,
    destroy,
    resetForm
  }
})
