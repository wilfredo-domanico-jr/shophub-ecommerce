import api from "../api";

export interface Voucher {
  id: number;
  code: string;
  description: string | null;
  type: "percent" | "fixed";
  value: string;
  max_discount: string | null;
  min_spend: string | null;
  starts_at: string | null;
  expires_at: string | null;
  usage_limit: number | null;
  per_customer_limit: number | null;
  used_count: number;
  is_active: boolean;
  is_public: boolean;
}

export interface VoucherPayload {
  code: string;
  description: string | null;
  type: "percent" | "fixed";
  value: number;
  max_discount: number | null;
  min_spend: number | null;
  starts_at: string | null;
  expires_at: string | null;
  usage_limit: number | null;
  per_customer_limit: number | null;
  is_active: boolean;
  is_public: boolean;
}

export function getAdminVouchers() {
  return api.get<Voucher[]>("/admin/vouchers").then((r) => r.data);
}

export function createVoucher(payload: VoucherPayload) {
  return api.post<Voucher>("/admin/vouchers", payload).then((r) => r.data);
}

export function updateVoucher(id: number, payload: VoucherPayload) {
  return api.put<Voucher>(`/admin/vouchers/${id}`, payload).then((r) => r.data);
}

export function deleteVoucher(id: number) {
  return api.delete(`/admin/vouchers/${id}`).then((r) => r.data);
}
