import api from "./api";

export interface OrderItem {
  id: number;
  product_id: number | null;
  product_name: string;
  product_price: string;
  quantity: number;
  subtotal: string;
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
  subtotal: string;
  shipping_fee: string;
  total: string;
  notes: string | null;
  created_at: string;
  items: OrderItem[];
}

export interface CreateOrderPayload {
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  shipping_address: string;
  notes?: string;
  items: { product_id: number; quantity: number }[];
}

export function createOrder(payload: CreateOrderPayload) {
  return api.post<Order>("/orders", payload).then((r) => r.data);
}

export function trackOrder(payload: { order_number: string; email: string }) {
  return api.post<Order>("/orders/track", payload).then((r) => r.data);
}
