import { describe, it, expect, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useCartStore, lineKey } from "../cart";

describe("cart store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("starts empty", () => {
    const cart = useCartStore();
    expect(cart.items).toHaveLength(0);
    expect(cart.count()).toBe(0);
    expect(cart.total()).toBe(0);
  });

  it("adds a new product with quantity 1 by default", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]).toMatchObject({ id: 1, name: "Widget", price: 100, quantity: 1, key: "1:" });
  });

  it("adding the same product again increments its quantity instead of duplicating", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.quantity).toBe(2);
  });

  it("keeps different variants of the same product as separate lines", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10, variant_label: "Red / M" });
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 11, variant_label: "Blue / L" });

    expect(cart.items).toHaveLength(2);
    expect(cart.items[0]!.key).toBe("1:10");
    expect(cart.items[1]!.key).toBe("1:11");
  });

  it("merges lines for the same product variant", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10, variant_label: "Red / M" });
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10, variant_label: "Red / M" }, 2);

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.quantity).toBe(3);
  });

  it("keeps a flat product and a variant of the same product id separate", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Tee", price: 100 });
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });

    expect(cart.items).toHaveLength(2);
  });

  it("supports adding a specific quantity", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 3);

    expect(cart.items[0]!.quantity).toBe(3);
  });

  it("removes an item by line key", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.addItem({ id: 2, name: "Gadget", price: 50 });

    cart.removeItem("1:");

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.id).toBe(2);
  });

  it("removes only the targeted variant line", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 10 });
    cart.addItem({ id: 1, name: "Tee", price: 100, variant_id: 11 });

    cart.removeItem("1:10");

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0]!.variant_id).toBe(11);
  });

  it("updates quantity by line key but never below 1", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    cart.updateQuantity("1:", 5);
    expect(cart.items[0]!.quantity).toBe(5);

    cart.updateQuantity("1:", 0);
    expect(cart.items[0]!.quantity).toBe(1);
  });

  it("lineKey() builds composite keys for flat and variant products", () => {
    expect(lineKey({ id: 5 })).toBe("5:");
    expect(lineKey({ id: 5, variant_id: null })).toBe("5:");
    expect(lineKey({ id: 5, variant_id: 9 })).toBe("5:9");
  });

  it("count() reflects the number of distinct line items", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 5);
    cart.addItem({ id: 2, name: "Gadget", price: 50 });

    expect(cart.count()).toBe(2);
  });

  it("total() sums price * quantity across all items", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 2); // 200
    cart.addItem({ id: 2, name: "Gadget", price: 50 }, 3); // 150

    expect(cart.total()).toBe(350);
  });

  it("clear() empties both the cart and any pending buy-now item", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 2);
    cart.setBuyNow({ id: 2, name: "Gadget", price: 50 });

    cart.clear();

    expect(cart.items).toHaveLength(0);
    expect(cart.buyNowItem).toBeNull();
    expect(cart.count()).toBe(0);
  });

  it("checkoutItems() returns the cart when no buy-now item is set", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.checkoutItems()).toEqual(cart.items);
  });

  it("buy-now checks out only that item, without touching the cart", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 2);
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

  it("clearBuyNow() falls back to checking out the cart", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.setBuyNow({ id: 2, name: "Gadget", price: 50 });

    cart.clearBuyNow();

    expect(cart.buyNowItem).toBeNull();
    expect(cart.checkoutItems()).toEqual(cart.items);
    expect(cart.checkoutTotal()).toBe(100);
  });
});
