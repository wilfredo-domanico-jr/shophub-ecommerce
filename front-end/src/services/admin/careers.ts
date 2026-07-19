import api from "../api";
import type { JobOpening } from "../careers";

export interface JobOpeningPayload {
  title: string;
  department: string;
  location: string;
  employment_type: string;
  description: string;
  is_active: boolean;
}

export function getAdminJobOpenings() {
  return api.get<JobOpening[]>("/admin/careers").then((r) => r.data);
}

export function createJobOpening(payload: JobOpeningPayload) {
  return api.post<JobOpening>("/admin/careers", payload).then((r) => r.data);
}

export function updateJobOpening(id: number, payload: JobOpeningPayload) {
  return api.put<JobOpening>(`/admin/careers/${id}`, payload).then((r) => r.data);
}

export function deleteJobOpening(id: number) {
  return api.delete<{ message: string }>(`/admin/careers/${id}`).then((r) => r.data);
}
