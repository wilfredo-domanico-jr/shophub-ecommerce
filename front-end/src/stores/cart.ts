import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useCartStore = defineStore('cart', () => {
  const items = ref<any[]>([]); // Cart items

  // "Buy now" checks out a single item without touching the cart.
  const buyNowItem = ref<any | null>(null);

  function setBuyNow(product: any, quantity = 1) {
    buyNowItem.value = { ...product, quantity };
  }

  function clearBuyNow() {
    buyNowItem.value = null;
  }

  function checkoutItems() {
    return buyNowItem.value ? [buyNowItem.value] : items.value;
  }

  function checkoutTotal() {
    return checkoutItems().reduce((sum, item) => sum + item.price * (item.quantity || 1), 0);
  }

  function addItem(product: any, quantity = 1) {
    const existing = items.value.find(i => i.id === product.id);
    if (existing) {
      existing.quantity = (existing.quantity || 1) + quantity;
    } else {
      items.value.push({ ...product, quantity });
    }
  }

  function removeItem(id: number) {
    items.value = items.value.filter(i => i.id !== id);
  }

  function updateQuantity(id: number, quantity: number) {
    const item = items.value.find(i => i.id === id);
    if (item) item.quantity = Math.max(1, quantity);
  }

  function count() {
    return items.value.length;
  }

  function total() {
    return items.value.reduce((sum, item) => sum + item.price * (item.quantity || 1), 0);
  }

  return {
    items,
    buyNowItem,
    setBuyNow,
    clearBuyNow,
    checkoutItems,
    checkoutTotal,
    addItem,
    removeItem,
    updateQuantity,
    count,
    total,
  };
});
