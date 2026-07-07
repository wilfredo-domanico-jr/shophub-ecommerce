import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "../services/api";

interface User {
  id: number;
  name: string;
  email: string;
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
  }

  async function fetchUser() {
    if (!localStorage.getItem("token")) {
      user.value = null;
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

  async function logout() {
    try {
      await api.post("/logout");
    } finally {
      localStorage.removeItem("token");
      user.value = null;
    }
  }

  return {
    user,
    initialized,
    isLoggedIn,
    isAdmin,
    login,
    logout,
    fetchUser,
  };
});
