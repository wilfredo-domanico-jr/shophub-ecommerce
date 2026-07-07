import api from "./api";

export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  icon: string | null;
  color_class: string | null;
  is_active: boolean;
  products_count: number;
}

export function getCategories() {
  return api.get<Category[]>("/categories").then((r) => r.data);
}

export function getCategory(slug: string) {
  return api.get<Category>(`/categories/${slug}`).then((r) => r.data);
}
