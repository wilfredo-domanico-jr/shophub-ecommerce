import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";

vi.mock("../../services/api", () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}));

import api from "../../services/api";
import { useAuthStore } from "../auth";

const mockedApi = api as unknown as { post: ReturnType<typeof vi.fn>; get: ReturnType<typeof vi.fn> };

describe("auth store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    mockedApi.post.mockReset();
    mockedApi.get.mockReset();
  });

  it("starts logged out", () => {
    const auth = useAuthStore();
    expect(auth.isLoggedIn).toBe(false);
    expect(auth.isAdmin).toBe(false);
  });

  it("login stores the token and user", async () => {
    const auth = useAuthStore();
    mockedApi.post.mockResolvedValueOnce({
      data: { token: "fake-token", user: { id: 1, name: "Admin", email: "admin@example.com", is_admin: true } },
    });

    await auth.login({ email: "admin@example.com", password: "secret" });

    expect(localStorage.getItem("token")).toBe("fake-token");
    expect(auth.isLoggedIn).toBe(true);
    expect(auth.isAdmin).toBe(true);
  });

  it("fetchUser is a no-op when there is no stored token", async () => {
    const auth = useAuthStore();
    await auth.fetchUser();

    expect(mockedApi.get).not.toHaveBeenCalled();
    expect(auth.user).toBeNull();
  });

  it("fetchUser hydrates the user when a token exists", async () => {
    localStorage.setItem("token", "existing-token");
    mockedApi.get.mockResolvedValueOnce({
      data: { id: 2, name: "Existing Admin", email: "existing@example.com", is_admin: true },
    });

    const auth = useAuthStore();
    await auth.fetchUser();

    expect(auth.user?.email).toBe("existing@example.com");
    expect(auth.initialized).toBe(true);
  });

  it("fetchUser clears a stale/invalid token on failure", async () => {
    localStorage.setItem("token", "stale-token");
    mockedApi.get.mockRejectedValueOnce(new Error("401"));

    const auth = useAuthStore();
    await auth.fetchUser();

    expect(localStorage.getItem("token")).toBeNull();
    expect(auth.user).toBeNull();
  });

  it("logout clears the token and user even if the API call fails", async () => {
    localStorage.setItem("token", "fake-token");
    mockedApi.post.mockRejectedValueOnce(new Error("network error"));

    const auth = useAuthStore();
    await auth.logout().catch(() => {});

    expect(localStorage.getItem("token")).toBeNull();
    expect(auth.user).toBeNull();
  });
});
