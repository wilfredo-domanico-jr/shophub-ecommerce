import { describe, it, expect, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useCartStore } from "../cart";

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
    expect(cart.items[0]).toMatchObject({ id: 1, name: "Widget", price: 100, quantity: 1 });
  });

  it("adding the same product again increments its quantity instead of duplicating", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0].quantity).toBe(2);
  });

  it("supports adding a specific quantity", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 }, 3);

    expect(cart.items[0].quantity).toBe(3);
  });

  it("removes an item by id", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });
    cart.addItem({ id: 2, name: "Gadget", price: 50 });

    cart.removeItem(1);

    expect(cart.items).toHaveLength(1);
    expect(cart.items[0].id).toBe(2);
  });

  it("updates quantity but never below 1", () => {
    const cart = useCartStore();
    cart.addItem({ id: 1, name: "Widget", price: 100 });

    cart.updateQuantity(1, 5);
    expect(cart.items[0].quantity).toBe(5);

    cart.updateQuantity(1, 0);
    expect(cart.items[0].quantity).toBe(1);
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
