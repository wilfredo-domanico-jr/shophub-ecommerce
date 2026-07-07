import api from "../api";
import type { Order } from "../orders";

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

export function getAdminOrders(params: Record<string, string> = {}) {
  return api.get<PaginatedResponse<Order>>("/admin/orders", { params }).then((r) => r.data);
}

export function getAdminOrder(id: number) {
  return api.get<Order>(`/admin/orders/${id}`).then((r) => r.data);
}

export function updateOrderStatus(id: number, status: Order["status"]) {
  return api.patch<Order>(`/admin/orders/${id}/status`, { status }).then((r) => r.data);
}
