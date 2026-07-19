import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import RatingInput from "../RatingInput.vue";

describe("RatingInput", () => {
  it("renders five star buttons", () => {
    const wrapper = mount(RatingInput, { props: { modelValue: 0 } });
    expect(wrapper.findAll("button")).toHaveLength(5);
  });

  it("emits the clicked star as the new value", async () => {
    const wrapper = mount(RatingInput, { props: { modelValue: 0 } });

    await wrapper.findAll("button")[3]!.trigger("click");

    expect(wrapper.emitted("update:modelValue")).toEqual([[4]]);
  });

  it("fills stars up to the model value", () => {
    const wrapper = mount(RatingInput, { props: { modelValue: 3 } });

    const opacities = wrapper.findAll("svg").map((svg) => svg.attributes("opacity"));
    expect(opacities).toEqual(["1", "1", "1", "0.25", "0.25"]);
  });

  it("marks the selected star as checked for assistive tech", () => {
    const wrapper = mount(RatingInput, { props: { modelValue: 2 } });

    const checked = wrapper.findAll("button").map((b) => b.attributes("aria-checked"));
    expect(checked).toEqual(["false", "true", "false", "false", "false"]);
  });
});
