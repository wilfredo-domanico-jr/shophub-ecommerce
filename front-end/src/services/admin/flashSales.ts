import api from "../api";

export interface FlashSale {
  id: number;
  title: string;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
}

export interface FlashSalePayload {
  title: string;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
}

export function getAdminFlashSales() {
  return api.get<FlashSale[]>("/admin/flash-sales").then((r) => r.data);
}

export function createFlashSale(payload: FlashSalePayload) {
  return api.post<FlashSale>("/admin/flash-sales", payload).then((r) => r.data);
}

export function updateFlashSale(id: number, payload: FlashSalePayload) {
  return api.put<FlashSale>(`/admin/flash-sales/${id}`, payload).then((r) => r.data);
}

export function deleteFlashSale(id: number) {
  return api.delete(`/admin/flash-sales/${id}`).then((r) => r.data);
}
