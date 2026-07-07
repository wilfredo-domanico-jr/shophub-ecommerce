import api from "./api";

export interface AppConfig {
  demo_mode: boolean;
  demo_admin_email: string | null;
  demo_admin_password: string | null;
}

export function getAppConfig() {
  return api.get<AppConfig>("/config").then((r) => r.data);
}
