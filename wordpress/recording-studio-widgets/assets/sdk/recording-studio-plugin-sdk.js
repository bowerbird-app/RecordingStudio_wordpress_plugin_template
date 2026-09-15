"use strict";
var RecordingStudioPluginSdk = (() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);
  var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

  // src/index.ts
  var index_exports = {};
  __export(index_exports, {
    SCHEMA_VERSION: () => SCHEMA_VERSION,
    SDK_VERSION: () => SDK_VERSION,
    destroy: () => destroy,
    mount: () => mount,
    refresh: () => refresh
  });

  // src/compareSemver.ts
  function parseSemver(value) {
    const match = /^(\d+)\.(\d+)\.(\d+)$/.exec(value.trim());
    if (!match) {
      return null;
    }
    return {
      major: Number(match[1]),
      minor: Number(match[2]),
      patch: Number(match[3])
    };
  }
  function compareSemver(left, right) {
    const a = parseSemver(left);
    const b = parseSemver(right);
    if (!a || !b) {
      return null;
    }
    if (a.major !== b.major) {
      return a.major - b.major;
    }
    if (a.minor !== b.minor) {
      return a.minor - b.minor;
    }
    return a.patch - b.patch;
  }
  function meetsMinimum(sdkVersion, minimumVersion) {
    const result = compareSemver(sdkVersion, minimumVersion);
    return result !== null && result >= 0;
  }

  // src/types.ts
  var SCHEMA_VERSION = 1;

  // src/parsePayload.ts
  function isPlainObject(value) {
    return typeof value === "object" && value !== null && !Array.isArray(value);
  }
  function invalid(message) {
    return { ok: false, reason: "invalid_payload", message };
  }
  function parsePayload(input) {
    if (!isPlainObject(input)) {
      return invalid("Payload must be an object.");
    }
    if (!("schema_version" in input)) {
      return invalid("schema_version is required.");
    }
    if (typeof input.schema_version !== "number" || !Number.isInteger(input.schema_version)) {
      return invalid("schema_version must be an integer.");
    }
    if (input.schema_version !== SCHEMA_VERSION) {
      return {
        ok: false,
        reason: "unknown_schema_version",
        message: `Unsupported schema_version ${input.schema_version}.`
      };
    }
    if (typeof input.html !== "string") {
      return invalid("html must be a string.");
    }
    if (!isPlainObject(input.configuration)) {
      return invalid("configuration must be an object.");
    }
    if (!isPlainObject(input.sdk)) {
      return invalid("sdk must be an object.");
    }
    if (typeof input.sdk.minimum_version !== "string") {
      return invalid("sdk.minimum_version must be a string.");
    }
    if (!parseSemver(input.sdk.minimum_version)) {
      return invalid("sdk.minimum_version must be a major.minor.patch version.");
    }
    const payload = {
      schema_version: SCHEMA_VERSION,
      html: input.html,
      configuration: input.configuration,
      sdk: {
        minimum_version: input.sdk.minimum_version
      }
    };
    return { ok: true, payload };
  }

  // src/registry.ts
  var instances = /* @__PURE__ */ new WeakMap();
  function getInstance(element) {
    return instances.get(element);
  }
  function setInstance(element, instance) {
    instances.set(element, instance);
  }
  function deleteInstance(element) {
    instances.delete(element);
  }

  // src/sanitizeHtml.ts
  function stripUnsafe(root) {
    root.querySelectorAll("script").forEach((element) => {
      element.remove();
    });
    const nodes = [root, ...root.querySelectorAll("*")];
    for (const element of nodes) {
      for (const attribute of [...element.attributes]) {
        const name = attribute.name.toLowerCase();
        if (name.startsWith("on")) {
          element.removeAttribute(attribute.name);
          continue;
        }
        if ((name === "href" || name === "src") && /^\s*javascript:/i.test(attribute.value)) {
          element.removeAttribute(attribute.name);
        }
      }
    }
  }
  function sanitizeHtml(html, documentRef) {
    const view = documentRef.defaultView;
    if (!view) {
      throw new Error("Document is not attached to a window.");
    }
    const parser = new view.DOMParser();
    const parsed = parser.parseFromString(`<div id="rs-sanitize-root">${html}</div>`, "text/html");
    const container = parsed.getElementById("rs-sanitize-root");
    const fragment = documentRef.createDocumentFragment();
    if (!container) {
      return fragment;
    }
    stripUnsafe(container);
    for (const child of [...container.childNodes]) {
      fragment.appendChild(documentRef.importNode(child, true));
    }
    return fragment;
  }
  function fragmentIsEmpty(fragment) {
    if (!fragment.hasChildNodes()) {
      return true;
    }
    return [...fragment.childNodes].every((node) => {
      return node.nodeType === Node.TEXT_NODE && !node.textContent?.trim();
    });
  }

  // src/render.ts
  function ensureWidgetRoot(element) {
    if (element.classList.contains("rs-widget")) {
      return element;
    }
    const existing = element.querySelector(":scope > .rs-widget");
    if (existing instanceof HTMLElement) {
      return existing;
    }
    const root = element.ownerDocument.createElement("div");
    root.className = "rs-widget";
    element.appendChild(root);
    return root;
  }
  function statusCopy(state) {
    switch (state) {
      case "loading":
        return "One moment.";
      case "empty":
        return "Nothing to show yet.";
      case "error":
        return "This widget could not load.";
      case "incompatible_version":
        return "This widget needs a newer plugin SDK.";
      default: {
        const _exhaustive = state;
        return _exhaustive;
      }
    }
  }
  function renderState(root, state) {
    root.dataset.rsState = state;
    root.replaceChildren();
    const status = root.ownerDocument.createElement("p");
    status.className = "rs-widget__status";
    status.textContent = statusCopy(state);
    root.appendChild(status);
  }
  function renderHtml(root, html) {
    const fragment = sanitizeHtml(html, root.ownerDocument);
    if (fragmentIsEmpty(fragment)) {
      renderState(root, "empty");
      return "empty";
    }
    root.dataset.rsState = "ready";
    root.replaceChildren(fragment);
    return "ready";
  }
  function clearWidget(root) {
    delete root.dataset.rsState;
    root.replaceChildren();
  }

  // src/version.ts
  var SDK_VERSION = "0.3.0";

  // src/instance.ts
  var WidgetInstance = class {
    constructor(element) {
      __publicField(this, "element");
      __publicField(this, "root");
      __publicField(this, "state", "loading");
      this.element = element;
      this.root = ensureWidgetRoot(element);
    }
    apply(payload) {
      this.state = "loading";
      renderState(this.root, "loading");
      const parsed = parsePayload(payload);
      if (!parsed.ok) {
        this.state = "error";
        renderState(this.root, "error");
        return;
      }
      if (!meetsMinimum(SDK_VERSION, parsed.payload.sdk.minimum_version)) {
        this.state = "incompatible_version";
        renderState(this.root, "incompatible_version");
        return;
      }
      this.state = renderHtml(this.root, parsed.payload.html);
    }
    destroy() {
      clearWidget(this.root);
      if (this.root !== this.element) {
        this.root.remove();
      }
      deleteInstance(this.element);
    }
  };
  function requireElement(element) {
    if (!(element instanceof HTMLElement)) {
      throw new TypeError("An HTMLElement is required.");
    }
    return element;
  }
  function mount(element, payload) {
    const host = requireElement(element);
    const existing = getInstance(host);
    if (existing) {
      existing.apply(payload);
      return handleFor(existing);
    }
    const instance = new WidgetInstance(host);
    setInstance(host, instance);
    instance.apply(payload);
    return handleFor(instance);
  }
  function refresh(element, payload) {
    const host = requireElement(element);
    const existing = getInstance(host);
    if (existing) {
      existing.apply(payload);
      return;
    }
    mount(host, payload);
  }
  function destroy(element) {
    const host = requireElement(element);
    getInstance(host)?.destroy();
  }
  function handleFor(instance) {
    return {
      refresh(payload) {
        instance.apply(payload);
      },
      destroy() {
        instance.destroy();
      }
    };
  }
  return __toCommonJS(index_exports);
})();
