# WordPress Plugin Demo runbook

Cold start from this repo to a working **WordPress Plugin Demo** block against the Rails dummy host.

Product UI name is **WordPress Plugin Demo**. The feature is the WordPress plugin feature. Do not call it Featured In.

## What you need

- Ruby 3.3+, Bundler, PostgreSQL 16, Node 22
- Docker only if you use `wp-env` (optional). Without Docker you can still build the ZIP and upload it to any WordPress that can reach the dummy host.

## 1. Boot the Rails dummy host

From `test/dummy/`:

```bash
bundle install
bin/rails db:setup
bin/dev
```

Open http://localhost:3000 and sign in at `/users/sign_in` with `admin@admin.com` / `Password`. The first screen asks for email. The second screen asks for password.

`db:setup` seeds Studio Workspace, the Getting Started page, enables embed on that page (`WpPluginDemo::Seed.ensure_studio_embed!`), registers the host Page renderer (`pages/embed`), and seeds two public Oauth clients:

- **Seed Demo App** with redirect `http://127.0.0.1:3000/callback` (dummy Oauth tests)
- **WordPress Plugin Demo** with the exact wp-admin callbacks below (Connect)

Named API paths the plugin uses (do not change these unless the host is broken):

- Authorize: `GET http://localhost:3000/recording_studio_oauth/oauth/authorize`
- Token + refresh (Connect `authorization_code` and Advanced `client_credentials`): `POST http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token`
- Pages index (block picker): `GET http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages`
- Embed: `GET http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages/{page_id}/actions/embed`

## 2. Print the public Connect client

Connect is the primary path. From `test/dummy/`:

```bash
bin/rails runner 'WpPluginDemo::Seed.print_connect_client!'
```

Example output shape:

```text
host_base_url=http://localhost:3000
connect_client_id=...
authorize_url=http://localhost:3000/recording_studio_oauth/oauth/authorize
token_url=http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token
redirect_uri=http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback
page_recording_id=<uuid of Getting Started>
```

This public client has no secret. Seeded exact redirect URIs:

- `http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback`
- `http://127.0.0.1:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback`

WordPress builds that callback with `admin_url('admin-post.php?action=recording_studio_oauth_callback')`. Any other WordPress origin must be added as an exact URI on this client in Oauth Admin Registered apps (`/admin/screens/oauth_clients`). No wildcards. No dynamic client registration.

To look up only the seeded Getting Started id after seed:

```bash
bin/rails runner 'puts WpPluginDemo::Seed.getting_started_page_recording_id'
```

The id is the active page id for **Getting Started**. It stays stable across `db:seed` / `ensure_studio_embed!` on an existing database. A fresh `db:setup` or `db:reset` creates a new UUID. Re-print with `print_connect_client!` or the lookup above after reset, then paste the new id into the WordPress block.

Embed HTML for Getting Started comes from `WpPluginDemo::Seed::GETTING_STARTED_BODY_HTML` via the host template `app/views/pages/embed.html.erb`. Re-seed does not rewrite that constant; change the constant (or template) and restart the dummy to refresh payload HTML.

## 3. Build the installable plugin ZIP

From the repository root (one command; runs `npm ci`, `npm run build`, asserts `build/sdk/`, writes the ZIP):

```bash
bin/build-plugin-zip
```

Alias: `bin/package-wordpress-zip`. Output: `pkg/recording-studio-widgets.zip`.

Pass `--skip-compile` only when `wordpress/recording-studio-widgets/build/` (including `build/sdk/`) is already fresh.

Package allowlists and boundary checks: [wordpress-packaging.md](wordpress-packaging.md).

## 4. Install in WordPress (`wp-env` or upload)

### Option A: `wp-env` (needs Docker)

From `wordpress/recording-studio-widgets/` after a successful ZIP build:

```bash
npm run env start
```

Open http://localhost:8888. Install and activate the plugin from `pkg/recording-studio-widgets.zip` (Plugins → Add New → Upload), or rely on `.wp-env.json` mounting the plugin directory (development). For a true upload test, use the ZIP.

Stop with `npm run env stop`.

### Option B: Upload ZIP to any local WordPress

Upload `pkg/recording-studio-widgets.zip` in wp-admin and activate **WordPress Plugin Demo**.

If that WordPress is not `localhost:8888` or `127.0.0.1:8888`, add its exact `admin-post.php?action=recording_studio_oauth_callback` URL on the public client first.

### Settings → WordPress Plugin Demo

1. Set **Host base URL** to `http://localhost:3000` (or the printed `host_base_url`). A Cloudflare tunnel origin is fine.
2. Set **OAuth client id** to the printed `connect_client_id`.
3. Click **Connect to Recording Studio**. WordPress redirects to that host's authorize URL. It does not bounce to `/wp-admin/`.
4. Sign in on the host with Users chrome (`admin@admin.com` / `Password` on the dummy).
5. Pick the Studio workspace when the host asks which workspace to connect.

A success notice means Connect tokens are stored on the WordPress server. Settings then shows **This site is connected.**, **Disconnect**, and **Connect again**. Disconnect clears the stored tokens only. Connect again starts PKCE and leaves the current tokens in place until finish succeeds. The host is not called with a revoke URL.

### Advanced (API keys fallback)

Use this only when you want client credentials instead of Connect.

Preferred for minting API keys in the host UI: sign in and open sidebar **API Keys** (`/recording_studio_api/api_clients`). Preferred for browsing page ids and the host embed preview: sidebar **Pages** (`/pages`). Preferred for the Connect client id: sidebar **Registered Apps** (`/admin/screens/oauth_clients`). Switch the root switcher to **Admin** if that screen returns 403.

From `test/dummy/`:

```bash
bin/rails runner 'WpPluginDemo::Seed.print_runbook_connection!'
```

Example output shape:

```text
host_base_url=http://localhost:3000
oauth_client_id=...
oauth_client_secret=...
page_recording_id=<uuid of Getting Started>
token_url=http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token
embed_url=http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages/<uuid>/actions/embed
```

This issues a new API client on Studio Workspace each time. Keep the printed secret.

Alternative for an isolated tree (new workspace + page each run):

```bash
bin/rails runner 'c = WpPluginDemo::Provision.isolated_client!; puts [c.oauth_client_id, c.oauth_client_secret, c.page_recording_id].join("\n")'
```

| Field | Value |
| --- | --- |
| OAuth client secret | printed `oauth_client_secret` |
| Token URL override | leave blank unless your host differs; default is `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token` |

Save settings. Use **Test connection**. A success notice means the host accepted client credentials.

### Insert the block

1. Edit a page or post.
2. Insert the **WordPress Plugin Demo** block.
3. Pick **Getting Started** from the page list, or paste the printed page id if the list is empty.
4. Preview or publish.

## 5. What “good” looks like

- The published page shows the SDK mount from BrowserPayload `schema_version: 1` (inspect the block wrapper `data-rs-payload` JSON; top-level `schema_version` is `1`).
- The block wrapper does not paint WordPress scaffold chrome (no teal card, no forced white text). Presentation matches the dummy Pages embed preview: host embed HTML and CSS pass through as-is.
- Browser Network has no OAuth client secret and no access token. Token and embed calls stay on the WordPress server (PHP). The browser loads only the baked SDK script/CSS and the page HTML.
- Editor preview (with `edit_posts`) can show the same mount without exposing secrets.

## 6. Optional smoke checklist

Use this when Docker or a full WordPress UI is available. It is not mandatory in CI on environments without Docker.

1. Dummy `/up` returns OK on port 3000.
2. `bin/build-plugin-zip` exits 0 and `unzip -l pkg/recording-studio-widgets.zip` lists `build/sdk/recording-studio-plugin-sdk.js`.
3. `bin/check-package-boundaries` prints `package boundaries ok`.
4. Settings **Connect to Recording Studio** completes after Users sign-in, or Advanced **Test connection** succeeds.
5. Published block shows `schema_version: 1` in `data-rs-payload`.
6. Browser Network has no `client_secret` and no `access_token` query or response body from the visitor origin.

## Host assumptions for Marikit

Confirm these against your local network and WordPress setup:

- WordPress must reach the Rails host URL you enter (from `wp-env` containers, `localhost:3000` may need `host.docker.internal` or another reachable hostname).
- CORS is not required for the happy path. PHP fetches the host. The visitor browser does not call the named API.
- The named API remains `wp_plugin_demo` with soft GET `:embed`. Connect uses authorization_code + PKCE S256. Advanced uses client_credentials. Both POST the named API token path. The discovery route `POST /recording_studio_api/oauth/token` is the public-API default and is not used for this client.
