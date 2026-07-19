import api from "../api";

export interface Newsletter {
  id: number;
  subject: string;
  body: string;
  image_url: string | null;
  status: "draft" | "sent";
  sent_at: string | null;
  created_at: string;
}

export interface NewsletterPayload {
  subject: string;
  body: string;
  image_url: string | null;
}

export interface AdminNewsletterIndex {
  subscribers_count: number;
  newsletters: Newsletter[];
}

export function getAdminNewsletters() {
  return api.get<AdminNewsletterIndex>("/admin/newsletters").then((r) => r.data);
}

export function createNewsletter(payload: NewsletterPayload) {
  return api.post<Newsletter>("/admin/newsletters", payload).then((r) => r.data);
}

export function updateNewsletter(id: number, payload: NewsletterPayload) {
  return api.put<Newsletter>(`/admin/newsletters/${id}`, payload).then((r) => r.data);
}

export function deleteNewsletter(id: number) {
  return api.delete<{ message: string }>(`/admin/newsletters/${id}`).then((r) => r.data);
}

export function sendNewsletter(id: number) {
  return api
    .post<{ message: string; newsletter: Newsletter }>(`/admin/newsletters/${id}/send`)
    .then((r) => r.data);
}

export interface NewsletterSubscriber {
  id: number;
  email: string;
  unsubscribed_at: string | null;
  created_at: string;
}

export interface PaginatedSubscribers {
  data: NewsletterSubscriber[];
  current_page: number;
  last_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export function getAdminSubscribers(params: { search?: string; page?: number }) {
  return api
    .get<PaginatedSubscribers>("/admin/newsletter-subscribers", { params })
    .then((r) => r.data);
}

export function deleteSubscriber(id: number) {
  return api
    .delete<{ message: string }>(`/admin/newsletter-subscribers/${id}`)
    .then((r) => r.data);
}
