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

Open http://localhost:3000 and sign in at `/users/sign_in` with `admin@admin.com` / `Password`.

`db:setup` seeds Studio Workspace, the Getting Started page, enables embed on that page (`WpPluginDemo::Seed.ensure_studio_embed!`), and registers the host Page renderer (`pages/embed`) so GET `:embed` returns real WordPress Plugin Demo HTML instead of Embeddable's fallback stub.

Named API paths the plugin uses (do not change these unless the host is broken):

- Token: `POST http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token`
- Embed: `GET http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/v1/pages/{page_recording_id}/actions/embed`

## 2. Create OAuth client credentials for `wp_plugin_demo`

Preferred: sign in to the dummy and open **Plugin credentials** at `/plugin_credentials`. Create or regenerate, then copy Host URL, Client id, Client secret, and Page id into WordPress.

Still from `test/dummy/`, the console print also works:

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

This issues a new OAuth client on Studio Workspace each time. Keep the printed secret.

Alternative for an isolated tree (new workspace + page each run):

```bash
bin/rails runner 'c = WpPluginDemo::Provision.isolated_client!; puts [c.oauth_client_id, c.oauth_client_secret, c.page_recording_id].join("\n")'
```

To look up only the seeded Getting Started id after seed:

```bash
bin/rails runner 'puts WpPluginDemo::Seed.getting_started_page_recording_id'
```

The id is the active `RecordingStudio::Recording` for the Page titled **Getting Started**. It stays stable across `db:seed` / `ensure_studio_embed!` on an existing database. A fresh `db:setup` or `db:reset` creates a new UUID. Re-print with `print_runbook_connection!` or the lookup above after reset, then paste the new id into the WordPress block.

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

### Settings → WordPress Plugin Demo

| Field | Value |
| --- | --- |
| Host base URL | `http://localhost:3000` (or the printed `host_base_url`) |
| OAuth client id | printed `oauth_client_id` |
| OAuth client secret | printed `oauth_client_secret` |
| Token URL override | leave blank unless your host differs; default is `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token` |

Save settings. Use **Test connection**. A success notice means the host accepted client credentials.

### Insert the block

1. Edit a page or post.
2. Insert the **WordPress Plugin Demo** block.
3. Set **page recording id** to the printed `page_recording_id` (Getting Started UUID from step 2).
4. Preview or publish.

## 5. What “good” looks like

- The published page shows the SDK mount from BrowserPayload `schema_version: 1` (inspect the block wrapper `data-rs-payload` JSON; top-level `schema_version` is `1`).
- Browser Network has no OAuth client secret and no access token. Token and embed calls stay on the WordPress server (PHP). The browser loads only the baked SDK script/CSS and the page HTML.
- Editor preview (with `edit_posts`) can show the same mount without exposing secrets.

## 6. Optional smoke checklist

Use this when Docker or a full WordPress UI is available. It is not mandatory in CI on environments without Docker.

1. Dummy `/up` returns OK on port 3000.
2. `bin/build-plugin-zip` exits 0 and `unzip -l pkg/recording-studio-widgets.zip` lists `build/sdk/recording-studio-plugin-sdk.js`.
3. `bin/check-package-boundaries` prints `package boundaries ok`.
4. Settings **Test connection** succeeds.
5. Published block shows `schema_version: 1` in `data-rs-payload`.
6. Browser Network has no `client_secret` and no `access_token` query or response body from the visitor origin.

## Host assumptions for Marikit

Confirm these against your local network and WordPress setup:

- WordPress must reach the Rails host URL you enter (from `wp-env` containers, `localhost:3000` may need `host.docker.internal` or another reachable hostname).
- CORS is not required for the happy path. PHP fetches the host. The visitor browser does not call the named API.
- The named API remains `wp_plugin_demo` with soft GET `:embed` only. No authorization-code OAuth in this phase.
