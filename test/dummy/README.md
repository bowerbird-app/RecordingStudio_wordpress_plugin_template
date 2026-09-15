# Dummy host

This Rails app is the host for RecordingStudio WordPress widgets. It boots on its own. WordPress is a separate origin.

## What it covers

- Devise authentication with a seeded admin user
- `Current.actor` wiring for Recording Studio events
- Root workspace plus seeded folder and page recordables
- Recording Studio default layout, FlatPack assets, and Tailwind source scanning
- Mounted `RecordingStudio::Engine` route behavior inside a host app
- Dummy-only `/docs/*` pages for host-app onboarding
- Named API `wp_plugin_demo` with soft GET `:embed` (BrowserPayload schema v1)
- CI eager-load workarounds: ignore Embeddable `lib/` on host Zeitwerk; clear API `admin_root_recordable_type_names` (no Admin mount)
- Env-only connectivity placeholders. No widget models, widget APIs, or WordPress render routes

## Quick start

```bash
cd test/dummy
bundle install
bin/rails db:setup
bin/dev
```

Run the commands above from the dummy app directory, not the repository root.

Then open the app and sign in with:

- Email: `admin@admin.com`
- Password: `Password`

## Useful routes

- `/` is the dummy app home page
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

Preferred for the install runbook (Getting Started page id + fresh client on Studio Workspace):

```bash
bin/rails runner 'WpPluginDemo::Seed.print_runbook_connection!'
```

Isolated tree (new workspace and page each run):

```bash
bin/rails runner 'c = WpPluginDemo::Provision.isolated_client!; puts [c.oauth_client_id, c.oauth_client_secret, c.page_recording_id].join("\n")'
```

Use the printed values in WordPress under **Settings → WordPress Plugin Demo** and as the block page recording id.

Full cold-start steps: [../../docs/wordpress-plugin-demo-runbook.md](../../docs/wordpress-plugin-demo-runbook.md).

## Why this app exists

Use this app to verify the Rails host for RecordingStudio WordPress widgets. If a layout, route, asset source, or Recording Studio initializer change breaks here, fix the host before you touch WordPress.

Authenticated pages use Recording Studio's shared default layout. Devise sign-in keeps `layouts/application`. Keep dummy docs page content short and host-focused.

The home page in `app/views/home/index.html.erb` stays a minimal demo surface. Do not turn it into a wall of documentation. The dummy docs pages exist so deeper explanations can live in focused sections.
