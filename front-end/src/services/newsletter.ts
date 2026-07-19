import api from "./api";

export function subscribeToNewsletter(email: string) {
  return api
    .post<{ message: string }>("/newsletter/subscribe", { email })
    .then((r) => r.data);
}

export function unsubscribeFromNewsletter(token: string) {
  return api
    .post<{ message: string }>("/newsletter/unsubscribe", { token })
    .then((r) => r.data);
}
