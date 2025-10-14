import { ref } from 'vue'
import { defineStore } from 'pinia'
import { router } from '@inertiajs/vue3'

const api_url = '/ticket/stationTicket'

export const useStationTicketStore = defineStore('stationTicketStore', () => {
  const items = ref([])
  const errors = ref([])
  const totalItems = ref(0)
  const isLoading = ref(false)

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

  return {
    items,
    totalItems,
    isLoading,
    index,
  }
})
