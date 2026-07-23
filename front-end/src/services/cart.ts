import api from "./api";

/** A cart line as the API returns it — live product data where the product
 *  still exists, snapshot fallbacks where it doesn't. */
export interface ServerCartItem {
  id: number;
  product_id: number | null;
  variant_id: number | null;
  name: string;
  variant_label: string | null;
  price: number | null;
  image: string | null;
  slug: string | null;
  quantity: number;
  stock: number;
  is_available: boolean;
}

interface CartResponse {
  items: ServerCartItem[];
}

export function getCart() {
  return api.get<CartResponse>("/cart").then((r) => r.data.items);
}

export function addCartItem(payload: {
  product_id: number;
  variant_id?: number;
  quantity?: number;
}) {
  return api.post<CartResponse>("/cart/items", payload).then((r) => r.data.items);
}

export function updateCartItem(id: number, quantity: number) {
  return api.patch<CartResponse>(`/cart/items/${id}`, { quantity }).then((r) => r.data.items);
}

export function removeCartItem(id: number) {
  return api.delete<CartResponse>(`/cart/items/${id}`).then((r) => r.data.items);
}

export function clearCart() {
  return api.delete("/cart");
}
