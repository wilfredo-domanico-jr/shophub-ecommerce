import api from "./api";

export interface FlashSaleInfo {
  title: string;
  starts_at: string;
  ends_at: string;
  is_live: boolean;
}

// The current-or-next scheduled sale; null when nothing is scheduled.
export function getCurrentFlashSale(): Promise<FlashSaleInfo | null> {
  return api
    .get<{ sale: FlashSaleInfo | null }>("/flash-sale")
    .then((r) => r.data.sale ?? null);
}
