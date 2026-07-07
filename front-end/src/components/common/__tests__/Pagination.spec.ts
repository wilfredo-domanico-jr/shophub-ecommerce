import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import Pagination from "../Pagination.vue";

describe("Pagination", () => {
  it("does not render when there is a single page and no results", () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, lastPage: 1, total: 0, from: null, to: null },
    });

    expect(wrapper.find("p").exists()).toBe(false);
  });

  it("renders the results summary when there is at least one result", () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, lastPage: 1, total: 5, from: 1, to: 5 },
    });

    expect(wrapper.text()).toContain("Showing");
    expect(wrapper.text()).toContain("5");
  });

  it("disables Prev on the first page and Next on the last page", () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 1, lastPage: 3, total: 30, from: 1, to: 10 },
    });

    const buttons = wrapper.findAll("button");
    const prev = buttons.find((b) => b.text() === "Prev");
    const next = buttons.find((b) => b.text() === "Next");

    expect(prev?.attributes("disabled")).toBeDefined();
    expect(next?.attributes("disabled")).toBeUndefined();
  });

  it("emits change with the correct page when a page button is clicked", async () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 2, lastPage: 5, total: 50, from: 11, to: 20 },
    });

    const buttons = wrapper.findAll("button");
    const pageThree = buttons.find((b) => b.text() === "3");
    await pageThree?.trigger("click");

    expect(wrapper.emitted("change")).toEqual([[3]]);
  });

  it("emits change with currentPage - 1 when Prev is clicked", async () => {
    const wrapper = mount(Pagination, {
      props: { currentPage: 3, lastPage: 5, total: 50, from: 21, to: 30 },
    });

    const prev = wrapper.findAll("button").find((b) => b.text() === "Prev");
    await prev?.trigger("click");

    expect(wrapper.emitted("change")).toEqual([[2]]);
  });
});
