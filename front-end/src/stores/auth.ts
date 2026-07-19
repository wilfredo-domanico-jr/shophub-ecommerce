import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "../services/api";
import { useCartStore } from "./cart";

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  default_shipping_address: string | null;
  is_admin: boolean;
}

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const initialized = ref(false);

  const isLoggedIn = computed(() => !!user.value);
  const isAdmin = computed(() => !!user.value?.is_admin);

  async function login(credentials: { email: string; password: string }) {
    const { data } = await api.post("/login", credentials);
    localStorage.setItem("token", data.token);
    user.value = data.user;
    initialized.value = true;
  }

  async function register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    const { data } = await api.post("/register", payload);
    localStorage.setItem("token", data.token);
    user.value = data.user;
    initialized.value = true;
  }

  async function fetchUser() {
    if (!localStorage.getItem("token")) {
      user.value = null;
      initialized.value = true;
      return;
    }

    try {
      const { data } = await api.get<User>("/me");
      user.value = data;
    } catch {
      localStorage.removeItem("token");
      user.value = null;
    } finally {
      initialized.value = true;
    }
  }

  // Social login lands with a ready-made token instead of credentials.
  async function loginWithToken(token: string) {
    localStorage.setItem("token", token);
    await fetchUser(); // clears the token itself if it's invalid
    if (!user.value) {
      throw new Error("Token login failed");
    }
  }

  function setUser(updated: User) {
    user.value = updated;
  }

  async function logout() {
    try {
      await api.post("/logout");
    } finally {
      localStorage.removeItem("token");
      user.value = null;
      // The cart belongs to the account that just signed out.
      useCartStore().clear();
    }
  }

  return {
    user,
    initialized,
    isLoggedIn,
    isAdmin,
    login,
    loginWithToken,
    register,
    logout,
    fetchUser,
    setUser,
  };
});
