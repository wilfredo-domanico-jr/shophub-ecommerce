import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import StarRating from "../StarRating.vue";

function countStars(rating: number) {
  const wrapper = mount(StarRating, { props: { rating } });
  const stars = wrapper.findAll("svg");
  const full = stars.filter((s) => s.attributes("opacity") === "1").length;
  const half = stars.filter((s) => s.attributes("opacity") === "0.5").length;
  const empty = stars.filter((s) => s.attributes("opacity") === "0.25").length;
  return { total: stars.length, full, half, empty };
}

describe("StarRating", () => {
  it("renders 5 stars total regardless of rating", () => {
    expect(countStars(3.5).total).toBe(5);
    expect(countStars(0).total).toBe(5);
    expect(countStars(5).total).toBe(5);
  });

  it("renders full stars for a whole number rating", () => {
    const { full, half, empty } = countStars(4);
    expect(full).toBe(4);
    expect(half).toBe(0);
    expect(empty).toBe(1);
  });

  it("renders a half star for a fractional rating", () => {
    const { full, half, empty } = countStars(3.5);
    expect(full).toBe(3);
    expect(half).toBe(1);
    expect(empty).toBe(1);
  });

  it("renders all empty stars for a zero rating, visually distinct from full", () => {
    const { full, half, empty } = countStars(0);
    expect(full).toBe(0);
    expect(half).toBe(0);
    expect(empty).toBe(5);
  });
});
