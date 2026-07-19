import api from "../api";
import type { Product } from "../products";
import type { PaginatedResponse } from "../pagination";

export function getAdminProducts(params: { search?: string; page?: number } = {}) {
  return api
    .get<PaginatedResponse<Product>>("/admin/products", { params })
    .then((r) => r.data);
}

export interface VariantPayload {
  id?: number;
  option_values: Record<string, string>;
  /** null = inherit the product price */
  price: number | null;
  stock_quantity: number;
  /** null = inherit the product image */
  image: string | null;
}

export interface ProductPayload {
  name: string;
  category_id: number;
  price: number;
  stock_quantity: number;
  image: string | null;
  options?: { name: string; values: string[] }[] | null;
  variants?: VariantPayload[];
}

export function createProduct(payload: ProductPayload) {
  return api.post<Product>("/admin/products", payload).then((r) => r.data);
}

export function updateProduct(id: number, payload: ProductPayload) {
  return api.put<Product>(`/admin/products/${id}`, payload).then((r) => r.data);
}

export function deleteProduct(id: number) {
  return api.delete(`/admin/products/${id}`).then((r) => r.data);
}
