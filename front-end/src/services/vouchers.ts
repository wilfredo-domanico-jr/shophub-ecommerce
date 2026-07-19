import api from "./api";
import type { CreateOrderPayload } from "./orders";

export interface PublicVoucher {
  code: string;
  description: string | null;
  type: "percent" | "fixed";
  value: string;
  max_discount: string | null;
  min_spend: string | null;
  expires_at: string | null;
  per_customer_limit: number | null;
}

// Active, publicly listed vouchers (admin-controlled via is_public).
export function getPublicVouchers() {
  return api.get<PublicVoucher[]>("/vouchers").then((r) => r.data);
}

export function voucherSummary(voucher: Pick<PublicVoucher, "type" | "value" | "max_discount">) {
  if (voucher.type === "percent") {
    const cap = voucher.max_discount
      ? ` (max ₱${Number(voucher.max_discount).toLocaleString()})`
      : "";
    return `${Number(voucher.value)}% off${cap}`;
  }
  return `₱${Number(voucher.value).toLocaleString()} off`;
}

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
