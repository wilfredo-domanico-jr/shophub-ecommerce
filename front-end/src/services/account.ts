import api from "./api";
import type { AxiosError } from "axios";
import type { User } from "../stores/auth";
import type { Order } from "./orders";
import type { PaginatedResponse } from "./pagination";

export function firstValidationError(error: unknown, fallback: string): string {
  const err = error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>;
  const errors = err.response?.data?.errors;
  return (errors && Object.values(errors)[0]?.[0]) || err.response?.data?.message || fallback;
}

export interface UpdateProfilePayload {
  name: string;
  email: string;
  phone?: string | null;
  default_shipping_address?: string | null;
}

export interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export function updateProfile(payload: UpdateProfilePayload) {
  return api.patch<User>("/profile", payload).then((r) => r.data);
}

export function changePassword(payload: ChangePasswordPayload) {
  return api.patch<{ message: string }>("/profile/password", payload).then((r) => r.data);
}

export function forgotPassword(email: string) {
  return api.post<{ message: string }>("/forgot-password", { email }).then((r) => r.data);
}

export function resetPassword(payload: ResetPasswordPayload) {
  return api.post<{ message: string }>("/reset-password", payload).then((r) => r.data);
}

export function getMyOrders(page = 1) {
  return api
    .get<PaginatedResponse<Order>>("/my/orders", { params: { page } })
    .then((r) => r.data);
}
