# Dummy host

This Rails app is the host for RecordingStudio WordPress widgets. It boots on its own. WordPress is a separate origin.

## What it covers

- Recording Studio Users auth chrome with a seeded admin user (`admin@admin.com` / `Password`). Dummy Tailwind carries unlayered `.fp-button[data-fp-style]` paint so primary Sign in stays charcoal even when the Users gem layout omits `flat_pack/application`. The dummy auth layout still links that sheet.
- `Current.actor` wiring for Recording Studio events
- Root workspace plus seeded folder and page recordables
- Recording Studio host shell with FlatPack sidebar (Home, Recordings tree, API Keys, Pages, Registered Apps), FlatPack kit CSS (`variables`, `application`, `rich_text`), and Tailwind source scanning
- Recording Studio Admin at `/admin` with Oauth registered apps (`/admin/screens/oauth_clients`) as sidebar **Registered Apps**
- Recording Studio API clients UI at `/recording_studio_api/api_clients` (sidebar **API Keys**) for minting WordPress client credentials
- Pages browser at `/pages` with an iframe-only show screen (`/pages/:id` → isolated `/pages/:id/embed_preview`)
- Mounted `RecordingStudio::Engine` route behavior inside a host app
- Dummy-only `/docs/*` pages for host-app onboarding
- Named API `wp_plugin_demo` with soft GET `:embed` (BrowserPayload schema v1)
- **Seed Demo App** Oauth client for dummy Oauth tests. Staff create the public **WordPress** Connect client once (relay redirect). Seed does not write that app.
- Host Page embed renderer (`pages/embed`) with seeded Getting Started FlatPack (`Badge`, `Card`, `Button::Pill`, `Modal`) for the WordPress Plugin Demo block. CSS is packed into the payload `<style>` tag so WordPress can paint those components. The packed sheet pins `system-ui` and font weights on the embed root. There is no `@font-face`.
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

Then open `/users/sign_in`. The first screen asks for email. The second screen asks for password.

- Email: `admin@admin.com`
- Password: `Password`

After sign-in, use sidebar **API Keys** to open API clients (`/recording_studio_api/api_clients`) when you need WordPress client credentials. Use sidebar **Pages** to browse page ids and open a page show that iframes the bare embed preview. Use sidebar **Registered Apps** to open Oauth Admin (`/admin/screens/oauth_clients`). Switch the root switcher to **Admin** if that screen says you lack access. Admin tables load through a Turbo frame, so `app/javascript/application.js` must import `@hotwired/turbo-rails`.

## Useful routes

- `/` is the dummy app home page
- `/docs/recordings_tree` shows the seeded recordings tree
- `/pages` (signed-in) lists untrashed Page recordings; `/pages/:id` is host-sidebar chrome plus the embed preview iframe only; `/pages/:id/embed_preview` is the bare WP-comparable surface
- `/admin/screens/oauth_clients` (sidebar **Registered Apps**, signed-in, Admin root) lists and manages OAuth apps
- `/recording_studio_api/api_clients` (signed-in) lists and mints API client credentials for WordPress
- `/recording_studio` redirects to `/` while the mounted Recording Studio engine stays available under that prefix for non-root routes
- `/users/sign_in` is Recording Studio Users auth chrome (email first, then password)
- `/docs/install`, `/docs/config`, `/docs/recordable_types`, `/docs/recordings_tree`, `/docs/gem_views`, `/docs/methods` are dummy-only starter pages
- `/up` is the Rails health check
- WordPress Plugin Demo named API (not the public `/recording_studio_api/api/v1` surface):
  - `GET /recording_studio_oauth/wordpress/connect` (Connect start; relay)
  - `GET /recording_studio_oauth/wordpress/callback` (Registered App redirect)
  - `GET /recording_studio_oauth/oauth/authorize` (after the relay; signed-out visitors use Users chrome; consent submit is a full page)
  - `POST /recording_studio_api/apis/wp_plugin_demo/oauth/token` (Connect authorization_code + refresh_token, and Advanced client_credentials)
  - `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages` (Page index for the WordPress picker)
  - `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages/:id/actions/embed`
  - short alias `GET .../pages/:id/embed`

## OAuth client for the WordPress plugin

Connect is the primary path. The plugin starts PKCE at the Oauth WordPress relay against the public **WordPress** client, then users sign in with Users chrome and pick a workspace.

`db:seed` does not create that client. After a fresh setup, open sidebar **Registered Apps** and create a public app named **WordPress** with redirect `{host}/recording_studio_oauth/wordpress/callback`. Use client id `rsoauth_id_wordpress` to match the plugin ZIP default, or set `RECORDING_STUDIO_CLIENT_ID`.

```bash
bin/rails runner 'WpPluginDemo::Seed.print_connect_client!'
```

That prints the baked client id (`rsoauth_id_wordpress`, no secret), connect URL, named token URL, relay redirect, example WordPress `return_to`, and Getting Started page id.

The Registered App redirect is `{host}/recording_studio_oauth/wordpress/callback`. WordPress `return_to` stays `…/wp-admin/admin-post.php?action=recording_studio_oauth_callback`. The relay allowlists that shape. Do not add each WordPress origin as a Registered App redirect.

For minting Advanced API keys in the host UI, use sidebar **API Keys** after sign-in (`/recording_studio_api/api_clients`).

For the cold-start `wp_plugin_demo` API client used under Advanced, console runners still work:

```bash
bin/rails runner 'WpPluginDemo::Seed.print_runbook_connection!'
```

Isolated tree (new workspace and page each run):

```bash
bin/rails runner 'c = WpPluginDemo::Provision.isolated_client!; puts [c.oauth_client_id, c.oauth_client_secret, c.page_recording_id].join("\n")'
```

Use `print_connect_client!` for Settings → Connect. Use the Advanced print for API keys. In the block, pick a page from the host list or paste a page id.

After `db:reset`, the Getting Started `page_recording_id` changes. Look it up again with:

```bash
bin/rails runner 'puts WpPluginDemo::Seed.getting_started_page_recording_id'
```

Staff ZIP shipping and dummy cold start: [../../docs/wordpress-plugin-demo-runbook.md](../../docs/wordpress-plugin-demo-runbook.md).
