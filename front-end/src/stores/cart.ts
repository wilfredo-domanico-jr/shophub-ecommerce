import { defineStore } from 'pinia';
import { ref } from 'vue';
import {
  addCartItem,
  clearCart as clearCartRemote,
  getCart,
  removeCartItem,
  updateCartItem,
  type ServerCartItem,
} from '../services/cart';

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
  /** cart_items row id on the server. */
  serverId: number;
  /** false when the product is deleted, deactivated, or out of stock. */
  available: boolean;
  stock: number;
}

// Two variants of one product are separate cart lines, so lines are keyed
// by product id + variant id ("12:34"; flat products end up as "12:").
export function lineKey(product: Pick<CartProduct, 'id' | 'variant_id'>): string {
  return `${product.id}:${product.variant_id ?? ''}`;
}

function toLine(item: ServerCartItem): CartLine {
  return {
    // A deleted product leaves a dead line: id 0, synthetic key, price 0.
    // It can never reach checkout because it's excluded as unavailable.
    id: item.product_id ?? 0,
    key: item.product_id !== null
      ? lineKey({ id: item.product_id, variant_id: item.variant_id })
      : `gone:${item.id}`,
    serverId: item.id,
    name: item.name,
    price: item.price ?? 0,
    image: item.image,
    slug: item.slug,
    variant_id: item.variant_id,
    variant_label: item.variant_label,
    quantity: item.quantity,
    stock: item.stock,
    available: item.is_available,
  };
}

// The cart lives server-side (adding requires an account), so every mutation
// goes through the API and the response is the new local state. Only buy-now
// stays client-side — it's a transient express checkout, not cart contents.
export const useCartStore = defineStore('cart', () => {
  const items = ref<CartLine[]>([]);

  // "Buy now" checks out a single item without touching the cart.
  const buyNowItem = ref<CartLine | null>(null);

  // Line keys with an in-flight mutation — lets the UI disable a line's
  // controls while its request is outstanding, so fast clicking can't fire
  // overlapping PATCH/DELETE calls whose responses could land out of order.
  const pendingKeys = ref<Set<string>>(new Set());

  function isPending(key: string) {
    return pendingKeys.value.has(key);
  }

  function setServerItems(serverItems: ServerCartItem[]) {
    items.value = (serverItems ?? []).map(toLine);
  }

  /** Pulls the account's cart; called after login and on session restore. */
  async function load() {
    try {
      setServerItems(await getCart());
    } catch {
      // Signed out or transient failure — keep whatever is shown.
    }
  }

  function setBuyNow(product: CartProduct, quantity = 1) {
    buyNowItem.value = {
      ...product,
      key: lineKey(product),
      quantity,
      serverId: 0,
      available: true,
      stock: quantity,
    };
  }

  function clearBuyNow() {
    buyNowItem.value = null;
  }

  function availableItems() {
    return items.value.filter((item) => item.available);
  }

  function checkoutItems() {
    return buyNowItem.value ? [buyNowItem.value] : availableItems();
  }

  function checkoutTotal() {
    return checkoutItems().reduce((sum, item) => sum + item.price * (item.quantity || 1), 0);
  }

  async function addItem(product: CartProduct, quantity = 1) {
    setServerItems(
      await addCartItem({
        product_id: product.id,
        variant_id: product.variant_id ?? undefined,
        quantity,
      })
    );
  }

  // Local-only reset for logout — the server cart belongs to the account
  // and must survive for the next sign-in.
  function clear() {
    items.value = [];
    buyNowItem.value = null;
  }

  async function clearItems() {
    items.value = [];
    try {
      await clearCartRemote();
    } catch {
      // Best effort — a leftover server cart resyncs on the next load().
    }
  }

  async function removeItem(key: string) {
    if (pendingKeys.value.has(key)) return;
    const line = items.value.find(i => i.key === key);
    if (!line) return;

    pendingKeys.value.add(key);
    items.value = items.value.filter(i => i.key !== key);
    try {
      setServerItems(await removeCartItem(line.serverId));
    } catch {
      await load();
    } finally {
      pendingKeys.value.delete(key);
    }
  }

  async function updateQuantity(key: string, quantity: number) {
    if (pendingKeys.value.has(key)) return;
    const line = items.value.find(i => i.key === key);
    if (!line) return;

    const clamped = Math.max(1, quantity);
    pendingKeys.value.add(key);
    line.quantity = clamped;
    try {
      setServerItems(await updateCartItem(line.serverId, clamped));
    } catch {
      await load();
    } finally {
      pendingKeys.value.delete(key);
    }
  }

  function count() {
    return items.value.length;
  }

  function total() {
    return availableItems().reduce((sum, item) => sum + item.price * (item.quantity || 1), 0);
  }

  return {
    items,
    buyNowItem,
    load,
    isPending,
    setBuyNow,
    clearBuyNow,
    availableItems,
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
