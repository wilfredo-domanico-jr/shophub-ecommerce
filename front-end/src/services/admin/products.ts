import api from "../api";
import type { Product } from "../products";

export function getAdminProducts() {
  return api.get<Product[]>("/admin/products").then((r) => r.data);
}

export function createProduct(payload: Partial<Product>) {
  return api.post<Product>("/admin/products", payload).then((r) => r.data);
}

export function updateProduct(id: number, payload: Partial<Product>) {
  return api.put<Product>(`/admin/products/${id}`, payload).then((r) => r.data);
}

export function deleteProduct(id: number) {
  return api.delete(`/admin/products/${id}`).then((r) => r.data);
}
