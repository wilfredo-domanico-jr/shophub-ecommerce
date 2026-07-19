// Regenerates src/data/categoryIcons.ts from the heroicons package (MIT).
// Run from front-end/:  node scripts/generate-category-icons.mjs
import { readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const SRC = join(process.cwd(), "node_modules", "heroicons", "24", "outline");
const OUT = join(process.cwd(), "src", "data", "categoryIcons.ts");

// UI/navigation icons that make poor category icons.
const EXCLUDE_PREFIXES = [
  "arrow", "chevron", "bars-", "ellipsis", "x-mark", "x-circle", "plus", "minus",
  "check", "chat-bubble-oval", "arrows-", "cursor", "backward", "forward",
  "arrow-uturn", "no-symbol", "queue-list", "numbered-list", "list-bullet",
  "adjustments", "funnel", "magnifying-glass-minus", "magnifying-glass-plus",
  "exclamation", "question-mark", "information-circle", "hand-raised",
  "hand-thumb-down", "eye-slash", "bell-slash", "signal-slash", "wifi-slash",
  "speaker-x", "video-camera-slash", "phone-x", "phone-arrow", "archive-box-x",
  "document-arrow", "arrow-down", "arrow-up", "arrow-left", "arrow-right",
  "bars", "battery-0", "battery-50",
];

const files = readdirSync(SRC).filter((f) => f.endsWith(".svg"));

const icons = [];
for (const file of files) {
  const name = file.replace(/\.svg$/, "");
  if (EXCLUDE_PREFIXES.some((p) => name.startsWith(p))) continue;

  const svg = readFileSync(join(SRC, file), "utf8");
  const ds = [...svg.matchAll(/\sd="([^"]+)"/g)].map((m) => m[1]);
  if (!ds.length) continue;

  const label = name
    .split("-")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");

  // Multiple paths are merged: an SVG `d` attribute may contain several
  // M... subpaths, so one <path> element can render them all.
  icons.push({ label, icon: ds.join(" ") });
}

icons.sort((a, b) => a.label.localeCompare(b.label));

const body = icons
  .map((i) => `  { label: ${JSON.stringify(i.label)}, icon: ${JSON.stringify(i.icon)} },`)
  .join("\n");

writeFileSync(
  OUT,
  `// Generated from heroicons 24/outline (MIT) — see scripts note in the repo docs.
// Each entry is the icon's SVG path data, drawn with stroke on a 24x24 viewBox.

export interface CategoryIcon {
  label: string;
  icon: string;
}

export const CATEGORY_ICONS: CategoryIcon[] = [
${body}
];
`,
  "utf8"
);

console.log(`Wrote ${icons.length} icons to ${OUT}`);
