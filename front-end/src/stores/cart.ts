import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface CartProduct {
  id: number;
  name: string;
  price: number;
  image?: string | null;
  variant_id?: number | null;
  variant_label?: string | null;
  [extra: string]: unknown;
}

export interface CartLine extends CartProduct {
  key: string;
  quantity: number;
}

// Two variants of one product are separate cart lines, so lines are keyed
// by product id + variant id ("12:34"; flat products end up as "12:").
export function lineKey(product: Pick<CartProduct, 'id' | 'variant_id'>): string {
  return `${product.id}:${product.variant_id ?? ''}`;
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartLine[]>([]);

  // "Buy now" checks out a single item without touching the cart.
  const buyNowItem = ref<CartLine | null>(null);

  function setBuyNow(product: CartProduct, quantity = 1) {
    buyNowItem.value = { ...product, key: lineKey(product), quantity };
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

  function addItem(product: CartProduct, quantity = 1) {
    const key = lineKey(product);
    const existing = items.value.find(i => i.key === key);
    if (existing) {
      existing.quantity = (existing.quantity || 1) + quantity;
    } else {
      items.value.push({ ...product, key, quantity });
    }
  }

  function clear() {
    items.value = [];
    buyNowItem.value = null;
  }

  function clearItems() {
    items.value = [];
  }

  function removeItem(key: string) {
    items.value = items.value.filter(i => i.key !== key);
  }

  function updateQuantity(key: string, quantity: number) {
    const item = items.value.find(i => i.key === key);
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
    clear,
    clearItems,
    removeItem,
    updateQuantity,
    count,
    total,
  };
});
