import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
  }),
  getters: {
    cartItems: (state) => state.items,
    cartTotal: (state) => {
      return state.items.reduce((total, item) => total + item.unit_price * item.quantity, 0)
    },
    itemCount: (state) => state.items.length,
  },
  actions: {
    addItem(product) {
      const existingItem = this.items.find((item) => item.product_id === product.product_id)
      if (existingItem) {
        existingItem.quantity++
      } else {
        this.items.push({ ...product, quantity: 1 })
      }
    },
    removeItem(product_id) {
      const index = this.items.findIndex((item) => item.product_id === product_id)
      if (index !== -1) {
        this.items.splice(index, 1)
      }
    },
    incrementItem(product_id) {
        const existingItem = this.items.find((item) => item.product_id === product_id)
        if (existingItem) {
          existingItem.quantity++
        }
    },
    decrementItem(product_id) {
        const existingItem = this.items.find((item) => item.product_id === product_id)
        if (existingItem && existingItem.quantity > 1) {
            existingItem.quantity--
        } else if (existingItem && existingItem.quantity === 1) {
            this.removeItem(product_id)
        }
    },
    clearCart() {
      this.items = []
    },
  },
})
