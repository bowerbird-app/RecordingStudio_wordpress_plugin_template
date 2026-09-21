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

`db:setup` seeds Studio Workspace, the Getting Started page, enables embed on that page (`WpPluginDemo::Seed.ensure_studio_embed!`), registers the host Page renderer (`pages/embed`), and seeds one public Oauth client:

- **Seed Demo App** with redirect `http://127.0.0.1:3000/callback` (dummy Oauth tests)

It does not create the **WordPress** Connect client. Create that app once. See [Create the WordPress Registered App](#create-the-wordpress-registered-app).

Named API paths the plugin uses (do not change these unless the host is broken):

- Connect start: `GET http://localhost:3000/recording_studio_oauth/wordpress/connect`
- Relay callback: `GET http://localhost:3000/recording_studio_oauth/wordpress/callback`
- Authorize (after the relay): `GET http://localhost:3000/recording_studio_oauth/oauth/authorize`
- Token + refresh (Connect `authorization_code` and Advanced `client_credentials`): `POST http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token`
- Pages index (block picker): `GET http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages`
- Embed: `GET http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages/{page_id}/actions/embed`

## 2. Create the WordPress Registered App

Create this app once after a fresh `db:setup` or `db:reset`. Seed does not write it.

1. Sign in at `/users/sign_in` with `admin@admin.com` / `Password`.
2. Open sidebar **Registered Apps** (`/admin/screens/oauth_clients`). Switch the root switcher to **Admin** if the screen returns 403.
3. Create a public app named **WordPress**.
4. Set the redirect to `{host}/recording_studio_oauth/wordpress/callback`. On the dummy that is `http://localhost:3000/recording_studio_oauth/wordpress/callback`.
5. Use client id `rsoauth_id_wordpress` if you want the plugin ZIP default. Otherwise define `RECORDING_STUDIO_CLIENT_ID` in `wp-config.php` or an mu-plugin.

The plugin ZIP bakes `http://localhost:3000` and `rsoauth_id_wordpress`. To point at a tunnel or another host, define `RECORDING_STUDIO_HOST_BASE_URL` and `RECORDING_STUDIO_CLIENT_ID` in `wp-config.php` or an mu-plugin, or add filters `recording_studio_host_base_url` and `recording_studio_client_id`.

## 3. Print the public Connect client

Connect is the primary path. Print after the WordPress app exists. From `test/dummy/`:

```bash
bin/rails runner 'WpPluginDemo::Seed.print_connect_client!'
```

Example output shape:

```text
host_base_url=http://localhost:3000
connect_client_id=rsoauth_id_wordpress
connect_url=http://localhost:3000/recording_studio_oauth/wordpress/connect
token_url=http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token
relay_redirect_uri=http://localhost:3000/recording_studio_oauth/wordpress/callback
return_to=http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback
page_recording_id=<uuid of Getting Started>
```

This public client has no secret. The Registered App redirect is the relay callback, not each WordPress admin-post URL. WordPress sends `return_to` as `admin_url('admin-post.php?action=recording_studio_oauth_callback')`. The Oauth 0.3.0 relay allowlists that shape. Do not add each WordPress origin as a Registered App redirect.

To look up only the seeded Getting Started id after seed:

```bash
bin/rails runner 'puts WpPluginDemo::Seed.getting_started_page_recording_id'
```

The id is the active page id for **Getting Started**. It stays stable across `db:seed` / `ensure_studio_embed!` on an existing database. A fresh `db:setup` or `db:reset` creates a new UUID. Create the WordPress app again after reset, then re-print with `print_connect_client!` or the lookup above and paste the new id into the WordPress block.

Embed HTML for Getting Started comes from `WpPluginDemo::Seed::GETTING_STARTED_BODY_HTML` via the host template `app/views/pages/embed.html.erb`. Re-seed does not rewrite that constant; change the constant (or template) and restart the dummy to refresh payload HTML.

## 4. Build the installable plugin ZIP

From the repository root (one command; runs `npm ci`, `npm run build`, asserts `build/sdk/`, writes the ZIP):

```bash
bin/build-plugin-zip
```

Alias: `bin/package-wordpress-zip`. Output: `pkg/recording-studio-widgets.zip`.

Pass `--skip-compile` only when `wordpress/recording-studio-widgets/build/` (including `build/sdk/`) is already fresh.

Package allowlists and boundary checks: [wordpress-packaging.md](wordpress-packaging.md).

## 5. Install in WordPress (`wp-env` or upload)

### Option A: `wp-env` (needs Docker)

From `wordpress/recording-studio-widgets/` after a successful ZIP build:

```bash
npm run env start
```

Open http://localhost:8888. Install and activate the plugin from `pkg/recording-studio-widgets.zip` (Plugins → Add New → Upload), or rely on `.wp-env.json` mounting the plugin directory (development). For a true upload test, use the ZIP.

Stop with `npm run env stop`.

### Option B: Upload ZIP to any local WordPress

Upload `pkg/recording-studio-widgets.zip` in wp-admin and activate **WordPress Plugin Demo**.

If that WordPress origin is not the usual `localhost:8888` shape, the relay still accepts `…/wp-admin/admin-post.php?action=recording_studio_oauth_callback`. You do not add that URL on the Registered App.

### Settings → WordPress Plugin Demo

1. Click **Connect to Recording Studio**. There is no host URL field and no client id field.
2. WordPress shows **Taking you to Recording Studio to connect…**, then opens `{host}/recording_studio_oauth/wordpress/connect`. It does not bounce to `/wp-admin/`.
3. Sign in on the host with Users chrome (`admin@admin.com` / `Password` on the dummy).
4. Pick the Studio workspace when the host asks which workspace to connect.

A success notice means Connect tokens are stored on the WordPress server. Settings then shows **This site is connected.** as both the return notice and a success banner, plus **Disconnect** and **Connect again**. The banner stays after you refresh. Disconnect clears the stored tokens only. Connect again shows the same leaving page, starts PKCE, and leaves the current tokens in place until finish succeeds. The host is not called with a revoke URL.

### Advanced (API keys fallback)

Use this only when you want client credentials instead of Connect. Open **Advanced** and use **Connect via API key**. Advanced has **API key** and **Secret key** only. The host is the same baked cloud host.

Preferred for minting API keys in the host UI: sign in and open sidebar **API Keys** (`/recording_studio_api/api_clients`). Preferred for browsing page ids and the host embed preview: sidebar **Pages** (`/pages`). Preferred for the shared WordPress app: sidebar **Registered Apps** (`/admin/screens/oauth_clients`). Switch the root switcher to **Admin** if that screen returns 403.

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
| API key | printed `oauth_client_id` |
| Secret key | printed `oauth_client_secret` |
| Token URL | baked as `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token`. There is no override field. |

Save settings. Use **Test connection**. A success notice means the host accepted client credentials. Leave **Secret key** empty and save to clear a stored secret. A failed Connect refresh does not use this secret as a fallback. An older install that only stored `client_id` plus `client_secret` still uses that `client_id` as the Advanced API key until you save a value in **API key**.

### Insert the block

1. Edit a page or post.
2. Insert the **WordPress Plugin Demo** block.
3. Pick **Getting Started** from the page list, or paste the printed page id if the list is empty.
4. Preview or publish.

## 6. What good looks like

- The published page shows the SDK mount from BrowserPayload `schema_version: 1` (inspect the block wrapper `data-rs-payload` JSON; top-level `schema_version` is `1`).
- The block wrapper does not paint WordPress scaffold chrome (no teal card, no forced white text). Presentation matches the dummy Pages embed preview: host embed HTML and CSS pass through as-is.
- Browser Network has no OAuth client secret and no access token. Token and embed calls stay on the WordPress server (PHP). The browser loads only the baked SDK script/CSS and the page HTML.
- Editor preview (with `edit_posts`) can show the same mount without exposing secrets.

## 7. Optional smoke checklist

Use this when Docker or a full WordPress UI is available. It is not mandatory in CI on environments without Docker.

1. Dummy `/up` returns OK on port 3000.
2. `bin/build-plugin-zip` exits 0 and `unzip -l pkg/recording-studio-widgets.zip` lists `build/sdk/recording-studio-plugin-sdk.js`.
3. `bin/check-package-boundaries` prints `package boundaries ok`.
4. Settings **Connect to Recording Studio** completes after Users sign-in, or Advanced **Test connection** succeeds.
5. Published block shows `schema_version: 1` in `data-rs-payload`.
6. Browser Network has no `client_secret` and no `access_token` query or response body from the visitor origin.

## Host assumptions for Marikit

Confirm these against your local network and WordPress setup:

- WordPress must reach the baked host (`http://localhost:3000` unless you override `CloudHost`). From `wp-env` containers, `localhost:3000` may need `host.docker.internal` or another reachable hostname.
- CORS is not required for the happy path. PHP fetches the host. The visitor browser does not call the named API.
- The named API remains `wp_plugin_demo` with soft GET `:embed`. Connect uses authorization_code + PKCE S256. Advanced uses client_credentials. Both POST the named API token path. The discovery route `POST /recording_studio_api/oauth/token` is the public-API default and is not used for this client.
