import api from "./api";
import type { CreateOrderPayload } from "./orders";

export interface VoucherPreview {
  code: string;
  description: string | null;
  subtotal: string;
  discount: string;
  total: string;
}

// Cosmetic pre-checkout pricing — the order endpoint re-validates the code.
export function previewVoucher(payload: {
  code: string;
  items: CreateOrderPayload["items"];
}) {
  return api.post<VoucherPreview>("/vouchers/preview", payload).then((r) => r.data);
}
