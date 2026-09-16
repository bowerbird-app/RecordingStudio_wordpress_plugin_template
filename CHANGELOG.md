# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.4.2] - 2026-09-16

### Added
- Host Page embed renderer at `pages/embed` with seeded Getting Started HTML for the WordPress Plugin Demo BrowserPayload.
- Integration coverage that GET `:embed` for Getting Started returns real demo HTML (not Embeddable's fallback stub).

### Changed
- `Page` registers Embeddable with `renderer: "pages/embed"`.
- Runbook and dummy README document re-seed and `page_recording_id` lookup after `db:reset`.

### Upgrade notes
- Restart the dummy after upgrade so the new `pages/embed` template loads.
- Re-run `bin/rails db:seed` (or `WpPluginDemo::Seed.ensure_studio_embed!`) if embed was never enabled on Getting Started.
- After `db:reset`, look up `WpPluginDemo::Seed.getting_started_page_recording_id` again before pasting into the WordPress block.

## [0.4.1] - 2026-09-15

### Added
- `bin/build-plugin-zip` (alias of `bin/package-wordpress-zip`) runs `npm ci && npm run build`, asserts baked SDK under `build/sdk/`, and writes `pkg/recording-studio-widgets.zip`.
- Cold-start runbook at `docs/wordpress-plugin-demo-runbook.md` (dummy host → OAuth → ZIP → Settings → block).
- `WpPluginDemo::Seed.issue_runbook_connection!` / `print_runbook_connection!` and `getting_started_page_recording_id` for Settings fields and a concrete seeded page id.

### Changed
- Package-boundary ZIP build compiles plugin assets by default; `--skip-compile` skips when assets are already built (CI).
- Root README and `docs/wordpress-packaging.md` point at the one-command ZIP and the runbook.

### Upgrade notes
- Prefer `bin/build-plugin-zip` for a reproducible installable ZIP. Do not zip `src/` or `node_modules/`.
- For local WordPress testing against the dummy host, follow `docs/wordpress-plugin-demo-runbook.md`.

## [0.4.0] - 2026-09-15

### Added
- WordPress Plugin Demo client: `RecordingStudio\StudioClient` facade (token cache, host HTTP, BrowserPayload v1 parse), settings form, editor REST preview, SSR block shell, and baked SDK under `build/sdk/`.
- PHP unit tests for `BrowserPayload`, `PluginSettings`, and `StudioClient` (injectable HTTP).

### Changed
- Plugin product name and block title to **WordPress Plugin Demo** (plugin version 0.2.0).
- Block `viewScript` (`front.js`) mounts `RecordingStudioPluginSdk` from `data-rs-payload`.
- CI runs `tests/php/run.php` instead of the placeholder text check.

### Upgrade notes
- Rebuild the plugin (`npm run build`) so `build/sdk/` is present before packaging the ZIP.
- Configure **Settings → WordPress Plugin Demo** with dummy host OAuth credentials from `WpPluginDemo::Provision`.

## [0.3.1] - 2026-09-15

### Added
- Dummy host named API `wp_plugin_demo` with soft GET `:embed` (BrowserPayload schema v1).
- `WpPluginDemo::{Contract,Provision,Seed}` helpers and integration coverage for token, embed, 401, and named-vs-public isolation.
- Generated Recording Studio API, Embeddable, Publishable, and Attachable migrations for the dummy host.

### Changed
- Dummy Gemfile pins API `v0.5.5`, Embeddable `v0.2.1`, Admin `v2.0.2`, Publishable `v0.2.0`, and Attachable `v0.5.1`.
- Dummy recordable types list includes Embed, Publishable/Attachable, and API gem types required to boot under CI eager load.
- Dummy ignores Recording Studio Embeddable `lib/` on the host Zeitwerk loader so CI eager load does not expect `Version` from `version.rb`.
- Dummy clears `admin_root_recordable_type_names` so AdminApi can load without mounting AdminRoot.

### Upgrade notes
- Point dummy or host Gemfiles at the new API / Embeddable / Admin / Publishable / Attachable tags and run their migration generators.
- Re-register default resource actions and Embeddable's soft `:embed` after `config.api :wp_plugin_demo` so the named API is not empty.
- Do not mount Admin or enable Publishable on Page for this host shape.
- On hosts that pin Embeddable and use CI/`config.eager_load`, ignore Embeddable `lib/` on the main autoloader (or wait for an Embeddable fix).
- If you do not mount Admin, set `admin_root_recordable_type_names = []` and still list `RecordingStudioApi::AdminApi` when the API engine models load.

## [0.3.0] - 2026-09-14

### Added
- WordPress plugin scaffold at `wordpress/recording-studio-widgets` from `@wordpress/create-block` 4.98.0 (`--variant dynamic`, `--wp-env`).
- Placeholder RecordingStudio Widget dynamic block with the same safe text in the editor and on the published page.
- Optional Settings page placeholder. No OAuth, widget discovery, or iframe rendering.
- `bin/check-package-boundaries` builds the gem and the installable WordPress ZIP, then fails if dummy host code appears in either artifact.
- CI runs plugin JavaScript lint and build, PHP lint and WordPress coding standards, package-boundary checks, and uploads the WordPress ZIP.
- Root README documents the dummy host, `wp-env`, supported versions, packaging checks, and what this phase does not include. `docs/wordpress-packaging.md` records the artifact allowlists.

### Changed
- Product identity is RecordingStudio WordPress widgets. The Rubygems name stays `recording_studio_wordpress_plugin_template`.
- Homepage and source URLs use `https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template`.
- Dummy home page and dummy README describe the Rails host for WordPress widgets, not an internal addon template.
- Dummy initializer and dummy tests use `RecordingStudioWordpressPluginTemplate` after the rename.
- CI Postgres database name is `recording_studio_wordpress_plugin_template_test`.

### Upgrade notes
- Point homepage and source links at `bowerbird-app/RecordingStudio_wordpress_plugin_template`.
- In a host that copied the dummy initializer, call `RecordingStudioWordpressPluginTemplate.configure`.
- No widget models, OAuth, or WordPress render routes ship in this release.

## [0.2.2] - 2026-09-11

### Changed
- Gemspec `recording_studio` floor `~> 4.1` → `~> 4.2` so copied addons match Accessible 0.7+.
- Dummy Accessible tag `v0.6.0` → `v0.9.1`; FlatPack tag `v0.1.133` → `v0.1.177`. Recording Studio stays `v4.2.0`; Root Switchable stays `v0.5.0`.
- Root `Gemfile.lock` Rails `8.1.1` → `8.1.3.1` (with `json` `2.21.2`, `mail` `2.9.1`, `nokogiri` `1.19.4`) to match the dummy host lock and Active Storage CVE-2026-66066.
- Extra Cloud Agent skills now come from the plugin catalog (`skill-sources.json`) instead of a hardcoded extra URL. A missing or invalid catalog is skipped so Recording Studio skills still fetch. Failures still warn and exit 0.
- Docs and pin tests no longer mention `recording_studio/v3.0.0`, FlatPack `v0.1.133`, or Accessible `v0.6.0`.

### Added
- After skills, the Cloud Agent fetch hook lists plugin `*.mdc` rules from `RecordingStudio_cursor_plugin` into `.cursor/rules/` (gitignored, not packaged). A missing rules directory warns and skips. Failures still exit 0.
- Cloud Agent skill-fetch hook so copied addons load Recording Studio skills at Build time. `.cursor/environment.json` names the environment `recording-studio-gem-template`. `install` is `.cursor/install.sh`, which runs `.cursor/fetch-skills.sh` after provisioning. `snapshot` is omitted on purpose so Builds run install instead of reusing a laptop Personal snapshot. The script lists `recording-studio-*` skill ids from the public GitHub contents API and writes each `SKILL.md` into `.cursor/skills/` (gitignored, not packaged). Failures warn and still exit 0.
- Dummy Accessible migration for `depends_on_recording_id` (Accessible 0.8+).

### Upgrade notes
- Bump addon gemspecs to `spec.add_dependency "recording_studio", "~> 4.2"`.
- Point host/dummy Gemfiles at Accessible `v0.9.1` and FlatPack `v0.1.177` (Recording Studio tag stays `v4.2.0`).
- Run `bin/rails generate recording_studio_accessible:migrations` then `bin/rails db:migrate`. Do not hand-edit Accessible tables.
- Rebuild Tailwind after the FlatPack tag bump: `bin/rails tailwindcss:build`.
- Align root gem-suite `Gemfile.lock` Rails to `8.1.3.1` if it is still on `8.1.1`.

## [0.2.1] - 2026-09-01

### Added
- Full Cloud Agent development environment. `.cursor/install.sh` now provisions the whole stack at Build time on Cursor's default image — Ruby (pinned by `.ruby-version`), PostgreSQL 16, gem dependencies for both the gem and the dummy host app, the seeded dummy database, and compiled Tailwind/FlatPack CSS — then runs the existing `.cursor/fetch-skills.sh`. `snapshot` stays omitted so Builds run `install` as before.
- `.cursor/start.sh` per-boot hook that starts PostgreSQL and waits for readiness.
- `.cursor/environment.json` now declares `start` plus `rails-server` and `tailwind-watch` terminals and exposes port 3000, so a fresh Cloud Agent boots straight into a running, signed-in-ready dummy app.

### Notes
- The install script is idempotent; running it against a warm machine reuses the existing Ruby, packages, and gems.
- No gem runtime code changed. `.cursor/` files are excluded from the packaged gem.

## [0.2.0] - 2026-08-21

New addons copied from this template are born on Recording Studio 4.x.

### Added
- Gemspec dependency `recording_studio`, `~> 4.1`
- Dummy host wiring for Accessible (`enable_capability(:accessible, on: Workspace)`) and an opt-in `RecordingStudio::Capabilities::Example.to` mixin. `.to` wraps core 4.2.0 `include_for` (not a fourth verb, and not a raw `enable_capability` / `set_capability_options` path). Installing the gem does not enable the mixin globally; only dummy Workspace opts in.
- `bin/rename_gem` leftover-identity rewrite/verification for README, homepage, and changelog URLs that still say `RecordingStudioWordpressPluginTemplate` or point at `bowerbird-app/recording_studio_wordpress_plugin_template`

### Changed
- Dummy GitHub tags: Recording Studio `v4.2.0`, Accessible `v0.6.0`, Root Switchable `v0.5.0`, FlatPack `v0.1.133`
- Dummy authenticated layout is Recording Studio's default layout plus FlatPack CSS/JS; Devise keeps its own sign-in layout
- Dummy app security pins: Rails `8.1.3.1`, `json` `2.21.2`, `mail` `2.9.1`, Brakeman `8.0.6`
- Require `RecordingStudio::Hooks` and `RecordingStudio::Services::BaseService` from core instead of shipping copies

### Removed
- Copied `lib/recording_studio_wordpress_plugin_template/hooks.rb` and `lib/recording_studio_wordpress_plugin_template/services/base_service.rb`
- Product-shipped `ExampleService`
- Custom `flat_pack_sidebar` authenticated shell

### Upgrade notes
- Point dummy or host Gemfiles at Recording Studio `v4.2.0` (not `recording_studio/v3.0.0`)
- Add `spec.add_dependency "recording_studio", "~> 4.1"` to addon gemspecs
- Include `RecordingStudio::UsesDefaultLayout` (or set `layout "recording_studio/default_layout"`) for authenticated screens
- Delete any copied Hooks or BaseService files and require the core classes
- Keep recordable declarations; they are required, not a v3-only concern
- If Accessible is bundled, call `RecordingStudio.enable_capability(:accessible, on: Workspace)` (or your root type)

## [0.1.2] - 2026-07-21

### Changed
- Bumped the dummy app FlatPack dependency from `v0.1.33` to `v0.1.129`

## [0.1.1] - 2026-04-28

### Changed
- Bumped the dummy app FlatPack dependency from `0.1.2` to `0.1.33` and pinned it by tag in `test/dummy/Gemfile`

## [0.1.0] - 2025-12-04

### Added
- Initial release
- Rails mountable engine structure
- PostgreSQL with UUID primary keys support
- TailwindCSS v4 integration
- GitHub Codespaces devcontainer configuration
- Docker Compose setup with PostgreSQL and Redis
- Install generator for host applications
- Comprehensive README and documentation
- Basic test suite with Minitest

[Unreleased]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.4.0
[0.3.1]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.3.1
[0.3.0]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.3.0
[0.2.2]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.2.2
[0.2.1]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.2.1
[0.2.0]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.2.0
[0.1.2]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.1.2
[0.1.1]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.1.1
[0.1.0]: https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template/releases/tag/v0.1.0
