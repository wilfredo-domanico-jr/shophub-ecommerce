import api from "../api";

export interface DashboardStats {
  total_sales: number;
  orders_count: number;
  products_count: number;
  customers_count: number;
  sales_series: { date: string; label: string; total: number }[];
  recent_orders: {
    id: number;
    order_number: string;
    customer_name: string;
    status: string;
    total: string;
    created_at: string;
  }[];
}

export function getDashboardStats() {
  return api.get<DashboardStats>("/admin/dashboard/stats").then((r) => r.data);
}
