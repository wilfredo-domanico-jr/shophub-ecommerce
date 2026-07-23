import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import type { ServerCartItem } from "../../services/cart";

// In-memory stand-in for the cart API: the store's contract is "send a
// mutation, adopt the returned item list", so the fake mirrors the real
// endpoints' upsert/remove semantics against a tiny registered catalog.
interface CatalogEntry {
  name: string;
  price: number | null;
  variant_label?: string | null;
  available?: boolean;
}

const catalog = new Map<string, CatalogEntry>();
let lines: { id: number; product_id: number; variant_id: number | null; quantity: number }[] = [];
let nextId = 1;

function catalogKey(productId: number, variantId: number | null | undefined) {
  return `${productId}:${variantId ?? ""}`;
}

function registerProduct(
  productId: number,
  entry: CatalogEntry,
  variantId: number | null = null
) {
  catalog.set(catalogKey(productId, variantId), entry);
}

function snapshot(): ServerCartItem[] {
  return lines.map((line) => {
    const entry = catalog.get(catalogKey(line.product_id, line.variant_id));
    return {
      id: line.id,
      product_id: line.product_id,
      variant_id: line.variant_id,
      name: entry?.name ?? "Unknown",
      variant_label: entry?.variant_label ?? null,
      price: entry?.price ?? null,
      image: null,
      slug: null,
      quantity: line.quantity,
      stock: entry?.available === false ? 0 : 10,
      is_available: entry?.available !== false,
    };
  });
}

vi.mock("../../services/cart", () => ({
  getCart: vi.fn(async () => snapshot()),
  addCartItem: vi.fn(
    async (payload: { product_id: number; variant_id?: number; quantity?: number }) => {
      const variantId = payload.variant_id ?? null;
      const existing = lines.find(
        (l) => l.product_id === payload.product_id && l.variant_id === variantId
      );
      if (existing) {
        existing.quantity += payload.quantity ?? 1;
      } else {
        lines.push({
          id: nextId++,
          product_id: payload.product_id,
          variant_id: variantId,
          quantity: payload.quantity ?? 1,
        });
      }
      return snapshot();
    }
  ),
  updateCartItem: vi.fn(async (id: number, quantity: number) => {
    const line = lines.find((l) => l.id === id);
    if (line) line.quantity = quantity;
    return snapshot();
  }),
  removeCartItem: vi.fn(async (id: number) => {
    lines = lines.filter((l) => l.id !== id);
    return snapshot();
  }),
  clearCart: vi.fn(async () => {
    lines = [];
  }),
}));

import { useCartStore, lineKey } from "../cart";

describe("cart store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    catalog.clear();
    lines = [];
    nextId = 1;
    registerProduct(1, { name: "Widget", price: 100 });
    registerProduct(2, { name: "Gadget", price: 50 });
    registerProduct(1, { name: "Tee", price: 100, variant_label: "Red / M" }, 10);
    registerProduct(1, { name: "Tee", price: 100, variant_label: "Blue / L" }, 11);
  });

  it("starts empty", () => {
    const cart = useCartStore();
    expect(cart.items).toHaveLength(0);
    expect(cart.count()).toBe(0);
    expect(cart.total()).toBe(0);
  });

  it("adds a new product with quantity 1 by default", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]).toMatchObject({
      id: 1,
      name: "Widget",
      price: 100,
      quantity: 1,
      key: "1:",
      available: true,
    });
  });

  it("adding the same product again increments its quantity instead of duplicating", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });
    await cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.quantity).toBe(2);
  });

  it("keeps different variants of the same product as separate lines", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 11 });

    expect(cart.items).toHaveLength(2);
    expect(cart.items[0]!.key).toBe("1:10");
    expect(cart.items[1]!.key).toBe("1:11");
  });

  it("merges lines for the same product variant", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 }, 2);

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.quantity).toBe(3);
  });

  it("keeps a flat product and a variant of the same product id separate", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });

    expect(cart.items).toHaveLength(2);
  });

  it("supports adding a specific quantity", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 }, 3);

    expect(cart.items[0]!.quantity).toBe(3);
  });

  it("removes an item by line key", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });
    await cart.addItem({ id: 2, name: "Gadget", price: 50 });

    await cart.removeItem("1:");

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.id).toBe(2);
  });

  it("removes only the targeted variant line", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });
    await cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 11 });

    await cart.removeItem("1:10");

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.variant_id).toBe(11);
  });

  it("updates quantity by line key but never below 1", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });

    await cart.updateQuantity("1:", 5);
    expect(cart.items[0]!.quantity).toBe(5);

    await cart.updateQuantity("1:", 0);
    expect(cart.items[0]!.quantity).toBe(1);
  });

  it("lineKey() builds composite keys for flat and variant products", () => {
    expect(lineKey({ id: 5 })).toBe("5:");
    expect(lineKey({ id: 5, variant_id: null })).toBe("5:");
    expect(lineKey({ id: 5, variant_id: 9 })).toBe("5:9");
  });

  it("count() reflects the number of distinct line items", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 }, 5);
    await cart.addItem({ id: 2, name: "Gadget", price: 50 });

    expect(cart.count()).toBe(2);
  });

  it("total() sums price * quantity across all items", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 }, 2); // 200
    await cart.addItem({ id: 2, name: "Gadget", price: 50 }, 3); // 150

    expect(cart.total()).toBe(350);
  });

  it("load() pulls the server cart, so a page refresh restores it", async () => {
    lines.push({ id: 7, product_id: 1, variant_id: null, quantity: 2 });

    const cart = useCartStore();
    await cart.load();

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]).toMatchObject({ id: 1, quantity: 2, serverId: 7 });
  });

  it("flags unavailable lines and keeps them out of totals and checkout", async () => {
    registerProduct(3, { name: "Retired Thing", price: 80, available: false });
    lines.push({ id: 8, product_id: 3, variant_id: null, quantity: 1 });
    lines.push({ id: 9, product_id: 1, variant_id: null, quantity: 2 });

    const cart = useCartStore();
    await cart.load();

    expect(cart.items).toHaveLength(2);
    expect(cart.items.find((i) => i.id === 3)!.available).toBe(false);
    expect(cart.availableItems()).toHaveLength(1);
    expect(cart.total()).toBe(200); // only the Widget line counts
    expect(cart.checkoutItems()).toHaveLength(1);
    expect(cart.checkoutItems()[0]!.id).toBe(1);
  });

  it("clear() empties both the cart and any pending buy-now item", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 }, 2);
    cart.setBuyNow({ id: 2, name: "Gadget", price: 50 });

    cart.clear();

    expect(cart.items).toHaveLength(0);
    expect(cart.buyNowItem).toBeNull();
    expect(cart.count()).toBe(0);
  });

  it("checkoutItems() returns the cart when no buy-now item is set", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.checkoutItems()).toEqual(cart.items);
  });

  it("buy-now checks out only that item, without touching the cart", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 }, 2);
    cart.setBuyNow({ id: 2, name: "Gadget", price: 50 }, 3);

    expect(cart.checkoutItems()).toHaveLength(1);
    expect(cart.checkoutItems()[0]).toMatchObject({ id: 2, quantity: 3 });
    expect(cart.checkoutTotal()).toBe(150);
    expect(cart.items).toHaveLength(1); // cart untouched
    expect(cart.total()).toBe(200);
  });

  it("buy-now carries the variant fields and key", () => {
    const cart = useCartStore();
    cart.setBuyNow({ id: 2, name: "Tee", price: 100, variant_id: 7, variant_label: "Red / S" }, 2);

    expect(cart.buyNowItem).toMatchObject({
      key: "2:7",
      variant_id: 7,
      variant_label: "Red / S",
      quantity: 2,
    });
  });

  it("clearBuyNow() falls back to checking out the cart", async () => {
    const cart = useCartStore();
    await cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.setBuyNow({ id: 2, name: "Gadget", price: 50 });

    cart.clearBuyNow();

    expect(cart.buyNowItem).toBeNull();
    expect(cart.checkoutItems()).toEqual(cart.items);
    expect(cart.checkoutTotal()).toBe(100);
  });
});
