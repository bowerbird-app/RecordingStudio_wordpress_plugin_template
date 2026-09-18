# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.4.16] - 2026-09-18

### Changed
- WordPress Plugin Demo Settings splits Connect from Advanced. Connect uses host plus the top **OAuth client id**. Advanced is a dropdown labeled **Connect via API key** with its own **API key**, **Secret key**, and optional token URL override. **Save settings** and **Test connection** come after that dropdown. Test connection still probes Advanced client credentials.
- StudioClient Advanced token requests send the Advanced API key, not the Connect OAuth client id. A blank top OAuth client id is valid for Advanced-only. A blank Advanced API key is valid for Connect-only.
- Existing Advanced installs that stored only `client_id` and `client_secret` still work. If `api_key` is empty and a secret is stored, reads treat `client_id` as the Advanced API key. An empty Secret key on save still clears the stored secret.

### Upgrade notes
- Rebuild and reinstall the plugin ZIP (`bin/build-plugin-zip` or `npm run build` under `wordpress/recording-studio-widgets`).
- Connect still needs host plus the public Registered App id. Advanced now has its own `api_key` option. You do not have to re-enter keys. The next Settings save writes the fallback value into `api_key` if the field shows it.
- Dummy host, Oauth seeds, and named API paths are unchanged.

## [0.4.15] - 2026-09-18

### Changed
- Dummy host pins `recording_studio_oauth` v0.2.2 and `recording_studio_api` v0.5.6. Connect consent stays a full page. A public Registered App can finish `authorization_code` on the named token URL and use that bearer on named resource paths.

### Upgrade notes
- Dummy-only. Point Oauth at GitHub tag `v0.2.2` and API at `v0.5.6`. From `test/dummy` run `bundle update recording_studio_oauth recording_studio_api`. Keep the seeded WordPress Plugin Demo client. A handmade public client (`api_key=public`) can Connect and call named `wp_plugin_demo` resources. No WordPress plugin ZIP rebuild. No new Oauth clients.

## [0.4.14] - 2026-09-18

### Fixed
- Connect to Recording Studio and Connect again now show a short leaving-WordPress page before the host authorize URL. Settings keep the `connected` return notice and always show a success banner from Connect status, so a missed `rs_notice` still reads as connected.
- A failed Connect refresh no longer silently swaps in Advanced `client_credentials`. The block shows the Connect reconnect error instead of **Host rejected the OAuth client credentials.**
- Saving Settings with an empty Advanced client secret now clears the stored secret. A leftover public-client secret no longer sticks after a blank submit.

### Upgrade notes
- Rebuild and reinstall the plugin ZIP (`bin/build-plugin-zip` or `npm run build` under `wordpress/recording-studio-widgets`). Connect still uses the same PKCE path and host allowlist. If a leftover Advanced secret is still stored, submit Advanced with the secret field empty to clear it. PKCE and token storage are unchanged.

## [0.4.13] - 2026-09-18

### Fixed
- Connect to Recording Studio now allowlists the configured host origin before `wp_safe_redirect`. An external host such as a Cloudflare tunnel reaches its authorize URL instead of falling back to `/wp-admin/`.

### Upgrade notes
- Rebuild and reinstall the plugin ZIP (`bin/build-plugin-zip` or `npm run build` under `wordpress/recording-studio-widgets`) so Settings Connect can leave wp-admin for the configured host. No named API or dummy host changes.

### Follow-up
- Clearing and prefilling the Advanced client secret stays later work.

## [0.4.12] - 2026-09-18

### Added
- Dummy sidebar **Registered Apps** item linking to Oauth Admin registered apps (`/admin/screens/oauth_clients`) so the Connect client id is findable without a Rails runner.

### Upgrade notes
- Dummy-only. Sign in and open sidebar **Registered Apps** for Oauth clients. The screen still requires the Admin root. Switch the root switcher to **Admin** if the link returns 403. API Keys and Pages stay in the sidebar. No WordPress plugin, Connect, PKCE, or named API changes.

## [0.4.11] - 2026-09-18

### Added
- Dummy host mounts Recording Studio Users auth chrome at `/users/sign_in`. The first screen is email only. Password is the second screen.
- Dummy Gemfile pins `recording_studio_user` `v0.11.0`. Seed records Avery Admin's Profile under the shared People root with Accessible owner access.
- Dummy seeds a public Oauth client named **WordPress Plugin Demo** (`confidential: false`, `api_key: wp_plugin_demo`) with exact wp-admin admin-post redirect URIs for `localhost:8888` and `127.0.0.1:8888`. `WpPluginDemo::Seed.print_connect_client!` prints host, public client id, authorize URL, named token URL, callback, and page id. No client secret.
- WordPress Plugin Demo Settings → Connect starts PKCE S256 authorize, stores Connect tokens on the server, and uses them for embed. Advanced keeps API keys and `print_runbook_connection!` as the client_credentials fallback.
- Connected Settings show **This site is connected.**, **Disconnect**, and **Connect again**. Connect again starts PKCE and leaves the current tokens in place until finish succeeds.
- Host token and authorize errors (`invalid_client`, `redirect_uri_mismatch`, `invalid_redirect_uri`, `invalid_grant`, `access_denied`) map to short Settings notices.
- The block editor lists pages from `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages` so you can pick a title or paste a UUID. Dummy named API Page operations include `index`.

### Upgrade notes
- Dummy-only Users install is unchanged: run `bin/rails generate recording_studio_user:install` and `bin/rails generate recording_studio_user:migrations` from `test/dummy`. Register `RecordingStudioUser::People` and `RecordingStudioUser::Profile`. Skip Devise sessions, registrations, and passwords. Add `recording_studio_user_auth_for :users`. Re-seed so the admin Profile and the public WordPress Plugin Demo Oauth client exist.
- WordPress Settings now prefer Connect (host URL + public client id, then **Connect to Recording Studio**). When the site is already connected, use **Connect again** without disconnecting first. A failed reconnect keeps the previous tokens. Add any other WordPress origin as an exact redirect URI on the public client in Oauth Admin Registered apps. Keep API keys under Advanced.
- Connect and Advanced both POST `/recording_studio_api/apis/wp_plugin_demo/oauth/token`. Grant type is `authorization_code` (PKCE) or `client_credentials`. The discovery route `/recording_studio_api/oauth/token` stays unused for this named client.
- Rebuild plugin assets (`npm run build` in `wordpress/recording-studio-widgets`) so the block shows the page picker. Page index is `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages`.

## [0.4.10] - 2026-09-16

### Fixed
- WordPress Plugin Demo block no longer ships create-block scaffold chrome (`#21759b` background and white text) on the block wrapper. Removed the block `style` handle, `style.scss`, and unused `editor.scss` so editor and front pass through host embed HTML/CSS as-is.

### Upgrade notes
- Rebuild and reinstall the plugin ZIP (`bin/build-plugin-zip` or `npm run build` under `wordpress/recording-studio-widgets`) so editor and front stop enqueueing the old block stylesheet. Dummy Pages embed preview is unchanged. No named API or BrowserPayload changes.

## [0.4.9] - 2026-09-16

### Fixed
- Dummy Pages show (`/pages/:id`) keeps host sidebar chrome only. Main content is the embed preview iframe. Removed the duplicate PageNav back control, PageTitle, and marketing subtitle.

### Upgrade notes
- Dummy-only. Open a page from `/pages` to see the iframe-only show screen. Use the sidebar **Pages** item to return to the list. No WordPress plugin or named API changes.

## [0.4.8] - 2026-09-16

### Added
- Dummy FlatPack sidebar **API Keys** item linking to Recording Studio API clients (`/recording_studio_api/api_clients`) for minting WordPress client credentials.
- Dummy **Pages** browser (`/pages`, `/pages/:id`) with a FlatPack table of untrashed Page recordings and a host show screen that iframes an isolated WordPress embed preview (`/pages/:id/embed_preview`).

### Changed
- Dummy sidebar hides **Registered apps**. Oauth Admin routes and screens at `/admin/screens/oauth_clients` stay available; they are no longer a sidebar destination.
- Dummy sidebar order is Home, Recordings tree, API Keys, Pages.

### Upgrade notes
- Dummy-only. Sign in and use sidebar **API Keys** for API client credentials and **Pages** for page ids plus the embed preview. Oauth Admin remains at `/admin/screens/oauth_clients` without a sidebar link. No WordPress plugin or named API changes.

## [0.4.7] - 2026-09-16

### Fixed
- Dummy `application.js` imports `@hotwired/turbo-rails` so Admin lazy `turbo-frame` tables (Registered apps) load instead of staying on skeletons forever.

### Upgrade notes
- Dummy-only. Restart the dummy (or hard-refresh) so the new importmap entry for Turbo boots. No WordPress plugin or named API changes.

## [0.4.6] - 2026-09-16

### Changed
- Dummy host replaces the custom `/plugin_credentials` mint screen with Recording Studio Oauth Admin (**Registered apps** in the FlatPack sidebar, `/admin/screens/oauth_clients`).
- Dummy pins `recording_studio_oauth` v0.2.0 and `recording_studio_site_settings` v0.1.0; seeds Admin root, Accessible grants, and optional Seed Demo App OAuth client.

### Removed
- Dummy route, controller, views, and integration tests for `plugin_credentials`.

### Upgrade notes
- Dummy-only. Sign in and use sidebar **Registered apps** for OAuth client management. Console runners (`WpPluginDemo::Seed.print_runbook_connection!`, `WpPluginDemo::Provision.isolated_client!`) still provision `wp_plugin_demo` API credentials for WordPress.

## [0.4.5] - 2026-09-16

### Fixed
- Dummy layouts load `flat_pack/application` so FlatPack's `.fp-skip-link` hide-until-focus rules apply. The Skip to content control stays keyboard-focusable and no longer overlaps the sidebar brand at rest.

### Upgrade notes
- Dummy-only. Restart the dummy (or hard-refresh) so the new stylesheet link loads. No WordPress plugin or named API changes.

## [0.4.4] - 2026-09-16

### Added
- Dummy host FlatPack sidebar shell with Home, Recordings tree, and Plugin credentials links.
- Signed-in Plugin credentials page at `/plugin_credentials` that shows host URL, Getting Started page id, and mint/regenerate for `wp_plugin_demo` OAuth client credentials (secret reveal-once via flash).
- `WpPluginDemo::Seed.present_runbook_connection` for admin-screen display without leaking secrets on ordinary GETs.

### Changed
- Authenticated dummy pages use `layouts/host` (`FlatPack::SidebarLayout`) instead of Recording Studio's sidebar-free default layout. Devise sign-in still uses `layouts/application`.

### Upgrade notes
- Dummy-only. Restart the dummy and sign in to see the sidebar. Existing WordPress plugin Settings and named API paths are unchanged.
- Prefer `/plugin_credentials` for handing OAuth fields to WordPress; `print_runbook_connection!` still works from the console.

## [0.4.3] - 2026-09-16

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

## [0.4.2] - 2026-09-16

### Fixed
- Dummy Tailwind `@source` paths now scan FlatPack and Recording Studio through `vendor/flat_pack` and `vendor/recording_studio` symlinks (from `recording_studio_root_switchable:link_tailwind_sources`), so Cloud Agent / Cloudflare tunnel CSS includes FlatPack utilities instead of a near-empty build.
- CI and dummy rake explicitly link Tailwind gem sources before `tailwindcss:build` (Root Switchable's enhance can miss when load order skips it).

### Changed
- Cloud Agent `install.sh` / `start.sh` link Tailwind gem sources before building or watching CSS.
- `.gitignore` ignores the machine-local `vendor/flat_pack`, `vendor/recording_studio`, and `vendor/recording_studio_root_switchable` symlinks.

### Upgrade notes
- In the dummy (or any host using the same layout), run:
  `bin/rails recording_studio_root_switchable:link_tailwind_sources && bin/rails tailwindcss:build`
- Point Tailwind `@source` at `vendor/flat_pack/...` and `vendor/recording_studio/...` (not only `vendor/bundle/**` or `/usr/local/bundle/ruby/**`).

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
