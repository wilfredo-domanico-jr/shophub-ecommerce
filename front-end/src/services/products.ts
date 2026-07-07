import api from "./api";

export interface Product {
  id: number;
  category_id: number;
  category?: { id: number; name: string; slug: string };
  name: string;
  slug: string;
  description: string | null;
  price: string;
  original_price: string | null;
  stock_quantity: number;
  image: string | null;
  is_featured: boolean;
  is_flash_sale: boolean;
  sold_count: number;
  flash_sale_goal: number | null;
  rating: string;
  is_active: boolean;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

export function getProducts(params: Record<string, string | number | boolean> = {}) {
  return api.get<PaginatedResponse<Product>>("/products", { params }).then((r) => r.data);
}

export function getFeaturedProducts() {
  return getProducts({ featured: true });
}

export function getFlashSaleProducts() {
  return getProducts({ flash_sale: true });
}

export function getProduct(slug: string) {
  return api.get<Product>(`/products/${slug}`).then((r) => r.data);
}
