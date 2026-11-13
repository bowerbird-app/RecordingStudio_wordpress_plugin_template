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

async function colors(page, selector) {
  return page.$eval(selector, (el) => {
    const style = getComputedStyle(el);
    return { color: style.color, background: style.backgroundColor };
  });
}

function assertContrast(label, style) {
  if (!style) {
    throw new Error(`${label}: button missing`);
  }
  if (style.color === style.background) {
    throw new Error(`${label}: label ${style.color} matches fill ${style.background}`);
  }
  console.log(`${label}: label ${style.color} on ${style.background}`);
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

  for (const url of process.argv.slice(2)) {
    await page.goto(url, { waitUntil: "domcontentloaded", timeout: 20000 });
    const found = await page.locator("a.fp-button[data-fp-style], button.fp-button[data-fp-style]").count();
    if (found === 0) {
      console.log(`${url}: no Flatpack button on the page, skipped`);
      continue;
    }
    assertContrast(url, await colors(page, "a.fp-button[data-fp-style], button.fp-button[data-fp-style]"));
  }
} finally {
  await browser.close();
}
