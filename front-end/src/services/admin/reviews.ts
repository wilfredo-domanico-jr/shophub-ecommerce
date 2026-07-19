import api from "../api";
import type { PaginatedResponse } from "../pagination";

export interface AdminReview {
  id: number;
  product_id: number;
  user_id: number;
  rating: number;
  comment: string | null;
  photos: string[] | null;
  photo_urls: string[];
  is_hidden: boolean;
  created_at: string;
  user: { id: number; name: string } | null;
  product: { id: number; name: string; slug: string } | null;
}

export interface AdminReviewQuery {
  page?: number;
  search?: string;
  rating?: number;
}

export function getAdminReviews(params: AdminReviewQuery = {}) {
  return api
    .get<PaginatedResponse<AdminReview>>("/admin/reviews", { params })
    .then((r) => r.data);
}

export function setReviewVisibility(id: number, isHidden: boolean) {
  return api
    .patch<AdminReview>(`/admin/reviews/${id}/visibility`, { is_hidden: isHidden })
    .then((r) => r.data);
}

export function deleteAdminReview(id: number) {
  return api.delete(`/admin/reviews/${id}`);
}
