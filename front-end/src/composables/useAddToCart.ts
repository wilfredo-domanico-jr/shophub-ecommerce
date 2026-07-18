import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { useToastStore } from "../stores/toast";

// Adding to cart requires an account: guests are sent to login and come
// back to the page they were on via ?redirect=.
export function useAddToCart() {
  const auth = useAuthStore();
  const cartStore = useCartStore();
  const toast = useToastStore();
  const route = useRoute();
  const router = useRouter();

  async function ensureSignedIn(
    guestMessage = "Sign in to add items to your cart."
  ): Promise<boolean> {
    if (!auth.initialized) {
      await auth.fetchUser();
    }

    if (!auth.isLoggedIn) {
      toast.info(guestMessage);
      router.push({
        name: "CustomerLogin",
        query: { redirect: route.fullPath },
      });
      return false;
    }

    return true;
  }

  async function addToCart<T extends { id: number; name: string }>(
    product: T,
    quantity = 1,
    guestMessage = "Sign in to add items to your cart."
  ): Promise<boolean> {
    if (!(await ensureSignedIn(guestMessage))) {
      return false;
    }

    cartStore.addItem(product, quantity);
    toast.success(`${product.name} added to cart.`);
    return true;
  }

  return { addToCart, ensureSignedIn };
}
