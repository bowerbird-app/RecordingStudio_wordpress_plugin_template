# Dummy host

This Rails app is the host for RecordingStudio WordPress widgets. It boots on its own. WordPress is a separate origin.

## What it covers

- Devise authentication with a seeded admin user
- `Current.actor` wiring for Recording Studio events
- Root workspace plus seeded folder and page recordables
- Recording Studio host shell with FlatPack sidebar (Home, Recordings tree, Plugin credentials), FlatPack assets, and Tailwind source scanning
- Mounted `RecordingStudio::Engine` route behavior inside a host app
- Dummy-only `/docs/*` pages for host-app onboarding
- Signed-in `/plugin_credentials` to mint or regenerate `wp_plugin_demo` OAuth fields for WordPress Settings
- Named API `wp_plugin_demo` with soft GET `:embed` (BrowserPayload schema v1)
- Host Page embed renderer (`pages/embed`) with seeded Getting Started HTML for the WordPress Plugin Demo block
- CI eager-load workarounds: ignore Embeddable `lib/` on host Zeitwerk; clear API `admin_root_recordable_type_names` (no Admin mount)
- Env-only connectivity placeholders. No widget models, widget APIs, or WordPress render routes

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

## Useful routes

- `/` is the dummy app home page
- `/docs/recordings_tree` shows the seeded recordings tree
- `/plugin_credentials` (signed-in) shows host URL, Getting Started page id, and mint/regenerate for WordPress OAuth fields
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

Preferred for Marikit and local install: sign in to the dummy and open **Plugin credentials** (`/plugin_credentials`). Create or regenerate credentials, copy the fields into WordPress **Settings → WordPress Plugin Demo**, and put the page id on the block.

Console still works for the cold-start runbook:

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

## Why this app exists

Use this app to verify the Rails host for RecordingStudio WordPress widgets. If a layout, route, asset source, or Recording Studio initializer change breaks here, fix the host before you touch WordPress.

Authenticated pages use the FlatPack host sidebar layout (`layouts/host`). Devise sign-in keeps `layouts/application`. Keep dummy docs page content short and host-focused.

The home page in `app/views/home/index.html.erb` stays a minimal demo surface. Do not turn it into a wall of documentation. The dummy docs pages exist so deeper explanations can live in focused sections.
