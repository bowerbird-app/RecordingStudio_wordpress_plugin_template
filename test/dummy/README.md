# Dummy host

This Rails app is the host for RecordingStudio WordPress widgets. It boots on its own. WordPress is a separate origin.

## What it covers

- Devise authentication with a seeded admin user
- `Current.actor` wiring for Recording Studio events
- Root workspace plus seeded folder and page recordables
- Recording Studio host shell with FlatPack sidebar (Home, Recordings tree, API Keys, Pages), FlatPack kit CSS (`variables`, `application`, `rich_text`), and Tailwind source scanning
- Recording Studio Admin at `/admin` with Oauth registered apps (`/admin/screens/oauth_clients`) — not a sidebar item
- Recording Studio API clients UI at `/recording_studio_api/api_clients` (sidebar **API Keys**) for minting WordPress client credentials
- Pages browser at `/pages` with a WordPress embed preview iframe (`/pages/:id` → isolated `/pages/:id/embed_preview`)
- Mounted `RecordingStudio::Engine` route behavior inside a host app
- Dummy-only `/docs/*` pages for host-app onboarding
- Named API `wp_plugin_demo` with soft GET `:embed` (BrowserPayload schema v1)
- Host Page embed renderer (`pages/embed`) with seeded Getting Started HTML for the WordPress Plugin Demo block
- CI eager-load workarounds: ignore Embeddable `lib/` on host Zeitwerk
- Env-only connectivity placeholders. No widget models or WordPress render routes in Rails

## Quick start

```bash
cd test/dummy
bundle install
bin/rails db:setup
bin/rails recording_studio_root_switchable:link_tailwind_sources
bin/rails tailwindcss:build
bin/dev
```

`link_tailwind_sources` creates `vendor/flat_pack` and `vendor/recording_studio` so Tailwind can scan FlatPack classes. Without it, the dummy CSS build is nearly empty and the UI looks unstyled.

Run the commands above from the dummy app directory, not the repository root.

Then open the app and sign in with:

- Email: `admin@admin.com`
- Password: `Password`

After sign-in, use sidebar **API Keys** to open API clients (`/recording_studio_api/api_clients`) when you need WordPress client credentials. Use sidebar **Pages** to browse page recording ids and open the WordPress embed preview. Oauth Admin registered apps stay at `/admin/screens/oauth_clients` (not in the sidebar). Switch the root switcher to **Admin** if staff screens say you lack access. Admin tables load through a Turbo frame, so `app/javascript/application.js` must import `@hotwired/turbo-rails`.

## Useful routes

- `/` is the dummy app home page
- `/docs/recordings_tree` shows the seeded recordings tree
- `/pages` (signed-in) lists untrashed Page recordings; `/pages/:id` shows the WordPress embed preview iframe; `/pages/:id/embed_preview` is the bare WP-comparable surface
- `/admin/screens/oauth_clients` (signed-in, Admin root) lists and manages OAuth apps
- `/recording_studio_api/api_clients` (signed-in) lists and mints API client credentials for WordPress
- `/recording_studio` redirects to `/` while the mounted Recording Studio engine stays available under that prefix for non-root routes
- `/users/sign_in` is the Devise sign-in page
- `/docs/install`, `/docs/config`, `/docs/recordable_types`, `/docs/recordings_tree`, `/docs/gem_views`, `/docs/methods` are dummy-only starter pages
- `/up` is the Rails health check
- WordPress Plugin Demo named API (not the public `/recording_studio_api/api/v1` surface):
  - `POST /recording_studio_api/apis/wp_plugin_demo/oauth/token`
  - `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages/:id/actions/embed`
  - short alias `GET .../pages/:id/embed`

## OAuth client for the WordPress plugin

The WordPress plugin talks to the named API `wp_plugin_demo` with OAuth client credentials.

For minting API client credentials in the host UI, use sidebar **API Keys** after sign-in (`/recording_studio_api/api_clients`).

For staff-managed OAuth apps (PKCE public clients, etc.), open `/admin/screens/oauth_clients` after sign-in (Admin root).

For the cold-start `wp_plugin_demo` API client used in the WordPress runbook, console runners still work:

```bash
bin/rails runner 'WpPluginDemo::Seed.print_runbook_connection!'
```

Isolated tree (new workspace and page each run):

```bash
bin/rails runner 'c = WpPluginDemo::Provision.isolated_client!; puts [c.oauth_client_id, c.oauth_client_secret, c.page_recording_id].join("\n")'
```

Use the printed values in WordPress under **Settings → WordPress Plugin Demo** and as the block page recording id.

After `db:reset`, the Getting Started `page_recording_id` changes. Look it up again with:

```bash
bin/rails runner 'puts WpPluginDemo::Seed.getting_started_page_recording_id'
```

Full cold-start steps: [../../docs/wordpress-plugin-demo-runbook.md](../../docs/wordpress-plugin-demo-runbook.md).
