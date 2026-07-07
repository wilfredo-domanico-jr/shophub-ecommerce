import api from "../api";
import type { Category } from "../categories";
import type { PaginatedResponse } from "../pagination";

export function getAdminCategories(
  params: { search?: string; page?: number; per_page?: number } = {}
) {
  return api
    .get<PaginatedResponse<Category>>("/admin/categories", { params })
    .then((r) => r.data);
}

export function createCategory(payload: Partial<Category>) {
  return api.post<Category>("/admin/categories", payload).then((r) => r.data);
}

export function updateCategory(id: number, payload: Partial<Category>) {
  return api.put<Category>(`/admin/categories/${id}`, payload).then((r) => r.data);
}

export function deleteCategory(id: number) {
  return api.delete(`/admin/categories/${id}`).then((r) => r.data);
}
