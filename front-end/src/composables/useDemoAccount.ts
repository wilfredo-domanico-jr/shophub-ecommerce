import { computed, ref } from "vue";
import { useAuthStore } from "../stores/auth";
import { getAppConfig } from "../services/config";

// Fetched once per page load and shared by every component that asks.
const demoEmails = ref<string[]>([]);
let loaded = false;

/**
 * Whether the signed-in user is one of the shared demo accounts.
 * The backend blocks profile/password/checkout-detail changes for them —
 * this lets the UI disable those controls instead of surfacing errors.
 */
export function useDemoAccount() {
  const auth = useAuthStore();

  if (!loaded) {
    loaded = true;
    getAppConfig()
      .then((config) => {
        demoEmails.value = config.demo_mode
          ? [config.demo_admin_email, config.demo_customer_email]
              .filter((email): email is string => !!email)
              .map((email) => email.toLowerCase())
          : [];
      })
      .catch(() => {
        demoEmails.value = [];
      });
  }

  const isDemoAccount = computed(() => {
    const email = auth.user?.email?.toLowerCase();
    return !!email && demoEmails.value.includes(email);
  });

  return { isDemoAccount };
}
