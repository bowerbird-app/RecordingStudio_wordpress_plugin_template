# RecordingStudio WordPress widgets

This repo is the RecordingStudio WordPress widgets addon. The Rubygems name stays `recording_studio_wordpress_plugin_template`.

Homepage: [github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template](https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template)

Two packages live here. The Rails dummy host proves the engine. The WordPress plugin is a separate installable block. They do not share a process. They do not share an origin.

## Architecture

The dummy app in `test/dummy/` is a Rails 8.1 host. It mounts Recording Studio, signs in with Devise, and renders FlatPack. It boots with no WordPress process. Connectivity placeholders are env-only. The dummy app has no widget models, widget APIs, or WordPress render routes.

The plugin in `wordpress/recording-studio-widgets/` is a dynamic block named **WordPress Plugin Demo**. It stores OAuth client credentials server-side, fetches BrowserPayload v1 JSON from the dummy host named API `wp_plugin_demo`, server-renders the payload, and mounts the baked plugin SDK on the front. `wp-env` serves WordPress on port 8888. The dummy host serves Rails on port 3000.

### Phase 4 (WordPress Plugin Demo client)

- **Settings → WordPress Plugin Demo**: host base URL, OAuth client id/secret, optional token URL override, and **Test connection**.
- **Block**: `pageRecordingId` attribute (UUID). Editor preview uses `GET /wp-json/recording-studio/v1/preview/{uuid}` (`edit_posts`).
- **Front**: SSR `data-rs-payload` plus `viewScript` (`front.js`) calling `window.RecordingStudioPluginSdk.mount` — no secrets in the page.
- **SDK**: committed under `assets/sdk/`, copied to `build/sdk/` on `npm run build`.
- Provision OAuth credentials on the dummy host with `WpPluginDemo::Seed.print_runbook_connection!` or `WpPluginDemo::Provision.isolated_client!` (see `test/dummy/README.md`).

Cold start (clone → ZIP → working block): [docs/wordpress-plugin-demo-runbook.md](docs/wordpress-plugin-demo-runbook.md).

`docs/gem_template/` stays as architectural reference for the engine conventions. `docs/wordpress-packaging.md` describes the gem and ZIP allowlists. This README is the product guide.

## Start the Rails dummy

From `test/dummy/`:

```bash
bundle install
bin/rails db:setup
bin/dev
```

Open http://localhost:3000 and sign in at `/users/sign_in`.

A Cloud Agent already starts PostgreSQL and the dummy server from `.cursor/`. Open port 3000. No extra environment variables are required. The dummy `database.yml` defaults match the provisioned PostgreSQL cluster.

### Login credentials

| Field    | Value             |
|----------|-------------------|
| Email    | admin@admin.com   |
| Password | Password          |

The login form is prefilled with these credentials.

### Useful dummy routes

- `/` is the dummy app home page
- `/users/sign_in` is the Devise sign-in page
- `/recording_studio` redirects to `/` while the mounted Recording Studio engine remains data and API focused
- `/` home, `/docs/recordings_tree`, and `/admin/screens/oauth_clients` (**Registered apps**, signed-in) are the dummy sidebar destinations
- `/docs/install`, `/docs/config`, `/docs/recordable_types`, `/docs/recordings_tree`, `/docs/gem_views`, `/docs/methods` are dummy-only starter pages

## Start WordPress with wp-env

`wp-env` needs Docker. From `wordpress/recording-studio-widgets/`:

```bash
npm install
npm run build
npm run env start
```

Open http://localhost:8888. The tests site uses port 8889.

If Docker is not available, skip `npm run env start`. You can still build the plugin, lint PHP, and check the ZIP.

Stop WordPress with `npm run env stop`.

## Supported versions

These versions come from the gemspec, the dummy Gemfile, `@wordpress/create-block` 4.98.0, and `@wordpress/env` 11.15.0 defaults.

| Component | Version |
|-----------|---------|
| Ruby | 3.3+ |
| Rails | 8.1+ |
| PostgreSQL | 16 |
| Node | 22 (CI) |
| TailwindCSS | 4 |
| RecordingStudio | 4.x (`~> 4.2` in the gemspec; dummy GitHub tag `v4.2.0`) |
| Accessible | dummy GitHub tag `v0.9.1` |
| API | dummy GitHub tag `v0.5.5` |
| Embeddable | dummy GitHub tag `v0.2.1` |
| Admin | dummy GitHub tag `v2.0.2` (boot-only for this host) |
| Publishable | dummy GitHub tag `v0.2.0` (Embeddable hard dep; not mixed into Page) |
| Attachable | dummy GitHub tag `v0.5.1` (Publishable boot dep) |
| Root Switchable | dummy GitHub tag `v0.5.0` |
| FlatPack | dummy GitHub tag `v0.1.177` |
| Devise | latest |
| `@wordpress/create-block` | 4.98.0 |
| `@wordpress/scripts` | 35.0.0 |
| `@wordpress/env` | 11.15.0 |
| WordPress core (plugin header) | 6.8 or newer |
| WordPress core (`wp-env` `core`) | `WordPress/WordPress` (current trunk clone) |
| PHP (plugin header) | 7.4 or newer |
| PHP (CI) | 8.3 |
| Dummy origin | port 3000 |
| WordPress origin | port 8888 |
| WordPress tests origin | port 8889 |

The dummy Gemfile keeps `github:` sources so Bundler can fetch those gems. The gemspec still pins `recording_studio` to `~> 4.2` so the addon declares the core dependency even when GitHub is the fetch source.

## How packaging is verified

Build an installable WordPress ZIP in one command from the repository root:

```bash
bin/build-plugin-zip
```

That runs `npm ci && npm run build` in `wordpress/recording-studio-widgets/`, asserts `build/sdk/` is present, and writes `pkg/recording-studio-widgets.zip`. Alias: `bin/package-wordpress-zip`.

Verify both the gem and the ZIP stay free of dummy host code:

```bash
bin/check-package-boundaries
```

The script builds the gem into `pkg/` and the WordPress ZIP (compiling assets unless you pass `--skip-compile`). It fails if dummy host paths or dummy source markers appear in either artifact, or if the ZIP omits the baked SDK.

The gem may contain `app/`, `config/`, `db/`, `lib/`, `MIT-LICENSE`, `Rakefile`, and `README.md`. The ZIP may contain the plugin bootstrap PHP, `includes/`, compiled `build/` assets (including `build/sdk/`), and `readme.txt`. See `docs/wordpress-packaging.md`.

CI builds plugin assets, runs `bin/check-package-boundaries --skip-compile`, and uploads the ZIP.

Step-by-step host + WordPress install: [docs/wordpress-plugin-demo-runbook.md](docs/wordpress-plugin-demo-runbook.md).

## Out of scope

Later phases may add widget discovery, richer editor pickers, and embed response caching. The dummy host still has no WordPress render routes or widget models beyond the named API surface.

## Dummy Recording Studio host

Authenticated dummy pages use the FlatPack host sidebar layout (`layouts/host`) plus FlatPack CSS and JS. Devise keeps its own sign-in layout.

The dummy host follows Recording Studio's root recording pattern:

- Workspace is the top-level recordable
- Folder and Page demonstrate nested recordables under the workspace root
- Each configured recordable declares `recording_studio_recordable(...)`. Strict declaration validation stays enabled
- A root `RecordingStudio::Recording` wraps the Workspace
- `Current.actor` is set from `current_user` (Devise) in `ApplicationController`

Capability mixins are opt-in. Installing this gem does not enable mixins on host types. The dummy Workspace enables Accessible and the example mixin. Folder and Page do not.

```ruby
RecordingStudio.enable_capability(:accessible, on: Workspace)
include RecordingStudio::Capabilities::Example.to(label: "dummy workspace")
```

`.to` wraps `RecordingStudio::Capabilities.include_for`. Use core `RecordingStudio::Hooks` and `RecordingStudio::Services::BaseService`.

All dummy views use FlatPack ViewComponents. Use the live FlatPack demo at [flatpack.bowerbird.io](https://flatpack.bowerbird.io/) before you add custom UI.
