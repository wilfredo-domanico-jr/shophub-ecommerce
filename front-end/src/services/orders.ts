import api from "./api";

export interface OrderItem {
  id: number;
  product_id: number | null;
  product_variant_id: number | null;
  product_name: string;
  variant_label: string | null;
  product_price: string;
  quantity: number;
  subtotal: string;
  /** Present on /my/orders; null when the product was deleted. */
  product?: { id: number; slug: string } | null;
}

export interface Order {
  id: number;
  order_number: string;
  user_id: number | null;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  shipping_address: string;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled";
  payment_method: string;
  payment_status: "unpaid" | "paid";
  paid_at: string | null;
  subtotal: string;
  shipping_fee: string;
  voucher_code: string | null;
  discount: string;
  total: string;
  notes: string | null;
  created_at: string;
  items: OrderItem[];
}

export type PaymentMethod = "Cash on Delivery" | "Card";

export interface CreateOrderPayload {
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  shipping_address: string;
  notes?: string;
  payment_method?: PaymentMethod;
  voucher_code?: string;
  items: { product_id: number; variant_id?: number; quantity: number }[];
}

export function createOrder(payload: CreateOrderPayload) {
  return api.post<Order>("/orders", payload).then((r) => r.data);
}

/** Creates a fresh Stripe Checkout session for an unpaid card order. */
export function payOrder(orderId: number) {
  return api.post<{ url: string }>(`/orders/${orderId}/pay`).then((r) => r.data);
}

export interface PaymentStatus {
  id: number;
  order_number: string;
  payment_status: Order["payment_status"];
  paid_at: string | null;
}

export function getPaymentStatus(orderNumber: string) {
  return api
    .get<PaymentStatus>(`/my/orders/${orderNumber}/payment-status`)
    .then((r) => r.data);
}

export interface TrackedOrder {
  order_number: string;
  status: Order["status"];
  created_at: string;
  voucher_code: string | null;
  discount: string;
  total: string;
  items: Pick<OrderItem, "id" | "product_name" | "variant_label" | "quantity" | "subtotal">[];
}

export function trackOrder(payload: { order_number: string; email: string }) {
  return api.post<TrackedOrder>("/orders/track", payload).then((r) => r.data);
}
