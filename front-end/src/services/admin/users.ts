import api from "../api";
import type { PaginatedResponse } from "../pagination";

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  created_at: string;
}

export function getAdminUsers(params: { search?: string; page?: number } = {}) {
  return api
    .get<PaginatedResponse<AdminUser>>("/admin/users", { params })
    .then((r) => r.data);
}

export function createAdminUser(payload: { name: string; email: string; password: string }) {
  return api.post<AdminUser>("/admin/users", payload).then((r) => r.data);
}

export function updateAdminUser(
  id: number,
  payload: { name: string; email: string; password?: string }
) {
  return api.put<AdminUser>(`/admin/users/${id}`, payload).then((r) => r.data);
}

export function deleteAdminUser(id: number) {
  return api.delete(`/admin/users/${id}`).then((r) => r.data);
}
