import { spawnSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
const playwrightSpec = process.env.PLAYWRIGHT_CORE || "playwright-core";
const playwright = await import(
  playwrightSpec.startsWith("/") ? pathToFileURL(playwrightSpec).href : playwrightSpec
);
const chromium = playwright.chromium || playwright.default?.chromium;

const root = dirname(fileURLToPath(import.meta.url));
const css = readFileSync(
  join(root, "../wordpress/recording-studio-widgets/assets/admin/embed-contrast.css"),
  "utf8"
);

const theme = `
.wp-block button,
.wp-block a,
.editor-styles-wrapper .wp-block a,
.wp-site-blocks .wp-block-post-content a {
  color: rgb(51, 51, 51);
  background-color: rgb(51, 51, 51);
}
`;

const button = `
<a class="fp-button" data-fp-style="primary" href="#getting-started-connect"
   style="--fp-button-text:#fff;--fp-button-background:rgb(51, 51, 51)">Connect</a>
`;

const fixture = `<!DOCTYPE html>
<html>
  <head>
    <style>${theme}</style>
    <style>${css}</style>
  </head>
  <body>
    <div class="wp-site-blocks">
      <div class="wp-block-post-content">
        <div class="wp-block" data-rs-widget>
          <article data-wordpress-plugin-demo-embed="page" data-theme="rounded">${button}</article>
        </div>
      </div>
    </div>
    <div class="editor-styles-wrapper">
      <div class="wp-block rs-wordpress-plugin-demo-editor-preview">${button}</div>
    </div>
  </body>
</html>`;

function chromeExecutable() {
  for (const name of ["google-chrome", "google-chrome-stable", "chromium", "chromium-browser"]) {
    const found = spawnSync("bash", ["-lc", `command -v ${name}`], { encoding: "utf8" });
    const path = (found.stdout || "").trim();
    if (found.status === 0 && path) return path;
  }
  return null;
}

const MINIMUM_CONTRAST = 4.5;

async function colors(page, selector) {
  const count = await page.locator(selector).count();
  if (count === 0) {
    throw new Error(`${selector}: button missing`);
  }
  return page.$eval(selector, (el) => {
    const toRgba = (color) => {
      const canvas = document.createElement("canvas");
      canvas.width = 1;
      canvas.height = 1;
      const context = canvas.getContext("2d", { willReadFrequently: true });
      context.clearRect(0, 0, 1, 1);
      context.fillStyle = "#fff";
      context.fillRect(0, 0, 1, 1);
      context.clearRect(0, 0, 1, 1);
      context.fillStyle = color;
      context.fillRect(0, 0, 1, 1);
      const [red, green, blue, alpha] = context.getImageData(0, 0, 1, 1).data;
      return { red, green, blue, alpha: alpha / 255 };
    };
    const over = (base, color) => {
      const alpha = color.alpha;
      return {
        red: (color.red * alpha) + (base.red * (1 - alpha)),
        green: (color.green * alpha) + (base.green * (1 - alpha)),
        blue: (color.blue * alpha) + (base.blue * (1 - alpha)),
        alpha: alpha + (base.alpha * (1 - alpha)),
      };
    };
    const layers = [];
    let node = el;
    while (node) {
      const layer = toRgba(getComputedStyle(node).backgroundColor);
      layers.push(layer);
      if (layer.alpha >= 1) break;
      node = node.parentElement;
    }
    let background = { red: 255, green: 255, blue: 255, alpha: 1 };
    for (let index = layers.length - 1; index >= 0; index -= 1) {
      background = over(background, layers[index]);
    }
    if (background.alpha < 1) {
      throw new Error("button background is still transparent");
    }
    const style = getComputedStyle(el);
    return {
      foreground: toRgba(style.color),
      background,
      color: style.color,
      backgroundColor: style.backgroundColor,
    };
  });
}

function channel(value) {
  const share = value / 255;
  return share <= 0.04045 ? share / 12.92 : ((share + 0.055) / 1.055) ** 2.4;
}

function luminance(color) {
  return (0.2126 * channel(color.red)) + (0.7152 * channel(color.green)) + (0.0722 * channel(color.blue));
}

function contrastRatio(sample) {
  const alpha = sample.foreground.alpha;
  const blended = {
    red: (sample.foreground.red * alpha) + (sample.background.red * (1 - alpha)),
    green: (sample.foreground.green * alpha) + (sample.background.green * (1 - alpha)),
    blue: (sample.foreground.blue * alpha) + (sample.background.blue * (1 - alpha)),
  };
  const lighter = Math.max(luminance(blended), luminance(sample.background));
  const darker = Math.min(luminance(blended), luminance(sample.background));
  return (lighter + 0.05) / (darker + 0.05);
}

function assertContrast(label, sample) {
  const ratio = contrastRatio(sample);
  if (ratio < MINIMUM_CONTRAST) {
    throw new Error(
      `${label}: contrast ${ratio.toFixed(2)} is below ${MINIMUM_CONTRAST}:1 (${sample.color} on ${sample.backgroundColor})`
    );
  }
  console.log(`${label}: contrast ${ratio.toFixed(2)}:1 (${sample.color} on ${sample.backgroundColor})`);
}

const executablePath = chromeExecutable();
if (!executablePath) {
  console.error("Headless contrast check needs google-chrome or chromium");
  process.exit(1);
}

const browser = await chromium.launch({ executablePath, headless: true });
const page = await browser.newPage();

try {
  await page.setContent(fixture, { waitUntil: "domcontentloaded" });
  assertContrast(
    "front-end fixture",
    await colors(page, "[data-rs-widget] a.fp-button[data-fp-style]")
  );
  assertContrast(
    "editor fixture",
    await colors(page, ".editor-styles-wrapper a.fp-button[data-fp-style]")
  );

  await page.setContent(`<!DOCTYPE html>
<html>
  <body style="background:#fff">
    <a class="fp-button" data-fp-style="primary" style="color:rgb(51, 51, 51);background-color:transparent">Connect</a>
  </body>
</html>`, { waitUntil: "domcontentloaded" });
  assertContrast("transparent fill over the page", await colors(page, "a.fp-button"));

  const args = process.argv.slice(2);
  const targets = [];
  for (let index = 0; index < args.length; index += 1) {
    const flag = args[index];
    const url = args[index + 1];
    if (!url || url.startsWith("--")) {
      throw new Error(`${flag} needs a page URL`);
    }
    if (flag === "--fixture") {
      targets.push({ label: "button-contrast fixture", url });
    } else if (flag === "--embed") {
      targets.push({ label: "studio widget embed", url });
    } else {
      throw new Error(`Unknown contrast argument ${flag}`);
    }
    index += 1;
  }
  if (targets.length === 0) {
    throw new Error("Pass --fixture and --embed page URLs");
  }

  for (const target of targets) {
    await page.goto(target.url, { waitUntil: "domcontentloaded", timeout: 20000 });
    const selector = "a.fp-button[data-fp-style], button.fp-button[data-fp-style]";
    if ((await page.locator(selector).count()) === 0) {
      throw new Error(`${target.label} has no .fp-button\n${await page.content()}`);
    }
    assertContrast(target.label, await colors(page, selector));
  }
} finally {
  await browser.close();
}
