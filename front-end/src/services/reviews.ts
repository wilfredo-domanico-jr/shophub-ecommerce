import api from "./api";
import type { PaginatedResponse } from "./pagination";

export interface Review {
  id: number;
  product_id: number;
  user_id: number;
  rating: number;
  comment: string | null;
  photos: string[] | null;
  photo_urls: string[];
  created_at: string;
  user?: { id: number; name: string };
}

/** Paginator plus a rating → count map for the distribution bars. */
export type ReviewsPage = PaginatedResponse<Review> & {
  breakdown: Record<string, number>;
};

export interface CreateReviewPayload {
  rating: number;
  comment?: string;
  photos?: File[];
}

export function getProductReviews(slug: string, page = 1) {
  return api
    .get<ReviewsPage>(`/products/${slug}/reviews`, { params: { page } })
    .then((r) => r.data);
}

export function createReview(slug: string, payload: CreateReviewPayload) {
  // Photos ride along on the create request, so it has to be multipart.
  const form = new FormData();
  form.append("rating", String(payload.rating));
  if (payload.comment) form.append("comment", payload.comment);
  for (const photo of payload.photos ?? []) {
    form.append("photos[]", photo);
  }

  return api.post<Review>(`/products/${slug}/reviews`, form).then((r) => r.data);
}

export function updateReview(id: number, payload: { rating: number; comment?: string }) {
  return api.patch<Review>(`/reviews/${id}`, payload).then((r) => r.data);
}

export function deleteReview(id: number) {
  return api.delete(`/reviews/${id}`);
}
