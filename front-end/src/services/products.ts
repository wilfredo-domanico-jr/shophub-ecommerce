import api from "./api";
import type { PaginatedResponse } from "./pagination";

export interface ProductOption {
  name: string;
  values: string[];
}

export interface ProductVariant {
  id: number;
  option_values: Record<string, string>;
  /** null = inherits the product price */
  price: string | null;
  stock_quantity: number;
  /** null = inherits the product image */
  image: string | null;
}

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
  options?: ProductOption[] | null;
  variants?: ProductVariant[];
  variants_count?: number;
  is_featured: boolean;
  is_flash_sale: boolean;
  sold_count: number;
  flash_sale_goal: number | null;
  rating: string;
  is_active: boolean;
}

export interface ProductQueryParams {
  search?: string;
  category?: string;
  sort?: "price_asc" | "price_desc" | "newest";
  page?: number;
  per_page?: number;
  featured?: boolean;
  flash_sale?: boolean;
}

export function getProducts(params: ProductQueryParams = {}) {
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
