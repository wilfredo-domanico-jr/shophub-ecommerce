import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from "axios";

// No default Content-Type: axios picks application/json for object bodies
// and multipart/form-data for FormData; forcing JSON here would make axios
// serialize FormData uploads to JSON, mangling attached files.
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
});

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token: string | null = localStorage.getItem("token");

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// On 401 the stored token is stale — drop it. Only pages that require auth
// redirect to login (the router guard handles that on navigation); background
// calls during guest browsing must not hijack the page.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && localStorage.getItem("token")) {
      localStorage.removeItem("token");

      const path = window.location.pathname;
      if (path.startsWith("/account")) {
        window.location.href = "/login?redirect=" + encodeURIComponent(path);
      } else if (path.startsWith("/admin") && path !== "/admin/login") {
        window.location.href = "/admin/login";
      }
    }

    return Promise.reject(error);
  }
);

export default api;
