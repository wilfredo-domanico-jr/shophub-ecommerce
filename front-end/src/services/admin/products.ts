import api from "../api";
import type { Product } from "../products";
import type { PaginatedResponse } from "../pagination";

export function getAdminProducts(params: { search?: string; page?: number } = {}) {
  return api
    .get<PaginatedResponse<Product>>("/admin/products", { params })
    .then((r) => r.data);
}

export interface ProductPayload {
  name: string;
  category_id: number;
  price: number;
  stock_quantity: number;
  image: string | null;
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
