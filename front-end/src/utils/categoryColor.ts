// A category's color_class is either a CSS class (the brand gradient presets)
// or a raw hex color from the admin color picker. Renderers bind both helpers:
// exactly one of them produces output for any given value.

export function categoryColorClass(
  value: string | null | undefined,
  fallback = "bg-gray-300"
): string {
  if (!value) return fallback;
  return value.startsWith("#") ? "" : value;
}

export function categoryColorStyle(
  value: string | null | undefined
): Record<string, string> | undefined {
  return value?.startsWith("#") ? { background: value } : undefined;
}
