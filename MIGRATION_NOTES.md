# Migration Notes

## Tailwind / FlatPack CSS in the dummy

The dummy scans FlatPack components and Recording Studio views through machine-local symlinks under `test/dummy/vendor/`:

```bash
cd test/dummy
bin/rails recording_studio_root_switchable:link_tailwind_sources
bin/rails tailwindcss:build
```

`app/assets/tailwind/application.css` prefers `vendor/flat_pack` and `vendor/recording_studio`, with fallbacks for `vendor/bundle` and system gem paths. Without the link step, Tailwind builds a near-empty stylesheet and FlatPack screens look unstyled.

---

## Current Requirements

- Ruby 3.3 or newer
- Rails 8.1 or newer
- Recording Studio 4.x (`~> 4.2` in the gemspec; dummy GitHub tag `v4.2.0`)
- Accessible dummy tag `v0.9.1` and Root Switchable dummy tag `v0.5.0`
- FlatPack dummy tag `v0.1.190`
- Users dummy tag `v0.11.0`
- Oauth dummy tag `v0.4.0`
- Public RubyGems and GitHub access for dependency installation

## 0.4.22

Staff edit `ProductConfig` (`NAME`, `OAUTH_CONNECT`, `API_KEYS`) before `bin/build-plugin-zip`. `all()` runs filter `recording_studio_product_config`. Defaults stay both modes on, name `WordPress Plugin Demo`. Site owners do not edit the class. `RECORDING_STUDIO_HOST_BASE_URL` and `RECORDING_STUDIO_CLIENT_ID` stay the wp-config overrides.

Rebuild the ZIP only when you want a different name or a single connect mode. A default ZIP does not change Settings.

## 0.4.21

Connect uses the Oauth 0.4.0 central relay. PHP path constants match those gem paths. `db:seed` still does not create the WordPress client.

From `test/dummy`, pin Oauth `v0.4.0`, run `bundle update recording_studio_oauth`, then run `bin/rails db:migrate`. Rebuild and reinstall the plugin ZIP.

On the existing WordPress Registered App, set the redirect to `{host}/recording_studio_oauth/callback`. Turn **Use central relay** on. Add allowed return pattern `https://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback`. For a local http WordPress, also add `http://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback`.

Token exchange `redirect_uri` is that same callback URL. Connect start is `GET {host}/recording_studio_oauth/connect`.

`/recording_studio_oauth/wordpress/connect` and `/wordpress/callback` are gone.

## 0.4.20

The Getting Started embed is a FlatPack demo for the WordPress block. `pages/embed` renders `FlatPack::Badge::Component`, `FlatPack::Card::Component`, `FlatPack::Button::Pill::Component`, and `FlatPack::Modal::Component` (used as the popup). FlatPack has no `Popup` class on `v0.1.190`.

Embeddable `HtmlSanitizer` removes `link` and `script` from BrowserPayload HTML. The dummy packs `flat_pack/variables`, `flat_pack/application` (imports stripped), and the dummy Tailwind build into a `<style data-wp-plugin-demo-flatpack-assets="1">` tag. Unlayered rules on `[data-wordpress-plugin-demo-embed]` pin `--font-sans` to FlatPack `system-ui` and restate `.font-medium`, `.font-semibold`, and `.font-bold` so a WordPress theme cannot replace family or weight. FlatPack `v0.1.190` has no `@font-face` files, so the payload does not load remote fonts. A short containment rule keeps the modal in the block instead of covering the WordPress page. Dummy Pages `/pages/:id/embed_preview` uses the same fragment, so you can eyeball it without WordPress.

1. From `test/dummy`, point FlatPack at GitHub tag `v0.1.190`.
2. Run `bundle update flat_pack`.
3. Run `bin/rails tailwindcss:build`.
4. Restart the dummy. Do not re-seed. Oauth and Connect stay as they were.

The dummy overrides `layouts/recording_studio_user/auth` so it also links `flat_pack/application`. FlatPack `v0.1.190` paints `.fp-button[data-fp-style="primary"]` in that sheet. The Users gem layout still omits it and only loads dummy Tailwind plus `yield :head`. Dummy `users_auth_primary_buttons.css` paints primary buttons with charcoal `oklch(0.3211 0 0)` and is injected on `RecordingStudioUser::Auth::BaseController` so Continue with email and Sign in stay solid when the gem layout is the one that loads. Packed embed CSS stays in the Getting Started payload only.

## 0.4.19

Staff bake one ZIP against a Recording Studio cloud host. Site owners install that ZIP and click **Connect to Recording Studio**.

1. On the cloud host, open **Registered Apps**.
2. Create a public app named **WordPress** with redirect `{host}/recording_studio_oauth/wordpress/callback`.
3. Copy the client id.
4. Put the host URL and that client id in `CloudHost` (`wordpress/recording-studio-widgets/includes/RecordingStudio/CloudHost.php`).
5. Run `bin/build-plugin-zip` and distribute `pkg/recording-studio-widgets.zip`.

Site owners do not enter a client id, open Registered Apps, or edit wp-config for normal use. `RECORDING_STUDIO_HOST_BASE_URL` and `RECORDING_STUDIO_CLIENT_ID` stay for local and tunnel testing only. `db:seed` still does not create the WordPress Connect client.

## 0.4.18

`db:seed` no longer creates the public **WordPress** Oauth Connect client. Staff create that Registered App once.

1. Sign in to the dummy host.
2. Open sidebar **Registered Apps** (`/admin/screens/oauth_clients`). Switch the root switcher to **Admin** if the screen returns 403.
3. Create a public app named **WordPress**.
4. Set the redirect to `{host}/recording_studio_oauth/wordpress/callback`. On the dummy that is `http://localhost:3000/recording_studio_oauth/wordpress/callback`.
5. Use client id `rsoauth_id_wordpress` if you want the plugin ZIP default. Otherwise set `RECORDING_STUDIO_CLIENT_ID` in `wp-config.php` or an mu-plugin.

The plugin still bakes `http://localhost:3000` and `rsoauth_id_wordpress`. Advanced API key helpers (`print_runbook_connection!`, `Provision.isolated_client!`) are unchanged. Tests that need a Connect client call `WpPluginDemo::Seed.ensure_connect_client!` in setup. `print_connect_client!` still prints fields after the app exists.

Keep an app that 0.4.17 already seeded. Do not re-seed to recreate it.

## 0.4.17

Connect is one click against the Oauth 0.3.0 WordPress relay. Settings no longer ask for a host URL or client id.

The plugin bakes dummy-friendly cloud defaults in `CloudHost`:

- Host `http://localhost:3000`
- Client id `rsoauth_id_wordpress`
- Token URL `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token`

To point a ZIP at another host later, define `RECORDING_STUDIO_HOST_BASE_URL` and `RECORDING_STUDIO_CLIENT_ID` in `wp-config.php` or an mu-plugin. Or add WordPress filters `recording_studio_host_base_url` and `recording_studio_client_id`. Do not add a Settings field for those values.

From `test/dummy`:

1. Pin `recording_studio_oauth` at GitHub tag `v0.3.0`.
2. Run `bundle update recording_studio_oauth`.
3. Re-seed so `WpPluginDemo::Seed.ensure_connect_client!` writes the public **WordPress** app with relay redirects and `client_id=rsoauth_id_wordpress`.

Rebuild and reinstall the plugin ZIP. Click **Connect to Recording Studio**. Advanced stays API key plus Secret key. An empty Secret key still clears the stored secret.

## 0.4.16

WordPress Settings splits Connect from Advanced. Connect uses host plus **OAuth client id**. Advanced stores `api_key` plus `client_secret` (Secret key). Test connection still probes Advanced keys.

If `api_key` is empty and a secret is stored, the plugin reads the existing `client_id` as the Advanced API key. Save Settings to persist that value in `api_key`. An empty Secret key still clears the stored secret.

Rebuild and reinstall the plugin ZIP. No dummy host, named API, or Oauth seed changes.

## 0.4.15

Dummy host pins Recording Studio Oauth `v0.2.2` and API `v0.5.6`. Connect consent stays a full-page submit. A handmade public Registered App can finish Connect token exchange and call named `wp_plugin_demo` resources with that bearer.

From `test/dummy`, run `bundle update recording_studio_oauth recording_studio_api`. Restart the dummy host. Keep the seeded WordPress Plugin Demo client. Do not create new Oauth clients.

## 0.4.14

Connect start prints a short leaving-WordPress page, then sends the browser to the same allowlisted authorize URL. Settings still accept `rs_notice=connected` and always show **This site is connected.** from stored Connect tokens.

If Connect tokens are the chosen source and refresh fails, the plugin returns that Connect error. It does not POST `client_credentials` with a leftover Advanced secret.

Submit Advanced with an empty client secret to clear a stored secret.

Rebuild and reinstall the plugin ZIP. No dummy host, named API, or Oauth seed changes.

## 0.4.13

Connect to Recording Studio allowlists the hostname from the saved host base URL, then uses `wp_safe_redirect`. WordPress no longer rejects a Cloudflare tunnel or other external host and no longer sends the browser to `/wp-admin/`.

Rebuild and reinstall the plugin ZIP. Add the WordPress callback URL on the public client when the origin is not already registered. No dummy host or named API changes.

## 0.4.12

Dummy sidebar **Registered Apps** opens Oauth Admin at `/admin/screens/oauth_clients`. Use it to find the Connect public client id. Switch the root switcher to **Admin** if the screen returns 403. API Keys and Pages stay in the sidebar.

## 0.4.11

This version mounts Recording Studio Users auth chrome on the dummy host and adds WordPress Plugin Demo Connect.

Host `User` stays the Devise actor. People is the shared root. Profile is the only child. Connect is the primary WordPress path. API keys stay under Advanced.

From `test/dummy/`:

1. Pin `recording_studio_user` at GitHub tag `v0.11.0` in the dummy Gemfile. Do not add it to the root Gemfile or gemspec.
2. Run `bundle install`.
3. Run `bin/rails generate recording_studio_user:install`.
4. Run `bin/rails generate recording_studio_user:migrations`.
5. Register `"RecordingStudioUser::People"` and `"RecordingStudioUser::Profile"` in `config/initializers/recording_studio.rb`.
6. Skip Devise sessions, registrations, and passwords. Point confirmations and OmniAuth callbacks at the Users controllers. Add `recording_studio_user_auth_for :users`. Keep `:omniauthable` on host `User` so that `devise_for` mapping boots while `omniauth_providers` stays empty.
7. Enable `section :users` on `AdminRoot`.
8. Run `bin/rails db:migrate`.
9. Re-seed so `RecordingStudioUser.record_profile!` creates Avery Admin's Profile when `profile_for` is nil, and so the public **WordPress Plugin Demo** Oauth client exists with the exact wp-admin admin-post redirect URIs.

Leave `omniauth_providers = {}`. Leave OTP off. Do not add People to the root switcher. Do not bootstrap People as an owned access root.

WordPress Settings: host URL + public client id, then Connect to Recording Studio. When the site is already connected, Settings shows Disconnect and Connect again. Connect again starts PKCE and does not clear tokens first. A failed reconnect keeps the previous tokens. Sign in through Users chrome and pick a workspace. Print Connect fields with `WpPluginDemo::Seed.print_connect_client!`. Keep `print_runbook_connection!` for Advanced API keys.

Any WordPress origin other than `localhost:8888` / `127.0.0.1:8888` must be added as an exact redirect URI on that public client in Oauth Admin Registered apps.

Connect and Advanced both POST `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token`. Do not invent a second ACL. The discovery route `/recording_studio_api/oauth/token` is the public-API default and is not used for this named client.

The dummy named API lists pages at `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages`. The WordPress block picker uses that index. Rebuild plugin assets after upgrade (`npm run build` in `wordpress/recording-studio-widgets`). Paste a UUID if the list is empty.

## 0.4.10

The WordPress block no longer registers a create-block stylesheet on `.wp-block-recording-studio-recording-studio-widget`. Rebuild the plugin assets and reinstall the ZIP so editor and front stop overriding host embed presentation.

## 0.4.3

Host `Page` now declares `renderer: "pages/embed"`. The template renders WordPress Plugin Demo HTML from `WpPluginDemo::Seed.embed_body_html_for`. Without that template, Embeddable returns its fallback stub and a raw `#<Page:…>` dump in the WP block.

After upgrade:

1. Restart the dummy host.
2. Confirm GET `:embed` for Getting Started includes "WordPress Plugin Demo" and omits the stub phrase.
3. After `db:reset`, re-print `page_recording_id` with `WpPluginDemo::Seed.getting_started_page_recording_id`.

## Verification

Install both bundles and run the complete gem and dummy app test path:

```bash
bundle install
BUNDLE_GEMFILE=test/dummy/Gemfile bundle install
bundle exec rake test:all
```

Run the dummy app from its directory for browser verification:

```bash
cd test/dummy
bin/dev
```

Use the [FlatPack repository](https://github.com/bowerbird-app/flatpack) and the live FlatPack demo linked from the top-level README for current component documentation.
